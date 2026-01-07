<?php
/**
 * GDOS Core - Módulo: Product Carousel
 */

namespace GDOS\Modules\ProductCarousel;

if ( ! defined( 'ABSPATH' ) ) exit;

class ProductCarousel {

    const SLUG = 'gdos-product-carousel';
    protected static $enqueued = false;

    public function __construct() {
        add_action('init', [$this, 'register_shortcode']);
    }

    public function register_shortcode() {
        add_shortcode('gdos_product_carousel', [$this, 'render_shortcode']);
    }

    protected function enqueue_assets() {
        if ( self::$enqueued ) return;
        self::$enqueued = true;

        $base = plugin_dir_url(__FILE__);
        wp_register_style( self::SLUG, $base . 'assets/css/frontend.css', [], '1.0.0' );
        wp_register_script( self::SLUG, $base . 'assets/js/frontend.js', [], '1.0.0', true );

        wp_enqueue_style( self::SLUG );
        wp_enqueue_script( self::SLUG );
    }

    public function render_shortcode( $atts = [] ) {
        if ( ! class_exists('\WooCommerce') ) return '';

        $atts = shortcode_atts([
            'category' => 'ollas-y-cacerolas',
            'limit'    => 12,
            'order'    => 'desc',
            'orderby'  => 'date', // puedes usar 'popularity' si prefieres ventas
            'title'    => '',     // opcional: título arriba del carrusel
            'id'       => '',     // opcional: id personalizado del carrusel
        ], $atts, 'gdos_product_carousel');

        $this->enqueue_assets();

        // Query de productos
        $args = [
            'status'         => 'publish',
            'limit'          => (int) $atts['limit'],
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'return'         => 'objects',
            'paginate'       => false,
            'stock_status'   => 'instock',
            'category'       => array_filter(array_map('trim', explode(',', $atts['category']))),
        ];

        $products = wc_get_products( $args );
        if ( empty($products) ) return '';

        // Datos para template
        $data = [
            'id'       => $atts['id'] ? sanitize_html_class($atts['id']) : ('gdos-carousel-' . wp_generate_password(6, false, false)),
            'title'    => $atts['title'],
            'products' => array_map([$this, 'map_product'], $products),
        ];

        ob_start();
        $this->load_template('carousel.php', $data);
        return ob_get_clean();
    }

    protected function map_product( $product ) {
        /** @var \WC_Product $product */
        $regular = (float) $product->get_regular_price();
        $sale    = (float) $product->get_sale_price();
        $price   = (float) $product->get_price();
        $on_sale = $product->is_on_sale() && $sale > 0 && $regular > $sale;

        $discount_pct = 0;
        if ( $on_sale && $regular > 0 ) {
            $discount_pct = round( ( ($regular - $sale) / $regular ) * 100 );
        }

        // Badge "Más vendido": etiqueta 'mas-vendido' o meta '_gdos_mas_vendido'
        $has_best_seller_tag = has_term( ['mas-vendido', 'Más vendido'], 'product_tag', $product->get_id() );
        $is_best_seller_meta = (bool) get_post_meta($product->get_id(), '_gdos_mas_vendido', true);
        $is_best_seller      = $has_best_seller_tag || $is_best_seller_meta;

        // Shipping tag logic
        $tags = wp_get_post_terms( $product->get_id(), 'product_tag', ['fields' => 'names'] );
        $tags_lower = array_map('mb_strtolower', (array) $tags);
        $shipping_label = '';
        if ( in_array('flash y express', $tags_lower, true) ) {
            $shipping_label = 'Envío en 2 horas';
        } elseif ( in_array('express', $tags_lower, true) ) {
            $shipping_label = 'Envío en el día';
        }

        // Imagen (1:1 preferente)
        $image_id  = $product->get_image_id();
        $image_src = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src('woocommerce_thumbnail');

        return [
            'id'             => $product->get_id(),
            'permalink'      => $product->get_permalink(),
            'title'          => $product->get_name(),
            'image'          => $image_src,
            'regular_html'   => $regular ? wc_price($regular) : '',
            'sale_html'      => $on_sale ? wc_price($sale) : wc_price($price),
            'on_sale'        => $on_sale,
            'discount_pct'   => $discount_pct,
            'best_seller'    => $is_best_seller,
            'shipping_label' => $shipping_label,
        ];
    }

    protected function load_template( $template, $vars = [] ) {
        $file = plugin_dir_path(__FILE__) . 'templates/' . $template;
        if ( file_exists($file) ) {
            extract($vars);
            include $file;
        }
    }
}

// Bootstrap del módulo (si tu core usa autoload PSR-4, incluye esta clase en el map).
add_action('plugins_loaded', function() {
    // Evitar colisiones si el Core ya inicializa módulos automáticamente
    if ( class_exists('\GDOS\Modules\ProductCarousel\ProductCarousel') ) {
        new ProductCarousel();
    }
});
