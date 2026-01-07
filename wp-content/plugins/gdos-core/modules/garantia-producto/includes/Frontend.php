<?php
// REFACTORIZADO: 2025-12-06 (FIX: Fatal Error en Enqueue Styles)
// /modules/garantia-producto/includes/Frontend.php

namespace GDOS\Modules\GarantiaProducto\includes;

use GDOS\Modules\GarantiaProducto\GarantiaProducto;
// use GDOS\Core\Assets; // Comentado temporalmente por conflicto de tipo (Array vs String)

if (! \defined('ABSPATH')) {
    exit;
}

class Frontend
{

    public function __construct()
    {
        \add_shortcode('mostrar_garantia', [$this, 'render_shortcode']);
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    /**
     * Renderiza el shortcode de garantía.
     * * @return string HTML del mensaje.
     */
    public function render_shortcode(): string
    {
        if (! \function_exists('is_product') || ! \is_product()) {
            return '';
        }

        global $product;
        if (! \is_a($product, 'WC_Product')) {
            $product = \wc_get_product(\get_the_ID());
        }

        if (! $product) {
            return '';
        }

        $pid     = $product->get_id();
        $message = '';

        // 1. Prioridad Alta: Garantía manual específica (Meta)
        $manual_warranty = \get_post_meta($pid, '_garantia_producto', true);
        $manual_messages = GarantiaProducto::get_manual_warranty_messages();

        if (! empty($manual_warranty) && isset($manual_messages[$manual_warranty])) {
            $message = $manual_messages[$manual_warranty];
        }

        // 2. Prioridad Media: Garantía por Marca (Taxonomía)
        if (empty($message)) {

            // WPO: Cacheamos la marca del producto
            $cache_key          = 'gdos_warranty_brand_' . $pid;
            $product_brand_slug = \wp_cache_get($cache_key);

            if (false === $product_brand_slug) {
                $terms = \wp_get_post_terms($pid, 'pa_marca', ['fields' => 'slugs']);
                if (! \is_wp_error($terms) && ! empty($terms)) {
                    $product_brand_slug = $terms[0];
                } else {
                    $product_brand_slug = '';
                }
                \wp_cache_set($cache_key, $product_brand_slug);
            }

            if (! empty($product_brand_slug)) {
                $brand_map = GarantiaProducto::get_brand_warranty_map();
                if (isset($brand_map[$product_brand_slug])) {
                    $warranty_key = $brand_map[$product_brand_slug];
                    if (isset($manual_messages[$warranty_key])) {
                        $message = $manual_messages[$warranty_key];
                    }
                }
            }
        }

        // 3. Fallback: Garantía por defecto
        if (empty($message)) {
            $message = GarantiaProducto::get_default_warranty_message();
        }

        return '<div class="garantia-producto">' . \esc_html($message) . '</div>';
    }

    public function enqueue_styles(): void
    {
        // Carga Condicional Estricta
        if (\function_exists('is_product') && \is_product()) {

            // FIX: Usamos plugins_url nativo porque Assets::get devolvía un Array
            // y wp_enqueue_style requiere estrictamente un String (URL).

            // Apuntamos al archivo module.php para obtener la base URL correcta
            // __DIR__ es /includes/, subimos un nivel a /garantia-producto/
            $module_base = \dirname(__DIR__) . '/module.php';

            $src = \plugins_url('assets/css/frontend.css', $module_base);

            // Calculamos versión basada en archivo para cache-busting real
            $file_path = \dirname(__DIR__) . '/assets/css/frontend.css';
            $ver       = \file_exists($file_path) ? \filemtime($file_path) : '1.0';

            \wp_enqueue_style('gdos-garantia-producto', $src, [], $ver);
        }
    }
}
