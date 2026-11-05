# Compatibility review — November 5, 2026

Reviewed all 17 files at upstream `45c8d65ec335fc9d1a4b352fdf5b31dfbe3e13e6` against the canonical Attention platform directory routes at `2c49fc62`. Work is isolated on `codex/wordpress-compatibility-qa`.

Confirmed repairs:

- Profiles now unwrap the actual `profile` envelope; card identifiers, names, descriptions, media, ratings and distances map to the canonical public fields. Previously the entire response envelope was treated as a business.
- Categories use the actual categories endpoint; collections unwrap their real envelope. Unknown private top-level organization fields and collection criteria are omitted.
- Removed persistent record caching: canonical directory responses are no-store, so old hour-long transients cannot revive a removed or private business. REST success/error responses and shortcode renders request no-store behavior. Full-page/CDN caching that runs before WordPress must still bypass the directory page.
- The server-side proxy uses WordPress's safe HTTP client, HTTPS, no redirects and a bounded response size. It never forwards old administrator bearer credentials to public endpoints. Missing configuration and upstream failures produce readable messages without raw upstream responses.
- Public filters match actual API bounds; geo values must be numeric, in range and paired. Unknown/private context parameters cannot change the configured site. Search, category links, same-page profile navigation and previous/next pages now affect real requests.
- Labelled search, visible keyboard focus, 44-pixel action targets and narrow-screen card sizing. No invented distance, rating or closed status from unknown data. Icons survive WordPress's normal HTML sanitizer.
- Native block bindings use explicit supported public scalar fields. The optional Gravitec button is off by default, uses the supported enrollment command, and shows inline status rather than browser alerts. Native HTML patterns are valid under the UnicodeSets `v` rules.
- Settings retain WordPress's administrator capability and nonce checks, validate connection/site/application values, avoid echoing legacy credentials and escape legacy-cache SQL wildcard prefixes.
- Documentation no longer advertises nonexistent routes, a geolocation UI or an automatically configured push/PWA service. Versions agree at 1.1.0.

Local validation:

- `php run_tests.php`: **65 contract/rendering assertions passed** using canonical-shaped HTTP fixtures and WordPress stubs, with PHP warnings converted to failures. This includes plain-permalink WordPress page routing, active filters and resetting profile/page selection when searching.
- `node run_push_tests.cjs`: **11 notification UI/native HTML pattern assertions passed**; no provider requests or permission prompts.
- PHP syntax checks pass for all plugin and test PHP files; JavaScript syntax check passes.
- The optional WordPress PHPUnit fixtures were corrected to the canonical shapes/function names but are not claimed executed without a native WP test harness.

Real WordPress installation/browser acceptance and any native JetEngine-builder checks are separate evidence, pending the coordinating agent. The release ZIP excludes tests, IDE stubs, Git metadata and local QA artifacts. No provider credentials were read, no external notifications were sent, and no live site was changed by this local review.

## Clear-filter recovery — 1.1.2

The native category empty state exposed a navigation gap: search retained the selected category but offered no way to reset it. The search shortcode now shows **Clear filters** when directory filters, profile selection or pagination are present. It removes only the explicit directory query-key allowlist, preserving WordPress `page_id`/`p` and unrelated host-page parameters. Both canonical `radius_mi` and the legacy `radius` name are cleared. Zero coordinates remain active; blank filters do not create an unnecessary control. The link wraps with the search form and retains a 44-pixel keyboard-accessible target.

Local validation: **87 PHP contract/rendering assertions**, **11 Node UI assertions**, all **11 PHP syntax checks**, production JavaScript syntax, and whitespace checks pass. The added regressions cover every removable key and preservation of plain-permalink/unrelated query parameters. Native empty-category → clear → populated-listing verification is recorded after installation below.
