<?php
/**
 * Plugin Name: GateCHA CAPTCHA
 * Plugin URI:  https://github.com/Upellift99/GateCHA
 * Description: Self-hosted ALTCHA proof-of-work CAPTCHA via GateCHA. Protects WordPress forms without cookies, fingerprinting, or third-party services.
 * Author:      Upellift
 * Author URI:  https://github.com/Upellift99
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.7
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

define( 'GATECHA_VERSION', '1.0.0' );
define( 'GATECHA_PLUGIN_FILE', __FILE__ );
define( 'GATECHA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GATECHA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/*----------------------------------------------------------------------
 * Load core class
 *---------------------------------------------------------------------*/
require_once GATECHA_PLUGIN_DIR . 'includes/class-gatecha.php';

// Set asset URLs.
GateCHA::$widget_script_src = GATECHA_PLUGIN_URL . 'assets/js/altcha-widget.min.js';
GateCHA::$widget_style_src  = GATECHA_PLUGIN_URL . 'assets/css/gatecha.css';
GateCHA::$wp_script_src     = GATECHA_PLUGIN_URL . 'assets/js/gatecha.js';

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

foreach ( $gatecha_integrations as $integration ) {
	$file = GATECHA_PLUGIN_DIR . 'includes/integrations/' . $integration . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

/*----------------------------------------------------------------------
 * Activation / deactivation
 *---------------------------------------------------------------------*/
register_activation_hook( __FILE__, function () {
	// Options are created when the user first saves settings.
} );

/*----------------------------------------------------------------------
 * Init: load text domain
 *---------------------------------------------------------------------*/
add_action( 'init', function () {
	load_plugin_textdomain( 'gatecha-captcha', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
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
