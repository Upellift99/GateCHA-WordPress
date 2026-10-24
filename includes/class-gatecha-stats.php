<?php
/**
 * GateCHA statistics in the WordPress admin.
 *
 * Reads the configured key's numbers from the instance's GET /api/v1/stats
 * with the key's stats token, and shows them in a dashboard widget and at the
 * top of Settings → GateCHA. Admin only, and nothing is requested until a
 * stats token is saved.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches, caches and renders the key's statistics.
 */
class GateCHA_Stats {

	/**
	 * Transient holding the last response, with the settings it was read with.
	 *
	 * @var string
	 */
	const CACHE = 'gatecha_stats_cache';

	/**
	 * How long a successful read is reused.
	 *
	 * @var int
	 */
	const TTL = 600;

	/**
	 * How long a failed read is reused, so a down instance is not asked again
	 * on every admin page load.
	 *
	 * @var int
	 */
	const ERROR_TTL = 120;

	/**
	 * Days of history requested.
	 *
	 * @var int
	 */
	const DAYS = 30;

	/**
	 * Hook the widget and the admin stylesheet.
	 */
	public static function init() {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_style' ) );
	}

	/**
	 * Whether statistics can be requested at all.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return GateCHA::$instance->is_configured() && '' !== GateCHA::$instance->get_stats_token();
	}

	/**
	 * Add the dashboard widget for administrators, once a stats token is set.
	 */
	public static function register_widget() {
		if ( ! current_user_can( 'manage_options' ) || ! self::is_available() ) {
			return;
		}
		wp_add_dashboard_widget(
			'gatecha_stats',
			__( 'GateCHA CAPTCHA', 'gatecha-captcha' ),
			array( __CLASS__, 'render_widget' )
		);
	}

	/**
	 * Load the stylesheet on the two screens that show statistics.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public static function enqueue_style( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'index.php', 'settings_page_gatecha_admin' ), true ) ) {
			return;
		}
		wp_enqueue_style(
			'gatecha-admin',
			GATECHA_PLUGIN_URL . 'assets/css/gatecha-admin.css',
			array(),
			GATECHA_VERSION
		);
	}

	/**
	 * Return the statistics, from cache when fresh.
	 *
	 * The cache records which URL and token it was read with, so changing
	 * either in the settings takes effect on the next page load rather than
	 * after the cache expires.
	 *
	 * @return array|WP_Error Decoded /api/v1/stats response.
	 */
	public static function get() {
		$gatecha   = GateCHA::$instance;
		$signature = md5( $gatecha->get_url() . '|' . $gatecha->get_stats_token() );

		$cached = get_transient( self::CACHE );
		if ( is_array( $cached ) && isset( $cached['sig'] ) && $cached['sig'] === $signature ) {
			return isset( $cached['error'] ) ? new WP_Error( 'gatecha_stats', $cached['error'] ) : $cached['data'];
		}

		$result = self::fetch();
		if ( is_wp_error( $result ) ) {
			set_transient( self::CACHE, array( 'sig' => $signature, 'error' => $result->get_error_message() ), self::ERROR_TTL );
		} else {
			set_transient( self::CACHE, array( 'sig' => $signature, 'data' => $result ), self::TTL );
		}
		return $result;
	}

	/**
	 * Request the statistics from the instance.
	 *
	 * @return array|WP_Error
	 */
	private static function fetch() {
		$gatecha  = GateCHA::$instance;
		$response = wp_remote_get(
			add_query_arg( 'days', self::DAYS, $gatecha->get_url() . '/api/v1/stats' ),
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $gatecha->get_stats_token(),
					'Accept'        => 'application/json',
				),
				'timeout' => 5,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'gatecha_stats',
				/* translators: %s: error detail from the HTTP request */
				sprintf( __( 'GateCHA server unreachable (%s).', 'gatecha-captcha' ), $response->get_error_message() )
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 401 === $status ) {
			return new WP_Error(
				'gatecha_stats',
				__( 'The stats token was rejected. Generate a new one on the key\'s page in GateCHA and paste it in the settings.', 'gatecha-captcha' )
			);
		}
		if ( 404 === $status ) {
			return new WP_Error(
				'gatecha_stats',
				__( 'This GateCHA server does not serve statistics yet. Update it to the latest version.', 'gatecha-captcha' )
			);
		}

