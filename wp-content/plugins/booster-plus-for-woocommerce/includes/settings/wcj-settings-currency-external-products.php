<?php
/**
 * Booster for WooCommerce - Settings - Currency for External Products
 *
 * @version 6.0.0
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'title' => __( 'Currency for External Products Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => '',
		'id'    => 'wcj_currency_external_products_options',
	),
	array(
		'title'    => __( 'Currency', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set currency for all external products.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_external_products_symbol', // mislabeled, should be.'wcj_currency_external_products_code'.
		'default'  => 'EUR',
		'type'     => 'select',
		'options'  => wcj_get_woocommerce_currencies_and_symbols(),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_currency_external_products_options',
	),
);
