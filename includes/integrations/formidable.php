<?php
/**
 * Integration: Formidable Forms.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( gatecha_plugin_active( 'formidable' ) ) :

	/**
	 * Autoloader for GateCHA Formidable field classes.
	 *
	 * @param string $class_name Class name to load.
	 */
	function gatecha_formidable_autoloader( $class_name ) {
		if ( ! preg_match( '/^GateCHA.+$/', $class_name ) ) {
			return;
		}
		$filepath = __DIR__ . '/formidable/' . $class_name . '.php';
		if ( file_exists( $filepath ) ) {
			require_once $filepath;
		}
	}

	spl_autoload_register( 'gatecha_formidable_autoloader' );

	/**
	 * Register the autoloader and field type on plugins_loaded.
	 */
	add_action( 'plugins_loaded', function () {
		$plugin = GateCHA::$instance;
		if ( ! $plugin->is_formidable_enabled() ) {
			return;
		}

		add_filter( 'frm_available_fields', function ( $fields ) {
			$fields['gatecha'] = array(
				'name' => 'GateCHA',
				'icon' => 'frm_icon_font frm_shield_check_icon',
			);
			return $fields;
		} );

		add_filter( 'frm_get_field_type_class', function ( $class, $field_type ) {
			if ( 'gatecha' === $field_type ) {
				$class = 'GateCHAFieldType';
			}
			return $class;
		}, 10, 2 );
	} );

endif;
