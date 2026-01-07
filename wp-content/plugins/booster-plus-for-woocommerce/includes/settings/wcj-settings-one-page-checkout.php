<?php
/**
 * Booster for WooCommerce - Settings - One page Checkout
 *
 * @version 6.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_opc_options',
	),
	array(
		'title'   => __( 'Products', 'woocommerce-jetpack' ),
		'type'    => 'multiselect',
		'id'      => 'wcj_opc_global_ids',
		'default' => '',
		'class'   => 'chosen_select',
		'options' => wcj_get_products(),
		'desc'    => __( 'If you did not set product_ids on the shortcode [wcj_one_page_checkout], then the selected products will be used.', 'woocommerce-jetpack' ),
	),
	array(
		'title'    => __( 'Position Priority', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Please select the possition priority for One page checkout. Set to zero to use default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_one_page_checkout_hooks_priority',
		'default'  => 50,
		'type'     => 'number',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_opc_global_ids',
	),
);


return $settings;
