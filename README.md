# Attention Directory for WordPress

Version **1.1.1**. Requires WordPress 6.5+ and PHP 7.4+.

Display an Attention site/app's public business directory inside WordPress. Organization privacy, site membership and listing eligibility remain controlled by the platform. This plugin provides public reads; it does not provide organization administration or require an administrator API token.

## Setup

1. Install the `attention-directory` folder and activate **Attention Network Directory**.
2. Open **Settings → Attention Directory**.
3. Set API Base URL to `https://app.attentionnetwork.net/api` and enter the site/app's public link code from **Publishing → Sites & Apps**.
4. Put `[attention_search]`, `[attention_categories]` and `[attention_directory]` on a page. Profile links open the selected business on that same page, with a link back to the directory.

The proxy does not send previously stored bearer credentials. Responses and shortcode pages request no-store behavior rather than keeping removed/private entries in old transients. Configure any full-page caching/CDN plugin to bypass the directory page as well; a cache that runs before WordPress cannot be controlled by this plugin.

## Shortcodes

- `[attention_directory limit="20" category="optional-category-slug"]`: business cards, URL-based filters, previous/next pages and profile drilldown.
- `[attention_org slug="business-slug"]`: a specific public business profile.
- `[attention_search]` or `[attention_directory_search]`: labelled search form. Uses the canonical `q` filter.
- `[attention_categories]`: public categories with links that filter the current directory page.
- `[attention_push_prompt]`: optional Gravitec enrollment button. Off by default; only available after explicitly enabling it in settings and entering a valid site application ID.

Public query parameters include `q`, `category`, `city`, `state`, `zip`/`postal_code`, `sort`, and paired `lat`/`lng` with optional `radius_mi`. Page size is capped at 100, radius at 100 miles. The shortcode uses `attn_page` for navigation to avoid WordPress's own `page` parameter. Unsupported/private context parameters cannot change the configured site.

## Public WordPress endpoints

- `/wp-json/attention/v1/organizations` → `{items,paging,facets}`.
- `/wp-json/attention/v1/organizations/{slug}` → `{item}`.
- `/wp-json/attention/v1/categories` → `{items}`.
- `/wp-json/attention/v1/collections/{slug}` → `{collection,items}`.

The adapter consumes the platform's canonical `/v1/tenants/{siteSlug}/directory/...` endpoints, including the actual `profile`, `categories` and `collection` envelopes. Cards expose canonical public fields plus convenient aliases: `id`, `name`, `summary`, `fullDescription`, `logoUrl`, `rating_average`, `rating_count`, `distance_mi`, `is_open` and `status_label`.

See [JetEngine integration](JETENGINE_INTEGRATION.md) and [styling](STYLING_CONTRACT.md). Native block bindings support explicit `slug`, inherited `attentionOrganizationSlug` context, or the profile's `org_slug` query parameter. Supported fields are `name`, `summary`, `description`, `phone`, `email`, `logo_url`, `website_url`, `rating_average`, `rating_count` and `status_label`; unsupported array/private fields return no value.

## Notification readiness

Configure the Gravitec application and service worker for the **WordPress domain** before enabling the optional button. The plugin loads the SDK with automatic enrollment disabled, and only requests browser permission after a visitor clicks. It does not create provider applications, configure your provider account, send notifications, or imply that permission equals delivery. No real provider enrollment or delivery is claimed by local tests.

## Validation

Run `php run_tests.php` for the dependency-free contract/rendering checks and `php -l` for PHP syntax. Those tests use canonical API response fixtures and WordPress stubs; they do not replace real WordPress/browser acceptance or establish JetEngine compatibility on a version that has not been installed. WordPress PHPUnit fixtures live under `src/__tests__` for an independently configured WP test harness.

Licensed under GPLv2 or later.
