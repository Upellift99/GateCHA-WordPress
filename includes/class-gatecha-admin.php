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

	register_setting( 'gatecha_options', GateCHA::$option_fail_mode, array(
		'type'              => 'string',
		'sanitize_callback' => function ( $value ) {
			return in_array( $value, array( 'open', 'closed' ), true ) ? $value : 'closed';
		},
		'default'           => 'closed',
	) );

	add_settings_field(
		GateCHA::$option_fail_mode,
		__( 'Failure Mode', 'gatecha-captcha' ),
		'gatecha_settings_select_callback',
		'gatecha_admin',
		'gatecha_section_general',
		array(
			'name'    => GateCHA::$option_fail_mode,
			'options' => array(
				'closed' => __( 'Fail closed — block submissions when server is unreachable', 'gatecha-captcha' ),
				'open'   => __( 'Fail open — allow submissions when server is unreachable', 'gatecha-captcha' ),
			),
			'hint'    => __( 'Determines how forms behave if the GateCHA server cannot be reached. "Fail closed" is safer but may block legitimate users during an outage.', 'gatecha-captcha' ),
		)
	);

	register_setting( 'gatecha_options', GateCHA::$option_auto_verify, array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 1,
	) );

	add_settings_field(
		GateCHA::$option_auto_verify,
		__( 'Auto Verify', 'gatecha-captcha' ),
		'gatecha_settings_checkbox_callback',
		'gatecha_admin',
		'gatecha_section_general',
		array(
			'name' => GateCHA::$option_auto_verify,
			'hint' => __( 'Automatically start verification when the user focuses the form. When disabled, the user must click the checkbox manually.', 'gatecha-captcha' ),
		)
	);

	register_setting( 'gatecha_options', GateCHA::$option_hide_branding, array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 0,
	) );

	add_settings_field(
		GateCHA::$option_hide_branding,
		__( 'Hide Branding', 'gatecha-captcha' ),
		'gatecha_settings_checkbox_callback',
		'gatecha_admin',
		'gatecha_section_general',
		array(
			'name' => GateCHA::$option_hide_branding,
			'hint' => __( 'Hide the ALTCHA logo and "Protected by ALTCHA" footer. Keeping branding visible is recommended.', 'gatecha-captcha' ),
		)
	);

	/*--------------------------------------------------------------
	 * Interaction signals section
	 *-------------------------------------------------------------*/
	add_settings_section(
		'gatecha_section_his',
		__( 'Interaction Signals', 'gatecha-captcha' ),
		function () {
			echo '<p>' . esc_html__( 'Proof-of-work proves a browser did the work. It does not prove a human filled the form in. Interaction signals add a second opinion: how the form was filled, as counts and durations only. Never what was typed, never pointer coordinates, never an IP address.', 'gatecha-captcha' ) . '</p>';
			echo '<p>' . esc_html__( 'Requires GateCHA 0.7.0 or later. Scores show up on your dashboard under HIS Monitor.', 'gatecha-captcha' ) . '</p>';
		},
		'gatecha_admin'
	);

	register_setting( 'gatecha_options', GateCHA::$option_his_enabled, array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 0,
	) );

	add_settings_field(
		GateCHA::$option_his_enabled,
		__( 'Collect Interaction Signals', 'gatecha-captcha' ),
		'gatecha_settings_checkbox_callback',
		'gatecha_admin',
		'gatecha_section_his',
		array(
			'name' => GateCHA::$option_his_enabled,
			'hint' => __( 'Load the collector from your GateCHA instance and send the aggregates along with each verification. On its own this changes nothing about who gets through: scores are recorded, never enforced.', 'gatecha-captcha' ),
		)
	);

	register_setting( 'gatecha_options', GateCHA::$option_his_block, array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 0,
	) );

	add_settings_field(
		GateCHA::$option_his_block,
		__( 'Reject Suspected Automation', 'gatecha-captcha' ),
		'gatecha_settings_checkbox_callback',
		'gatecha_admin',
		'gatecha_section_his',
		array(
			'name' => GateCHA::$option_his_block,
			'hint' => __( 'Fail verification when GateCHA flags a submission as automated. Needs the setting above. Turn it on once you have watched your own scores for a few days: a false positive here is silent, the visitor simply cannot submit and never tells you.', 'gatecha-captcha' ),
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
 * Render a select field.
 *
 * @param array $args Field arguments.
 */
function gatecha_settings_select_callback( array $args ) {
	$name    = $args['name'];
	$options = isset( $args['options'] ) ? $args['options'] : array();
	$hint    = isset( $args['hint'] ) ? $args['hint'] : '';
	$value   = (string) get_option( $name, '' );
	?>
	<select name="<?php echo esc_attr( $name ); ?>"
			id="<?php echo esc_attr( $name ); ?>">
		<?php foreach ( $options as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
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
 * Admin notice: GateCHA server unreachable
 *---------------------------------------------------------------------*/

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$error = get_transient( 'gatecha_last_server_error' );
	if ( ! $error ) {
		return;
	}

	$time_ago = human_time_diff( $error['time'], time() );
	$mode     = 'open' === $error['mode']
		? __( 'fail open (submissions allowed)', 'gatecha-captcha' )
		: __( 'fail closed (submissions blocked)', 'gatecha-captcha' );
	?>
	<div class="notice notice-warning">
		<p>
			<strong><?php esc_html_e( 'GateCHA CAPTCHA', 'gatecha-captcha' ); ?>:</strong>
			<?php
			printf(
				/* translators: 1: error detail, 2: human-readable time ago, 3: current failure mode */
				esc_html__( 'Server unreachable (%1$s). Last error %2$s ago. Current mode: %3$s.', 'gatecha-captcha' ),
				esc_html( $error['detail'] ),
				esc_html( $time_ago ),
				esc_html( $mode )
			);
			?>
			<a href="<?php echo esc_url( admin_url( 'options-general.php?page=gatecha_admin' ) ); ?>">
				<?php esc_html_e( 'Settings', 'gatecha-captcha' ); ?>
			</a>
		</p>
	</div>
	<?php
} );

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
