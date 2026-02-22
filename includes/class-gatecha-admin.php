<?php
/**
 * GateCHA admin settings page.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the settings menu page.
 */
add_action( 'admin_menu', function () {
	add_options_page(
		__( 'GateCHA CAPTCHA', 'gatecha-captcha' ),
		__( 'GateCHA', 'gatecha-captcha' ),
		'manage_options',
		'gatecha_admin',
		'gatecha_options_page_html'
	);
} );

/**
 * Register settings, sections, and fields.
 */
add_action( 'admin_init', function () {

	/*--------------------------------------------------------------
	 * General section
	 *-------------------------------------------------------------*/
	add_settings_section(
		'gatecha_section_general',
		__( 'General Settings', 'gatecha-captcha' ),
		function () {
			echo '<p>' . esc_html__( 'Enter the URL of your GateCHA instance and the API key.', 'gatecha-captcha' ) . '</p>';
		},
		'gatecha_admin'
	);

	register_setting( 'gatecha_options', GateCHA::$option_url, array(
		'type'              => 'string',
		'sanitize_callback' => function ( $value ) {
			return untrailingslashit( esc_url_raw( trim( $value ) ) );
		},
		'default'           => '',
	) );

	add_settings_field(
		GateCHA::$option_url,
		__( 'GateCHA URL', 'gatecha-captcha' ),
		'gatecha_settings_field_callback',
		'gatecha_admin',
		'gatecha_section_general',
		array(
			'name' => GateCHA::$option_url,
			'type' => 'url',
			'hint' => __( 'Example: https://gatecha.example.com', 'gatecha-captcha' ),
		)
	);

	register_setting( 'gatecha_options', GateCHA::$option_api_key, array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	) );

	add_settings_field(
		GateCHA::$option_api_key,
		__( 'API Key', 'gatecha-captcha' ),
		'gatecha_settings_field_callback',
		'gatecha_admin',
		'gatecha_section_general',
		array(
			'name' => GateCHA::$option_api_key,
			'type' => 'text',
			'hint' => __( 'Your GateCHA API key (starts with gk_).', 'gatecha-captcha' ),
		)
	);

	/*--------------------------------------------------------------
	 * WordPress section
	 *-------------------------------------------------------------*/
	add_settings_section(
		'gatecha_section_wordpress',
		__( 'WordPress', 'gatecha-captcha' ),
		function () {
			echo '<p>' . esc_html__( 'Enable CAPTCHA on built-in WordPress forms.', 'gatecha-captcha' ) . '</p>';
		},
		'gatecha_admin'
	);

	$wp_integrations = array(
		GateCHA::$option_wp_login          => __( 'Login', 'gatecha-captcha' ),
		GateCHA::$option_wp_register       => __( 'Registration', 'gatecha-captcha' ),
		GateCHA::$option_wp_reset_password => __( 'Password Reset', 'gatecha-captcha' ),
		GateCHA::$option_wp_comments       => __( 'Comments', 'gatecha-captcha' ),
	);

	foreach ( $wp_integrations as $option_name => $label ) {
		register_setting( 'gatecha_options', $option_name, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			$option_name,
			$label,
			'gatecha_settings_checkbox_callback',
			'gatecha_admin',
			'gatecha_section_wordpress',
			array( 'name' => $option_name )
		);
	}

	/*--------------------------------------------------------------
	 * WooCommerce section
	 *-------------------------------------------------------------*/
	$wc_active = gatecha_plugin_active( 'woocommerce' );

	add_settings_section(
		'gatecha_section_woocommerce',
		__( 'WooCommerce', 'gatecha-captcha' ),
		function () use ( $wc_active ) {
			if ( ! $wc_active ) {
				echo '<p>' . esc_html__( 'WooCommerce is not active.', 'gatecha-captcha' ) . '</p>';
			}
		},
		'gatecha_admin'
	);

	$wc_integrations = array(
		GateCHA::$option_wc_login          => __( 'Login', 'gatecha-captcha' ),
		GateCHA::$option_wc_register       => __( 'Registration', 'gatecha-captcha' ),
		GateCHA::$option_wc_reset_password => __( 'Password Reset', 'gatecha-captcha' ),
	);

	foreach ( $wc_integrations as $option_name => $label ) {
		register_setting( 'gatecha_options', $option_name, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			$option_name,
			$label,
			'gatecha_settings_checkbox_callback',
			'gatecha_admin',
			'gatecha_section_woocommerce',
			array(
				'name'     => $option_name,
				'disabled' => ! $wc_active,
			)
		);
	}

	/*--------------------------------------------------------------
	 * Third-party integrations section
	 *-------------------------------------------------------------*/
	add_settings_section(
		'gatecha_section_integrations',
		__( 'Integrations', 'gatecha-captcha' ),
		function () {
			echo '<p>' . esc_html__( 'Enable CAPTCHA on third-party form plugins.', 'gatecha-captcha' ) . '</p>';
		},
		'gatecha_admin'
	);

	$integrations = array(
		GateCHA::$option_cf7          => array(
			'label'  => __( 'Contact Form 7', 'gatecha-captcha' ),
			'plugin' => 'contact-form-7',
		),
		GateCHA::$option_wpforms      => array(
			'label'  => __( 'WPForms', 'gatecha-captcha' ),
			'plugin' => 'wpforms',
		),
		GateCHA::$option_gravityforms => array(
			'label'  => __( 'Gravity Forms', 'gatecha-captcha' ),
			'plugin' => 'gravityforms',
		),
		GateCHA::$option_elementor    => array(
			'label'  => __( 'Elementor Pro Forms', 'gatecha-captcha' ),
			'plugin' => 'elementor',
		),
		GateCHA::$option_forminator   => array(
			'label'  => __( 'Forminator', 'gatecha-captcha' ),
			'plugin' => 'forminator',
		),
		GateCHA::$option_formidable   => array(
			'label'  => __( 'Formidable Forms', 'gatecha-captcha' ),
			'plugin' => 'formidable',
		),
		GateCHA::$option_html_forms   => array(
			'label'  => __( 'HTML Forms', 'gatecha-captcha' ),
			'plugin' => 'html-forms',
		),
	);

	foreach ( $integrations as $option_name => $info ) {
		$active = gatecha_plugin_active( $info['plugin'] );

		register_setting( 'gatecha_options', $option_name, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			$option_name,
			$info['label'],
			'gatecha_settings_checkbox_callback',
			'gatecha_admin',
			'gatecha_section_integrations',
			array(
				'name'     => $option_name,
				'disabled' => ! $active,
				'hint'     => $active ? '' : __( 'Plugin not active.', 'gatecha-captcha' ),
			)
		);
	}
} );

