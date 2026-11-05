<?php
/**
 * Shortcodes for Attention Directory
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Make an internal WP REST request to the proxy
 */
function attention_directory_internal_request($endpoint, $params = array())
{
    nocache_headers();
    $request = new WP_REST_Request('GET', $endpoint);
    $request->set_query_params($params);
    return rest_do_request($request);
}

/**
 * [attention_directory]
 * Renders the organization listing
 */
function attention_directory_listing_shortcode($atts)
{
    if (isset($_GET['org_slug']) && is_string($_GET['org_slug']) && $_GET['org_slug'] !== '') {
        return '<p><a href="' . esc_url(remove_query_arg('org_slug')) . '">' . esc_html__('Back to directory', 'attention-directory') . '</a></p>' . attention_directory_org_shortcode(array('slug' => sanitize_title(wp_unslash($_GET['org_slug']))));
    }
    $atts = shortcode_atts(array(
        'limit' => 10,
        'class' => '',
        'category' => '',
    ), $atts, 'attention_directory');

    $params = array('page_size' => intval($atts['limit']));
    foreach (array('q', 'category', 'city', 'state', 'zip', 'postal_code', 'sort', 'lat', 'lng', 'radius_mi') as $filter) {
        if (isset($_GET[$filter]) && is_scalar($_GET[$filter])) { $params[$filter] = sanitize_text_field(wp_unslash($_GET[$filter])); }
    }
    if (isset($_GET['attn_page']) && is_scalar($_GET['attn_page'])) { $params['page'] = sanitize_text_field(wp_unslash($_GET['attn_page'])); }
    if (!empty($atts['category'])) {
        $params['category'] = sanitize_text_field($atts['category']);
    }

    $response = attention_directory_internal_request('/attention/v1/organizations', $params);

    if ($response->is_error()) {
        return '<p class="attention-error">' . esc_html__('Failed to load directory.', 'attention-directory') . '</p>';
    }

    $data = $response->get_data();
    $items = isset($data['items']) ? $data['items'] : array();

    if (empty($items)) {
        return '<p class="attention-empty">' . esc_html__('No organizations found.', 'attention-directory') . '</p>';
    }

    ob_start();
    ?>
    <div class="attention-directory-container <?php echo esc_attr($atts['class']); ?>">
        <div class="attention-grid"
            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: var(--attention-gap, 1.5rem);">
            <?php foreach ($items as $item): ?>
                <div class="attention-card"
                    data-as-item-type="organization"
                    data-as-item-id="<?php echo esc_attr($item['id']); ?>"
                    data-as-version="v1"
                    style="border: 1px solid var(--attention-border, #eee); padding: 1.5rem; border-radius: 8px;">
                    <?php if (!empty($item['logoUrl'])): ?>
                        <img src="<?php echo esc_url($item['logoUrl']); ?>" alt="<?php echo esc_attr($item['name']); ?> logo"
                            style="max-height: 48px; object-fit: contain; margin-bottom: 1rem;" />
                    <?php endif; ?>
                    <h3 style="margin-top: 0;">
                        <?php echo esc_html($item['name']); ?>
                    </h3>
                    <p style="color: var(--attention-muted-text, #666);">
                        <?php echo esc_html($item['summary'] ?? ''); ?>
                    </p>
                    <a href="<?php echo esc_url(add_query_arg('org_slug', $item['slug'])); ?>" class="button attention-button">
                        <?php esc_html_e('View Profile', 'attention-directory'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($data['paging']['total_pages']) && (int) $data['paging']['total_pages'] > 1): ?>
        <nav class="attention-pagination" aria-label="<?php echo esc_attr__('Directory pages', 'attention-directory'); ?>">
            <?php if (!empty($data['paging']['has_prev'])): ?><a href="<?php echo esc_url(add_query_arg('attn_page', max(1, (int) $data['paging']['page'] - 1))); ?>"><?php esc_html_e('Previous', 'attention-directory'); ?></a><?php endif; ?>
            <span><?php echo esc_html(sprintf(__('Page %1$d of %2$d', 'attention-directory'), $data['paging']['page'], $data['paging']['total_pages'])); ?></span>
            <?php if (!empty($data['paging']['has_next'])): ?><a href="<?php echo esc_url(add_query_arg('attn_page', (int) $data['paging']['page'] + 1)); ?>"><?php esc_html_e('Next', 'attention-directory'); ?></a><?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('attention_directory', 'attention_directory_listing_shortcode');

/**
 * [attention_org slug="..."]
 * Renders a single organization card
 */
function attention_directory_org_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'slug' => '',
        'class' => '',
    ), $atts, 'attention_org');

    // Default to the org_slug in the URL if no slug is provided by the shortcode
    $slug = $atts['slug'];
    if (empty($slug) && isset($_GET['org_slug']) && is_scalar($_GET['org_slug'])) {
        $slug = sanitize_title(wp_unslash($_GET['org_slug']));
    }

    if (empty($slug)) {
        return '<p class="attention-error">' . esc_html__('Please provide an organization slug.', 'attention-directory') . '</p>';
    }

    $response = attention_directory_internal_request('/attention/v1/organizations/' . rawurlencode($slug));

    if ($response->is_error()) {
        return '<p class="attention-error">' . esc_html__('Organization not found.', 'attention-directory') . '</p>';
    }

    $data = $response->get_data();
    $org = isset($data['item']) ? $data['item'] : null;

    if (!$org) {
        return '<p class="attention-error">' . esc_html__('Organization not found.', 'attention-directory') . '</p>';
    }

    ob_start();
    ?>
    <div class="attention-profile <?php echo esc_attr($atts['class']); ?>"
         data-as-item-type="organization"
         data-as-item-id="<?php echo esc_attr($org['id']); ?>"
         data-as-version="v1">
        <div class="attention-profile-header" style="text-align: center; margin-bottom: 2rem;">
            <?php if (!empty($org['logoUrl'])): ?>
                <img src="<?php echo esc_url($org['logoUrl']); ?>" alt="<?php echo esc_attr($org['name']); ?> logo"
                    style="max-width: 200px; max-height: 120px; object-fit: contain; margin-bottom: 1rem;" />
            <?php endif; ?>
            <h2>
                <?php echo esc_html($org['name']); ?>
            </h2>
            <?php if (!empty($org['ratingAverage'])): ?>
                <div class="attention-rating">
                    <strong><?php echo number_format((float) $org['ratingAverage'], 1); ?> ★</strong>
                    <?php if (!empty($org['ratingCount'])): ?>
                        <span style="color: #666; font-size: 0.9em;">(<?php echo intval($org['ratingCount']); ?>
                            <?php esc_html_e('reviews', 'attention-directory'); ?>)</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($org['fullDescription'])): ?>
            <div class="attention-profile-description" style="margin-bottom: 2rem;">
                <?php echo wp_kses_post($org['fullDescription']); ?>
            </div>
        <?php endif; ?>

        <?php if (array_filter(array($org['primaryPhone'] ?? '', $org['primaryEmail'] ?? '', $org['websiteUrl'] ?? ''), function ($value) { return is_string($value) && trim($value) !== ''; })): ?>
        <div class="attention-profile-contact" style="background: #f9f9f9; padding: 1.5rem; border-radius: 8px;">
            <h3 style="margin-top: 0;"><?php esc_html_e('Contact Information', 'attention-directory'); ?></h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php if (!empty($org['primaryPhone'])): ?>
                    <li style="margin-bottom: 0.5rem;"><strong><?php esc_html_e('Phone:', 'attention-directory'); ?></strong>
                        <?php echo esc_html($org['primaryPhone']); ?></li>
                <?php endif; ?>
                <?php if (!empty($org['primaryEmail'])): ?>
                    <li style="margin-bottom: 0.5rem;"><strong><?php esc_html_e('Email:', 'attention-directory'); ?></strong> <a
                            href="mailto:<?php echo esc_attr($org['primaryEmail']); ?>"><?php echo esc_html($org['primaryEmail']); ?></a>
                    </li>
                <?php endif; ?>
                <?php if (!empty($org['websiteUrl'])): ?>
                    <li style="margin-bottom: 0.5rem;"><strong><?php esc_html_e('Website:', 'attention-directory'); ?></strong>
                        <a href="<?php echo esc_url($org['websiteUrl']); ?>" target="_blank"
                            rel="noopener noreferrer"><?php echo esc_url($org['websiteUrl']); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('attention_org', 'attention_directory_org_shortcode');

