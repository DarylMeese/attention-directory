<?php
/**
 * JetEngine Custom Rendering Callbacks
 * 
 * Provides native WordPress data formatters to transform raw Attention Network
 * API shapes (ratings, distances, booleans) into semantically clean HTML components
 * suitable for Crocoblock Custom Listings dynamically.
 * 
 * Follows the "Styling Contract": No inline colors. Use stable CSS classes powered
 * by CSS variables or theme stylesheets.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom callbacks in the JetEngine UI
 */
function attention_directory_register_jetengine_callbacks($callbacks)
{
    $callbacks['attention_render_stars'] = __('Attention: Render Rating Stars', 'attention-directory');
    $callbacks['attention_format_distance'] = __('Attention: Format Distance (Plain)', 'attention-directory');
    $callbacks['attention_format_distance_icon'] = __('Attention: Format Distance (With Icon)', 'attention-directory');
    $callbacks['attention_render_status'] = __('Attention: Open/Closed Status', 'attention-directory');
    $callbacks['attention_render_categories_tags'] = __('Attention: Render Categories (Pills)', 'attention-directory');
    $callbacks['attention_render_categories_plain'] = __('Attention: Render Categories (Plain Text)', 'attention-directory');
    return $callbacks;
}
add_filter('jet-engine/listing/data/callbacks', 'attention_directory_register_jetengine_callbacks');

/**
 * Helper to safely decode stringified JSON inputs.
 */
function attention_directory_maybe_decode($data)
{
    if (is_string($data)) {
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    return $data;
}

/**
 * Callback: Render Rating Stars
 * 
 * @param mixed $rating The average rating value (e.g. 4.8)
 * @return string HTML for stars
 */
function attention_render_stars($rating)
{
    if (empty($rating) || !is_numeric($rating)) {
        return '';
    }

    $rating = floatval($rating);
    $formatted = number_format_i18n($rating, 1);

    // Outputs semantic HTML with an SVG icon (using currentColor)
    $html = '<span class="attention-rating attention-rating-stars" data-rating="' . esc_attr($rating) . '">'
        . esc_html($formatted)
        . ' <svg class="attention-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
        . '</span>';

    return wp_kses_post($html);
}

/**
 * Callback: Format Distance (Plain)
 * 
 * @param mixed $distance Distance in miles (e.g. 2.5)
 * @return string HTML for distance
 */
function attention_format_distance($distance)
{
    if (empty($distance) && $distance !== 0 && $distance !== '0') {
        return '';
    }

    $formatted = number_format_i18n(floatval($distance), 1);
    $html = '<span class="attention-distance attention-distance-plain">' . esc_html($formatted) . ' mi</span>';
    return wp_kses_post($html);
}

/**
 * Callback: Format Distance (With Icon)
 * 
 * @param mixed $distance Distance in miles (e.g. 2.5)
 * @return string HTML for distance
 */
function attention_format_distance_icon($distance)
{
    if (empty($distance) && $distance !== 0 && $distance !== '0') {
        return '';
    }

    $formatted = number_format_i18n(floatval($distance), 1);
    $html = '<span class="attention-distance attention-distance-icon">'
        . '<svg class="attention-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> '
        . esc_html($formatted) . ' mi</span>';
    return wp_kses_post($html);
}

/**
 * Callback: Open/Closed Status
 * Relies on the precomputed 'is_open' boolean and 'status_label' from the API Proxy flattening.
 * 
 * @param mixed $is_open 
 * @return string HTML badge
 */
function attention_render_status($is_open)
{
    // Only render status if explicitly provided by the API Proxy flattening logic
    if ($is_open === '' || $is_open === null) {
        return '';
    }

    $is_open_bool = filter_var($is_open, FILTER_VALIDATE_BOOLEAN);

    if ($is_open_bool) {
        $label = __('Open Now', 'attention-directory');
        $html = '<span class="attention-status attention-status-open" data-status="open">' . esc_html($label) . '</span>';
    } else {
        $label = __('Closed', 'attention-directory');
        $html = '<span class="attention-status attention-status-closed" data-status="closed">' . esc_html($label) . '</span>';
    }

    return wp_kses_post($html);
}

/**
 * Callback: Render Categories (Pills)
 * 
 * @param array|string $categories Categories array or JSON string from API
 * @return string HTML
 */
function attention_render_categories_tags($categories)
{
    $categories = attention_directory_maybe_decode($categories);
    if (empty($categories) || !is_array($categories)) {
        return '';
    }

    ob_start();
    echo '<div class="attention-category-tags">';
    foreach ($categories as $cat) {
        $cat_name = is_string($cat) ? $cat : (isset($cat['name']) ? $cat['name'] : (isset($cat['label']) ? $cat['label'] : ''));
        if (empty($cat_name))
            continue;

        echo '<span class="attention-tag">' . esc_html($cat_name) . '</span>';
    }
    echo '</div>';
    return wp_kses_post(ob_get_clean());
}

/**
 * Callback: Render Categories (Plain Text)
 * 
 * @param array|string $categories Categories array or JSON string from API
 * @return string HTML
 */
function attention_render_categories_plain($categories)
{
    $categories = attention_directory_maybe_decode($categories);
    if (empty($categories) || !is_array($categories)) {
        return '';
    }

    $cat_names = array();
    foreach ($categories as $cat) {
        $cat_name = is_string($cat) ? $cat : (isset($cat['name']) ? $cat['name'] : (isset($cat['label']) ? $cat['label'] : ''));
        if (!empty($cat_name)) {
            $cat_names[] = esc_html($cat_name);
        }
    }

    if (empty($cat_names)) {
        return '';
    }

    $html = '<span class="attention-categories-plain">' . implode(', ', $cat_names) . '</span>';
    return wp_kses_post($html);
}
