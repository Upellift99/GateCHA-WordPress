<?php
/**
 * Formidable Forms: GateCHA field type.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Formidable field type for GateCHA CAPTCHA.
 */
class GateCHAFieldType extends FrmFieldType {

	/**
	 * Field type identifier.
	 *
	 * @var string
	 */
	protected $type = 'gatecha';

	/**
	 * Whether the field stores a value.
	 *
	 * @var bool
	 */
	protected $has_input = true;

	/**
	 * Render the front-end field HTML.
	 *
	 * @param array $args Field display arguments.
	 * @return string Field HTML.
	 */
	public function front_field_input( $args ) {
		$plugin = GateCHA::$instance;
		return wp_kses(
			$plugin->render_widget(),
			GateCHA::$html_escape_allowed_tags
		);
	}

	/**
	 * Validate the field value on submission.
	 *
	 * @param array $args Validation arguments.
	 * @return array Errors array.
	 */
	public function validate( $args ) {
		$errors = array();

		$plugin = GateCHA::$instance;
		if ( $plugin->is_formidable_enabled() ) {
			$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The ALTCHA token is itself the anti-bot check and is verified server-side by GateCHA; no privileged or data-modifying action is performed on this read.
			if ( false === $plugin->verify( $payload ) ) {
				$errors[ 'field' . $args['id'] ] = esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' );
			}
		}

		return $errors;
	}
}
