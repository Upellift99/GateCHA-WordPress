=== GateCHA CAPTCHA ===
Contributors: gatecha
Tags: captcha, gatecha, altcha, spam, proof-of-work
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
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
* **Interaction signals** — Optional second opinion on each submission, scored from counts and durations alone. Off by default.

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

= What are interaction signals? =

Proof-of-work proves a browser did the work. It does not prove a human filled the form in, and a headless browser solving the challenge passes it exactly like a visitor does. Interaction signals describe how the form was filled: how long the page was open, whether the pointer moved and how far, how many scrolls, touches and keystrokes there were, and how irregular the typing rhythm was. Your GateCHA instance turns those eight numbers into a score between 0 and 1, where higher means more likely automated.

Only aggregates leave the browser. Never what was typed, never where the pointer was, never an IP address. Turn it on under **Settings → GateCHA → Interaction Signals**.

= Does enabling interaction signals block anyone? =

Not by itself. Collection only records scores, which you can watch on your GateCHA dashboard under HIS Monitor. Rejecting flagged submissions is a second, separate setting.

Turn that second one on only once you have watched your own traffic, because a false positive is invisible: the visitor cannot submit and will not tell you. To see the scores in your own logs first, hook the `gatecha_his_result` action:

`add_action( 'gatecha_his_result', function ( $score, $suspected ) { error_log( "GateCHA HIS $score" ); }, 10, 2 );`

= Can I use this with a custom form? =

Yes, use the `[gatecha]` shortcode to place the widget anywhere. Then verify the `altcha` POST field server-side.

= How do I bypass the CAPTCHA for automated testing (e.g. Playwright)? =

Define a bypass token in your `wp-config.php`:

`define( 'GATECHA_BYPASS_TOKEN', 'your-secret-test-token' );`

Then in your tests, set the `altcha` hidden input to this token before submitting the form:

`document.querySelector('input[name="altcha"]').value = 'your-secret-test-token';`

The plugin will accept the token as a valid verification without contacting the GateCHA server. **Never define this constant in production.**

== Screenshots ==

1. The GateCHA server dashboard — challenges, verifications and failures are tracked centrally across all your sites and API keys.

== External Services ==

This plugin connects to your self-hosted GateCHA instance for CAPTCHA challenge generation and verification. Two API calls are made:

1. **GET /api/v1/challenge** — Fetched by the user's browser to obtain a proof-of-work challenge.
2. **POST /api/v1/verify** — Called from your WordPress server to verify the solved challenge.

One more request happens only when **Collect Interaction Signals** is enabled, which it is not by default:

3. **GET /api/public/his.js** — The interaction-signal collector, loaded by the user's browser from the same instance. It measures aggregates on the pages that carry a CAPTCHA (durations, event counts, total pointer distance, typing rhythm variance) and hands them to your WordPress server with the form, which forwards them to /api/v1/verify. It reads no field values, no pointer coordinates and no key contents.

No data is sent to any third-party service. All communication is between your WordPress installation and your own GateCHA instance at the URL you configure in Settings → GateCHA.

* GateCHA source code: [https://github.com/Upellift99/GateCHA](https://github.com/Upellift99/GateCHA)

== Source Code ==

The full source of this plugin is available at [https://github.com/Upellift99/GateCHA-WordPress](https://github.com/Upellift99/GateCHA-WordPress).

The plugin's own JavaScript (`assets/js/gatecha.js`) and CSS (`assets/css/gatecha.css`) are shipped unminified and human-readable.

The plugin bundles one third-party library in minified form:

* **ALTCHA widget** — `assets/js/altcha-widget.min.js`
    * Version: 3.2.2
    * License: MIT
    * Source code: [https://github.com/altcha-org/altcha](https://github.com/altcha-org/altcha)

This is the unmodified production build distributed on npm as the [`altcha`](https://www.npmjs.com/package/altcha) package (it corresponds to the package's `dist/main/altcha.min.js` build). To obtain and review the human-readable source, run `npm install altcha@3.2.2` and inspect the package's `src/` directory on [GitHub](https://github.com/altcha-org/altcha).

== Changelog ==

= 1.2.0 =
* Bundled ALTCHA widget updated from 2.2.4 to 3.2.2.
* ALTCHA's own interaction signature is switched off explicitly, in line with the plugin's no-fingerprinting promise.
* Widget translations now register into the widget's i18n store, and the language follows your site locale.
* No change to the proof-of-work itself, which is identical in both widget versions.

= 1.1.0 =
* Optional interaction signals (HIS), off by default: the collector is loaded from your own GateCHA instance and its aggregates are forwarded to /api/v1/verify on every protected form.
* Optional rejection of submissions GateCHA flags as automated, a separate setting from collection.
* New `gatecha_his_result` action, fired with the score and the flag on every verification that carried signals, for logging your own traffic before deciding to block on it.
* Requires GateCHA 0.7.0 or later for these two settings. Older instances are unaffected and keep working as before.

= 1.0.0 =
* Initial release.
* WordPress login, registration, password reset, and comments integration.
* WooCommerce login, registration, and password reset integration.
* Contact Form 7, WPForms, Gravity Forms, Elementor Pro, Forminator, Formidable Forms, and HTML Forms integration.
* `[gatecha]` shortcode for custom form placement.

== Upgrade Notice ==

= 1.2.0 =
Updates the bundled ALTCHA widget to 3.2.2. Same proof-of-work, same settings; check your forms once after updating.

= 1.1.0 =
Adds optional interaction signals for spam that solves the proof-of-work. Nothing changes until you enable it under Settings → GateCHA.

= 1.0.0 =
Initial release.
