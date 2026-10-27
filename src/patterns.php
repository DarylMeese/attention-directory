<?php
/**
 * Block Patterns for Attention Directory
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Directory_Patterns
{

    /**
     * Register Block Patterns
     */
    public static function register_patterns()
    {
        if (!function_exists('register_block_pattern')) {
            return;
        }

        register_block_pattern_category(
            'attention-directory',
            array('label' => __('Attention Directory', 'attention-directory'))
        );

        register_block_pattern(
            'attention-directory/organization-card',
            array(
                'title' => __('Attention Organization Profile', 'attention-directory'),
                'description' => __('A complete profile view bound to an organization using the Block Bindings API.', 'attention-directory'),
                'categories' => array('attention-directory'),
                'content' => '
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:heading {"textAlign":"center","metadata":{"bindings":{"content":{"source":"attention-directory/organization","args":{"field":"name"}}}}} -->
    <h2 class="wp-block-heading has-text-align-center">Organization Name</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","metadata":{"bindings":{"content":{"source":"attention-directory/organization","args":{"field":"summary"}}}}} -->
    <p class="has-text-align-center">Summary goes here.</p>
    <!-- /wp:paragraph -->

    <!-- wp:separator -->
    <hr class="wp-block-separator has-alpha-channel-opacity"/>
    <!-- /wp:separator -->

    <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"attention-directory/organization","args":{"field":"description"}}}}} -->
    <p>Full description goes here.</p>
    <!-- /wp:paragraph -->

    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
    <div class="wp-block-group">
        <!-- wp:paragraph -->
        <p><strong>Phone:</strong> </p>
        <!-- /wp:paragraph -->
        <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"attention-directory/organization","args":{"field":"phone"}}}}} -->
        <p>123-456-7890</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->'
            )
        );
    }
}

add_action('init', array('Attention_Directory_Patterns', 'register_patterns'));
