<?php
/**
 * Integration: Elementor Pro Forms.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( gatecha_plugin_active( 'elementor' ) ) :

	$plugin = GateCHA::$instance;

	if ( $plugin->is_elementor_enabled() ) {
		/**
		 * Register the GateCHA form field with Elementor Pro.
		 *
		 * @param \ElementorPro\Modules\Forms\Registrars\Form_Fields_Registrar $registrar
		 */
		add_action( 'elementor_pro/forms/fields/register', function ( $registrar ) {
			require_once __DIR__ . '/elementor/field.php';
			$registrar->register( new GateCHA_Elementor_Form_Field() );
		} );

		// Enqueue widget assets globally when Elementor integration is active,
		// because Elementor popups load forms dynamically.
		add_action( 'wp_enqueue_scripts', function () {
			gatecha_enqueue_scripts();
			gatecha_enqueue_styles();
		} );
	}

endif;