		$json = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $status || ! is_array( $json ) || ! isset( $json['totals'], $json['daily'] ) || ! is_array( $json['daily'] ) ) {
			return new WP_Error(
				'gatecha_stats',
				/* translators: %d: HTTP status code */
				sprintf( __( 'Unexpected answer from the GateCHA server (HTTP %d).', 'gatecha-captcha' ), $status )
			);
		}
		return $json;
	}

	/**
	 * Dashboard widget body.
	 */
	public static function render_widget() {
		self::render( 3 );
		printf(
			'<p class="gatecha-stats__more"><a href="%s">%s</a></p>',
			esc_url( admin_url( 'options-general.php?page=gatecha_admin' ) ),
			esc_html__( 'Details and settings', 'gatecha-captcha' )
		);
	}

	/**
	 * Print the statistics block.
	 *
	 * @param int $countries How many countries to list.
	 */
	public static function render( $countries ) {
		$stats = self::get();
		if ( is_wp_error( $stats ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html( $stats->get_error_message() ) . '</p></div>';
			return;
		}

		$totals = $stats['totals'];
		$daily  = $stats['daily'];
		$today  = end( $daily );
		$ok     = (int) $totals['verifications_ok'];
		$fail   = (int) $totals['verifications_fail'];
		$checks = $ok + $fail;

		echo '<div class="gatecha-stats">';

		if ( isset( $stats['enabled'] ) && ! $stats['enabled'] ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'This API key is disabled on the GateCHA server: protected forms cannot be verified until it is enabled again.', 'gatecha-captcha' ) . '</p></div>';
		}

		echo '<h3 class="gatecha-stats__heading">' . esc_html__( 'Today', 'gatecha-captcha' ) . '</h3>';
		echo '<ul class="gatecha-stats__figures">';
		self::figure( (int) $today['verifications_ok'], 'ok', __( 'verified', 'gatecha-captcha' ) );
		self::figure( (int) $today['verifications_fail'], 'fail', __( 'failed', 'gatecha-captcha' ) );
		self::figure( (int) $today['challenges_issued'], '', __( 'challenges', 'gatecha-captcha' ) );
		echo '</ul>';

		echo '<h3 class="gatecha-stats__heading">';
		/* translators: %d: number of days */
		echo esc_html( sprintf( __( 'Last %d days', 'gatecha-captcha' ), (int) $stats['days'] ) );
		echo '</h3>';

		if ( 0 === $checks ) {
			echo '<p class="gatecha-stats__empty">' . esc_html__( 'No form submission checked yet. Submit a protected form once to see it counted here.', 'gatecha-captcha' ) . '</p>';
		} else {
			echo '<p class="gatecha-stats__summary">';
			echo esc_html(
				sprintf(
					/* translators: 1: verified submissions, 2: failed submissions, 3: failure percentage */
					__( '%1$s verified, %2$s failed (%3$s%%).', 'gatecha-captcha' ),
					number_format_i18n( $ok ),
					number_format_i18n( $fail ),
					number_format_i18n( 100 * $fail / $checks, 1 )
				)
			);
			echo '</p>';
			self::chart( $daily );
		}

		if ( $countries > 0 && ! empty( $stats['countries'] ) ) {
			echo '<h3 class="gatecha-stats__heading">' . esc_html__( 'Countries', 'gatecha-captcha' ) . '</h3>';
			echo '<table class="gatecha-stats__countries"><tbody>';
			foreach ( array_slice( $stats['countries'], 0, $countries ) as $row ) {
				printf(
					'<tr><th scope="row">%s</th><td>%s</td><td class="gatecha-stats__fail">%s</td></tr>',
					esc_html( self::country_name( (string) $row['country'] ) ),
					esc_html(
						/* translators: %s: number of verified submissions */
						sprintf( __( '%s verified', 'gatecha-captcha' ), number_format_i18n( (int) $row['verifications_ok'] ) )
					),
					esc_html(
						/* translators: %s: number of failed verifications */
						sprintf( __( '%s failed', 'gatecha-captcha' ), number_format_i18n( (int) $row['verifications_fail'] ) )
					)
				);
			}
			echo '</tbody></table>';
		}

		echo '<p class="gatecha-stats__note">' . esc_html__( 'Counted by your GateCHA server. Refreshed every 10 minutes.', 'gatecha-captcha' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Print one headline figure.
	 *
	 * @param int    $value The number.
	 * @param string $tone  'ok', 'fail' or ''.
	 * @param string $label What the number counts.
	 */
	private static function figure( $value, $tone, $label ) {
		printf(
			'<li class="gatecha-stats__figure%s"><span class="gatecha-stats__value">%s</span> <span class="gatecha-stats__label">%s</span></li>',
			$tone ? ' gatecha-stats__figure--' . esc_attr( $tone ) : '',
			esc_html( number_format_i18n( $value ) ),
			esc_html( $label )
		);
	}

	/**
	 * Print the daily bars: verified and failed stacked, one bar per day.
	 *
	 * @param array $daily Days, oldest first.
	 */
	private static function chart( array $daily ) {
		$max = 1;
		foreach ( $daily as $day ) {
			$max = max( $max, (int) $day['verifications_ok'] + (int) $day['verifications_fail'] );
		}

		$count  = count( $daily );
		$width  = 300;
		$height = 60;
		$slot   = $width / max( 1, $count );
		$bar    = max( 1, $slot - 2 );

		$first = reset( $daily );
		$last  = end( $daily );
		$label = sprintf(
			/* translators: 1: first date, 2: last date */
			__( 'Verified and failed submissions per day, %1$s to %2$s.', 'gatecha-captcha' ),
			$first['date'],
			$last['date']
		);

		printf(
			'<svg class="gatecha-stats__chart" viewBox="0 0 %1$d %2$d" preserveAspectRatio="none" role="img" aria-label="%3$s">',
			(int) $width,
			(int) $height,
			esc_attr( $label )
		);
		foreach ( array_values( $daily ) as $i => $day ) {
			$ok   = (int) $day['verifications_ok'];
			$fail = (int) $day['verifications_fail'];
			$x    = $i * $slot;
			$h_ok = $height * $ok / $max;
			$h_ko = $height * $fail / $max;
			$tip  = sprintf(
				/* translators: 1: date, 2: verified count, 3: failed count */
				__( '%1$s: %2$d verified, %3$d failed', 'gatecha-captcha' ),
				$day['date'],
				$ok,
				$fail
			);
			echo '<g><title>' . esc_html( $tip ) . '</title>';
			if ( $h_ok > 0 ) {
				printf( '<rect class="gatecha-stats__bar-ok" x="%.2f" y="%.2f" width="%.2f" height="%.2f"/>', $x, $height - $h_ok, $bar, $h_ok );
			}
			if ( $h_ko > 0 ) {
				printf( '<rect class="gatecha-stats__bar-fail" x="%.2f" y="%.2f" width="%.2f" height="%.2f"/>', $x, $height - $h_ok - $h_ko, $bar, $h_ko );
			}
			echo '</g>';
		}
		echo '</svg>';
	}

	/**
	 * Country name for an ISO 3166-1 alpha-2 code, in the admin's language
	 * when PHP's intl extension is present, the code itself otherwise.
	 *
	 * @param string $code Country code; empty for sources that could not be located.
	 * @return string
	 */
	private static function country_name( $code ) {
		if ( '' === $code ) {
			return __( 'Unknown', 'gatecha-captcha' );
		}
		if ( class_exists( 'Locale' ) ) {
			$name = Locale::getDisplayRegion( 'und-' . $code, get_user_locale() );
			if ( $name && $name !== $code ) {
				return $name;
			}
		}
		return $code;
	}
}
