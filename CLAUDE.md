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

GateCHA-WordPress/
├── CLAUDE.md                    - Project guide
├── LICENSE                      - GPL-2.0-or-later
├── .gitignore
├── .github/                     - GitHub Actions workflows
└── gatecha-captcha/             - WordPress plugin directory
    ├── gatecha-captcha.php      - Main plugin file (headers, hooks, init)
    ├── includes/
│   ├── class-gatecha.php        - Core class: API calls, verify logic
│   ├── class-gatecha-admin.php  - Admin settings page (Settings → GateCHA)
│   └── integrations/            - One file per integration
│       ├── wordpress-login.php
│       ├── wordpress-register.php
│       ├── wordpress-comments.php
│       ├── wordpress-reset-password.php
│       ├── woocommerce-login.php
│       ├── woocommerce-register.php
│       ├── woocommerce-reset-password.php
│       ├── contact-form-7.php
│       ├── wpforms.php
│       ├── gravityforms.php
│       ├── elementor.php
│       ├── forminator.php
│       ├── formidable.php
│       └── html-forms.php
    ├── assets/
    │   ├── js/
    │   │   └── altcha-widget.min.js - ALTCHA Web Component
    │   └── css/
    │       └── gatecha.css
    ├── languages/               - i18n (.pot, .po, .mo)
    ├── readme.txt               - WordPress.org plugin page
    └── uninstall.php            - Cleanup on plugin deletion

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
