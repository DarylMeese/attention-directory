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
    register_setting('attention_directory_settings', 'attention_directory_base_url', 'attention_directory_validate_base_url');
	register_setting('attention_directory_settings', 'attention_directory_tenant_slug', 'attention_directory_validate_slug');
	register_setting('attention_directory_settings', 'attention_directory_push_app_id', 'attention_directory_validate_push_id');
	register_setting('attention_directory_settings', 'attention_directory_push_enabled', array('type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => false));
}
add_action('admin_init', 'attention_directory_register_settings');

function attention_directory_validate_base_url($value) {
    $parts = is_string($value) ? wp_parse_url($value) : false;
    if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        add_settings_error('attention_directory_base_url', 'invalid_url', __('Enter a public HTTPS API URL without credentials, query parameters or a fragment.', 'attention-directory'));
        return get_option('attention_directory_base_url', 'https://app.attentionnetwork.net/api');
    }
    return rtrim(esc_url_raw($value), '/');
}
function attention_directory_validate_push_id($value) {
    if ($value === '') { return ''; }
    if (is_string($value) && preg_match('/^[A-Za-z0-9_-]{8,128}$/', $value)) { return $value; }
    add_settings_error('attention_directory_push_app_id', 'invalid_push_id', __('Enter the Gravitec site application ID provided for this WordPress domain.', 'attention-directory'));
    return get_option('attention_directory_push_app_id', '');
}
function attention_directory_validate_slug($value) {
    if (is_string($value) && preg_match('/^[a-zA-Z0-9-]{1,191}$/', $value)) { return $value; }
    add_settings_error('attention_directory_tenant_slug', 'invalid_site', __('Enter the site slug shown in Sites & Apps.', 'attention-directory'));
    return get_option('attention_directory_tenant_slug', '');
}

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
					<th scope="row"><label for="attention-directory-base"><?php _e('API Base URL', 'attention-directory'); ?></label></th>
					<td>
						<input id="attention-directory-base" type="url" required name="attention_directory_base_url"
							value="<?php echo esc_attr(get_option('attention_directory_base_url', 'https://app.attentionnetwork.net/api')); ?>"
							class="regular-text" />
						<p class="description">
							<?php _e('Use https://app.attentionnetwork.net/api. Public directory entries are refreshed on each request to honor privacy changes.', 'attention-directory'); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="attention-directory-site"><?php _e('Site / App slug', 'attention-directory'); ?></label></th>
					<td>
						<input id="attention-directory-site" type="text" required maxlength="191" pattern="[A-Za-z0-9\-]+" name="attention_directory_tenant_slug"
							value="<?php echo esc_attr(get_option('attention_directory_tenant_slug')); ?>"
							class="regular-text" />
						<p class="description"><?php _e('Use the public link code from Publishing → Sites & Apps. This is a site identifier, not an organization UUID.', 'attention-directory'); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Public directory access', 'attention-directory'); ?></th>
					<td>
						<p class="description">
							<?php _e('No API key is required. Only the selected site’s public directory is available. Previously stored credentials are not displayed or sent.', 'attention-directory'); ?>
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="attention-directory-push"><?php _e('Gravitec Site / App ID', 'attention-directory'); ?></label></th>
					<td>
						<input id="attention-directory-push" type="text" maxlength="128" pattern="[A-Za-z0-9_\-]{8,128}" name="attention_directory_push_app_id"
							value="<?php echo esc_attr(get_option('attention_directory_push_app_id')); ?>"
							class="regular-text" placeholder="e.g. 7f8a9b2c..." />
						<p class="description">
							<?php _e('Attention Push Site/App ID for web push notifications.', 'attention-directory'); ?>
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Offer push notifications', 'attention-directory'); ?></th>
					<td>
						<input type="hidden" name="attention_directory_push_enabled" value="0" />
						<label><input type="checkbox" name="attention_directory_push_enabled" value="1" <?php checked(get_option('attention_directory_push_enabled', false)); ?> /> <?php esc_html_e('Enable the optional notification button', 'attention-directory'); ?></label>
						<p class="description">
							<?php _e('Enable only after the Gravitec application and service worker are configured for this WordPress domain. Visitors choose whether to subscribe. This plugin does not create provider applications or send notifications.', 'attention-directory'); ?></p>
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
	$prefix = $wpdb->esc_like('_transient_attn_dir_') . '%';
	$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", $prefix));
	$prefix_timeout = $wpdb->esc_like('_transient_timeout_attn_dir_') . '%';
	$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", $prefix_timeout));
}
