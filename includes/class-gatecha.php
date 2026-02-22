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

	// Asset URLs (set by main plugin file).
	public static $widget_script_src = '';
	public static $widget_style_src  = '';
	public static $wp_script_src     = '';

	// Option name constants.
	public static $option_url       = 'gatecha_url';
	public static $option_api_key   = 'gatecha_api_key';
	public static $option_fail_mode = 'gatecha_fail_mode';

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
		'altcha-widget' => array(
			'challengeurl'   => array(),
			'strings'        => array(),
			'auto'           => array(),
			'hidelogo'       => array(),
			'hidefooter'     => array(),
			'name'           => array(),
			'maxnumber'      => array(),
			'refetchonexpire' => array(),
			'expire'         => array(),
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

		$url     = $this->get_url();
		$api_key = $this->get_api_key();

		$response = wp_remote_post(
			$url . '/api/v1/verify',
			array(
				'body'    => wp_json_encode( array( 'payload' => $payload ) ),
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
			do_action( 'gatecha_verify_result', $result );
			return $result;
		}

		return $this->handle_server_error(
			sprintf( 'HTTP %d — %s', $status, wp_remote_retrieve_response_message( $response ) )
		);
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
		$translations = array(
			'error'     => __( 'Verification failed. Try again later.', 'gatecha-captcha' ),
			'footer'    => '',
			'label'     => __( "I'm not a robot", 'gatecha-captcha' ),
			'verified'  => __( 'Verified', 'gatecha-captcha' ),
			'verifying' => __( 'Verifying...', 'gatecha-captcha' ),
			'waitAlert' => __( 'Verifying... please wait.', 'gatecha-captcha' ),
		);

		return apply_filters( 'gatecha_translations', $translations );
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

		$attrs = array(
			'challengeurl'    => $this->get_challengeurl(),
			'strings'         => wp_json_encode( $this->get_translations() ),
			'auto'            => 'onfocus',
			'hidelogo'        => '',
			'hidefooter'      => '',
			'refetchonexpire' => '',
		);

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
