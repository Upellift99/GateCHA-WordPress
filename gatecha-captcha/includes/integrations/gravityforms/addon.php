<?php
/**
 * Gravity Forms add-on for GateCHA.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GateCHA Gravity Forms add-on.
 */
class GateCHA_GFFormsAddOn extends GFAddOn {

	protected $_version     = GATECHA_VERSION;
	protected $_min_gravityforms_version = '2.5';
	protected $_slug        = 'gatecha';
	protected $_path        = 'gatecha-captcha/gatecha-captcha.php';
	protected $_full_path   = __FILE__;
	protected $_title       = 'GateCHA CAPTCHA';
	protected $_short_title = 'GateCHA';

	/** @var GateCHA_GFFormsAddOn|null */
	private static $_instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return GateCHA_GFFormsAddOn
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Register the GateCHA field type.
	 */
	public function pre_init() {
		parent::pre_init();

		if ( $this->is_gravityforms_supported() && class_exists( 'GF_Field' ) ) {
			GF_Fields::register( new GateCHA_GFForms_Field() );
		}
	}
}
