=== Attention Network Directory ===
Contributors: attentionnetwork
Tags: directory, organizations, rbac, api
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Secure, stylable directory system integrated with Attention Network API.

== Description ==

This plugin allows Attention Network tenants to embed their directory and organization profiles directly into their WordPress site. It uses a secure server-side REST proxy to protect your API keys and provides modern integration features like Block Bindings and JetEngine compatibility.

== Installation ==

1. Upload the `attention-directory` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > Attention Directory** and enter your API Base URL, Tenant Slug, and API Key.

== Frequently Asked Questions ==

= How do I style the directory? =
The plugin uses CSS variables. You can override them in your theme's Customizer or child theme:
`
:root {
  --attention-accent: #ff0000;
  --attention-card-radius: 20px;
}
`

= Does it work with JetEngine? =
Yes! Use JetEngine's REST API connection to hit `/wp-json/attention/v1/listing` or `/wp-json/attention/v1/navigation`.

== Changelog ==

= 1.0.0 =
* Initial release.
* Support for Block Bindings API (WP 6.5+).
* REST API Proxy with transient caching.
* Shortcode support: [attention_directory] and [attention_org slug="..."]
