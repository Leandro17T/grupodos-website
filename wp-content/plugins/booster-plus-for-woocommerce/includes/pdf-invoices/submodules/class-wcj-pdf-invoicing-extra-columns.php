<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Extra Columns
 *
 * @version 6.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_PDF_Invoicing_Extra_Columns' ) ) :
		/**
		 * WCJ_PDF_Invoicing_Extra_columns.
		 *
		 * @version 6.0.0
		 */
	class WCJ_PDF_Invoicing_Extra_Columns extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 6.0.0
		 */
		public function __construct() {

			$this->id         = 'pdf_invoicing_extra_columns';
			$this->parent_id  = 'pdf_invoicing';
			$this->short_desc = __( 'How to add extra columns(info) to pdf', 'woocommerce-jetpack' );
			$this->desc       = '';
			parent::__construct( 'submodule' );
		}
	}

endif;

return new WCJ_PDF_Invoicing_Extra_Columns();
