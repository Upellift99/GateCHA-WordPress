<?php
/**
 * Integration: WordPress Password Reset form.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the widget on the lost password form.
 */
add_action( 'lostpassword_form', function () {
	$plugin = GateCHA::$instance;
	if ( $plugin->is_wp_reset_password_enabled() ) {
		gatecha_render_widget_echo();
	}
} );

/**
 * Verify the CAPTCHA on password reset request.
 *
 * @param WP_Error $errors Error object.
 */
add_action( 'lostpassword_post', function ( $errors ) {
	// Let WooCommerce handle its own reset form.
	if ( gatecha_plugin_active( 'woocommerce' ) && isset( $_POST['woocommerce-lost-password-nonce'] ) ) { // phpcs:ignore
		return;
	}

	$plugin = GateCHA::$instance;
	if ( $plugin->is_wp_reset_password_enabled() ) {
		$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore
		if ( false === $plugin->verify( $payload ) ) {
			$errors->add(
				'gatecha_error',
				'<strong>' . esc_html__( 'Error', 'gatecha-captcha' ) . '</strong>: '
				. esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' )
			);
		}
	}
} );
