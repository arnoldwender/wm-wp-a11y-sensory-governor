<?php
declare(strict_types=1);

namespace WMCyber\A11y\Governor\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\A11y\Governor\Calculator\ContrastCalculator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class RestController {
	private const REST_NAMESPACE = 'wm-sensory/v1';

	public function register_routes(): void {
		register_rest_route( self::REST_NAMESPACE, '/calculate-contrast', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'handle_calculate_contrast' ],
			'permission_callback' => [ $this, 'check_public_contrast_access' ],
			'args'                => [
				'foreground' => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'background' => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	/**
	 * Guard public contrast calculations with transient-backed HMAC-SHA256 rate limiting.
	 *
	 * Administrators (`manage_options`) bypass the rate limit. Unauthenticated callers are
	 * keyed by a salted HMAC-SHA256 digest of `REMOTE_ADDR` so raw IP addresses are never stored.
	 *
	 * @return bool|WP_Error
	 */
	public function check_public_contrast_access( WP_REST_Request $request ) {
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		$limit = function_exists( 'apply_filters' )
			? (int) apply_filters( 'wm_sensory_rest_rate_limit', 60, $request )
			: 60;

		if ( $limit <= 0 ) {
			return true;
		}

		$raw_ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_scalar( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) )
			: '0.0.0.0';

		$salt = function_exists( 'wp_salt' )
			? (string) wp_salt( 'auth' )
			: ( defined( 'AUTH_SALT' ) ? (string) AUTH_SALT : 'wm-sensory-governor-rl-salt' );

		$key  = 'wm_sensory_rl_' . substr( hash_hmac( 'sha256', $raw_ip, $salt ), 0, 28 );
		$hits = function_exists( 'get_transient' ) ? (int) get_transient( $key ) : 0;

		if ( $hits >= $limit ) {
			return new WP_Error(
				'wm_sensory_rate_limited',
				__( 'Too many contrast calculation requests. Please wait a minute and try again.', 'wm-a11y-sensory-governor' ),
				[ 'status' => 429 ]
			);
		}

		if ( function_exists( 'set_transient' ) ) {
			$window = defined( 'MINUTE_IN_SECONDS' ) ? (int) MINUTE_IN_SECONDS : 60;
			set_transient( $key, $hits + 1, $window );
		}

		return true;
	}

	public function handle_calculate_contrast( WP_REST_Request $request ): WP_REST_Response {
		$fg_raw = $request->get_param( 'foreground' );
		$bg_raw = $request->get_param( 'background' );
		$fg     = is_scalar( $fg_raw ) ? sanitize_text_field( (string) $fg_raw ) : '';
		$bg     = is_scalar( $bg_raw ) ? sanitize_text_field( (string) $bg_raw ) : '';

		if ( '' === $fg || '' === $bg ) {
			return new WP_REST_Response( [ 'error' => 'foreground and background hex codes required' ], 400 );
		}

		// An invalid colour is a 400, not a verdict: "#zzzzzz" on white used to come back as 21:1 PASS_AA.
		try {
			$result = ( new ContrastCalculator() )->calculate_contrast( $fg, $bg );
		} catch ( \InvalidArgumentException $e ) {
			return new WP_REST_Response( [ 'success' => false, 'error' => $e->getMessage() ], 400 );
		}

		return new WP_REST_Response( [
			'success' => true,
			'data'    => $result,
		], 200 );
	}
}
