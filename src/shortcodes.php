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
    $atts = shortcode_atts(array(
        'limit' => 10,
        'class' => '',
        'category' => '',
    ), $atts, 'attention_directory');

    $params = array('page_size' => intval($atts['limit']));
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
    if (empty($slug) && isset($_GET['org_slug'])) {
        $slug = sanitize_title($_GET['org_slug']);
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
                <a href="<?php echo esc_url(home_url('/category/' . $cat_slug)); ?>"
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

    $current_q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

    ob_start();
    ?>
    <form method="get" class="attention-search-form <?php echo esc_attr($atts['class']); ?>"
        style="display: flex; gap: 0.5rem;">
        <input type="text" name="q" placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
            value="<?php echo esc_attr($current_q); ?>" style="flex: 1; padding: 0.5rem;" />
        <button type="submit" class="button"
            style="padding: 0.5rem 1rem;"><?php esc_html_e('Search', 'attention-directory'); ?></button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('attention_search', 'attention_directory_search_shortcode');

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
    if (empty($push_app_id)) {
        return '';
    }

    ob_start();
    ?>
    <div class="attention-push-wrap <?php echo esc_attr($atts['class']); ?>" style="display: inline-block;">
        <button type="button" class="button attention-push-button" onclick="if(window.Gravitec){window.Gravitec.push(['prompt']);}else{alert('Notification service initializing...');}" style="display: inline-flex; align-items: center; gap: 8px;">
            <span>🔔</span> <?php echo esc_html($atts['button_text']); ?>
        </button>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('attention_push_prompt', 'attention_directory_push_prompt_shortcode');

