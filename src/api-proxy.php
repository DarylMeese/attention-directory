<?php
/**
 * REST API Proxy for Attention Network
 */

if (!defined('ABSPATH')) {
	exit;
}

class Attention_Directory_API_Proxy
{

	/**
	 * Register REST API routes
	 */
	public static function register_routes()
	{
		// e.g., /wp-json/attention/v1/organizations
		register_rest_route('attention/v1', '/organizations', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array(__CLASS__, 'get_organizations'),
			'permission_callback' => '__return_true',
		));

		// e.g., /wp-json/attention/v1/organizations/acme-corp
		register_rest_route('attention/v1', '/organizations/(?P<slug>[a-zA-Z0-9-]+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array(__CLASS__, 'get_organization_single'),
			'permission_callback' => '__return_true',
		));

		// e.g., /wp-json/attention/v1/categories
		register_rest_route('attention/v1', '/categories', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array(__CLASS__, 'get_categories'),
			'permission_callback' => '__return_true',
		));

		// e.g., /wp-json/attention/v1/collections/food-guide
		register_rest_route('attention/v1', '/collections/(?P<slug>[a-zA-Z0-9-]+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array(__CLASS__, 'get_collection'),
			'permission_callback' => '__return_true',
		));
	}

	/**
	 * Allowed query parameters for directory listing and collection requests.
	 */
	private static $allowed_query_params = array(
		'q',
		'search',
		'category',
		'lat',
		'lng',
		'radius_mi',
		'city',
		'state',
		'zip',
		'sort',
		'page',
		'page_size',
		'limit',
	);

	/**
	 * Filter and sanitize query parameters against the whitelist to prevent transient cache flooding.
	 */
	private static function sanitize_query_params($query_params)
	{
		if (!is_array($query_params) || empty($query_params)) {
			return array();
		}

		$sanitized = array();
		foreach (self::$allowed_query_params as $key) {
			if (isset($query_params[$key]) && is_scalar($query_params[$key])) {
				$val = sanitize_text_field((string) $query_params[$key]);
				if ('' !== $val) {
					if (in_array($key, array('page', 'page_size', 'limit'), true)) {
						$sanitized[$key] = max(1, min(1000, intval($val)));
					} elseif (in_array($key, array('lat', 'lng', 'radius_mi'), true)) {
						$num = floatval($val);
						// round to 4 decimals to normalize cache keys and prevent transient flooding
						$sanitized[$key] = round($num, 4);
					} else {
						$sanitized[$key] = substr($val, 0, 100);
					}
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Fetch data from Attention Network API
	 */
	private static function fetch_from_api($endpoint, $query_params = array())
	{
		$base_url = get_option('attention_directory_base_url', 'http://localhost:3001');
		$tenant_slug = get_option('attention_directory_tenant_slug', '');
		$api_key = get_option('attention_directory_api_key', '');
		$cache_ttl = intval(get_option('attention_directory_cache_ttl', 3600));

		if (empty($base_url) || empty($tenant_slug) || !filter_var($base_url, FILTER_VALIDATE_URL)) {
			return new WP_Error('attn_config_missing', 'Attention Network API configuration is incomplete.', array('status' => 500));
		}

		$url = rtrim($base_url, '/') . '/v1/tenants/' . rawurlencode($tenant_slug) . '/directory' . $endpoint;

		$safe_query_params = self::sanitize_query_params($query_params);
		if (!empty($safe_query_params)) {
			$url = add_query_arg($safe_query_params, $url);
		}

		// Create a transient cache key based on the full URL safely
		$cache_key = 'attn_dir_' . md5($url);
		$cached = get_transient($cache_key);

		if (false !== $cached) {
			// We store parsed JSON in the transient directly
			return $cached;
		}

		$args = array(
			'timeout' => 15,
			'headers' => array(
				'Accept' => 'application/json',
			),
		);

		if (!empty($api_key)) {
			$args['headers']['Authorization'] = 'Bearer ' . $api_key;
		}

		$response = wp_remote_get($url, $args);

		if (is_wp_error($response)) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);

		if (200 !== $status_code) {
			return new WP_Error('attn_api_error', 'Attention Network API Error', array('status' => $status_code));
		}

		$data = json_decode($body, true);

		if (is_null($data)) {
			return new WP_Error('attn_api_malformed', 'Attention Network API returned malformed JSON', array('status' => 500));
		}

		// Cache successful response
		if ($cache_ttl > 0) {
			set_transient($cache_key, $data, $cache_ttl);
		}

		return $data;
	}

	/**
	 * Handler for /organizations
	 */
	public static function get_organizations(WP_REST_Request $request)
	{
		$params = $request->get_query_params();
		$data = self::fetch_from_api('/organizations', $params);

		if (is_wp_error($data)) {
			return $data;
		}

		if (isset($data['items']) && is_array($data['items'])) {
			foreach ($data['items'] as &$item) {
				$item = self::flatten_organization_fields($item);
			}
		}

		// Should naturally match listing: { items: [...], paging: {...}, facets: {...} }
		return rest_ensure_response($data);
	}

	/**
	 * Handler for /organizations/:slug
	 */
	public static function get_organization_single(WP_REST_Request $request)
	{
		$slug = $request->get_param('slug');
		$data = self::fetch_from_api('/organizations/' . rawurlencode($slug));

		if (is_wp_error($data)) {
			return $data;
		}

		// WP expects { item: {...} } specifically for JetEngine Single Object queries
		// If the platform API returns the object raw, nest it here:
		if (!isset($data['item'])) {
			$data = array('item' => $data);
		}

		if (isset($data['item']) && is_array($data['item'])) {
			$data['item'] = self::flatten_organization_fields($data['item']);
		}

		return rest_ensure_response($data);
	}

	/**
	 * Handler for /categories
	 */
	public static function get_categories(WP_REST_Request $request)
	{
		// Navigation payload includes categories
		$data = self::fetch_from_api('/navigation');

		if (is_wp_error($data)) {
			return $data;
		}

		// Extract just the categories portion for JetEngine lists
		$categories = isset($data['navigation']['categories']) ? $data['navigation']['categories'] : array();

		return rest_ensure_response(array('items' => $categories));
	}

	/**
	 * Handler for /collections/:slug
	 */
	public static function get_collection(WP_REST_Request $request)
	{
		$slug = $request->get_param('slug');
		$params = $request->get_query_params();

		$data = self::fetch_from_api('/collections/' . rawurlencode($slug), $params);

		if (is_wp_error($data)) {
			return $data;
		}

		return rest_ensure_response($data);
	}

	/**
	 * Flattens fields on an organization to map predictably for JetEngine.
	 */
	private static function flatten_organization_fields($item)
	{
		if (!is_array($item))
			return $item;

		// Distance
		if (isset($item['distanceMiles'])) {
			$item['distance_mi'] = $item['distanceMiles'];
		} else {
			$item['distance_mi'] = null;
		}

		// Rating
		$item['rating_average'] = isset($item['ratingAverage']) ? $item['ratingAverage'] : null;
		$item['rating_count'] = isset($item['ratingCount']) ? $item['ratingCount'] : 0;
		if (isset($item['ratingCount']) && $item['ratingCount'] == 0) {
			$item['rating_average'] = null; // Enforce missing average if no counts
		}

		// Open Status
		if (isset($item['isOpen'])) {
			$item['is_open'] = $item['isOpen'];
			$item['status_label'] = $item['isOpen'] ? __('Open Now', 'attention-directory') : __('Closed', 'attention-directory');
		} else {
			// Don't invent Open/Closed status unless explicitly provided
			$item['is_open'] = null;
			$item['status_label'] = null;
		}

		return $item;
	}
}

add_action('rest_api_init', array('Attention_Directory_API_Proxy', 'register_routes'));
