<?php
/**
 * Gravity Forms field type for GateCHA.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Gravity Forms field that renders the GateCHA widget.
 */
class GateCHA_GFForms_Field extends GF_Field {

	public $type = 'gatecha';

	/**
	 * Field label in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return 'GateCHA';
	}

	/**
	 * Field settings in the form editor.
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array(
			'label_setting',
			'description_setting',
			'css_class_setting',
		);
	}

	/**
	 * Field button group in the editor.
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => 'GateCHA',
		);
	}

	/**
	 * Render the field input.
	 *
	 * @param array  $form  Form object.
	 * @param string $value Current value.
	 * @param array  $entry Entry object.
	 * @return string Field HTML.
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		if ( $this->is_form_editor() ) {
			return '<div class="ginput_container">'
				. esc_html__( 'GateCHA CAPTCHA widget will appear here.', 'gatecha-captcha' )
				. '</div>';
		}

		$plugin = GateCHA::$instance;
		return '<div class="ginput_container">'
			. wp_kses( $plugin->render_widget(), GateCHA::$html_escape_allowed_tags )
			. '</div>';
	}

	/**
	 * Validate the field submission.
	 *
	 * @param string|array $value      Field value.
	 * @param array        $form       Form object.
	 */
	public function validate( $value, $form ) {
		$plugin = GateCHA::$instance;
		if ( $plugin->is_gravityforms_enabled() ) {
			$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The ALTCHA token is itself the anti-bot check and is verified server-side by GateCHA; no privileged or data-modifying action is performed on this read.
			if ( false === $plugin->verify( $payload ) ) {
				$this->failed_validation  = true;
				$this->validation_message = esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' );
			}
		}
	}

	/**
	 * Do not save the CAPTCHA value.
	 *
	 * @param string $value      Field value.
	 * @param array  $form       Form object.
	 * @param string $input_name Input name.
	 * @param int    $lead_id    Entry ID.
	 * @param array  $lead       Entry object.
	 * @return string
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		return '';
	}
}
