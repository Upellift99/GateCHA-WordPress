# GateCHA CAPTCHA for WordPress - Project Guide
## Overview
Official WordPress plugin for GateCHA, a self-hosted ALTCHA proof-of-work
CAPTCHA service. This plugin adds CAPTCHA protection to WordPress forms by 
connecting to a GateCHA instance for challenge generation and server-side
verification.

Unlike the generic ALTCHA plugin, this plugin is dedicated to GateCHA and 
delegates both challenge creation AND verification to the GateCHA server
via its API (`/api/v1/challenge` and `/api/v1/verify`), ensuring stats
(challenges issued, verified, failed) are tracked centrally.

## Tech Stack

- **Language**: PHP 7.4+ (WordPress minimum)
- **Frontend**: ALTCHA widget (Web Component via `altcha` npm package)
- **API**: GateCHA REST API (`/api/v1/challenge`, `/api/v1/verify`)
- **Target**: WordPress 6.0+, WooCommerce 8.0+ (optional)

## Plugin Structure

The plugin files live at the **repo root** (not in a subdirectory), so the
WordPress.org SVN deploy maps the root straight into `trunk/`. The release
workflow repackages them into a top-level `gatecha-captcha/` folder for the
GitHub ZIP.

GateCHA-WordPress/
├── gatecha-captcha.php          - Main plugin file (headers, hooks, init)
├── readme.txt                   - WordPress.org plugin page
├── uninstall.php                - Cleanup on plugin deletion
├── includes/
│   ├── class-gatecha.php        - Core class: API calls, verify logic
│   ├── class-gatecha-admin.php  - Admin settings page (Settings → GateCHA)
│   └── integrations/            - One file per integration
│       ├── wordpress-login.php  (+ register, comments, reset-password)
│       ├── woocommerce-*.php     - login, register, reset-password
│       ├── contact-form-7.php / wpforms.php / gravityforms.php
│       └── elementor.php / forminator.php / formidable.php / html-forms.php
├── assets/
│   ├── js/altcha-widget.min.js  - ALTCHA Web Component
│   └── css/gatecha.css
├── languages/                   - i18n (.pot, .po, .mo)
│
├── CLAUDE.md                    - Project guide (not deployed)
├── LICENSE                      - GPL-2.0-or-later
├── .distignore                  - Files excluded from the SVN deploy
├── .gitattributes               - Line-ending / binary handling
├── .wordpress-org/              - WP.org page assets (icon, banner) — not the plugin
└── .github/workflows/
    ├── release.yml              - On `v*` tag: build ZIP + deploy to SVN trunk/tag
    └── update-readme-assets.yml - On readme/asset push: update WP.org page only

Deploy uses the 10up GitHub Actions and requires repo secrets
`SVN_USERNAME` (WP.org account `gatecha`) and `SVN_PASSWORD`.

## Key Architecture Decisions

- **No local verification**: All verification goes through GateCHA's
`/api/v1/verify` endpoint. This ensures centralized stats tracking
and replay protection. The plugin never stores or uses HMAC secrets.
- **Settings are minimal**: GateCHA URL + API key. That's it.
- **Integrations mirror the official ALTCHA plugin**: Same hooks and
filters for form integrations, making migration straightforward.
- **Widget is bundled**: The ALTCHA Web Component JS/CSS is shipped
with the plugin (no external CDN dependency).

## Configuration (wp-admin → Settings → GateCHA)

| Setting       | Description                          | Example                              |
|---------------|--------------------------------------|--------------------------------------|
| GateCHA URL   | Base URL of GateCHA instance         | `https://gatecha.example.com`        |
| API Key       | GateCHA API key (`gk_` prefix)       | `gk_6e16d2ff387822c52426cdb6`        |
| Collect Interaction Signals | Load the collector and forward `his_signals` to `/verify` (off by default) | checkbox |
| Reject Suspected Automation | Fail verification when GateCHA flags the submission (needs the above) | checkbox |

## Interaction Signals (HIS)

Optional, off by default, and it needs GateCHA 0.7.0 or later.

When enabled, `render_widget()` enqueues the collector the instance serves at
`/api/public/his.js`. It attaches to any form carrying an ALTCHA widget and, on
submit, writes eight aggregates (durations, event counts, pointer distance,
typing rhythm variance) into a hidden `gatecha_his_signals` field. `verify()`
reads that field and forwards it as `his_signals`, so every one of the 14
integrations is covered by the single central call rather than one change each.

The response then carries `his_bot_score` (0 to 1, higher is more bot-like,
the reverse of reCAPTCHA) and `his_bot_suspected`. The plugin fires
`gatecha_his_result` with both, and only rejects the submission when the second
setting is on.

Two rules the collector's behaviour depends on, worth keeping in mind before
changing anything here:

- the aggregates must be forwarded whole or not at all. A missing
  `time_to_first_ms` is scored server-side as "first interaction at 0 ms", one
  of the automation penalties, so a partial payload invents evidence against
  the visitor. `get_his_signals()` returns null unless all eight are numeric;
- the collector is served by the instance, never bundled, so the aggregates
  cannot drift from what the server that scores them expects. An instance older
  than 0.6.0 answers 404 and verification carries on unchanged.

## CAPTCHA Flow

Browser                    WordPress Backend            GateCHA Server
    │                              │                           │
    │ 1. Page load                 │                           │
    │    Widget fetches challenge ─────────────────────────────►
    │    ◄──────────────────────────────────────── challenge   │
    │                              │                           │
    │ 2. Solves PoW (client-side)  │                           │
    │                              │                           │
    │ 3. Form submit ─────────────►│                           │
    │    (includes altcha payload) │                           │
    │                              │ 4. POST /api/v1/verify ──►│
    │                              │ ◄──── { ok: true/false }  │
    │                              │                           │
    │ ◄── Accept / Reject form     │                           │

## Testing Bypass

For automated testing (Playwright, Cypress, etc.), define a bypass token
in `wp-config.php` to skip server-side CAPTCHA verification:

```php
define( 'GATECHA_BYPASS_TOKEN', 'your-secret-test-token' );
```

When the `altcha` form field matches this token, `verify()` returns `true`
immediately without calling the GateCHA server. In your test:

```js
await page.evaluate((token) => {
  document.querySelector('input[name="altcha"]').value = token;
}, process.env.GATECHA_BYPASS_TOKEN);
```

**Never define `GATECHA_BYPASS_TOKEN` in production.**

## Key Conventions

- Plugin slug: `gatecha-captcha`
- Text domain: `gatecha-captcha` (for i18n)
- Options prefix: `gatecha_` (e.g., `gatecha_url`, `gatecha_api_key`)
- All API calls use `wp_remote_get` / `wp_remote_post`
- Nonces and capability checks on all admin forms
- GPL-2.0-or-later license (WordPress.org requirement)
- WordPress coding standards (WPCS)
- readme.txt follows WordPress.org plugin handbook format

## WordPress.org Publishing Checklist

- [ ] `readme.txt` with proper headers, description, FAQ, changelog
- [ ] Stable tag matches plugin version header
- [ ] No external API calls without disclosure in readme.txt
- [ ] GPL-2.0-or-later compatible license
- [ ] Sanitize/escape all inputs and outputs
- [ ] Use nonces for form submissions
- [ ] Text domain matches plugin slug
- [ ] No CDN dependencies (bundle all assets)
- [ ] `uninstall.php` cleans up options on deletion
