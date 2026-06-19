<?php
/**
 * Integration: Forminator.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( gatecha_plugin_active( 'forminator' ) ) :

	/**
	 * Inject the widget into form button markup.
	 *
	 * @param string $html Form HTML.
	 * @return string Modified HTML.
	 */
	add_filter( 'forminator_render_button_markup', function ( $html ) {
		return gatecha_forminator_inject_widget( $html );
	} );

	/**
	 * Fallback: inject into fields markup (multi-page forms).
	 *
	 * @param string $html Fields HTML.
	 * @return string Modified HTML.
	 */
	add_filter( 'forminator_render_fields_markup', function ( $html ) {
		return gatecha_forminator_inject_widget( $html );
	} );

	/**
	 * Verify the CAPTCHA on form submission.
	 *
	 * @param bool|array $can_show      Submittable status.
	 * @param int        $id            Form ID.
	 * @param array      $form_settings Form settings.
	 * @return bool|array
	 */
	add_filter( 'forminator_cform_form_is_submittable', function ( $can_show, $id, $form_settings ) {
		$plugin = GateCHA::$instance;
		if ( $plugin->is_forminator_enabled() ) {
			$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The ALTCHA token is itself the anti-bot check and is verified server-side by GateCHA; no privileged or data-modifying action is performed on this read.
			if ( false === $plugin->verify( $payload ) ) {
				return array(
					'can_submit' => false,
					'error'      => esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' ),
				);
			}
		}
		return $can_show;
	}, 10, 3 );

endif;

/**
 * Inject the GateCHA widget into Forminator form HTML.
 *
 * @param string $html Form HTML.
 * @return string Modified HTML.
 */
function gatecha_forminator_inject_widget( $html ) {
	$plugin = GateCHA::$instance;
	if ( ! $plugin->is_forminator_enabled() ) {
		return $html;
	}

	$widget = wp_kses(
		$plugin->render_widget( true ),
		GateCHA::$html_escape_allowed_tags
	);

	// Try to inject before the last form row.
	$target = '<div class="forminator-row forminator-row-last"';
	$pos    = strpos( $html, $target );
	if ( false !== $pos ) {
		return substr_replace( $html, $widget, $pos, 0 );
	}

	// Try to inject before the submit button.
	$target = '<button class="forminator-button ';
	$pos    = strpos( $html, $target );
	if ( false !== $pos ) {
		return substr_replace( $html, $widget, $pos, 0 );
	}

	return $html . $widget;
}
