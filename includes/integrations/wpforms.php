<?php
/**
 * Integration: WPForms.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( gatecha_plugin_active( 'wpforms' ) ) :

	/**
	 * Render the widget before the WPForms submit button.
	 */
	add_action( 'wpforms_display_submit_before', function () {
		$plugin = GateCHA::$instance;
		if ( $plugin->is_wpforms_enabled() ) {
			gatecha_render_widget_echo();
		}
	} );

	/**
	 * Verify the CAPTCHA on WPForms submission.
	 *
	 * @param array $fields    Submitted fields.
	 * @param array $entry     Form entry data.
	 * @param array $form_data Form configuration.
	 */
	add_action( 'wpforms_process', function ( $fields, $entry, $form_data ) {
		$plugin = GateCHA::$instance;
		if ( $plugin->is_wpforms_enabled() ) {
			$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore
			if ( false === $plugin->verify( $payload ) ) {
				wpforms()->process->errors[ $form_data['id'] ]['header'] = esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' );
			}
		}
	}, 10, 3 );

endif;