/**
 * [attention_categories]
 * Renders a list of categories
 */
function attention_directory_categories_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'class' => '',
    ), $atts, 'attention_categories');

    $response = attention_directory_internal_request('/attention/v1/categories');

    if ($response->is_error()) {
        return '';
    }

    $data = $response->get_data();
    $categories = isset($data['items']) ? $data['items'] : array();

    if (empty($categories)) {
        return '';
    }

    ob_start();
    ?>
    <ul class="attention-categories-list <?php echo esc_attr($atts['class']); ?>"
        style="list-style: none; padding: 0; display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <?php foreach ($categories as $cat):
            $cat_slug = is_string($cat) ? sanitize_title($cat) : (isset($cat['slug']) ? $cat['slug'] : (isset($cat['name']) ? sanitize_title($cat['name']) : ''));
            $cat_label = is_string($cat) ? $cat : (isset($cat['name']) ? $cat['name'] : '');
            if (empty($cat_slug))
                continue;
            ?>
            <li>
                <a href="<?php echo esc_url(add_query_arg(array('category' => $cat_slug, 'attn_page' => 1))); ?>"
                    style="display: block; padding: 0.5rem 1rem; border: 1px solid #ccc; border-radius: 999px; text-decoration: none; color: inherit;">
                    <?php echo esc_html($cat_label); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    return ob_get_clean();
}
add_shortcode('attention_categories', 'attention_directory_categories_shortcode');

