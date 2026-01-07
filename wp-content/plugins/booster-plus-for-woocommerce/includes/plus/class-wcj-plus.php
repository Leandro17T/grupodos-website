<?php
/**
 * Booster for WooCommerce - Plus
 *
 * @version 6.0.0
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Plus' ) ) :
		/**
		 * WCJ_Plus.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
	class WCJ_Plus {

		/**
		 * Constructor.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
		public function __construct() {
			require_once 'class-wcj-plus-functions.php';
			require_once 'class-wcj-plus-filters.php';
			require_once 'class-wcj-plus-site-key-section.php';
			require_once 'class-wcj-plus-site-key-manager.php';
		}

	}

endif;

return new WCJ_Plus();
