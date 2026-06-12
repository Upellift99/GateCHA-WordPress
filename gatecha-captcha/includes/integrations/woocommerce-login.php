<?php
/**
 * Integration: WooCommerce Login form.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the widget on the WooCommerce login form.
 */
add_action( 'woocommerce_login_form', function () {
	$plugin = GateCHA::$instance;
	if ( $plugin->is_wc_login_enabled() ) {
		gatecha_render_widget_echo();
	}
} );

/**
 * Verify the CAPTCHA on WooCommerce login.
 *
 * Only processes submissions that include the WooCommerce login nonce.
 *
 * @param WP_User|WP_Error|null $user User object or error.
 * @return WP_User|WP_Error
 */
add_filter( 'authenticate', function ( $user ) {
	if ( $user instanceof WP_Error ) {
		return $user;
	}

	// Only handle WooCommerce login submissions.
	if ( ! isset( $_POST['woocommerce-login-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Detecting which host form is being submitted by field presence only; no data is processed and the host handles its own nonce.
		return $user;
	}

	$plugin = GateCHA::$instance;
	if ( $plugin->is_wc_login_enabled() ) {
		$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The ALTCHA token is itself the anti-bot check and is verified server-side by GateCHA; no privileged or data-modifying action is performed on this read.
		if ( false === $plugin->verify( $payload ) ) {
			return new WP_Error(
				'gatecha_error',
				esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' )
			);
		}
	}

	return $user;
}, 20, 1 );
