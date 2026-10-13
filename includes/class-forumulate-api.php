<?php
/**
 * Server-side HTTP calls to the Forumulate API.
 *
 * Uses the WordPress HTTP API (wp_remote_get) so proxies, timeouts and SSL
 * settings configured for the site apply. Sends an identifying User-Agent:
 * Forumulate sits behind a WAF that refuses some anonymous library agents.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Minimal API client.
 */
class Forumulate_Api {

	/**
	 * GET a JSON document.
	 *
	 * @param string $path      Path below the API base, starting with '/'.
	 * @param array  $query     Query arguments.
	 * @param bool   $with_auth Send the stored token, if there is one.
	 * @return array|WP_Error Decoded JSON (array) or an error.
	 */
	public static function get_json( $path, $query = array(), $with_auth = false ) {
		$url = Forumulate_Options::api_base() . $path;
		if ( ! empty( $query ) ) {
			$url = add_query_arg( array_map( 'rawurlencode', $query ), $url );
		}

		$headers = array(
			'Accept'     => 'application/json, application/feed+json',
			'User-Agent' => 'forumulate-wordpress/' . FORUMULATE_VERSION . '; ' . home_url( '/' ),
		);
		if ( $with_auth && Forumulate_Token::has() ) {
			$headers['Authorization'] = 'Bearer ' . Forumulate_Token::get();
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 2,
				'headers'     => $headers,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'forumulate_http',
				/* translators: %d: HTTP status code. */
				sprintf( __( 'Forumulate answered HTTP %d.', 'forumulate' ), $code ),
				array( 'status' => $code )
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'forumulate_json', __( 'Forumulate answered something that was not JSON.', 'forumulate' ) );
		}
		return $data;
	}

	/**
	 * The public path prefix for a space: /public/orgs/{org}/spaces/{space}.
	 *
	 * @param string $space Space id or slug.
	 * @return string '' when the org or space is not configured.
	 */
	public static function public_space_path( $space ) {
		$org = (string) Forumulate_Options::get( 'org' );
		if ( ! Forumulate_Options::is_ref( $org ) || ! Forumulate_Options::is_ref( $space ) ) {
			return '';
		}
		return '/public/orgs/' . rawurlencode( $org ) . '/spaces/' . rawurlencode( $space );
	}
}
