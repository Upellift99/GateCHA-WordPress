<?php
/**
 * Integration: Gravity Forms.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( gatecha_plugin_active( 'gravityforms' ) ) :

	/**
	 * Register the GateCHA add-on when Gravity Forms is loaded.
	 */
	add_action( 'gform_loaded', function () {
		$plugin = GateCHA::$instance;
		if ( ! $plugin->is_gravityforms_enabled() ) {
			return;
		}

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		GFForms::include_addon_framework();

		require_once __DIR__ . '/gravityforms/addon.php';
		require_once __DIR__ . '/gravityforms/field.php';

		GFAddOn::register( 'GateCHA_GFFormsAddOn' );
	}, 5 );

endif;
