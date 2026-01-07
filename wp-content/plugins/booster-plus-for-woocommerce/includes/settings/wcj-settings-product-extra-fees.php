<?php
/**
 * Booster for WooCommerce - Settings - Product Extra Fees
 *
 * @version 6.0.1
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$products     = wcj_get_products();
$product_cats = wcj_get_terms( 'product_cat' );
$product_tags = wcj_get_terms( 'product_tag' );
$user_roles   = wcj_get_user_roles_options();

$settings     = array(
	array(
		'title' => __( 'Fees', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_product_fees_general_options',
	),
	array(
		'title'             => __( 'Total Fees', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_fees_total_number',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_product_fees_general_options',
	),
);
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_product_fees_total_number', 1 ) );
$fees_titles  = wcj_get_option( 'wcj_product_fees_data_titles', array() );
$fees         = array();
for ( $i = 1; $i <= $total_number; $i ++ ) {
	$fees[ $i ] = null !== $fees_titles && isset( $fees_titles[ $i ] ) ? $fees_titles[ $i ] : '';
}

for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title' => __( 'Fee', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'  => 'title',
				'id'    => "wcj_product_fees_data_options[$i]",
			),
			array(
				'title'   => __( 'Enable/Disable', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => "wcj_product_fees_data_enabled[$i]",
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Title', 'woocommerce-jetpack' ),
				'id'      => "wcj_product_fees_data_titles[$i]",
				'default' => __( 'Fee', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'    => 'text',
			),
			array(
				'title'             => __( 'Value', 'woocommerce-jetpack' ),
				'id'                => "wcj_product_fees_data_values[$i]",
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => array( 'step' => 0.000001 ),
			),
			array(
				'title'   => __( 'Type', 'woocommerce-jetpack' ),
				'id'      => "wcj_product_fees_data_types[$i]",
				'default' => 'fixed',
				'type'    => 'select',
				'options' => array(
					'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
					'percent' => __( 'Percent', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'   => __( 'Taxable', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => "wcj_product_fees_data_taxable[$i]",
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'             => __( 'Cart Minimum Quantity', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Minimum amount of items in cart.', 'woocommerce-jetpack' ),
				'id'                => "wcj_product_fees_cart_min_amount[$i]",
				'default'           => 1,
				'type'              => 'number',
				'custom_attributes' => array( 'min' => 1 ),
			),
			array(
				'title'    => __( 'Cart Maximum Quantity', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Maximum amount of items in cart.', 'woocommerce-jetpack' ) . '<br />' . __( 'Zero or empty values will not be considered', 'woocommerce-jetpack' ),
				'id'       => "wcj_product_fees_cart_max_amount[$i]",
				'default'  => '',
				'type'     => 'number',
			),
			array(
				'title'             => __( 'Cart Minimum Total', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Minimum total amount in cart.', 'woocommerce-jetpack' ),
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
				'id'                => "wcj_product_fees_cart_min_total_amount[$i]",
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
			array(
				'title'             => __( 'Cart Maximum Total', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Maximum total amount in cart.', 'woocommerce-jetpack' ),
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
				'id'                => "wcj_product_fees_cart_max_total_amount[$i]",
				'type'              => 'number',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
			array(
				'title'    => __( 'Products - Include', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => "wcj_product_fees_products_to_include[$i]",
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $products,
			),
			array(
				'title'    => __( 'Products - Exclude', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => "wcj_product_fees_products_to_exclude[$i]",
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $products,
			),
			array(
				'title'    => __( 'Product Categories - Include', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => "wcj_product_fees_product_cats_to_include[$i]",
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $product_cats,
			),
			array(
				'title'    => __( 'Product Categories - Exclude', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => "wcj_product_fees_product_cats_to_exclude[$i]",
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $product_cats,
			),
			array(
				'title'    => __( 'Product Tags - Include', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => "wcj_product_fees_product_tags_to_include[$i]",
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $product_tags,
			),
			array(
				'title'    => __( 'Product Tags - Exclude', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => "wcj_product_fees_product_tags_to_exclude[$i]",
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $product_tags,
			),
			array(
				'title'    => __( 'Enable by User Role', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable the option.', 'woocommerce-jetpack' ),
				'id'       => "wcj_product_fees_enable_by_user_role[$i]",
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $user_roles,
			),
			array(
				'title'    => __( 'Priority', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'The higher the number the higher the priority.', 'woocommerce-jetpack' ) . '<br />' . __( 'Will mostly make sense for overlapping.', 'woocommerce-jetpack' ),
				'id'       => "wcj_checkout_fees_priority[$i]",
				'type'     => 'number',
				'default'  => 0,
			),
			array(
				'type' => 'sectionend',
				'id'   => "wcj_product_fees_data_options[$i]",
			),
		)
	);
}
return $settings;
