<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * this official WooCommerce MyParcel plugin by MyParcel
 * URL: https://www.myparcel.nl
 */
if ( ! class_exists( 'WFACP_Compatibility_With_WC_MyParcel_2_1_4' ) ) {
	#[AllowDynamicProperties]
	class WFACP_Compatibility_With_WC_MyParcel_2_1_4 {
		private static $instance = null;
		private $page_version = null;

		private $billing_new_fields = [
			'billing_street_name',
			'billing_house_number',
			'billing_house_number_suffix',

		];
		private $shipping_new_fields = [
			'shipping_street_name',
			'shipping_house_number',
			'shipping_house_number_suffix',
		];

		private function __construct() {

			if ( WFACP_Common::is_funnel_builder_3() ) {
				add_action( 'wffn_rest_checkout_form_actions', [ $this, 'setup_fields_billing' ] );
			} else {
				$this->setup_fields_billing();
			}
			/* prevent third party fields and wrapper*/

			add_action( 'wfacp_add_billing_shipping_wrapper', '__return_false' );

			add_filter( 'wfacp_third_party_billing_fields', [ $this, 'disabled_third_party_billing_fields' ] );
			add_filter( 'wfacp_third_party_shipping_fields', [ $this, 'disabled_third_party_shipping_fields' ] );
		}

		public function setup_fields_billing() {
			$this->page_version = WFACP_Common::get_checkout_page_version();
			if ( ! version_compare( $this->page_version, '2.1.3', '>' ) ) {
				return;
			}

			new WFACP_Add_Address_Field( 'street_name', array(
				'label'       => __( 'Street name', 'woocommerce-myparcel' ),
				'placeholder' => __( 'Street name', 'woocommerce-myparcel' ),
				'class'       => [ 'form-row-first', 'address-field', 'wfacp_street_name', 'wfacp-draggable' ],
				'cssready'    => [ 'wfacp-col-full' ],
				'clear'       => false,
				'required'    => false,
				'priority'    => 90,
			) );

			new WFACP_Add_Address_Field( 'house_number', array(
				'label'       => __( 'No.', 'woocommerce-myparcel' ),
				'placeholder' => __( 'No.', 'woocommerce-myparcel' ),
				'class'       => [ 'form-row-first', 'address-field', 'wfacp_house_number', 'wfacp-draggable' ],
				'cssready'    => [ 'wfacp-col-full' ],
				'clear'       => false,
				'required'    => false,
				'priority'    => 90,
			) );

			new WFACP_Add_Address_Field( 'house_number_suffix', array(
				'label'       => __( 'Suffix', 'woocommerce-myparcel' ),
				'placeholder' => __( 'Suffix', 'woocommerce-myparcel' ),
				'class'       => [ 'form-row-first', 'address-field', 'wfacp_house_number_suffix', 'wfacp-draggable' ],
				'cssready'    => [ 'wfacp-col-full' ],
				'clear'       => false,
				'required'    => false,
				'priority'    => 90,
			) );

			new WFACP_Add_Address_Field( 'street_name', array(
				'label'       => __( 'Street name', 'woocommerce-myparcel' ),
				'placeholder' => __( 'Street name', 'woocommerce-myparcel' ),
				'class'       => [ 'form-row-first', 'address-field', 'wfacp_street_name', 'wfacp-draggable' ],
				'cssready'    => [ 'wfacp-col-full' ],
				'clear'       => false,
				'required'    => false,
				'priority'    => 60,
			), 'shipping' );

			new WFACP_Add_Address_Field( 'house_number', array(
				'label'       => __( 'No.', 'woocommerce-myparcel' ),
				'placeholder' => __( 'No.', 'woocommerce-myparcel' ),
				'class'       => [ 'form-row-first', 'address-field', 'wfacp_house_number', 'wfacp-draggable' ],
				'cssready'    => [ 'wfacp-col-full' ],
				'clear'       => false,
				'required'    => false,
				'priority'    => 60,
			), 'shipping' );

			new WFACP_Add_Address_Field( 'house_number_suffix', array(
				'label'       => __( 'Suffix', 'woocommerce-myparcel' ),
				'placeholder' => __( 'Suffix', 'woocommerce-myparcel' ),
				'class'       => [ 'form-row-first', 'address-field', 'wfacp_house_number_suffix', 'wfacp-draggable' ],
				'cssready'    => [ 'wfacp-col-full' ],
				'clear'       => false,
				'required'    => false,
				'priority'    => 60,
			), 'shipping' );
		}

		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function disabled_third_party_billing_fields( $fields ) {
			if ( is_array( $this->billing_new_fields ) && count( $this->billing_new_fields ) ) {
				foreach ( $this->billing_new_fields as $i => $key ) {
					if ( isset( $fields[ $key ] ) ) {
						unset( $fields[ $key ] );
					}
				}
			}

			return $fields;
		}

		public function disabled_third_party_shipping_fields( $fields ) {
			if ( is_array( $this->shipping_new_fields ) && count( $this->shipping_new_fields ) ) {
				foreach ( $this->shipping_new_fields as $i => $key ) {
					if ( isset( $fields[ $key ] ) ) {
						unset( $fields[ $key ] );
					}
				}
			}

			return $fields;
		}
	}

	WFACP_Compatibility_With_WC_MyParcel_2_1_4::get_instance();

}

