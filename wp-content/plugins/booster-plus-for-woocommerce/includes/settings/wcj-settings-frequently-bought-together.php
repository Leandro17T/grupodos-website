<?php
/**
 * Booster for WooCommerce - Settings - Frequently Bought Together
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
		'title' => __( 'Frequently Bought Together', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_fbt_options',
	),
	array(
		'title'   => __( 'Title', 'woocommerce-jetpack' ),
		'id'      => 'wcj_fbt_title',
		'default' => 'Frequently Bought Together',
		'type'    => 'text',
	),
	array(
		'title'             => __( 'Global Products', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Enable this section if you want to add same Frequently Bought Together to all products. and if you want per product then please check "Booster: Frequently Bought Together" meta fields', 'woocommerce-jetpack' ) . ' ' .
			apply_filters( 'booster_message', '', 'desc' ),
		'type'              => 'checkbox',
		'id'                => 'wcj_fbt_global_enabled',
		'default'           => 'no',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'desc'    => __( 'Global Products', 'woocommerce-jetpack' ),
		'type'    => 'multiselect',
		'id'      => 'wcj_fbt_global_ids',
		'default' => '',
		'class'   => 'chosen_select',
		'options' => wcj_get_products( array(), 'publish', 256, true, true ),
	),
	array(
		'title'   => __( 'Position', 'woocommerce-jetpack' ),
		'id'      => 'wcj_fbt_display_position',
		'default' => 'woocommerce_after_single_product_summary',
		'type'    => 'select',
		'options' => array(
			'woocommerce_before_single_product'         => __( 'Before single product', 'woocommerce-jetpack' ),
			'woocommerce_before_single_product_summary' => __( 'Before single product summary', 'woocommerce-jetpack' ),
			'woocommerce_single_product_summary'        => __( 'Inside single product summary', 'woocommerce-jetpack' ),
			'woocommerce_after_single_product_summary'  => __( 'After single product summary', 'woocommerce-jetpack' ),
			'woocommerce_after_single_product'          => __( 'After single product', 'woocommerce-jetpack' ),
			'woocommerce_before_add_to_cart_form'       => __( 'Before add to cart form', 'woocommerce-jetpack' ),
			'woocommerce_before_add_to_cart_button'     => __( 'Before add to cart button', 'woocommerce-jetpack' ),
			'woocommerce_after_add_to_cart_button'      => __( 'After add to cart button', 'woocommerce-jetpack' ),
			'woocommerce_after_add_to_cart_form'        => __( 'After add to cart form', 'woocommerce-jetpack' ),
			'woocommerce_product_meta_start'            => __( 'Product meta start', 'woocommerce-jetpack' ),
			'woocommerce_product_meta_end'              => __( 'Product meta end', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Position Order (i.e. Priority)', 'woocommerce-jetpack' ),
		'id'      => 'wcj_fbt_display_position_priority',
		'default' => 10,
		'type'    => 'number',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_fbt_options',
	),
);


return $settings;
