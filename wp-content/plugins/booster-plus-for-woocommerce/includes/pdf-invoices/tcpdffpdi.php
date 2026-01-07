<?php
/**
 * Booster for WooCommerce - PDF Invoicing - TcpdfFpdi
 *
 * This is needed to get round namespaces parse error in PHP < 5.3.0.
 *
 * @version 6.0.0
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return new \setasign\Fpdi\TcpdfFpdi();
