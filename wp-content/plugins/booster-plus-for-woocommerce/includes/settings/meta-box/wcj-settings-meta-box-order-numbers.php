<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Order Numbers
 *
 * @version 6.0.0
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'title'             => __( 'Number', 'woocommerce-jetpack' ),
		'name'              => 'wcj_order_number',
		'default'           => '',
		'type'              => 'number',
		'custom_attributes' => 'required min="1"',
	),
);
