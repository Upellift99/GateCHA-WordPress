<?php
/**
 * Integration: WordPress Comments form.
 *
 * @package GateCHA_CAPTCHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the widget after comment fields (logged-out users).
 */
add_action( 'comment_form_after_fields', function () {
	$plugin = GateCHA::$instance;
	if ( $plugin->is_wp_comments_enabled() ) {
		gatecha_render_widget_echo();
	}
} );

/**
 * Render the widget for logged-in users.
 */
add_action( 'comment_form_logged_in_after', function () {
	$plugin = GateCHA::$instance;
	if ( $plugin->is_wp_comments_enabled() ) {
		gatecha_render_widget_echo();
	}
} );

/**
 * Verify the CAPTCHA before processing a comment.
 *
 * @param array $commentdata Comment data.
 * @return array Comment data (unchanged on success).
 */
add_filter( 'preprocess_comment', function ( $commentdata ) {
	// Skip trackbacks and pingbacks.
	$type = isset( $commentdata['comment_type'] ) ? $commentdata['comment_type'] : '';
	if ( 'trackback' === $type || 'pingback' === $type ) {
		return $commentdata;
	}

	$plugin = GateCHA::$instance;
	if ( $plugin->is_wp_comments_enabled() ) {
		$payload = isset( $_POST['altcha'] ) ? sanitize_text_field( wp_unslash( $_POST['altcha'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public form submission. The ALTCHA token is itself the anti-bot check and is verified server-side by GateCHA; no privileged or data-modifying action is performed on this read.
		if ( false === $plugin->verify( $payload ) ) {
			wp_die(
				'<p>' . esc_html__( 'CAPTCHA verification failed.', 'gatecha-captcha' ) . '</p>',
				esc_html__( 'Comment Submission Failure', 'gatecha-captcha' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}
	}

	return $commentdata;
} );
