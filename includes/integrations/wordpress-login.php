<?php
/**
 * Integration: WordPress Login form.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the widget on the login form.
 */
add_action( 'login_form', function () {
	$plugin = GateCHA::$instance;
	if ( $plugin->is_wp_login_enabled() ) {
		gatecha_render_widget_echo();
	}
} );

/**
 * Verify the CAPTCHA on authentication.
 *
 * @param WP_User|WP_Error|null $user     User object or error.
 * @param string                $username Username.
 * @param string                $password Password.
 * @return WP_User|WP_Error
 */
add_filter( 'authenticate', function ( $user, $username, $password ) {
	if ( $user instanceof WP_Error ) {
		return $user;
	}

	// Skip non-form authentication.
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return $user;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return $user;
	}
	if ( empty( $username ) && empty( $password ) ) {
		return $user;
	}

	// Let WooCommerce login handle its own verification.
	if ( gatecha_plugin_active( 'woocommerce' ) && isset( $_POST['woocommerce-login-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Detecting which host form is being submitted by field presence only; no data is processed and the host handles its own nonce.
		return $user;
	}

	$plugin = GateCHA::$instance;
	if ( $plugin->is_wp_login_enabled() ) {
		$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The ALTCHA token is itself the anti-bot check and is verified server-side by GateCHA; no privileged or data-modifying action is performed on this read.
		if ( false === $plugin->verify( $payload ) ) {
			return new WP_Error(
				'gatecha_error',
				'<strong>' . esc_html__( 'Error', 'gatecha-captcha' ) . '</strong>: '
				. esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' )
			);
		}
	}

	return $user;
}, 20, 3 );
