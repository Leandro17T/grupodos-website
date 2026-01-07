<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Paid Stamp
 *
 * @version 6.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_PDF_Invoicing_Paid_Stamp' ) ) :
		/**
		 * WCJ_PDF_Invoicing_Paid_stamp.
		 *
		 * @version 6.0.0
		 */
	class WCJ_PDF_Invoicing_Paid_Stamp extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 6.0.0
		 */
		public function __construct() {

			$this->id         = 'pdf_invoicing_paid_stamp';
			$this->parent_id  = 'pdf_invoicing';
			$this->short_desc = __( 'Paid Stamp', 'woocommerce-jetpack' );
			$this->desc       = '';
			parent::__construct( 'submodule' );
		}

	}

endif;

return new WCJ_PDF_Invoicing_Paid_Stamp();
