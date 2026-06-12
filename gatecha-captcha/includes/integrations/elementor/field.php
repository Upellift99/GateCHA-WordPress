<?php
/**
 * Elementor Pro Forms: GateCHA field type.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ElementorPro\Modules\Forms\Fields\Field_Base' ) ) {
	return;
}

/**
 * Custom Elementor form field that renders the GateCHA widget.
 */
class GateCHA_Elementor_Form_Field extends \ElementorPro\Modules\Forms\Fields\Field_Base {

	/**
	 * Field type identifier.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'gatecha';
	}

	/**
	 * Field display name.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'GateCHA', 'gatecha-captcha' );
	}

	/**
	 * Render the field on the frontend.
	 *
	 * @param mixed $item       Field settings.
	 * @param int   $item_index Field index.
	 * @param mixed $form       Form widget instance.
	 */
	public function render( $item, $item_index, $form ) {
		$plugin = GateCHA::$instance;
		echo wp_kses(
			$plugin->render_widget(),
			GateCHA::$html_escape_allowed_tags
		);
	}

	/**
	 * Validate the field on submission.
	 *
	 * @param mixed $field          Field settings.
	 * @param mixed $record         Form record.
	 * @param mixed $ajax_handler   Ajax handler instance.
	 */
	public function validation( $field, $record, $ajax_handler ) {
		$plugin = GateCHA::$instance;
		if ( $plugin->is_elementor_enabled() ) {
			$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The ALTCHA token is itself the anti-bot check and is verified server-side by GateCHA; no privileged or data-modifying action is performed on this read.
			if ( false === $plugin->verify( $payload ) ) {
				$ajax_handler->add_error(
					$field['id'],
					esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' )
				);
			}
		}
	}

	/**
	 * Field controls in the Elementor editor.
	 *
	 * @param mixed $widget Widget instance.
	 */
	public function update_controls( $widget ) {
		// No additional controls needed.
	}
}
