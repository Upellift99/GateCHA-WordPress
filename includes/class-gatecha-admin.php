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
			'label_for' => GateCHA::$option_url,
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
			'label_for' => GateCHA::$option_api_key,
			'type' => 'text',
			'hint' => __( 'Your GateCHA API key (starts with gk_).', 'gatecha-captcha' ),
		)
	);

	register_setting( 'gatecha_options', GateCHA::$option_stats_token, array(
		'type'              => 'string',
		'sanitize_callback' => function ( $value ) {
			$value = sanitize_text_field( (string) $value );
			if ( '' !== $value && 0 !== strpos( $value, 'gs_' ) ) {
				add_settings_error(
					GateCHA::$option_stats_token,
					'gatecha_stats_token_invalid',
					__( 'The stats token starts with gs_. The previous value was kept.', 'gatecha-captcha' )
				);
				return (string) get_option( GateCHA::$option_stats_token, '' );
			}
			return $value;
		},
		'default'           => '',
	) );

	add_settings_field(
		GateCHA::$option_stats_token,
		__( 'Stats Token', 'gatecha-captcha' ),
		'gatecha_settings_field_callback',
		'gatecha_admin',
		'gatecha_section_general',
		array(
			'name' => GateCHA::$option_stats_token,
			'label_for' => GateCHA::$option_stats_token,
			'type' => 'password',
			'hint' => __( 'Optional. Shows your CAPTCHA statistics in WordPress. Generate it on the key\'s page in your GateCHA dashboard (starts with gs_).', 'gatecha-captcha' ),
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
			'label_for' => GateCHA::$option_fail_mode,
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
 * Name the row a checkbox belongs to, for its accessible name.
 *
 * Every checkbox reads "Enable" on screen, and the row title beside it is not
 * its label, so a screen reader announced eighteen identical "Enable" boxes.
 * The title and section are read back from the Settings API registries, so no
 * caller has to repeat them, and both strings are already translated.
 *
 * @param string $name Field ID.
 * @return string e.g. "WooCommerce — Login", or the field title alone.
 */
function gatecha_settings_field_context( $name ) {
	global $wp_settings_fields, $wp_settings_sections;

	foreach ( (array) ( $wp_settings_fields['gatecha_admin'] ?? array() ) as $section_id => $fields ) {
		if ( ! isset( $fields[ $name ] ) ) {
			continue;
		}
		$title   = wp_strip_all_tags( $fields[ $name ]['title'] );
		$section = $wp_settings_sections['gatecha_admin'][ $section_id ]['title'] ?? '';
		// Only "Login", "Registration" and "Password Reset" repeat across the WordPress and
		// WooCommerce sections; every other title is unique on its own.
		if ( ! in_array( $section_id, array( 'gatecha_section_wordpress', 'gatecha_section_woocommerce' ), true ) || '' === $section ) {
			return $title;
		}
		return $section . ' — ' . $title;
	}

	return '';
}

/**
 * Print an aria-describedby attribute pointing at a field's hint.
 *
 * @param string $name Field ID.
 * @param string $hint Hint text; nothing is printed without one.
 */
function gatecha_settings_describedby( $name, $hint ) {
	if ( $hint ) {
		echo 'aria-describedby="' . esc_attr( $name . '-description' ) . '"';
	}
}

/**
 * Print a field's hint, with the ID its control is described by.
 *
 * @param string $name Field ID.
 * @param string $hint Hint text.
 */
function gatecha_settings_hint( $name, $hint ) {
	if ( $hint ) {
		echo '<p class="description" id="' . esc_attr( $name . '-description' ) . '">' . esc_html( $hint ) . '</p>';
	}
}

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
		   <?php gatecha_settings_describedby( $name, $hint ); ?>
		   autocomplete="off">
	<?php gatecha_settings_hint( $name, $hint ); ?>
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
			id="<?php echo esc_attr( $name ); ?>"
			style="max-width: 100%;"
			<?php gatecha_settings_describedby( $name, $hint ); ?>>
		<?php foreach ( $options as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php gatecha_settings_hint( $name, $hint ); ?>
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
			   <?php gatecha_settings_describedby( $name, $hint ); ?>
			   <?php checked( 1, $value ); ?>
			   <?php disabled( $disabled ); ?>>
		<span class="screen-reader-text"><?php echo esc_html( gatecha_settings_field_context( $name ) ); ?>: </span>
		<?php esc_html_e( 'Enable', 'gatecha-captcha' ); ?>
	</label>
	<?php gatecha_settings_hint( $name, $hint ); ?>
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

		<?php if ( ! GateCHA::$instance->is_configured() ) : ?>
			<?php GateCHA_Cloud::render_offer(); ?>
		<?php endif; ?>

		<?php if ( GateCHA_Stats::is_available() ) : ?>
			<div class="gatecha-stats-panel">
				<h2><?php esc_html_e( 'Statistics', 'gatecha-captcha' ); ?></h2>
				<?php GateCHA_Stats::render( 10 ); ?>
			</div>
		<?php endif; ?>

		<form action="options.php" method="post">
			<?php
			settings_errors();
			settings_fields( 'gatecha_options' );
			do_settings_sections( 'gatecha_admin' );
			submit_button();
			?>
		</form>

		<p class="description">
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
