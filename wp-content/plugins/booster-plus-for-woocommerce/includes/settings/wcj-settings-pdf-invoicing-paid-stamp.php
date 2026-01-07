<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Paid Stamp
 *
 * @version 6.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$available_gateways = WC()->payment_gateways->payment_gateways();
foreach ( $available_gateways as $key => $gateway ) {
	$available_gateways_options_array[ $key ] = $gateway->title;
}
$settings = array();

$settings = array_merge(
	$settings,
	array(
		array(
			'title' => 'Invoice',
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'wcj_invoicing_invoice_paid_stamp',
		),
		array(
			'title'   => __( 'Paid Stamp', 'woocommerce-jetpack' ),
			'desc'    => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
			'id'      => 'wcj_invoicing_invoice_paid_stamp_enabled',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'title'    => __( 'Custom Paid Stamp', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_invoice_custom_paid_stamp',
			'default'  => '',
			'type'     => 'text',
			'desc'     => sprintf(
				/* translators: %s: search term */
				__( 'Enter a local URL to an image you want to show in the invoice\'s paid stamp. Upload your image using the <a href="%s">media uploader</a>.', 'woocommerce-jetpack' ),
				admin_url( 'media-new.php' )
			) .
			wcj_get_invoicing_current_image_path_desc( 'wcj_invoicing_invoice_custom_paid_stamp' ),
			'desc_tip' => __( 'Leave blank to use the default', 'woocommerce-jetpack' ),
			'class'    => 'widefat',
		),
		array(
			'title'             => __( 'Payment gateways to include', 'woocommerce' ),
			'id'                => 'wcj_invoicing_invoice_paid_stamp_payment_gateways',
			'type'              => 'multiselect',
			'class'             => 'chosen_select',
			'css'               => 'width: 450px;',
			'default'           => '',
			'options'           => $available_gateways_options_array,
			'custom_attributes' => array( 'data-placeholder' => __( 'Select some gateways. Leave blank to include all.', 'woocommerce-jetpack' ) ),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_invoicing_invoice_paid_stamp',
		),
	)
);

return $settings;
