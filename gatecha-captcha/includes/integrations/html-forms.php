<?php
/**
 * Integration: HTML Forms.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( gatecha_plugin_active( 'html-forms' ) ) :

	/**
	 * Inject the widget before the closing </form> tag.
	 *
	 * @param string $html Form HTML.
	 * @return string Modified HTML.
	 */
	add_filter( 'hf_form_html', function ( $html ) {
		$plugin = GateCHA::$instance;
		if ( ! $plugin->is_html_forms_enabled() ) {
			return $html;
		}

		$widget = wp_kses(
			$plugin->render_widget( true ),
			GateCHA::$html_escape_allowed_tags
		);

		return str_replace( '</form>', $widget . '</form>', $html );
	} );

	/**
	 * Validate the CAPTCHA on form submission.
	 *
	 * @param string $error_code Current error code.
	 * @param mixed  $form       Form HTML.
	 * @param mixed  $data       Submitted data.
	 * @return string Error code or empty.
	 */
	add_filter( 'hf_validate_form', function ( $error_code, $form, $data ) {
		$plugin = GateCHA::$instance;
		if ( $plugin->is_html_forms_enabled() ) {
			$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The ALTCHA token is itself the anti-bot check and is verified server-side by GateCHA; no privileged or data-modifying action is performed on this read.
			if ( false === $plugin->verify( $payload ) ) {
				return 'gatecha_invalid';
			}
		}
		return $error_code;
	}, 10, 3 );

	/**
	 * Error message for failed GateCHA verification.
	 *
	 * @return string
	 */
	add_filter( 'hf_form_message_gatecha_invalid', function () {
		return esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' );
	} );

endif;
