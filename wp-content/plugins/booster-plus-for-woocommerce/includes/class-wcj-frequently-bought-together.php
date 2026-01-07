<?php
/**
 * Booster for WooCommerce - Module - Frequently Bought Together
 *
 * @version 6.0.1
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/include
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Frequently_Bought_Together' ) ) :
		/**
		 * WCJ_Frequently_bought_together.
		 *
		 * @version 6.0.0
		 */
	class WCJ_Frequently_Bought_Together extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 6.0.0
		 */
		public function __construct() {

			$this->id         = 'frequently_bought_together';
			$this->short_desc = __( 'Frequently Bought Together', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add Frequently Bought Together section with suggested products that are usually bought together with the current product being seen by the customer.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-frequently-bought-together';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );

				add_action(
					get_option( 'wcj_fbt_display_position', 'woocommerce_after_cart_totals' ),
					array( $this, 'wcj_add_fbt_products' ),
					get_option( 'wcj_fbt_display_position_priority', 10 )
				);

				add_action( 'wp_loaded', array( $this, 'wcj_fbt_add_to_cart' ), 20 );
				if ( wcj_is_frontend() ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'wcj_fbt_enqueue_scripts' ) );
				}
			}
		}

		/**
		 * Wcj_fbt_enqueue_scripts.
		 *
		 * @version 6.0.0
		 */
		public function wcj_fbt_enqueue_scripts() {
			wp_enqueue_style( 'wcj-fbt-style', wcj_plugin_url() . '/includes/css/wcj-fbt-style.css', array(), WCJ()->version );
			wp_enqueue_script( 'wcj-fbt-script', wcj_plugin_url() . '/includes/js/wcj-fbt-script.js', array(), WCJ()->version, true );
		}

		/**
		 * Wcj_add_fbt_products.
		 *
		 * @version 6.0.1
		 */
		public function wcj_add_fbt_products() {
			global $product;
			if ( $product && ! $product->is_type( array( 'grouped', 'external' ) ) ) {
				wp_enqueue_script( 'wc-add-to-cart-variation' );
				$this->wcj_fbt_products();
			}
		}

		/**
		 * Wcj_fbt_products.
		 *
		 * @version 6.0.0
		 */
		public function wcj_fbt_products() {
			global $product;
			$product_parent_id              = $product->get_id();
			$product_id                     = $product->get_id();
			$current_product                = wc_get_product( $product_id );
			$wcj_fbt_single_product_enabled = get_post_meta( $product_id, '_wcj_product_fbt_enabled', true );

			$add_to_cart_url = ! is_null( $product ) ? $product->get_permalink() : '';
			$add_to_cart_url = add_query_arg( 'action', 'wcj_fbt_add_to_cart', $add_to_cart_url );
			$add_to_cart_url = wp_nonce_url( $add_to_cart_url, 'wcj_fbt_add_to_cart' );

			if ( $current_product->is_type( 'variable' ) ) {
				$variations = $current_product->get_children();
				if ( empty( $variations ) ) {
					return '';
				}

				// first product variation.
				$product_id      = array_shift( $variations );
				$current_product = wc_get_product( $product_id );
			}

			$fbt_products[] = $current_product;

			if ( 'yes' === $wcj_fbt_single_product_enabled ) {
				$single_items = $this->wcj_fbt_get_single_items( $product_parent_id );
			}
			if ( isset( $single_items ) && $single_items && ! empty( $single_items ) ) {
				foreach ( $single_items as $item ) {
					$product        = wc_get_product( $item['id'] );
					$fbt_products[] = $product;
				}
			} else {
				$wcj_fbt_single_globle_enabled = get_option( 'wcj_fbt_global_enabled', 'no' );
				if ( 'yes' === $wcj_fbt_single_globle_enabled ) {
					$global_items = $this->wcj_fbt_get_global_items();
				}
				if ( isset( $global_items ) && $global_items && ! empty( $global_items ) ) {
					foreach ( $global_items as $item ) {
						$product        = wc_get_product( $item['id'] );
						$fbt_products[] = $product;
					}
				}
			}

			if ( is_array( $fbt_products ) && count( $fbt_products ) < 2 ) {
				return;
			}

			$fbt_count          = 0;
			$fbt_products_total = 0;

			$fbt_add_to_cart_button_label = get_option( 'wcj_fbt_add_to_cart_button_label', __( 'Add all to Cart', 'woocommerce-jetpack' ) );
			$fbt_price_for_all_label      = get_option( 'wcj_fbt_price_for_all_label', __( 'Price for all', 'woocommerce-jetpack' ) );
			$fbt_sec_title                = get_option( 'wcj_fbt_title', __( 'Frequently Bought Together', 'woocommerce-jetpack' ) );

			?>

		<div class="wcj_fbt_products_container woocommerce">
			<h3> <?php echo wp_kses_post( $fbt_sec_title ); ?> </h3>
			<form class="wcj_fbt_form" method="post" action="<?php echo esc_url( $add_to_cart_url ); ?>">
				<div class="wcj_fbt_products"> 
				<?php
				foreach ( $fbt_products as $product ) {
					$is_variation  = $product->is_type( 'variation' );
					$product_id    = $product->get_id();
					$product_price = wc_get_price_to_display( $product );
					$product_link  = $product->get_permalink();
					$product_image = $product->get_image();

					$product_title = $product->get_title();

					if ( $product->is_type( 'variation' ) ) :
						?>
							<?php $attribute_string = wc_get_formatted_variation( $product->get_variation_attributes(), true ); ?>
					<?php else : ?>
						<?php $attributes = $product->get_attributes(); ?>
						<?php foreach ( $attributes as $attribute ) : ?>
							<?php $attribute_string = $product->get_attribute( $attribute['name'] ); ?>
						<?php endforeach; ?>
						<?php
					endif;

					if ( isset( $attribute_string ) && '' !== $attribute_string ) {
						$product_title .= ' - ' . $attribute_string;
					}

					if ( $fbt_count > 0 ) {
						echo '<div class="wcj_fbt_product_plus">
                            &nbsp;+&nbsp;
				    	</div>';
					}

					echo '
				        <div class="wcj_fbt_product">
				          <div class="wcj_fbt_product_img">
				            <a href="' . esc_url( $product_link ) . '">' . wp_kses_post( $product_image ) . '</a>
				          </div>
				          <div class="wcj_fbt_product_title">
				            <input type="checkbox" name="wcj_fbt_product_id[]" value="' . wp_kses_post( $product_id ) . '" checked>
				            <a href="' . esc_url( $product_link ) . '">' . wp_kses_post( $product_title ) . '</a>
				          </div>
				          <div class="wcj_fbt_product_price">
				            <span>' . wp_kses_post( $product->get_price_html() ) . '</span>
				          </div>
				        </div>
				    ';

					$fbt_products_total += floatval( $product_price );
					$fbt_count++;
				}
				?>
				</div>
				<div class="wcj_fbt_add_to_cart">
					<div class="fbt_products_total">
					<span class="fbt_price_for_all_label"><?php echo esc_html( $fbt_price_for_all_label ); ?></span> : <span class="fbt_price_for_all_label"> <?php echo wp_kses_post( wc_price( $fbt_products_total ) ); ?> </span>
					</div>
					<button type="submit" class="wcj_fbt_submit_button button">
						<?php echo esc_html( $fbt_add_to_cart_button_label ); ?>
					</button>
				</div>
			</form>
		</div>
			<?php
		}

		/**
		 * Wcj_fbt_get_single_items
		 *
		 * @version 6.0.0
		 * @param int $product_id Get product id.
		 */
		public function wcj_fbt_get_single_items( $product_id ) {
			$ids   = get_post_meta( $product_id, '_wcj_product_fbt_products_ids', true );
			$items = array();
			if ( is_array( $ids ) && count( $ids ) > 0 ) {
				foreach ( $ids as $item ) {
					$item_id      = absint( isset( $item ) ? $item : 0 );
					$item_product = wc_get_product( $item_id );

					if ( ! $item_id && ( ! $item_product->is_purchasable() || ! $item_product->is_in_stock() ) ) {
						continue;
					}

					$items[] = array(
						'id' => $item_id,
					);
				}
			}

			if ( is_array( $items ) && count( $items ) > 0 ) {
				return $items;
			}
			return false;
		}

		/**
		 * Wcj_fbt_get_global_items
		 *
		 * @version 6.0.0
		 */
		public function wcj_fbt_get_global_items() {
			$ids   = get_option( 'wcj_fbt_global_ids', 'no' );
			$items = array();
			if ( is_array( $ids ) && count( $ids ) > 0 ) {
				foreach ( $ids as $item ) {
					$item_id      = absint( isset( $item ) ? $item : 0 );
					$item_product = wc_get_product( $item_id );

					if ( ! $item_id && ( ! $item_product->is_purchasable() || ! $item_product->is_in_stock() ) ) {
						continue;
					}

					$items[] = array(
						'id' => $item_id,
					);
				}
			}

			if ( is_array( $items ) && count( $items ) > 0 ) {
				return $items;
			}
			return false;
		}

		/**
		 * Wcj_fbt_add_to_cart
		 *
		 * @version 6.0.1
		 */
		public function wcj_fbt_add_to_cart() {
			$wpnonce = isset( $_REQUEST['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wcj_fbt_add_to_cart' ) : false;
			if ( ! ( isset( $_REQUEST['action'] ) && 'wcj_fbt_add_to_cart' === $_REQUEST['action'] && isset( $_REQUEST['wcj_fbt_product_id'] ) && $wpnonce ) ) {
				return;
			}

			$wcj_fbt_products = isset( $_POST['wcj_fbt_product_id'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['wcj_fbt_product_id'] ) ) : array();
			if ( is_array( $wcj_fbt_products ) && ! empty( $wcj_fbt_products ) ) {

				foreach ( $wcj_fbt_products as $id ) {
					$product = wc_get_product( $id );

					$attr         = array();
					$variation_id = '';

					if ( $product->is_type( 'variation' ) ) {
						$attr         = $product->get_variation_attributes();
						$variation_id = $product->get_id();
						$product_id   = $product->get_id();
					} else {
						$product_id = $product->get_id();
					}

					$cart_item_key = WC()->cart->add_to_cart( $product_id, 1, $variation_id, $attr );
					if ( $cart_item_key ) {
						$add_to_cart_message[ $product_id ] = 1;
					}
				}

				if ( isset( $add_to_cart_message ) && ! empty( $add_to_cart_message ) ) {
					wc_add_to_cart_message( $add_to_cart_message );
				}

				if ( get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes' ) {
					$url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : WC()->cart->get_cart_url();
				} else {
					$url = remove_query_arg( array( 'action', '_wpnonce' ) );
				}
				wp_safe_redirect( esc_url( $url ) );
				exit();
			} else {
				return;
			}
		}
	}
endif;

return new WCJ_Frequently_Bought_Together();
