<?php
/**
 * Integration: WordPress Registration form.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the widget on the registration form.
 *
 * Uses a distinct name ("altcha_register") to avoid collision when login and
 * register forms appear on the same page (e.g. WooCommerce My Account).
 */
add_action( 'register_form', function () {
	$plugin = GateCHA::$instance;
	if ( $plugin->is_wp_register_enabled() ) {
		gatecha_render_widget_echo( 'altcha_register' );
	}
} );

/**
 * Verify the CAPTCHA on registration.
 *
 * @param string   $sanitized_user_login Sanitised username.
 * @param string   $user_email           User email.
 * @param WP_Error $errors               Error object.
 */
add_action( 'register_post', function ( $sanitized_user_login, $user_email, $errors ) {
	$plugin = GateCHA::$instance;
	if ( $plugin->is_wp_register_enabled() ) {
		$payload = isset( $_POST['altcha_register'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha_register'] ) ) : ''; // phpcs:ignore
		if ( false === $plugin->verify( $payload ) ) {
			$errors->add(
				'gatecha_error',
				'<strong>' . esc_html__( 'Error', 'gatecha-captcha' ) . '</strong>: '
				. esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' )
			);
		}
	}
}, 10, 3 );
