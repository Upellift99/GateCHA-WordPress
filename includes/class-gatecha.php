<?php
/**
 * GateCHA core class.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main GateCHA plugin class (singleton).
 */
class GateCHA {

	/** @var GateCHA|null */
	public static $instance;

	// Asset URLs (set by main plugin file via set_asset_urls()).
	public static $widget_script_src = '';
	public static $widget_style_src  = '';
	public static $wp_script_src     = '';

	/**
	 * Populate the asset URLs from the plugin base URL.
	 *
	 * Called from the main plugin file. Kept inside the class so the
	 * assignments stay out of the global scope.
	 *
	 * @param string $plugin_url Plugin base URL (with trailing slash).
	 */
	public static function set_asset_urls( $plugin_url ) {
		// altcha-widget.min.js is the unmodified production build of the ALTCHA
		// widget (v3.2.2, MIT). Source code: https://github.com/altcha-org/altcha
		// (npm package "altcha", file dist/main/altcha.min.js). See readme.txt.
		self::$widget_script_src = $plugin_url . 'assets/js/altcha-widget.min.js';
		self::$widget_style_src  = $plugin_url . 'assets/css/gatecha.css';
		self::$wp_script_src     = $plugin_url . 'assets/js/gatecha.js';
	}

	// Option name constants.
	public static $option_url       = 'gatecha_url';
	public static $option_api_key   = 'gatecha_api_key';
	public static $option_fail_mode   = 'gatecha_fail_mode';
	public static $option_auto_verify = 'gatecha_auto_verify';
	public static $option_hide_branding = 'gatecha_hide_branding';
	public static $option_his_enabled   = 'gatecha_his_enabled';
	public static $option_his_block     = 'gatecha_his_block';

	/**
	 * Form field the interaction collector writes its aggregates into.
	 *
	 * Fixed by the collector served at /api/public/his.js, so it is a constant
	 * here rather than a setting: changing it on one side only would silently
	 * stop the signals from ever reaching /verify.
	 */
	const HIS_FIELD = 'gatecha_his_signals';

	/**
	 * The eight aggregates the collector emits, and the type each is cast to.
	 *
	 * @var array<string, string>
	 */
	private static $his_signal_fields = array(
		'duration_ms'           => 'int',
		'time_to_first_ms'      => 'int',
		'pointer_events'        => 'int',
		'pointer_distance'      => 'float',
		'scrolls'               => 'int',
		'touches'               => 'int',
		'keydowns'              => 'int',
		'key_interval_stdev_ms' => 'float',
	);

	// Integration option names.
	public static $option_wp_login          = 'gatecha_wp_login';
	public static $option_wp_register       = 'gatecha_wp_register';
	public static $option_wp_comments       = 'gatecha_wp_comments';
	public static $option_wp_reset_password = 'gatecha_wp_reset_password';
	public static $option_wc_login          = 'gatecha_wc_login';
	public static $option_wc_register       = 'gatecha_wc_register';
	public static $option_wc_reset_password = 'gatecha_wc_reset_password';
	public static $option_cf7               = 'gatecha_cf7';
	public static $option_wpforms           = 'gatecha_wpforms';
	public static $option_gravityforms      = 'gatecha_gravityforms';
	public static $option_elementor         = 'gatecha_elementor';
	public static $option_forminator        = 'gatecha_forminator';
	public static $option_formidable        = 'gatecha_formidable';
	public static $option_html_forms        = 'gatecha_html_forms';

	/**
	 * Allowed HTML tags for wp_kses() when rendering the widget.
	 *
	 * @var array
	 */
	public static $html_escape_allowed_tags = array(
		// ALTCHA widget v3 attribute surface (see render_widget()). Most options
		// moved into the single JSON `configuration` attribute in v3.
		'altcha-widget' => array(
			'challenge'     => array(),
			'configuration' => array(),
			'language'      => array(),
			'auto'          => array(),
			'name'          => array(),
		),
		'div'   => array(
			'class' => array(),
			'style' => array(),
		),
		'input' => array(
			'class' => array(),
			'id'    => array(),
			'name'  => array(),
			'type'  => array(),
			'value' => array(),
			'style' => array(),
		),
		'noscript' => array(),
	);

	/**
	 * Initialise the singleton.
	 */
	public function init() {
		self::$instance = $this;
	}

	/*------------------------------------------------------------------
	 * Settings getters
	 *-----------------------------------------------------------------*/

