<?php
/**
 * Integration: WooCommerce Registration form.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the widget on the WooCommerce registration form.
 */
add_action( 'woocommerce_register_form', function () {
	$plugin = GateCHA::$instance;
	if ( $plugin->is_wc_register_enabled() ) {
		gatecha_render_widget_echo( 'altcha_register' );
	}
} );

/**
 * Verify the CAPTCHA on WooCommerce registration.
 *
 * @param string   $username Username.
 * @param string   $email    Email.
 * @param WP_Error $errors   Error object.
 */
add_action( 'woocommerce_register_post', function ( $username, $email, $errors ) {
	$plugin = GateCHA::$instance;
	if ( $plugin->is_wc_register_enabled() ) {
		$payload = isset( $_POST['altcha_register'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha_register'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The ALTCHA token is itself the anti-bot check and is verified server-side by GateCHA; no privileged or data-modifying action is performed on this read.
		if ( false === $plugin->verify( $payload ) ) {
			$errors->add(
				'gatecha_error',
				esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' )
			);
		}
	}
}, 10, 3 );