/*----------------------------------------------------------------------
 * Field render callbacks
 *---------------------------------------------------------------------*/

/**
 * Render a text / URL / password input field.
 *
 * @param array $args Field arguments.
 */
function gatecha_settings_field_callback( array $args ) {
	$name  = $args['name'];
	$type  = isset( $args['type'] ) ? $args['type'] : 'text';
	$hint  = isset( $args['hint'] ) ? $args['hint'] : '';
	$value = esc_attr( (string) get_option( $name, '' ) );
	?>
	<input type="<?php echo esc_attr( $type ); ?>"
		   class="regular-text"
		   name="<?php echo esc_attr( $name ); ?>"
		   id="<?php echo esc_attr( $name ); ?>"
		   value="<?php echo esc_attr( $value ); ?>"
		   autocomplete="off">
	<?php if ( $hint ) : ?>
		<p class="description"><?php echo esc_html( $hint ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Render a checkbox field.
 *
 * @param array $args Field arguments.
 */
function gatecha_settings_checkbox_callback( array $args ) {
	$name     = $args['name'];
	$disabled = ! empty( $args['disabled'] );
	$hint     = isset( $args['hint'] ) ? $args['hint'] : '';
	$value    = (int) get_option( $name, 0 );
	?>
	<label for="<?php echo esc_attr( $name ); ?>">
		<input type="checkbox"
			   name="<?php echo esc_attr( $name ); ?>"
			   id="<?php echo esc_attr( $name ); ?>"
			   value="1"
			   <?php checked( 1, $value ); ?>
			   <?php disabled( $disabled ); ?>>
		<?php esc_html_e( 'Enable', 'gatecha-captcha' ); ?>
	</label>
	<?php if ( $hint ) : ?>
		<p class="description"><?php echo esc_html( $hint ); ?></p>
	<?php endif; ?>
	<?php
}

/*----------------------------------------------------------------------
 * Options page HTML
 *---------------------------------------------------------------------*/

/**
 * Render the plugin settings page.
 */
function gatecha_options_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<form action="options.php" method="post">
			<?php
			settings_errors();
			settings_fields( 'gatecha_options' );
			do_settings_sections( 'gatecha_admin' );
			submit_button();
			?>
		</form>

		<p style="opacity: .6;">
			<?php
			printf(
				/* translators: %s: plugin version number */
				esc_html__( 'GateCHA CAPTCHA for WordPress — v%s', 'gatecha-captcha' ),
				esc_html( GATECHA_VERSION )
			);
			?>
		</p>
	</div>
	<?php
}
