<?php
/**
 * Plugin Name: Attention Network Directory
 * Plugin URI: https://attentionnetwork.com
 * Description: Secure, stylable directory system integrated with Attention Network API.
 * Version: 1.0.0
 * Author: Attention Network
 * Author URI: https://attentionnetwork.com
 * License: GPLv2 or later
 * Text Domain: attention-directory
 * Requires at least: 6.5
 * Requires PHP: 7.4
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define constants.
define('ATTENTION_DIRECTORY_VERSION', '1.0.0');
define('ATTENTION_DIRECTORY_PATH', plugin_dir_path(__FILE__));
define('ATTENTION_DIRECTORY_URL', plugin_dir_url(__FILE__));
define('ATTENTION_DIRECTORY_BASENAME', plugin_basename(__FILE__));

/**
 * Initialize the plugin.
 */
function attention_directory_init()
{
    // Settings & Admin
    require_once ATTENTION_DIRECTORY_PATH . 'src/settings.php';

    // API Proxy & Data Layer
    require_once ATTENTION_DIRECTORY_PATH . 'src/api-proxy.php';

    // Shortcodes
    require_once ATTENTION_DIRECTORY_PATH . 'src/shortcodes.php';

    // Blocks & Bindings
    require_once ATTENTION_DIRECTORY_PATH . 'src/block-bindings.php';
    require_once ATTENTION_DIRECTORY_PATH . 'src/patterns.php';

    // JetEngine Integration
    require_once ATTENTION_DIRECTORY_PATH . 'src/jetengine-callbacks.php';

    // Assets
    add_action('wp_enqueue_scripts', 'attention_directory_enqueue_assets');
}
add_action('plugins_loaded', 'attention_directory_init');

/**
 * Enqueue frontend assets.
 */
function attention_directory_enqueue_assets()
{
    wp_enqueue_style(
        'attention-directory-style',
        ATTENTION_DIRECTORY_URL . 'assets/directory.css',
        array(),
        ATTENTION_DIRECTORY_VERSION
    );

    $push_app_id = get_option('attention_directory_push_app_id');
    if (!empty($push_app_id)) {
        wp_enqueue_script(
            'attention-push-client',
            'https://cdn.gravitec.net/storage/' . esc_attr($push_app_id) . '/client.js',
            array(),
            null,
            true
        );
    }
}

/**
 * Activation Hook
 */
register_activation_hook(__FILE__, function () {
    // Initialize default settings if they don't exist
    if (!get_option('attention_directory_base_url')) {
        update_option('attention_directory_base_url', 'https://api.attentionnetwork.com');
    }
});