	/**
	 * Get the configured GateCHA URL.
	 *
	 * @return string
	 */
	public function get_url() {
		return rtrim( trim( (string) get_option( self::$option_url ) ), '/' );
	}

	/**
	 * Get the configured API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		return trim( (string) get_option( self::$option_api_key ) );
	}

	/**
	 * Check whether the plugin is configured (URL + API key present).
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->get_url() && '' !== $this->get_api_key();
	}

	/**
	 * Get the failure mode setting ('open' or 'closed').
	 *
	 * @return string 'open' or 'closed'.
	 */
	public function get_fail_mode() {
		$mode = get_option( self::$option_fail_mode, 'closed' );
		return in_array( $mode, array( 'open', 'closed' ), true ) ? $mode : 'closed';
	}

	/**
	 * Whether interaction signals are collected and forwarded to /verify.
	 *
	 * @return bool
	 */
	public function is_his_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_his_enabled, 0 );
	}

	/**
	 * Whether a submission flagged as automated is rejected.
	 *
	 * Never true on its own: with collection off no score comes back, so there
	 * is nothing to block on.
	 *
	 * @return bool
	 */
	public function is_his_blocking() {
		return $this->is_his_enabled() && (bool) get_option( self::$option_his_block, 0 );
	}

	/*------------------------------------------------------------------
	 * Integration toggles
	 *-----------------------------------------------------------------*/

	public function is_wp_login_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_wp_login );
	}

	public function is_wp_register_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_wp_register );
	}

	public function is_wp_comments_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_wp_comments );
	}

	public function is_wp_reset_password_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_wp_reset_password );
	}

	public function is_wc_login_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_wc_login );
	}

	public function is_wc_register_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_wc_register );
	}

	public function is_wc_reset_password_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_wc_reset_password );
	}

	public function is_cf7_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_cf7 );
	}

	public function is_wpforms_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_wpforms );
	}

	public function is_gravityforms_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_gravityforms );
	}

	public function is_elementor_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_elementor );
	}

	public function is_forminator_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_forminator );
	}

	public function is_formidable_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_formidable );
	}

	public function is_html_forms_enabled() {
		return $this->is_configured() && (bool) get_option( self::$option_html_forms );
	}

	/*------------------------------------------------------------------
	 * Challenge URL
	 *-----------------------------------------------------------------*/

	/**
	 * Build the challenge URL that the widget fetches from.
	 *
	 * @return string
	 */
	public function get_challengeurl() {
		$url           = $this->get_url();
		$api_key       = $this->get_api_key();
		$challenge_url = $url . '/api/v1/challenge?apiKey=' . rawurlencode( $api_key );

		return apply_filters( 'gatecha_challenge_url', $challenge_url );
	}

	/**
	 * Build the URL of the interaction-signal collector.
	 *
	 * Served by the GateCHA instance rather than bundled with the plugin, so
	 * the aggregates always match what the server that scores them expects.
	 *
	 * @return string
	 */
	public function get_his_script_url() {
		return apply_filters( 'gatecha_his_script_url', $this->get_url() . '/api/public/his.js' );
	}

	/*------------------------------------------------------------------
	 * Interaction signals
	 *-----------------------------------------------------------------*/

	/**
	 * Read the interaction signals the collector wrote into the submitted form.
	 *
	 * Returns null unless the field holds a JSON object carrying all eight
	 * aggregates as numbers. Forwarding a partial object would be worse than
	 * forwarding nothing: a missing `time_to_first_ms` reads server-side as
	 * "first interaction at 0 ms", which is one of the automation penalties, so
	 * a truncated payload would invent evidence against the visitor.
	 *
	 * @return array|null Sanitized aggregates, or null when there is nothing to send.
	 */
	public function get_his_signals() {
		if ( ! $this->is_his_enabled() ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The signals are advisory metrics scored by GateCHA; no privileged or data-modifying action is performed on this read.
		if ( ! isset( $_POST[ self::HIS_FIELD ] ) ) {
			return null;
		}

		$raw = sanitize_text_field( wp_unslash( $_POST[ self::HIS_FIELD ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The signals are advisory metrics scored by GateCHA; no privileged or data-modifying action is performed on this read.

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}

		$signals = array();
		foreach ( self::$his_signal_fields as $field => $type ) {
			if ( ! isset( $decoded[ $field ] ) || ! is_numeric( $decoded[ $field ] ) ) {
				return null;
			}
			$signals[ $field ] = ( 'int' === $type ) ? (int) $decoded[ $field ] : (float) $decoded[ $field ];
		}

		return $signals;
	}

	/*------------------------------------------------------------------
	 * Server-side verification
	 *-----------------------------------------------------------------*/

	/**
	 * Verify a solved CAPTCHA payload against the GateCHA server.
	 *
	 * @param string $payload Base64-encoded ALTCHA payload from the form.
	 * @return bool True if verification succeeded, false otherwise.
	 */
	public function verify( $payload ) {
		if ( empty( $payload ) ) {
			do_action( 'gatecha_verify_result', false );
			return false;
		}

		// Bypass: if GATECHA_BYPASS_TOKEN is defined and the payload matches, skip server verification.
		if ( defined( 'GATECHA_BYPASS_TOKEN' ) && GATECHA_BYPASS_TOKEN === $payload ) {
			do_action( 'gatecha_verify_result', true );
			return true;
		}

		$url     = $this->get_url();
		$api_key = $this->get_api_key();

		$request_body = array( 'payload' => $payload );

		$signals = $this->get_his_signals();
		if ( null !== $signals ) {
			$request_body['his_signals'] = $signals;
		}

		$response = wp_remote_post(
			$url . '/api/v1/verify',
			array(
				'body'    => wp_json_encode( $request_body ),
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Referer'       => home_url(),
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->handle_server_error( $response->get_error_message() );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );

		if ( 200 === $status ) {
			$json   = json_decode( $body, true );
			$result = isset( $json['ok'] ) && true === $json['ok'];
			$result = $this->apply_his_outcome( $json, $result );
			do_action( 'gatecha_verify_result', $result );
			return $result;
		}

		return $this->handle_server_error(
			sprintf( 'HTTP %d — %s', $status, wp_remote_retrieve_response_message( $response ) )
		);
	}

	/**
	 * Apply the interaction score carried by a /verify response.
	 *
	 * Fires `gatecha_his_result` so a site can log the score without acting on
	 * it, which is the cheapest way to see how your own traffic distributes
	 * before deciding to block anything. Then, and only when blocking is
	 * switched on, turns a suspected submission into a failed verification.
	 *
	 * Both fields are absent from the response when the request carried no
	 * signals, and absent is not a score of zero: a visitor whose collector
	 * never ran is not thereby a bot. A PoW failure is left as it is; it has
	 * already failed for a better reason.
	 *
	 * @param mixed $json   Decoded /verify response.
	 * @param bool  $result Verification result so far.
	 * @return bool Result after the interaction score is taken into account.
	 */
	private function apply_his_outcome( $json, $result ) {
		if ( ! is_array( $json ) || ! isset( $json['his_bot_score'] ) ) {
			return $result;
		}

		$score     = (float) $json['his_bot_score'];
		$suspected = ! empty( $json['his_bot_suspected'] );

		/**
		 * Fires once per verification that carried interaction signals.
		 *
		 * @param float $score     Automation probability in [0,1]. Higher is more bot-like,
		 *                         which is the reverse of the reCAPTCHA convention.
		 * @param bool  $suspected Whether GateCHA judged the score past its own threshold.
		 */
		do_action( 'gatecha_his_result', $score, $suspected );

		if ( $result && $suspected && $this->is_his_blocking() ) {
			return false;
		}

		return $result;
	}

	/**
	 * Handle a server communication error during verification.
	 *
	 * Logs the error, stores it in a transient for admin notices,
	 * and returns true or false depending on the fail mode setting.
	 *
	 * @param string $detail Error detail message.
	 * @return bool True if fail-open, false if fail-closed.
	 */
	private function handle_server_error( $detail ) {
		$fail_open = 'open' === $this->get_fail_mode();

		$message = sprintf(
			'[GateCHA] Server unreachable (%s). Mode: fail %s.',
			$detail,
			$fail_open ? 'open — submission allowed' : 'closed — submission blocked'
		);

		// Write to WordPress debug log (visible when WP_DEBUG_LOG is enabled).
		error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		// Store for admin notice display (1-hour TTL).
		set_transient( 'gatecha_last_server_error', array(
			'time'    => time(),
			'detail'  => $detail,
			'mode'    => $fail_open ? 'open' : 'closed',
		), HOUR_IN_SECONDS );

		do_action( 'gatecha_verify_result', $fail_open );
		return $fail_open;
	}

	/*------------------------------------------------------------------
	 * Widget rendering
	 *-----------------------------------------------------------------*/

	/**
	 * Get translatable strings for the ALTCHA widget.
	 *
	 * @return array
	 */
	public function get_translations() {
		// v3 resolves UI strings from a global i18n store; the per-widget
		// `strings` attribute is gone, and gatecha.js registers this set for the
		// active language instead. Every key the widget can show is provided so
		// nothing falls back to an untranslated default. Branding is hidden
		// through `configuration`, not by blanking the footer string.
		$translations = array(
			'ariaLinkLabel'        => __( 'ALTCHA (official website)', 'gatecha-captcha' ),
			'cancel'               => __( 'Cancel', 'gatecha-captcha' ),
			'enterCode'            => __( 'Enter code', 'gatecha-captcha' ),
			'enterCodeAria'        => __( 'Enter code you hear. Press Space to play audio.', 'gatecha-captcha' ),
			'enterCodeFromImage'   => __( 'To proceed, please enter the code from the image below.', 'gatecha-captcha' ),
			'error'                => __( 'Verification failed. Try again later.', 'gatecha-captcha' ),
			'expired'              => __( 'Verification expired. Try again.', 'gatecha-captcha' ),
			'footer'               => __( 'Protected by <a href="https://altcha.org" target="_blank">ALTCHA</a>', 'gatecha-captcha' ),
			'getAudioChallenge'    => __( 'Get an audio challenge', 'gatecha-captcha' ),
			'label'                => __( "I'm not a robot", 'gatecha-captcha' ),
			'loading'              => __( 'Loading...', 'gatecha-captcha' ),
			'reload'               => __( 'Reload', 'gatecha-captcha' ),
			'verificationRequired' => __( 'Verification required!', 'gatecha-captcha' ),
			'verified'             => __( 'Verified', 'gatecha-captcha' ),
			'verify'               => __( 'Verify', 'gatecha-captcha' ),
			'verifying'            => __( 'Verifying...', 'gatecha-captcha' ),
			'waitAlert'            => __( 'Verifying... please wait.', 'gatecha-captcha' ),
		);

		return apply_filters( 'gatecha_translations', $translations );
	}

	/**
	 * Resolve the 2-letter language code for the widget from the site locale.
	 *
	 * @return string
	 */
	public function get_language() {
		$lang = strtolower( substr( (string) get_locale(), 0, 2 ) );
		if ( '' === $lang ) {
			$lang = 'en';
		}
		return apply_filters( 'gatecha_language', $lang );
	}

	/**
	 * Render the ALTCHA widget HTML.
	 *
	 * @param bool        $wrap     Wrap in a div container.
	 * @param string|null $language Language code override.
	 * @param string|null $name     Override the hidden field name (default "altcha").
	 * @return string Widget HTML.
	 */
	public function render_widget( $wrap = false, $language = null, $name = null ) {
		if ( ! $this->is_configured() ) {
			return '';
		}

		gatecha_enqueue_scripts();
		gatecha_enqueue_styles();
		gatecha_enqueue_his();

		$auto_verify    = (bool) get_option( self::$option_auto_verify, 1 );
		$hide_branding  = (bool) get_option( self::$option_hide_branding, 0 );

		// v3 takes most options through a single JSON `configuration` attribute.
		// ALTCHA's own interaction signature is switched off deliberately: this
		// plugin promises no fingerprinting, and where a site wants interaction
		// scoring it comes from GateCHA's own collector, whose aggregates the
		// instance is able to score.
		$configuration = array(
			'humanInteractionSignature' => false,
		);

		if ( $hide_branding ) {
			$configuration['hideLogo']   = true;
			$configuration['hideFooter'] = true;
		}

		$attrs = array(
			'challenge'     => $this->get_challengeurl(),
			'language'      => $language ? $language : $this->get_language(),
			'configuration' => wp_json_encode( $configuration ),
		);

		if ( $auto_verify ) {
			$attrs['auto'] = 'onfocus';
		}

		if ( $name ) {
			$attrs['name'] = $name;
		}

		/**
		 * Filter the widget attributes.
		 *
		 * @param array       $attrs    Widget attributes.
		 * @param string|null $language Language code.
		 * @param string|null $name     Hidden field name.
		 */
		$attrs = apply_filters( 'gatecha_widget_attrs', $attrs, $language, $name );

		$attributes = '';
		foreach ( $attrs as $key => $value ) {
			if ( '' === $value ) {
				$attributes .= ' ' . esc_attr( $key );
			} else {
				$attributes .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		$html = '<altcha-widget' . $attributes . '></altcha-widget>';

		$html .= '<noscript>' . esc_html__( 'This form requires JavaScript.', 'gatecha-captcha' ) . '</noscript>';

		if ( $wrap ) {
			$html = '<div class="gatecha-widget-wrap">' . $html . '</div>';
		}

		/**
		 * Filter the complete widget HTML.
		 *
		 * @param string      $html     Widget HTML.
		 * @param string|null $language Language code.
		 * @param string|null $name     Hidden field name.
		 */
		return apply_filters( 'gatecha_widget_html', $html, $language, $name );
	}
}

/*----------------------------------------------------------------------
 * Helper functions (not class methods)
 *---------------------------------------------------------------------*/

/**
 * Enqueue the ALTCHA widget script.
 */
function gatecha_enqueue_scripts() {
	wp_enqueue_script(
		'gatecha-widget',
		GateCHA::$widget_script_src,
		array(),
		GATECHA_VERSION,
		true
	);
	wp_enqueue_script(
		'gatecha-script',
		GateCHA::$wp_script_src,
		array( 'gatecha-widget' ),
		GATECHA_VERSION,
		true
	);

	// Hand the localized strings and language to gatecha.js, which registers
	// them into the widget's i18n store. Localize once even if several forms
	// render on the page.
	static $localized = false;
	$plugin = GateCHA::$instance;
	if ( ! $localized && $plugin ) {
		$localized = true;
		wp_localize_script(
			'gatecha-script',
			'gatechaI18n',
			array(
				'language' => $plugin->get_language(),
				'strings'  => $plugin->get_translations(),
			)
		);
	}
}

/**
 * Enqueue the interaction-signal collector, when the setting is on.
 *
 * The script attaches to every form on the page that carries an ALTCHA widget
 * and, on submit, writes its aggregates into a hidden field the plugin then
 * forwards to /verify. Forms without a widget are left alone.
 *
 * It is served by the configured GateCHA instance, the same host the widget
 * already fetches its challenge from, so no new party is involved. Instances
 * older than 0.6.0 answer 404 for it, which costs nothing: no field is written
 * and verification proceeds exactly as it did before.
 */
function gatecha_enqueue_his() {
	$plugin = GateCHA::$instance;

	if ( ! $plugin instanceof GateCHA || ! $plugin->is_his_enabled() ) {
		return;
	}

	wp_enqueue_script(
		'gatecha-his',
		$plugin->get_his_script_url(),
		array(),
		GATECHA_VERSION,
		true
	);
}

/**
 * Enqueue the widget stylesheet.
 */
function gatecha_enqueue_styles() {
	wp_enqueue_style(
		'gatecha-widget-styles',
		GateCHA::$widget_style_src,
		array(),
		GATECHA_VERSION,
		'all'
	);
}

/**
 * Add async/defer and type="module" to the widget script tag.
 *
 * @param string $tag    Script tag HTML.
 * @param string $handle Script handle.
 * @param string $src    Script source URL.
 * @return string Modified tag.
 */
function gatecha_script_tags( $tag, $handle, $src ) {
	if ( 'gatecha-widget' === $handle ) {
		$tag = str_replace( '<script ', '<script async defer type="module" ', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'gatecha_script_tags', 10, 3 );

/**
 * Check if a third-party plugin is active.
 *
 * @param string $name Plugin identifier.
 * @return bool
 */
function gatecha_plugin_active( $name ) {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$map = array(
		'contact-form-7' => 'contact-form-7/wp-contact-form-7.php',
		'elementor'      => 'elementor-pro/elementor-pro.php',
		'formidable'     => 'formidable/formidable.php',
		'forminator'     => 'forminator/forminator.php',
		'gravityforms'   => 'gravityforms/gravityforms.php',
		'html-forms'     => 'html-forms/html-forms.php',
		'woocommerce'    => 'woocommerce/woocommerce.php',
		'wpforms'        => array(
			'wpforms/wpforms.php',
			'wpforms-lite/wpforms.php',
		),
	);

	if ( ! isset( $map[ $name ] ) ) {
		return false;
	}

	$paths = (array) $map[ $name ];
	foreach ( $paths as $path ) {
		if ( is_plugin_active( $path ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Echo the rendered widget (convenience wrapper for integration files).
 *
 * @param string|null $name Hidden field name override.
 */
function gatecha_render_widget_echo( $name = null ) {
	$plugin = GateCHA::$instance;
	echo wp_kses(
		$plugin->render_widget( true, null, $name ),
		GateCHA::$html_escape_allowed_tags
	);
}

// Instantiate the singleton.
if ( ! isset( GateCHA::$instance ) ) {
	$gatecha = new GateCHA();
	$gatecha->init();
}
