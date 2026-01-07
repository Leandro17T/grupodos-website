<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Sale Flash
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
		'title'   => __( 'Enable', 'woocommerce-jetpack' ),
		'name'    => 'wcj_sale_flash_enabled',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'HTML', 'woocommerce-jetpack' ),
		'name'    => 'wcj_sale_flash',
		'default' => '<span class="onsale">' . __( 'Sale!', 'woocommerce' ) . '</span>',
		'type'    => 'textarea',
		'css'     => 'width:100%;min-height:100px;',
	),
);
