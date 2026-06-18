=== GateCHA CAPTCHA ===
Contributors: gatecha
Tags: captcha, gatecha, altcha, spam, proof-of-work
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted ALTCHA proof-of-work CAPTCHA via GateCHA. Protects WordPress forms without cookies, fingerprinting, or third-party services.

== Description ==

GateCHA CAPTCHA connects your WordPress site to your own [GateCHA](https://gatecha.org) instance — a self-hosted, open-source CAPTCHA management service based on the ALTCHA proof-of-work protocol.

**Why GateCHA?**

* **Privacy-first** — No cookies, no fingerprinting, no user tracking. Fully GDPR-compliant.
* **Self-hosted** — Your challenges and verifications stay on your own server. No data goes to third parties.
* **Proof-of-work** — Bots must solve a computational puzzle. No annoying image puzzles for humans.
* **Centralized stats** — Track challenges issued, verified, and failed across all your sites from one dashboard.

**Supported forms:**

* WordPress login, registration, password reset, and comments
* WooCommerce login, registration, and password reset
* Contact Form 7
* WPForms
* Gravity Forms
* Elementor Pro Forms
* Forminator
* Formidable Forms
* HTML Forms
* Custom placement via `[gatecha]` shortcode

**Setup in 2 steps:**

1. Enter your GateCHA instance URL
2. Enter your API key

That's it. Enable CAPTCHA on the forms you want to protect.

== Installation ==

1. Upload the `gatecha-captcha` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to **Settings → GateCHA**
4. Enter your GateCHA URL and API key
5. Enable CAPTCHA on the forms you want to protect

**Requirements:**

* A running [GateCHA](https://gatecha.org) instance
* An API key from your GateCHA dashboard (starts with `gk_`)

== Frequently Asked Questions ==

= What is GateCHA? =

GateCHA is a self-hosted CAPTCHA management service that wraps the ALTCHA proof-of-work protocol. It provides API key management, multi-site support, and an analytics dashboard. See [the GateCHA website](https://gatecha.org) for more information.

= How does proof-of-work CAPTCHA work? =

Instead of asking users to solve image puzzles, the browser solves a small computational challenge in the background. This is invisible to legitimate users but expensive for bots trying to submit forms at scale.

= Is my API key secure? =

The API key is used in the browser to fetch challenges, similar to how reCAPTCHA and hCaptcha use site keys. You can restrict your API key to specific domains in your GateCHA dashboard for additional security.

= Does this plugin send data to external services? =

Only to your own GateCHA instance. No data is sent to any third-party service. See the External Services section below.

= Can I use this with a custom form? =

Yes, use the `[gatecha]` shortcode to place the widget anywhere. Then verify the `altcha` POST field server-side.

= How do I bypass the CAPTCHA for automated testing (e.g. Playwright)? =

Define a bypass token in your `wp-config.php`:

`define( 'GATECHA_BYPASS_TOKEN', 'your-secret-test-token' );`

Then in your tests, set the `altcha` hidden input to this token before submitting the form:

`document.querySelector('input[name="altcha"]').value = 'your-secret-test-token';`

The plugin will accept the token as a valid verification without contacting the GateCHA server. **Never define this constant in production.**

== External Services ==

This plugin connects to your self-hosted GateCHA instance for CAPTCHA challenge generation and verification. Two API calls are made:

1. **GET /api/v1/challenge** — Fetched by the user's browser to obtain a proof-of-work challenge.
2. **POST /api/v1/verify** — Called from your WordPress server to verify the solved challenge.

No data is sent to any third-party service. All communication is between your WordPress installation and your own GateCHA instance at the URL you configure in Settings → GateCHA.

* GateCHA source code: [https://github.com/Upellift99/GateCHA](https://github.com/Upellift99/GateCHA)

== Source Code ==

The full source of this plugin is available at [https://github.com/Upellift99/GateCHA-WordPress](https://github.com/Upellift99/GateCHA-WordPress).

The plugin's own JavaScript (`assets/js/gatecha.js`) and CSS (`assets/css/gatecha.css`) are shipped unminified and human-readable.

The plugin bundles one third-party library in minified form:

* **ALTCHA widget** — `assets/js/altcha-widget.min.js`
    * Version: 2.2.4
    * License: MIT
    * Source code: [https://github.com/altcha-org/altcha](https://github.com/altcha-org/altcha)

This is the unmodified production build distributed on npm as the [`altcha`](https://www.npmjs.com/package/altcha) package (it corresponds to the package's `dist/altcha.js` ES module build). To obtain and review the human-readable source, run `npm install altcha@2.2.4` and inspect the package's `src/` directory on [GitHub](https://github.com/altcha-org/altcha).

== Changelog ==

= 1.0.0 =
* Initial release.
* WordPress login, registration, password reset, and comments integration.
* WooCommerce login, registration, and password reset integration.
* Contact Form 7, WPForms, Gravity Forms, Elementor Pro, Forminator, Formidable Forms, and HTML Forms integration.
* `[gatecha]` shortcode for custom form placement.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
