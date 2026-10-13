<?php
/**
 * The optional Forumulate API token.
 *
 * No public embed needs a token: comments, the widget, the changelog bell and
 * the blog / changelog feeds all read public endpoints. The token is only used
 * server-side, by this site, to check the connection and to look up where a
 * docs space is published. It never reaches a page a visitor loads.
 *
 * Storage, in order of preference:
 *  1. A `FORUMULATE_TOKEN` constant in wp-config.php. Nothing is written to the
 *     database at all, and the settings page says the constant is in charge.
 *  2. The `forumulate_token` option, encrypted with a key derived from this
 *     site's secret salts (AUTH_KEY / SECURE_AUTH_KEY), and not autoloaded. A
 *     database dump alone does not reveal it.
 *
 * The settings form never echoes the stored token back; it can only be
 * replaced or removed.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Token storage.
 */
class Forumulate_Token {

	const OPTION = 'forumulate_token';
	const CIPHER = 'aes-256-cbc';

	/**
	 * Is the token supplied by wp-config.php?
	 *
	 * @return bool
	 */
	public static function from_constant() {
		return defined( 'FORUMULATE_TOKEN' ) && is_string( FORUMULATE_TOKEN ) && '' !== FORUMULATE_TOKEN;
	}

	/**
	 * Is a token available?
	 *
	 * @return bool
	 */
	public static function has() {
		return '' !== self::get();
	}

	/**
	 * The plaintext token, or '' when there is none (or it can no longer be
	 * decrypted because the site's salts changed).
	 *
	 * @return string
	 */
	public static function get() {
		if ( self::from_constant() ) {
			return (string) FORUMULATE_TOKEN;
		}
		$stored = get_option( self::OPTION, '' );
		if ( ! is_string( $stored ) || '' === $stored ) {
			return '';
		}
		return self::decrypt( $stored );
	}

	/**
	 * Is there a stored value that can no longer be read?
	 *
	 * @return bool
	 */
	public static function is_unreadable() {
		$stored = get_option( self::OPTION, '' );
		return ! self::from_constant() && is_string( $stored ) && '' !== $stored && '' === self::decrypt( $stored );
	}

	/**
	 * Store a new token (encrypted, not autoloaded).
	 *
	 * @param string $token Plaintext token.
	 * @return bool
	 */
	public static function set( $token ) {
		$token = trim( (string) $token );
		if ( '' === $token ) {
			return false;
		}
		$sealed = self::encrypt( $token );
		if ( '' === $sealed ) {
			return false;
		}
		delete_option( self::OPTION );
		return add_option( self::OPTION, $sealed, '', false );
	}

	/**
	 * Remove the stored token.
	 */
	public static function clear() {
		delete_option( self::OPTION );
	}

	/**
	 * The encryption key: derived from the site's salts, never stored.
	 *
	 * @return string 32 raw bytes.
	 */
	private static function key() {
		return hash( 'sha256', wp_salt( 'auth' ) . '|forumulate-token|' . wp_salt( 'secure_auth' ), true );
	}

	/**
	 * Encrypt and authenticate. Format: `v1:` . base64( iv . hmac . ciphertext ).
	 *
	 * @param string $plain Plaintext.
	 * @return string '' when OpenSSL is unavailable.
	 */
	private static function encrypt( $plain ) {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}
		$iv     = random_bytes( 16 );
		$cipher = openssl_encrypt( $plain, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv );
		if ( false === $cipher ) {
			return '';
		}
		$mac = hash_hmac( 'sha256', $iv . $cipher, self::key(), true );
		// base64 here is transport encoding for binary ciphertext, not obfuscation.
		return 'v1:' . base64_encode( $iv . $mac . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Verify and decrypt.
	 *
	 * @param string $sealed Stored value.
	 * @return string '' when it does not verify.
	 */
	private static function decrypt( $sealed ) {
		if ( 0 !== strpos( $sealed, 'v1:' ) || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$raw = base64_decode( substr( $sealed, 3 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $raw || strlen( $raw ) < 49 ) {
			return '';
		}
		$iv     = substr( $raw, 0, 16 );
		$mac    = substr( $raw, 16, 32 );
		$cipher = substr( $raw, 48 );
		if ( ! hash_equals( hash_hmac( 'sha256', $iv . $cipher, self::key(), true ), $mac ) ) {
			return '';
		}
		$plain = openssl_decrypt( $cipher, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv );
		return false === $plain ? '' : $plain;
	}
}
