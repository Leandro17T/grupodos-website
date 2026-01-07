<?php
// REFACTORIZADO: 2025-12-06
// /modules/estado-stock-producto/EstadoStockProducto.php

namespace GDOS\Modules\EstadoStockProducto;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) {
    exit;
}

class EstadoStockProducto implements ModuleInterface
{

    public function boot(): void
    {
        \add_shortcode('stock_status', [$this, 'render_shortcode']);

        // Habilitar shortcodes en la descripción corta
        \add_filter('woocommerce_short_description', 'do_shortcode');

        // Carga de estilos
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    /**
     * Renderiza la etiqueta de estado de stock con icono SVG.
     * @return string
     */
    public function render_shortcode(): string
    {
        global $product;

        if (! $product instanceof \WC_Product) {
            $product = \wc_get_product(\get_the_ID());
        }

        if (! $product) {
            return '';
        }

        // URLs base para las imágenes (SVG)
        $base_img_url = \plugins_url('assets/images/', __FILE__);
        $icon_in      = $base_img_url . 'in-stock.svg';
        $icon_out     = $base_img_url . 'out-of-stock.svg';

        // WPO CRÍTICO:
        // Eliminamos el bucle foreach ($product->get_available_variations()).
        // WooCommerce gestiona el estado del padre automáticamente.
        // is_in_stock() en un variable retorna true si alguna variación tiene stock.
        $is_in_stock = $product->is_in_stock();

        if ($is_in_stock) {
            $text           = \__('Disponible en stock', 'gdos-core');
            $icon_url       = $icon_in;
            $modifier_class = 'gdos-stock-status--in-stock';
        } else {
            $text           = \__('Sin stock disponible', 'gdos-core');
            $icon_url       = $icon_out;
            $modifier_class = 'gdos-stock-status--out-of-stock';
        }

        return \sprintf(
            '<div class="gdos-stock-status %s"><img src="%s" alt="" class="gdos-stock-status__icon" width="20" height="20"> %s</div>',
            \esc_attr($modifier_class),
            \esc_url($icon_url), // URL segura
            \esc_html($text)     // Texto seguro
        );
    }

    /**
     * Carga de estilos condicional.
     */
    public function enqueue_styles(): void
    {
        $load_styles = false;

        // Condiciones de carga
        if (\function_exists('is_product') && \is_product()) $load_styles = true;
        if (\is_shop() || \is_product_category() || \is_product_tag()) $load_styles = true;

        // Soporte para la Landing Page específica mencionada anteriormente
        if (\is_page(67252)) $load_styles = true;

        if ($load_styles) {
            $css_rel  = 'assets/css/frontend.css';
            $css_path = \plugin_dir_path(__FILE__) . $css_rel;

            if (\file_exists($css_path)) {
                \wp_enqueue_style(
                    'gdos-estado-stock',
                    \plugins_url($css_rel, __FILE__),
                    [],
                    \filemtime($css_path)
                );
            }
        }
    }
}
