<?php
/**
 * Settings Page implementation for Attention Directory
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Add Settings Page to Menu
 */
function attention_directory_add_settings_page()
{
	add_options_page(
		__('Attention Directory Settings', 'attention-directory'),
		__('Attention Directory', 'attention-directory'),
		'manage_options',
		'attention-directory',
		'attention_directory_render_settings_page'
	);
}
add_action('admin_menu', 'attention_directory_add_settings_page');

/**
 * Register Settings
 */
function attention_directory_register_settings()
{
	register_setting('attention_directory_settings', 'attention_directory_base_url', 'esc_url_raw');
	register_setting('attention_directory_settings', 'attention_directory_tenant_slug', 'sanitize_text_field');
	register_setting('attention_directory_settings', 'attention_directory_api_key', 'sanitize_text_field');
	register_setting('attention_directory_settings', 'attention_directory_cache_ttl', 'intval');
	register_setting('attention_directory_settings', 'attention_directory_push_app_id', 'sanitize_text_field');
}
add_action('admin_init', 'attention_directory_register_settings');

/**
 * Render Settings Page
 */
function attention_directory_render_settings_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php _e('Attention Directory Settings', 'attention-directory'); ?></h1>
		<?php
		if (
			isset($_SERVER['REQUEST_METHOD']) &&
			'POST' === $_SERVER['REQUEST_METHOD'] &&
			isset($_POST['attention_directory_purge_cache']) &&
			check_admin_referer('attention_directory_purge_cache_action', 'attention_directory_purge_cache_nonce')
		) {
			attention_directory_purge_all_cache();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Attention Directory cache purged successfully.', 'attention-directory') . '</p></div>';
		}
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields('attention_directory_settings');
			do_settings_sections('attention_directory_settings');
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('API Base URL', 'attention-directory'); ?></th>
					<td>
						<input type="text" name="attention_directory_base_url"
							value="<?php echo esc_attr(get_option('attention_directory_base_url', 'https://api.attentionnetwork.com')); ?>"
							class="regular-text" />
						<p class="description">
							<?php _e('The base URL for the Attention Network API.', 'attention-directory'); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Tenant Slug', 'attention-directory'); ?></th>
					<td>
						<input type="text" name="attention_directory_tenant_slug"
							value="<?php echo esc_attr(get_option('attention_directory_tenant_slug')); ?>"
							class="regular-text" />
						<p class="description"><?php _e('Your specific tenant identifier.', 'attention-directory'); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('API Key (Bearer Token)', 'attention-directory'); ?></th>
					<td>
						<input type="password" name="attention_directory_api_key"
							value="<?php echo esc_attr(get_option('attention_directory_api_key')); ?>"
							class="regular-text" />
						<p class="description">
							<?php _e('Secure authentication key. This is never exposed on the frontend.', 'attention-directory'); ?>
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Attention Push App ID', 'attention-directory'); ?></th>
					<td>
						<input type="text" name="attention_directory_push_app_id"
							value="<?php echo esc_attr(get_option('attention_directory_push_app_id')); ?>"
							class="regular-text" placeholder="e.g. 7f8a9b2c..." />
						<p class="description">
							<?php _e('Attention Push Site/App ID for web push notifications.', 'attention-directory'); ?>
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Cache TTL (Seconds)', 'attention-directory'); ?></th>
					<td>
						<input type="number" name="attention_directory_cache_ttl"
							value="<?php echo esc_attr(get_option('attention_directory_cache_ttl', 3600)); ?>"
							class="small-text" />
						<p class="description">
							<?php _e('How long to cache API responses (default 3600s).', 'attention-directory'); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<hr />
		<h2><?php _e('Tools', 'attention-directory'); ?></h2>
		<p><?php _e('If you need to force refresh data from the Attention Network before the TTL expires, purge the cache.', 'attention-directory'); ?>
		</p>
		<form method="post" action="">
			<?php wp_nonce_field('attention_directory_purge_cache_action', 'attention_directory_purge_cache_nonce'); ?>
			<button type="submit" name="attention_directory_purge_cache" value="1" class="button button-secondary">
				<?php _e('Purge Directory Cache', 'attention-directory'); ?>
			</button>
		</form>
	</div>
	<?php
}

/**
 * Helper to purge all Attention Directory transients
 */
function attention_directory_purge_all_cache()
{
	global $wpdb;
	$prefix = '_transient_attn_dir_%';
	$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", $prefix));
	$prefix_timeout = '_transient_timeout_attn_dir_%';
	$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", $prefix_timeout));
}
