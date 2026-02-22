<?php
/**
 * Integration: WooCommerce Password Reset form.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the widget on the WooCommerce lost password form.
 */
add_action( 'woocommerce_lostpassword_form', function () {
	$plugin = GateCHA::$instance;
	if ( $plugin->is_wc_reset_password_enabled() ) {
		gatecha_render_widget_echo();
	}
} );

/**
 * Verify the CAPTCHA on WooCommerce password reset.
 *
 * Only processes submissions that include the WooCommerce lost-password nonce.
 *
 * @param WP_Error $errors Error object.
 */
add_action( 'lostpassword_post', function ( $errors ) {
	// Only handle WooCommerce reset submissions.
	if ( ! isset( $_POST['woocommerce-lost-password-nonce'] ) ) { // phpcs:ignore
		return;
	}

	$plugin = GateCHA::$instance;
	if ( $plugin->is_wc_reset_password_enabled() ) {
		$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore
		if ( false === $plugin->verify( $payload ) ) {
			$errors->add(
				'gatecha_error',
				esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' )
			);
		}
	}
} );
