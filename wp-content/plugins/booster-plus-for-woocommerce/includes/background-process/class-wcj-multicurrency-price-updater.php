<?php
/**
 * Booster for WooCommerce - Background Process - Multicurrency Price Updater
 *
 * Updates min and max prices
 *
 * @version 6.0.0
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Multicurrency_Price_Updater' ) ) :
		/**
		 * WCJ_Multicurrency_Price_Updater
		 *
		 * @version 6.0.0
		 */
	class WCJ_Multicurrency_Price_Updater extends WP_Background_Process {

		/**
		 * Wcj_bkg_process_price_updater
		 *
		 * @var string
		 */
		protected $action = 'wcj_bkg_process_price_updater';
		/**
		 * Task
		 *
		 * @version 6.0.0
		 * @param Array $item Get Items.
		 */
		protected function task( $item ) {
			$module = 'multicurrency';
			if ( wcj_is_module_enabled( $module ) ) {
				WCJ()->modules[ $module ]->save_min_max_prices_per_product( $item['id'], $item['currency'] );
			}
			return false;
		}

	}
endif;
