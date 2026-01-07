<?php
// REFACTORIZADO: 2025-12-06
// /modules/fecha-entrega-estimada/FechaEntregaEstimada.php

namespace GDOS\Modules\FechaEntregaEstimada;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) {
    exit;
}

class FechaEntregaEstimada implements ModuleInterface
{

    public function boot(): void
    {
        \add_shortcode('fecha_entrega_estimada', [$this, 'render_shortcode']);

        // WPO: Movemos la carga de assets al hook correcto para evitar FOUC (Flash of Unstyled Content)
        // y errores de ejecución de scripts en el body.
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Renderiza el contenedor del shortcode.
     * La lógica visual se maneja vía JS para no bloquear el renderizado PHP.
     */
    public function render_shortcode(): string
    {
        // Agregamos data-id por si el JS necesita contexto del producto actual
        $pid = \get_the_ID();
        return '<div class="gdos-fecha-entrega" data-product-id="' . \esc_attr($pid) . '"></div>';
    }

    /**
     * Carga condicional de estilos y scripts.
     */
    public function enqueue_assets(): void
    {
        global $post;

        // 1. Condicional Estricta: ¿Debemos cargar los assets?
        // Cargamos si es Ficha de Producto O si el post actual contiene el shortcode.
        $should_load = false;

        if (\function_exists('is_product') && \is_product()) {
            $should_load = true;
        } elseif (\is_a($post, 'WP_Post') && \has_shortcode($post->post_content, 'fecha_entrega_estimada')) {
            $should_load = true;
        }

        if (! $should_load) {
            return;
        }

        // 2. Definición de rutas base para evitar errores de tipo Array vs String
        $base_path = \plugin_dir_path(__FILE__); // Ruta servidor
        $base_url  = \plugin_dir_url(__FILE__);  // Ruta URL

        // --- CSS ---
        $css_file = 'assets/css/frontend.css';
        if (\file_exists($base_path . $css_file)) {
            \wp_enqueue_style(
                'gdos-fecha-entrega',
                $base_url . $css_file,
                [],
                \filemtime($base_path . $css_file) // Cache-busting automático
            );
        }

        // --- JS ---
        $js_file = 'assets/js/frontend.js';
        if (\file_exists($base_path . $js_file)) {
            \wp_enqueue_script(
                'gdos-fecha-entrega',
                $base_url . $js_file,
                ['jquery'], // Dependencias
                \filemtime($base_path . $js_file),
                true // Cargar en footer
            );

            // WPO: Localize script para pasar variables de PHP a JS si fuera necesario en el futuro
            // \wp_localize_script('gdos-fecha-entrega', 'GDOS_Delivery', ['ajaxurl' => admin_url('admin-ajax.php')]);
        }
    }
}
