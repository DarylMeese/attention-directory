<?php
/**
 * Block Bindings implementation for the Attention Directory plugin.
 * Requires WordPress 6.5+
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Directory_Block_Bindings
{

    /**
     * Register the block bindings sources.
     */
    public static function register_sources()
    {
        if (!function_exists('register_block_bindings_source')) {
            return; // Only available in WP 6.5+
        }

        register_block_bindings_source(
            'attention-directory/organization',
            array(
                'label' => __('Attention Organization Data', 'attention-directory'),
                'get_value_callback' => array(__CLASS__, 'get_organization_binding_value'),
                'uses_context' => array('attentionOrganizationSlug'),
            )
        );
    }

    /**
     * Callback to return bound values for an organization.
     */
    public static function get_organization_binding_value($source_args, $block_instance, $attribute_name)
    {
        // Find the slug from the block context, fallback to query arg if single page
        $slug = null;
        if (isset($block_instance->context['attentionOrganizationSlug'])) {
            $slug = $block_instance->context['attentionOrganizationSlug'];
        } elseif (isset($_GET['org_slug'])) {
            $slug = sanitize_text_field(wp_unslash($_GET['org_slug']));
        }

        if (empty($slug)) {
            return null;
        }

        // Ensure we have the field to fetch
        if (empty($source_args['field'])) {
            return null;
        }

        $field = $source_args['field'];

        // Make an internal REST API request to the proxy to utilize its caching
        $request = new WP_REST_Request('GET', '/attention/v1/organizations/' . rawurlencode($slug));
        $response = rest_do_request($request);

        if ($response->is_error()) {
            return null;
        }

        $data = $response->get_data();
        $item = isset($data['item']) ? $data['item'] : null;

        if (!$item) {
            return null;
        }

        // Return specific fields based on block binding requests
        switch ($field) {
            case 'name':
                return isset($item['name']) ? esc_html($item['name']) : null;
            case 'summary':
                return isset($item['summary']) ? esc_html($item['summary']) : null;
            case 'description':
                return isset($item['fullDescription']) ? wp_kses_post($item['fullDescription']) : null;
            case 'phone':
                return isset($item['primaryPhone']) ? esc_html($item['primaryPhone']) : null;
            case 'email':
                return isset($item['primaryEmail']) ? esc_html($item['primaryEmail']) : null;
            case 'logo_url':
                return isset($item['logoUrl']) ? esc_url($item['logoUrl']) : null;
            case 'rating_average':
                return isset($item['ratingAverage']) ? number_format((float) $item['ratingAverage'], 1) : null;
            default:
                // Allow dynamic dot-notation accessing if needed later
                return isset($item[$field]) ? esc_html($item[$field]) : null;
        }
    }
}

add_action('init', array('Attention_Directory_Block_Bindings', 'register_sources'));
