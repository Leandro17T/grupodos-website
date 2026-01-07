<?php
/**
 * Booster for WooCommerce - Module - Extra Fees
 *
 * @version 6.0.1
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Product_Extra_Fees' ) ) :

	/**
	 * WCJ_Product_Extra_Fees.
	 *
	 * @version 6.0.1
	 */
	class WCJ_Product_Extra_Fees extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 6.0.1
		 */
		public function __construct() {
			$this->id         = 'product_extra_fees';
			$this->short_desc = __( 'Product Extra Fees', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add product extra fees title, price, and set conditional product fees rules as per your requirement.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-extra-fees';
			parent::__construct();

			if ( $this->is_enabled() ) {
				if ( wcj_is_frontend() ) {
					add_action( 'woocommerce_cart_calculate_fees', array( $this, 'wcj_add_extra_fee_to_wc_cart' ), PHP_INT_MAX, 3 );
				}
			}
		}

		/**
		 * Get_product_extra_fees.
		 *
		 * @version 6.0.1
		 * @param bool $only_enabled indicate the display only enabled.
		 * @param bool $adjust_priority indicate the priority.
		 */
		public function get_product_extra_fees( $only_enabled = true, $adjust_priority = true ) {
			$total_number            = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_product_fees_total_number', 1 ) );
			$titles                  = wcj_get_option( 'wcj_product_fees_data_titles', array() );
			$types                   = wcj_get_option( 'wcj_product_fees_data_types', array() );
			$values                  = wcj_get_option( 'wcj_product_fees_data_values', array() );
			$cart_min                = wcj_get_option( 'wcj_product_fees_cart_min_amount', array() );
			$cart_min_total          = wcj_get_option( 'wcj_product_fees_cart_min_total_amount', array() );
			$cart_max                = wcj_get_option( 'wcj_product_fees_cart_max_amount', array() );
			$cart_max_total          = wcj_get_option( 'wcj_product_fees_cart_max_total_amount', array() );
			$taxable                 = wcj_get_option( 'wcj_product_fees_data_taxable', array() );
			$enabled                 = wcj_get_option( 'wcj_product_fees_data_enabled', array() );
			$products_incl           = wcj_get_option( 'wcj_product_fees_products_to_include', array() );
			$products_excl           = wcj_get_option( 'wcj_product_fees_products_to_exclude', array() );
			$product_categories_incl = wcj_get_option( 'wcj_product_fees_product_cats_to_include', array() );
			$product_categories_excl = wcj_get_option( 'wcj_product_fees_product_cats_to_exclude', array() );
			$product_tags_incl       = wcj_get_option( 'wcj_product_fees_product_tags_to_include', array() );
			$product_tags_excl       = wcj_get_option( 'wcj_product_fees_product_tags_to_exclude', array() );
			$enable_by_user_role     = wcj_get_option( 'wcj_product_fees_enable_by_user_role', array() );
			$priorities              = wcj_get_option( 'wcj_checkout_fees_priority', array() );

			$extra_fees = array();
			for ( $i = 1; $i <= $total_number; $i ++ ) {
				if ( ! isset( $priorities[ $i ] ) || empty( $priorities[ $i ] ) ) {
					$priorities[ $i ] = 0;
				}
				$enabled = isset( $enabled[ $i ] ) ? $enabled[ $i ] : 'yes';
				if ( $only_enabled && 'no' === $enabled ) {
					continue;
				}
				$extra_fees[ $i ] = array(
					'enabled'                 => $enabled,
					'cart_min'                => isset( $cart_min[ $i ] ) ? $cart_min[ $i ] : 1,
					'cart_min_total'          => isset( $cart_min_total[ $i ] ) ? $cart_min_total[ $i ] : 0,
					'cart_max'                => isset( $cart_max[ $i ] ) ? $cart_max[ $i ] : 0,
					'cart_max_total'          => isset( $cart_max_total[ $i ] ) ? $cart_max_total[ $i ] : '',
					'title'                   => isset( $titles[ $i ] ) ? $titles[ $i ] : '',
					'type'                    => isset( $types[ $i ] ) ? $types[ $i ] : 'fixed',
					'value'                   => isset( $values[ $i ] ) ? $values[ $i ] : 0,
					'priority'                => isset( $priorities[ $i ] ) ? ( $priorities[ $i ] ) : 0,
					'taxable'                 => isset( $taxable[ $i ] ) ? $taxable[ $i ] : 'yes',
					'products_incl'           => isset( $products_incl[ $i ] ) ? $products_incl[ $i ] : 0,
					'products_excl'           => isset( $products_excl[ $i ] ) ? $products_excl[ $i ] : 0,
					'product_categories_incl' => isset( $product_categories_incl[ $i ] ) ? $product_categories_incl[ $i ] : 0,
					'product_categories_excl' => isset( $product_categories_excl[ $i ] ) ? $product_categories_excl[ $i ] : 0,
					'product_tags_incl'       => isset( $product_tags_incl[ $i ] ) ? $product_tags_incl[ $i ] : 0,
					'product_tags_excl'       => isset( $product_tags_excl[ $i ] ) ? $product_tags_excl[ $i ] : 0,
					'enable_by_user_role'     => isset( $enable_by_user_role[ $i ] ) ? $enable_by_user_role[ $i ] : 0,
				);
			}
			if ( $adjust_priority ) {
				uksort(
					$extra_fees,
					function ( $a, $b ) use ( $extra_fees, $priorities ) {
						return $priorities[ $a ] <=> $priorities[ $b ];
					}
				);
			}
			return $extra_fees;

		}

		/**
		 * Get valid fees.
		 *
		 * @version 6.0.1
		 *
		 * @param object $cart contains the detailed object of the cart.
		 *
		 * @return array
		 */
		public function get_valid_fees( $cart ) {
			$titles  = wcj_get_option( 'wcj_product_fees_data_titles', array() );
			$types   = wcj_get_option( 'wcj_product_fees_data_types', array() );
			$values  = wcj_get_option( 'wcj_product_fees_data_values', array() );
			$taxable = wcj_get_option( 'wcj_product_fees_data_taxable', array() );

			$fees = $this->get_product_extra_fees();

			$fees_to_add = array();
			$valid_fees  = array();

			// Get Valid fees.
			foreach ( $fees as $fee_id => $fee_title ) {
				if ( ! $this->is_fee_valid( $fee_id, $cart ) ) {
					continue;
				}
				$valid_fees[] = $fee_id;
			}

			foreach ( $valid_fees as $fee_id ) {
				// Adding the fee.
				$title = ( isset( $titles[ $fee_id ] ) ? $titles[ $fee_id ] : __( 'Fee', 'woocommerce-jetpack' ) . ' #' . $fee_id );
				$value = isset( $values[ $fee_id ] ) ? $values[ $fee_id ] : 0;
				if ( isset( $types[ $fee_id ] ) && 'percent' === $types[ $fee_id ] ) {
					$value = $cart->get_cart_contents_total() * $value / 100;
				}
				$fees_to_add[ $fee_id ] = array(
					'name'      => $title,
					'amount'    => $value,
					'taxable'   => ( isset( $taxable[ $fee_id ] ) ? ( 'yes' === $taxable[ $fee_id ] ) : true ),
					'tax_class' => 'standard',
				);
			}

			return $fees_to_add;
		}

		/**
		 * Validate fee before add to cart.
		 *
		 * @version 6.0.1
		 *
		 * @param int     $fee_id indicates the free id.
		 * @param WC_Cart $cart indicates the cart object.
		 *
		 * @return bool
		 */
		public function is_fee_valid( $fee_id, WC_Cart $cart ) {
			$fees    = $this->get_product_extra_fees();
			$enabled = wcj_get_option( 'wcj_product_fees_data_enabled', array() );
			$values  = wcj_get_option( 'wcj_product_fees_data_values', array() );
			$value   = isset( $values[ $fee_id ] ) ? $values[ $fee_id ] : 0;
			// Check if is active and empty value.
			if (
			( isset( $enabled[ $fee_id ] ) && 'no' === $enabled[ $fee_id ] ) ||
			( '0' === $value || '' === $value )
			) {
				return false;
			}

			// Check cart quantity.
			if (
			$cart->get_cart_contents_count() < $fees[ $fee_id ]['cart_min'] ||
			( $fees[ $fee_id ]['cart_max'] > 0 && $cart->get_cart_contents_count() > $fees[ $fee_id ]['cart_max'] )
			) {
				return false;
			}

			// Check cart total.
			if (
			$cart->get_cart_contents_total() < $fees[ $fee_id ]['cart_min_total'] ||
			( ! empty( $fees[ $fee_id ]['cart_max_total'] ) && $fees[ $fee_id ]['cart_max_total'] > 0 && $cart->get_cart_contents_total() > $fees[ $fee_id ]['cart_max_total'] )
			) {
				return false;
			}

			// Enable by user role.
			$user_roles = $fees[ $fee_id ]['enable_by_user_role'];
			if ( ! empty( $user_roles ) && ! in_array( wcj_get_current_user_first_role(), $user_roles, true ) ) {
				return false;
			}

			// Include by product id.
			$products_incl = $fees[ $fee_id ]['products_incl'];
			if ( ! empty( $products_incl ) ) {
				$do_skip_by_products = true;
				$the_items           = $cart->get_cart();
				foreach ( $the_items as $cart_item_key => $values ) {
					if ( in_array( (string) $values['product_id'], $products_incl, true ) ) {
						$do_skip_by_products = false;
						break;
					}
				}
				if ( $do_skip_by_products ) {
					return false;
				}
			}

			// Exclude by product id.
			$products_excl = $fees[ $fee_id ]['products_excl'];
			if ( ! empty( $products_excl ) ) {
				$the_items = $cart->get_cart();
				foreach ( $the_items as $cart_item_key => $values ) {
					if ( in_array( (string) $values['product_id'], $products_excl, true ) ) {
						return false;
					}
				}
			}

			// Include by product category.
			$product_categories_incl = $fees[ $fee_id ]['product_categories_incl'];
			if ( ! empty( $product_categories_incl ) ) {
				$do_skip_by_cats = true;
				$the_items       = $cart->get_cart();
				foreach ( $the_items as $cart_item_key => $values ) {
					$product_categories = get_the_terms( $values['product_id'], 'product_cat' );
					if ( empty( $product_categories ) ) {
						continue;
					}
					foreach ( $product_categories as $product_category ) {
						if ( in_array( (string) $product_category->term_id, $product_categories_incl, true ) ) {
							$do_skip_by_cats = false;
							break;
						}
					}
					if ( ! $do_skip_by_cats ) {
						break;
					}
				}
				if ( $do_skip_by_cats ) {
					return false;
				}
			}

			// Exclude by product category.
			$product_categories_excl = $fees[ $fee_id ]['product_categories_excl'];
			if ( ! empty( $product_categories_excl ) ) {
				$the_items = $cart->get_cart();
				foreach ( $the_items as $cart_item_key => $values ) {
					$product_categories = get_the_terms( $values['product_id'], 'product_cat' );
					if ( empty( $product_categories ) ) {
						continue;
					}
					foreach ( $product_categories as $product_category ) {
						if ( in_array( (string) $product_category->term_id, $product_categories_excl, true ) ) {
							return false;
						}
					}
				}
			}

			// Include by product tag.
			$product_tags_incl = $fees[ $fee_id ]['product_tags_incl'];
			if ( ! empty( $product_tags_incl ) ) {
				$do_skip_by_tags = true;
				$the_items       = $cart->get_cart();
				foreach ( $the_items as $cart_item_key => $values ) {
					$product_tags = get_the_terms( $values['product_id'], 'product_tag' );
					if ( empty( $product_tags ) ) {
						continue;
					}
					foreach ( $product_tags as $product_tag ) {
						if ( in_array( (string) $product_tag->term_id, $product_tags_incl, true ) ) {
							$do_skip_by_tags = false;
							break;
						}
					}
					if ( ! $do_skip_by_tags ) {
						break;
					}
				}
				if ( $do_skip_by_tags ) {
					return false;
				}
			}

			// Exclude by product tag.
			$product_tags_excl = $fees[ $fee_id ]['product_tags_excl'];
			if ( ! empty( $product_tags_excl ) ) {
				$the_items = $cart->get_cart();
				foreach ( $the_items as $cart_item_key => $values ) {
					$product_tags = get_the_terms( $values['product_id'], 'product_tag' );
					if ( empty( $product_tags ) ) {
						continue;
					}
					foreach ( $product_tags as $product_tag ) {
						if ( in_array( (string) $product_tag->term_id, $product_tags_excl, true ) ) {
							return false;
						}
					}
				}
			}

			return true;
		}

		/**
		 * Wcj_add_extra_fee_to_wc_cart.
		 *
		 * @version 6.0.1
		 * @param object $cart contains the detailed object of the cart.
		 */
		public function wcj_add_extra_fee_to_wc_cart( $cart ) {
			if ( ! wcj_is_frontend() ) {
				return;
			}

			$fees_to_add = $this->get_valid_fees( $cart );

			if ( ! empty( $fees_to_add ) ) {
				foreach ( $fees_to_add as $fee_to_add ) {
					$cart->add_fee( $fee_to_add['name'], $fee_to_add['amount'], $fee_to_add['taxable'], $fee_to_add['tax_class'] );
				}
			}
		}

	}

endif;

return new WCJ_Product_Extra_Fees();
