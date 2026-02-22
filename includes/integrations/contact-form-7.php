<?php
/**
 * Integration: Contact Form 7.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( gatecha_plugin_active( 'contact-form-7' ) ) :

	/**
	 * Inject the widget before the submit button in CF7 forms.
	 *
	 * @param string $elements Form HTML elements.
	 * @return string Modified form elements.
	 */
	add_filter( 'wpcf7_form_elements', function ( $elements ) {
		$plugin = GateCHA::$instance;
		if ( ! $plugin->is_cf7_enabled() ) {
			return $elements;
		}

		$widget = wp_kses(
			$plugin->render_widget( true ),
			GateCHA::$html_escape_allowed_tags
		);

		// Try to inject before the submit input.
		$input_marker = '<input class="wpcf7-form-control wpcf7-submit ';
		$pos          = strpos( $elements, $input_marker );
		if ( false !== $pos ) {
			return substr_replace( $elements, $widget, $pos, 0 );
		}

		// Try to inject before a submit button.
		$button_marker = '<button class="wpcf7-form-control wpcf7-submit ';
		$pos           = strpos( $elements, $button_marker );
		if ( false !== $pos ) {
			return substr_replace( $elements, $widget, $pos, 0 );
		}

		// Fallback: append.
		return $elements . $widget;
	}, 100 );

	/**
	 * Verify the CAPTCHA on CF7 form submission.
	 *
	 * @param bool $spam Current spam status.
	 * @return bool True if spam.
	 */
	add_filter( 'wpcf7_spam', function ( $spam ) {
		if ( $spam ) {
			return $spam;
		}

		$plugin = GateCHA::$instance;
		if ( $plugin->is_cf7_enabled() ) {
			$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore
			if ( false === $plugin->verify( $payload ) ) {
				return true;
			}
		}

		return $spam;
	}, 9 );

endif;