/**
 * [attention_search]
 * Renders a search input form that redirects or sets query params securely
 */
function attention_directory_search_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'class' => '',
        'placeholder' => __('Search directory...', 'attention-directory'),
    ), $atts, 'attention_search');

    $current_q = isset($_GET['q']) && is_scalar($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
    // Clear only directory state; plain-permalink routing and host-page parameters survive.
    $filter_keys = array('q', 'category', 'city', 'state', 'zip', 'postal_code', 'sort', 'lat', 'lng', 'radius', 'radius_mi', 'attn_page', 'org_slug');
    $has_filters = false;
    foreach ($filter_keys as $filter) {
        if (isset($_GET[$filter]) && ((is_scalar($_GET[$filter]) && trim((string) $_GET[$filter]) !== '') || (is_array($_GET[$filter]) && !empty($_GET[$filter])))) {
            $has_filters = true;
            break;
        }
    }

    ob_start();
    ?>
    <form method="get" class="attention-search-form <?php echo esc_attr($atts['class']); ?>"
        style="display: flex; gap: 0.5rem;">
        <?php foreach (array('category', 'city', 'state', 'zip', 'postal_code', 'sort', 'lat', 'lng', 'radius_mi', 'page_id', 'p') as $preserved): ?>
            <?php if (isset($_GET[$preserved]) && is_scalar($_GET[$preserved])): ?>
                <input type="hidden" name="<?php echo esc_attr($preserved); ?>" value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_GET[$preserved]))); ?>" />
            <?php endif; ?>
        <?php endforeach; ?>
        <input type="hidden" name="attn_page" value="1" />
        <label class="screen-reader-text" for="<?php echo esc_attr($search_id = wp_unique_id('attention-search-')); ?>"><?php esc_html_e('Search the directory', 'attention-directory'); ?></label>
        <input type="search" id="<?php echo esc_attr($search_id); ?>" name="q" maxlength="100" placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
            value="<?php echo esc_attr($current_q); ?>" style="flex: 1; padding: 0.5rem;" />
        <button type="submit" class="button"
            style="padding: 0.5rem 1rem;"><?php esc_html_e('Search', 'attention-directory'); ?></button>
        <?php if ($has_filters): ?>
            <a class="attention-clear-filters" href="<?php echo esc_url(remove_query_arg($filter_keys)); ?>"><?php esc_html_e('Clear filters', 'attention-directory'); ?></a>
        <?php endif; ?>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('attention_search', 'attention_directory_search_shortcode');
add_shortcode('attention_directory_search', 'attention_directory_search_shortcode');

/**
 * [attention_push_prompt]
 * Renders an Attention Push subscription button
 */
function attention_directory_push_prompt_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'class' => '',
        'button_text' => __('Get Local Alerts & Deals', 'attention-directory'),
    ), $atts, 'attention_push_prompt');

    $push_app_id = get_option('attention_directory_push_app_id');
    if (empty($push_app_id) || !get_option('attention_directory_push_enabled', false)) {
        return '';
    }

    ob_start();
    ?>
    <div class="attention-push-wrap <?php echo esc_attr($atts['class']); ?>" style="display: inline-block;">
        <button type="button" class="button attention-push-button" style="display: inline-flex; align-items: center; gap: 8px;">
            <span>🔔</span> <?php echo esc_html($atts['button_text']); ?>
        </button>
        <span class="attention-push-status" role="status" aria-live="polite"></span>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('attention_push_prompt', 'attention_directory_push_prompt_shortcode');

