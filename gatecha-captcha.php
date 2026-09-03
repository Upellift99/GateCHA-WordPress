<?php
/**
 * Plugin Name: GateCHA CAPTCHA
 * Plugin URI:  https://gatecha.org/wordpress
 * Description: Self-hosted ALTCHA proof-of-work CAPTCHA via GateCHA. Protects WordPress forms without cookies, fingerprinting, or third-party services.
 * Author:      GateCHA
 * Author URI:  https://gatecha.org
 * Version:     1.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 7.1
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gatecha-captcha
 * Domain Path: /languages
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GATECHA_VERSION', '1.1.0' );
define( 'GATECHA_PLUGIN_FILE', __FILE__ );
define( 'GATECHA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GATECHA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/*----------------------------------------------------------------------
 * Load core class
 *---------------------------------------------------------------------*/
require_once GATECHA_PLUGIN_DIR . 'includes/class-gatecha.php';

// Set asset URLs (assigned inside the class to keep them out of global scope).
GateCHA::set_asset_urls( GATECHA_PLUGIN_URL );

/*----------------------------------------------------------------------
 * Load admin settings (admin only)
 *---------------------------------------------------------------------*/
if ( is_admin() ) {
	require_once GATECHA_PLUGIN_DIR . 'includes/class-gatecha-admin.php';
}

/*----------------------------------------------------------------------
 * Load integrations
 *---------------------------------------------------------------------*/
$gatecha_integrations = array(
	'wordpress-login',
	'wordpress-register',
	'wordpress-comments',
	'wordpress-reset-password',
	'woocommerce-login',
	'woocommerce-register',
	'woocommerce-reset-password',
	'contact-form-7',
	'wpforms',
	'gravityforms',
	'elementor',
	'forminator',
	'formidable',
	'html-forms',
);

foreach ( $gatecha_integrations as $gatecha_integration ) {
	$gatecha_file = GATECHA_PLUGIN_DIR . 'includes/integrations/' . $gatecha_integration . '.php';
	if ( file_exists( $gatecha_file ) ) {
		require_once $gatecha_file;
	}
}

/*----------------------------------------------------------------------
 * Activation / deactivation
 *---------------------------------------------------------------------*/
register_activation_hook( __FILE__, function () {
	// Options are created when the user first saves settings.
} );

/*----------------------------------------------------------------------
 * Plugin action link: Settings
 *---------------------------------------------------------------------*/
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=gatecha_admin' ) ) . '">'
		. esc_html__( 'Settings', 'gatecha-captcha' )
		. '</a>';
	array_unshift( $links, $settings_link );
	return $links;
} );

/*----------------------------------------------------------------------
 * Shortcode: [gatecha]
 *---------------------------------------------------------------------*/
add_shortcode( 'gatecha', function ( $attrs ) {
	$plugin = GateCHA::$instance;
	if ( ! $plugin || ! $plugin->is_configured() ) {
		return '';
	}
	return wp_kses(
		$plugin->render_widget( true ),
		GateCHA::$html_escape_allowed_tags
	);
} );
