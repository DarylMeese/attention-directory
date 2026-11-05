# JetEngine integration

Configure the plugin's API URL and site/app slug first. The same-origin endpoints contain only that site's public directory data; no browser authorization header is needed.

In the installed JetEngine version's REST API listing/query setup, use:

| Purpose | Endpoint | Items path |
|---|---|---|
| Business cards | `https://your-site.com/wp-json/attention/v1/organizations` | `items` |
| One business | `https://your-site.com/wp-json/attention/v1/organizations/business-slug` | `item` |
| Categories | `https://your-site.com/wp-json/attention/v1/categories` | `items` |
| Collection businesses | `https://your-site.com/wp-json/attention/v1/collections/collection-slug` | `items` |

Use `name`, `summary`, `logoUrl`, `rating_average`, `rating_count`, `distance_mi`, `is_open` and `categories` for dynamic fields. Map search controls to `q` (the proxy also accepts `search` as an alias), `category`, `city`, `state` or other documented public filters. Use `page` and `page_size` for REST pagination; inspect `paging.total_items` and `paging.total_pages`.

Available callbacks include rating stars, distance text/icon, open/closed status, category pills and category text. Unknown ratings, distances and hours remain unknown rather than appearing as zero miles, a false rating or a closed business. Icons use safe text markup retained by WordPress's normal HTML sanitizer.

Do not use the old undocumented `/listing` or `/navigation` URLs: those routes do not exist in this plugin. Do not configure a private management endpoint or administrator bearer token in a frontend listing. Disable third-party caching of these public responses when membership/privacy changes must be reflected immediately.

JetEngine menu names and pagination mapping vary by version. The dependency-free PHP runner verifies callback/DTO behavior; an installed JetEngine walkthrough must be recorded separately before claiming native builder acceptance.
