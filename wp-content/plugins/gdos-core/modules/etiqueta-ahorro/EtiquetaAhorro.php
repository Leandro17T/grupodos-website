<?php
// REFACTORIZADO: 2025-12-06
// /modules/etiqueta-ahorro/EtiquetaAhorro.php

namespace GDOS\Modules\EtiquetaAhorro;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) {
    exit;
}

class EtiquetaAhorro implements ModuleInterface
{

    public function boot(): void
    {
        // Registra el shortcode para mostrar el ahorro
        \add_shortcode('show_save_amount', [$this, 'render_save_amount_shortcode']);

        // Habilita los shortcodes en la descripción corta de WooCommerce
        \add_filter('woocommerce_short_description', 'do_shortcode');

        // Carga los estilos necesarios en el frontend
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    /**
     * Renderiza el shortcode que calcula y muestra el ahorro.
     * Optimizado para productos variables.
     * @return string
     */
    public function render_save_amount_shortcode(): string
    {
        global $product;

        if (! $product instanceof \WC_Product) {
            $product = \wc_get_product(\get_the_ID());
        }

        if (! $product) {
            return '';
        }

        // Si el producto no está en oferta, salimos rápido (WPO)
        if (! $product->is_on_sale()) {
            return '';
        }

        $max_saving = 0.0;

        // 1. Manejo Optimizado de Productos Variables
        if ($product->is_type('variable')) {
            // WPO: Usamos get_variation_prices() en lugar de get_available_variations().
            // Esto evita cargar objetos pesados de cada variación. Devuelve un array ligero de precios.
            $prices = $product->get_variation_prices(true);

            if (! empty($prices['regular_price']) && ! empty($prices['sale_price'])) {
                foreach ($prices['regular_price'] as $var_id => $regular_price) {
                    // Verificamos que exista el precio de venta correspondiente a esa variación
                    if (isset($prices['sale_price'][$var_id])) {
                        $sale_price = (float) $prices['sale_price'][$var_id];
                        $reg_price  = (float) $regular_price;

                        if ($reg_price > $sale_price) {
                            $diff = $reg_price - $sale_price;
                            if ($diff > $max_saving) {
                                $max_saving = $diff;
                            }
                        }
                    }
                }
            }
        }
        // 2. Manejo de Productos Simples (y otros tipos)
        else {
            $reg_price  = (float) $product->get_regular_price();
            $sale_price = (float) $product->get_sale_price();

            // Validación extra: a veces get_sale_price retorna vacío si no hay oferta
            if ($sale_price > 0 && $reg_price > $sale_price) {
                $max_saving = $reg_price - $sale_price;
            }
        }

        if ($max_saving > 0) {
            // wc_price se encarga de formatear la moneda y decimales según configuración de la tienda
            $formatted_save = \wc_price($max_saving);

            // Construcción HTML segura
            // CORREGIDO: Agregado el signo '!' al final del string de formato
            $html = \sprintf(
                '<span class="gdos-saving-badge">%s %s!</span>',
                \esc_html__('¡Ahorra hasta', 'gdos-core'), // Quitamos el espacio final porque %s %s ya lo agrega
                $formatted_save
            );

            return \wp_kses_post($html);
        }

        return '';
    }

    /**
     * Carga la hoja de estilos de forma optimizada y segura.
     */
    public function enqueue_styles(): void
    {
        $load_styles = false;

        // Lógica Condicional Estricta
        if (\function_exists('is_woocommerce') && \is_woocommerce()) $load_styles = true;
        if (! $load_styles && \is_front_page()) $load_styles = true;
        if (! $load_styles && \is_page(67252)) $load_styles = true; // Landing específica

        // Detección de Shortcode en páginas genéricas
        global $post;
        if (! $load_styles && \is_a($post, 'WP_Post') && \has_shortcode($post->post_content, 'products')) {
            $load_styles = true;
        }

        if ($load_styles) {
            $css_rel  = 'assets/css/frontend.css';
            $css_path = \plugin_dir_path(__FILE__) . $css_rel;

            if (\file_exists($css_path)) {
                \wp_enqueue_style(
                    'gdos-etiqueta-ahorro',
                    \plugins_url($css_rel, __FILE__),
                    [],
                    \filemtime($css_path)
                );
            }
        }
    }
}
