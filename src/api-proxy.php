<?php
/** Public site-scoped adapter. Never forward administrator credentials to public endpoints. */
if (!defined('ABSPATH')) { exit; }
class Attention_Directory_API_Proxy
{
    public static function register_routes()
    {
        foreach (array('/organizations' => 'get_organizations', '/organizations/(?P<slug>[a-zA-Z0-9-]+)' => 'get_organization_single', '/categories' => 'get_categories', '/collections/(?P<slug>[a-zA-Z0-9-]+)' => 'get_collection') as $route => $callback) {
            register_rest_route('attention/v1', $route, array('methods' => WP_REST_Server::READABLE, 'callback' => array(__CLASS__, $callback), 'permission_callback' => '__return_true'));
        }
    }
    private static function error($message, $status = 502)
    {
        return new WP_Error('attention_directory_unavailable', $message, array('status' => $status));
    }
    private static function sanitize_query_params($query)
    {
        $query = is_array($query) ? $query : array();
        if (isset($query['search']) && !isset($query['q'])) { $query['q'] = $query['search']; }
        $result = array();
        foreach (array('q', 'category', 'city', 'state', 'zip', 'postal_code', 'sort', 'page', 'page_size', 'limit', 'lat', 'lng', 'radius_mi') as $key) {
            if (!isset($query[$key]) || $query[$key] === '') { continue; }
            if (!is_scalar($query[$key])) { return self::error('Choose a valid directory filter.', 400); }
            $value = sanitize_text_field((string) $query[$key]);
            if (in_array($key, array('page', 'page_size', 'limit'), true)) {
                if (!ctype_digit($value) || (int) $value < 1) { return self::error('Choose a valid page and number of results.', 400); }
                $value = min($key === 'page' ? 1000 : 100, (int) $value);
            } elseif (in_array($key, array('lat', 'lng', 'radius_mi'), true)) {
                if (!is_numeric($value) || !is_finite((float) $value)) { return self::error('Enter valid map coordinates and distance.', 400); }
                $value = round((float) $value, 4);
                if (($key === 'lat' && abs($value) > 90) || ($key === 'lng' && abs($value) > 180) || ($key === 'radius_mi' && ($value <= 0 || $value > 100))) { return self::error('Choose valid coordinates and a radius up to 100 miles.', 400); }
            } elseif ($key === 'sort' && !in_array($value, array('relevance', 'distance', 'name', 'featured', 'new', 'newest', 'rating'), true)) { return self::error('Choose a valid sort order.', 400); }
            else { $value = substr($value, 0, in_array($key, array('zip', 'postal_code'), true) ? 20 : 100); }
            $result[$key] = $value;
        }
        if (isset($result['lat']) !== isset($result['lng']) || (isset($result['radius_mi']) && !isset($result['lat']))) { return self::error('Enter latitude and longitude together.', 400); }
        foreach ($query as $key => $value) {
            if (preg_match('/^attr\.[a-zA-Z0-9_-]{1,50}$/', (string) $key) && is_scalar($value) && count($result) < 30) { $result[$key] = substr(sanitize_text_field((string) $value), 0, 100); }
        }
        return $result;
    }
    private static function fetch_from_api($endpoint, $query = array())
    {
        $base = get_option('attention_directory_base_url', 'https://app.attentionnetwork.net/api');
        $tenant = get_option('attention_directory_tenant_slug', '');
        $parts = is_string($base) ? wp_parse_url($base) : false;
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || !is_string($tenant) || !preg_match('/^[a-zA-Z0-9-]{1,191}$/', $tenant)) { return self::error('The directory connection needs configuration by the site administrator.', 503); }
        $query = self::sanitize_query_params($query);
        if (is_wp_error($query)) { return $query; }
        $url = rtrim($base, '/') . '/v1/tenants/' . rawurlencode($tenant) . '/directory' . $endpoint;
        if ($query) { $url = add_query_arg($query, $url); }
        // Canonical directory responses are no-store. Old transients must never revive removed entries.
        $response = wp_safe_remote_get($url, array('timeout' => 15, 'redirection' => 0, 'limit_response_size' => 2 * 1024 * 1024, 'headers' => array('Accept' => 'application/json')));
        if (is_wp_error($response)) { return self::error('The directory could not be reached. Please try again shortly.'); }
        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status !== 200) { return self::error($status === 404 ? 'This directory entry is no longer available.' : 'The directory is temporarily unavailable. Please try again shortly.', $status === 404 ? 404 : ($status === 400 ? 400 : 503)); }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) { return self::error('The directory returned an unreadable response. Please try again shortly.'); }
        return $data;
    }
    private static function response($data)
    {
        $response = rest_ensure_response($data);
        $response->header('Cache-Control', 'no-store');
        return $response;
    }
    public static function get_organizations(WP_REST_Request $request)
    {
        $data = self::fetch_from_api('/organizations', $request->get_query_params());
        if (is_wp_error($data)) { return $data; }
        if (!isset($data['items']) || !is_array($data['items'])) { return self::error('The directory returned an unreadable list.'); }
        return self::response(array('items' => array_map(array(__CLASS__, 'flatten_organization_fields'), $data['items']), 'paging' => $data['paging'] ?? array(), 'facets' => $data['facets'] ?? array()));
    }
    public static function get_organization_single(WP_REST_Request $request)
    {
        $slug = $request->get_param('slug');
        if (!is_string($slug) || !preg_match('/^[a-zA-Z0-9-]{1,191}$/', $slug)) { return self::error('Choose a valid organization.', 400); }
        $data = self::fetch_from_api('/organizations/' . rawurlencode($slug));
        if (is_wp_error($data)) { return $data; }
        if (!isset($data['profile']) || !is_array($data['profile']) || !isset($data['profile']['organizationId'], $data['profile']['displayName'], $data['profile']['slug']) || !is_string($data['profile']['displayName'])) { return self::error('The directory returned an unreadable profile.'); }
        return self::response(array('item' => self::flatten_organization_fields($data['profile'])));
    }
    public static function get_categories(WP_REST_Request $request)
    {
        $data = self::fetch_from_api('/categories');
        if (is_wp_error($data)) { return $data; }
        return self::response(array('items' => isset($data['categories']) && is_array($data['categories']) ? $data['categories'] : array()));
    }
    public static function get_collection(WP_REST_Request $request)
    {
        $slug = $request->get_param('slug');
        if (!is_string($slug) || !preg_match('/^[a-zA-Z0-9-]{1,191}$/', $slug)) { return self::error('Choose a valid collection.', 400); }
        $data = self::fetch_from_api('/collections/' . rawurlencode($slug));
        if (is_wp_error($data)) { return $data; }
        if (!isset($data['collection']) || !is_array($data['collection'])) { return self::error('The directory returned an unreadable collection.'); }
        $collection = $data['collection'];
        return self::response(array('collection' => array_intersect_key($collection, array_flip(array('collectionId', 'slug', 'title', 'description', 'itemCount', 'cardLayout'))), 'items' => array_map(array(__CLASS__, 'flatten_organization_fields'), $collection['items'] ?? array())));
    }
    public static function flatten_organization_fields($item)
    {
        if (!is_array($item)) { return array(); }
        $fields = explode(' ', 'organizationId listingPlanId slug displayName profileName shortDescription profileDescription longDescription tagline organizationType verificationStatus isVerified category categories attributes featuredMediaId logoMediaId logoMediaUrl heroMediaUrl coverImageUrl coverImageMediaId rating reviewCount ratingAverage ratingCount ratingSource distance primaryPhone primaryEmail websiteUrl city region postalCode addressLine1 addressVisibilityPolicy address lat lng isOpen hoursText hours specialHours locations socialLinks socialLinksMap externalServiceLinks offers events reviews menus affiliations offerPasses giftCardDefinitions ghlIntegration');
        $item = array_intersect_key($item, array_flip($fields));
        $item['id'] = $item['organizationId'] ?? '';
        $item['name'] = $item['profileName'] ?? ($item['displayName'] ?? '');
        $item['summary'] = $item['shortDescription'] ?? ($item['profileDescription'] ?? '');
        $item['fullDescription'] = $item['profileDescription'] ?? ($item['longDescription'] ?? '');
        $item['logoUrl'] = $item['logoMediaUrl'] ?? null;
        $count = $item['ratingCount'] ?? ($item['reviewCount'] ?? 0);
        $item['ratingCount'] = is_numeric($count) ? max(0, (int) $count) : 0;
        $average = $item['ratingAverage'] ?? ($item['rating'] ?? null);
        $item['ratingAverage'] = $item['ratingCount'] > 0 && is_numeric($average) && $average >= 0 && $average <= 5 ? (float) $average : null;
        $item['rating_average'] = $item['ratingAverage']; $item['rating_count'] = $item['ratingCount'];
        $item['distance_mi'] = isset($item['distance']) && is_numeric($item['distance']) ? (float) $item['distance'] : null;
        $item['is_open'] = isset($item['isOpen']) && is_bool($item['isOpen']) ? $item['isOpen'] : null;
        $item['status_label'] = $item['is_open'] === null ? null : ($item['is_open'] ? __('Open now', 'attention-directory') : __('Closed', 'attention-directory'));
        return $item;
    }
}
add_action('rest_api_init', array('Attention_Directory_API_Proxy', 'register_routes'));
add_filter('rest_post_dispatch', function ($response, $server, $request) {
    if (strpos($request->get_route(), '/attention/v1/') === 0 && $response instanceof WP_REST_Response) { $response->header('Cache-Control', 'no-store'); }
    return $response;
}, 10, 3);
