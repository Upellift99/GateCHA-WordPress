<?php
/**
 * Uninstall GateCHA CAPTCHA.
 *
 * Removes all plugin options from the database when the plugin is deleted.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$gatecha_options = array(
	'gatecha_url',
	'gatecha_api_key',
	'gatecha_wp_login',
	'gatecha_wp_register',
	'gatecha_wp_comments',
	'gatecha_wp_reset_password',
	'gatecha_wc_login',
	'gatecha_wc_register',
	'gatecha_wc_reset_password',
	'gatecha_cf7',
	'gatecha_wpforms',
	'gatecha_gravityforms',
	'gatecha_elementor',
	'gatecha_forminator',
	'gatecha_formidable',
	'gatecha_html_forms',
	'gatecha_fail_mode',
);

foreach ( $gatecha_options as $option ) {
	delete_option( $option );
}

delete_transient( 'gatecha_last_server_error' );
