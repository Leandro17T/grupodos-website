<?php
/**
 * Booster for WooCommerce - Plus - Functions
 *
 * @version 6.0.0
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wcj_plus_get_update_server' ) ) {
	/**
	 * Wcj_plus_get_update_server.
	 *
	 * @version 6.0.0
	 * @since  1.0.0
	 */
	function wcj_plus_get_update_server() {
		return 'booster.io';
	}
}

if ( ! function_exists( 'wcj_plus_get_site_url' ) ) {
	/**
	 * Wcj_plus_get_site_url.
	 *
	 * @version 6.0.0
	 * @since  1.0.0
	 */
	function wcj_plus_get_site_url() {
		return str_replace( array( 'http://', 'https://' ), '', site_url() );
	}
}
