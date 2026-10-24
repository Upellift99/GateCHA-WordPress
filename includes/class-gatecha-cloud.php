<?php
/**
 * "Use GateCHA Cloud": connect the plugin to a hosted GateCHA instance.
 *
 * Optional, and only offered on Settings → GateCHA while no server is set.
 * Nothing is sent anywhere until an administrator clicks the button: the
 * plugin then sends the admin to cloud.gatecha.com with this site's address,
 * a return URL in this admin and a random state. After sign-up the admin comes
 * back with a one-time code, which this server exchanges for the GateCHA URL,
 * API key and stats token. Self-hosting stays fully available and unchanged.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The connect flow.
 */
class GateCHA_Cloud {

	/**
	 * Per-user transient holding the state of a connection in progress.
	 *
	 * @var string
	 */
	const STATE = 'gatecha_cloud_state_';

	/**
	 * How long an admin has to finish signing up.
	 *
	 * @var int
	 */
	const STATE_TTL = 1800;

	/**
	 * Hook the two admin-post endpoints and the result notice.
	 */
	public static function init() {
		add_action( 'admin_post_gatecha_cloud_connect', array( __CLASS__, 'start' ) );
		add_action( 'admin_post_gatecha_cloud_callback', array( __CLASS__, 'callback' ) );
		// Back from sign-up with an expired session: log in, then land here again.
		add_action( 'admin_post_nopriv_gatecha_cloud_callback', 'auth_redirect' );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
	}

	/**
	 * Base URL of the GateCHA Cloud service. Overridable for testing with the
	 * GATECHA_CLOUD_URL constant or the gatecha_cloud_url filter.
	 *
	 * @return string
	 */
	public static function base_url() {
		$url = defined( 'GATECHA_CLOUD_URL' ) ? GATECHA_CLOUD_URL : 'https://cloud.gatecha.com';
		return untrailingslashit( (string) apply_filters( 'gatecha_cloud_url', $url ) );
	}

	/**
	 * Settings page URL, with an optional result for the notice.
	 *
	 * @param array $args Query arguments.
	 * @return string
	 */
	private static function settings_url( array $args = array() ) {
		return add_query_arg( $args, admin_url( 'options-general.php?page=gatecha_admin' ) );
	}

	/**
	 * The offer shown above the settings while no GateCHA server is set.
	 */
	public static function render_offer() {
		?>
		<div class="gatecha-cloud-offer">
			<h2><?php esc_html_e( 'No GateCHA server yet?', 'gatecha-captcha' ); ?></h2>
			<p><?php esc_html_e( 'This plugin checks your forms through a GateCHA server. Host one yourself, free and open source, or let us host it for you.', 'gatecha-captcha' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="gatecha-cloud-offer__actions">
				<input type="hidden" name="action" value="gatecha_cloud_connect">
				<?php wp_nonce_field( 'gatecha_cloud_connect' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Use GateCHA Cloud', 'gatecha-captcha' ); ?></button>
				<a class="button" href="https://gatecha.org/docs/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Host it yourself (free)', 'gatecha-captcha' ); ?></a>
			</form>
			<p class="description">
				<?php esc_html_e( 'GateCHA Cloud is hosted in France, with a free 30-day trial and no card needed. You come back here with everything filled in.', 'gatecha-captcha' ); ?>
				<?php
				printf(
					/* translators: %s: host name of the GateCHA Cloud service */
					esc_html__( 'Clicking sends this site\'s address to %s to start the sign-up; nothing is sent before.', 'gatecha-captcha' ),
					esc_html( (string) wp_parse_url( self::base_url(), PHP_URL_HOST ) )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Start: remember a random state for this admin and go to the service.
	 */
	public static function start() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'gatecha-captcha' ), 403 );
		}
		check_admin_referer( 'gatecha_cloud_connect' );

		$state = wp_generate_password( 32, false, false );
		set_transient( self::STATE . get_current_user_id(), $state, self::STATE_TTL );

		$url = add_query_arg(
			array(
				'site'   => rawurlencode( home_url() ),
				'return' => rawurlencode( admin_url( 'admin-post.php?action=gatecha_cloud_callback' ) ),
				'state'  => rawurlencode( $state ),
			),
			self::base_url() . '/connect'
		);
		// The service is on another host by design, so wp_safe_redirect() would refuse it.
		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Return: check the state, exchange the code, save the settings.
	 */
	public static function callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'gatecha-captcha' ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- the state below is this flow's nonce.
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$code  = isset( $_GET['gatecha_code'] ) ? sanitize_text_field( wp_unslash( $_GET['gatecha_code'] ) ) : '';
		// phpcs:enable

		$key      = self::STATE . get_current_user_id();
		$expected = get_transient( $key );
		delete_transient( $key );
		if ( ! is_string( $expected ) || '' === $state || ! hash_equals( $expected, $state ) || '' === $code ) {
			self::finish( 'state' );
		}

		$response = wp_remote_post(
			self::base_url() . '/connect/exchange',
			array(
				'body'    => array( 'code' => $code ),
				'headers' => array( 'Accept' => 'application/json' ),
				'timeout' => 15,
			)
		);
		if ( is_wp_error( $response ) ) {
			self::finish( 'unreachable' );
		}
		$json = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) || ! is_array( $json ) ) {
			self::finish( 'exchange' );
		}

		$url   = isset( $json['gatecha_url'] ) ? esc_url_raw( (string) $json['gatecha_url'], array( 'https' ) ) : '';
		$api   = isset( $json['api_key'] ) ? (string) $json['api_key'] : '';
		$token = isset( $json['stats_token'] ) ? (string) $json['stats_token'] : '';
		if ( '' === $url || ! preg_match( '/^gk_[a-f0-9]{8,64}$/', $api ) || ! preg_match( '/^gs_[a-f0-9]{8,64}$/', $token ) ) {
			self::finish( 'exchange' );
		}

		update_option( GateCHA::$option_url, untrailingslashit( $url ) );
		update_option( GateCHA::$option_api_key, $api );
		update_option( GateCHA::$option_stats_token, $token );
		self::finish( 'connected' );
	}

	/**
	 * Back to the settings page with the outcome.
	 *
	 * @param string $result 'connected' or an error reason.
	 */
	private static function finish( $result ) {
		wp_safe_redirect( self::settings_url( array( 'gatecha_cloud' => $result ) ) );
		exit;
	}

	/**
	 * Outcome notice, on the settings page only.
	 */
	public static function notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'settings_page_gatecha_admin' !== $screen->id || empty( $_GET['gatecha_cloud'] ) ) {
			return;
		}
		$result = sanitize_key( wp_unslash( $_GET['gatecha_cloud'] ) );
		// phpcs:enable

		$messages = array(
			'connected'   => __( 'Connected to GateCHA Cloud. Choose below which forms to protect, then save.', 'gatecha-captcha' ),
			'state'       => __( 'The connection could not be confirmed: it expired, or was started from another browser. Start again.', 'gatecha-captcha' ),
			'unreachable' => __( 'GateCHA Cloud could not be reached to finish the connection. Start again in a few minutes, or paste the values from your confirmation email.', 'gatecha-captcha' ),
			'exchange'    => __( 'GateCHA Cloud did not confirm the connection. Start again, or paste the values from your confirmation email.', 'gatecha-captcha' ),
		);
		if ( ! isset( $messages[ $result ] ) ) {
			return;
		}
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			'connected' === $result ? 'success' : 'error',
			esc_html( $messages[ $result ] )
		);
	}
}
