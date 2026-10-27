# Attention Directory — WordPress Dynamic Directory & Hub

[![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)](attention-directory.php)
[![License](https://img.shields.io/badge/license-GPLv2-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-0073aa.svg)](https://wordpress.org)

**Attention Directory** seamlessly embeds Attention Network directory listings, organization profiles, interactive category filters, and map search interfaces into WordPress sites. It provides deep integration with modern page builders (Crocoblock JetEngine Dynamic Fields) and native WordPress Block Bindings.

---

## 🌟 Key Capabilities

### 1. WordPress REST API Proxy
- Exposes secure, sanitized endpoints (`/wp-json/attention/v1/directory/items`, `/locations`, `/organizations`) with cached transient proxies to protect backend API credentials.
- Automatic rate-limiting and query clamping to defend against cache flooding.

### 2. Crocoblock JetEngine Listing Callbacks
- Custom listing callbacks on `'jet-engine/listing/data/callbacks'` allowing site builders to format primitives into badges, opening tags, phone links, and SVG amenity icons directly inside Elementor and Gutenberg loops.

### 3. Native Block Bindings
- Binds WordPress 6.5+ core blocks (Paragraph, Heading, Image, Button) directly to Attention Directory fields without extra block libraries.

### 4. Attention Push Integration (Gravitec)
- Global web push notification opt-in prompt `[attention_push_prompt]` to broadcast updates, announcements, and local promotions to subscribers.

### 5. Shortcodes
- `[attention_directory]`: Main directory grid with search and category filtering.
- `[attention_directory_search]`: Interactive search bar with geolocation and radius matching.
- `[attention_push_prompt]`: Attention Push web notification subscription prompt.

---

## 📦 Installation & Setup

1. Clone or download into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/DarylMeese/attention-directory.git
   ```
2. Activate the plugin via **Plugins** -> **Installed Plugins**.
3. Navigate to **Settings** -> **Attention Directory** to configure your API URL, Organization/Tenant Context, and Attention Push App ID.

---

## 📄 License

Licensed under the GNU General Public License v2 (or later).
Copyright © 2026 Attention Network.
