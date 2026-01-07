<?php
/**
 * One page checkout product listing
 *
 * @version 6.0.0
 * @since  1.0.0
 * @package WooCommerce/Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
} ?>

<div class="wcj_opc_main">
	<div id="wcj_opc_notices"> </div>
	<div class="wcj_opc_products">
	<?php
	foreach ( $products as $product ) {

		$is_variation  = $product->is_type( 'variation' );
		$product_id    = $product->get_id();
		$product_price = wc_get_price_to_display( $product );
		$product_link  = $product->get_permalink();
		$product_image = $product->get_image();
		$product_title = $product->get_title();
		?>
		<div class="wcj_opc_row row">
				<div class="wcj_opc_col wcj_opc_product_img col-md-3">
					<a href="<?php echo esc_url( $product_link ); ?>"> <?php echo wp_kses_post( $product_image ); ?></a>
				</div>
				<div class="wcj_opc_col wcj_opc_product_title col-md-3">
					<a href="<?php echo esc_url( $product_link ); ?>">
					<?php echo wp_kses_post( $product_title ); ?>
					<?php if ( $product->is_type( 'variation' ) ) : ?>
							<?php $attribute_string = sprintf( '&nbsp;(%s)', wc_get_formatted_variation( $product->get_variation_attributes(), true ) ); ?>
							<span class="attributes"><?php echo esc_html( apply_filters( 'woocommerce_attribute', $attribute_string, $product->get_variation_attributes(), $product ) ); ?></span>
						<?php else : ?>
							<?php $attributes = $product->get_attributes(); ?>
							<?php foreach ( $attributes as $attribute ) : ?>
								<?php $attribute_string = sprintf( '&nbsp;(%s)', $product->get_attribute( $attribute['name'] ) ); ?>
							<span class="attributes"><?php echo esc_html( apply_filters( 'woocommerce_attribute', $attribute_string, $attribute, $product ) ); ?></span>
							<?php endforeach; ?>
						<?php endif; ?>

					</a>
				</div>

				<div class="wcj_opc_col wcj_opc_product_price col-md-3">
					<span> <?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				</div>

				<div class="wcj_opc_col wcj_opc_add_to_cart col-md-3">
				<?php
				if ( $product->is_in_stock() ) {
					?>
							<button class="wcj_opc_loader button wcj_opc_add_to_cart_btn" id="product_<?php echo wp_kses_post( $product->get_id() ); ?>" name="product_id" value="<?php echo wp_kses_post( $product->get_id() ); ?>" data-opc_product_id="<?php echo wp_kses_post( $product->get_id() ); ?>">
								<span><?php esc_html_e( 'Add to cart', 'woocommerce-jetpack' ); ?></span>
							</button>
						<?php
				} else {
					$availability      = $product->get_availability();
					$availability_html = empty( $availability['availability'] ) ? '' : '<span class="stock ' . esc_attr( $availability['class'] ) . '">' . esc_html( $availability['availability'] ) . '</span>';
					echo wp_kses_post( apply_filters( 'woocommerce_stock_html', $availability_html, $availability['availability'], $product ) );
				}
				?>
				</div>
			</div>
	<?php } ?>
	</div>

</div>
