<?php
// REFACTORIZADO: 2025-05-21
// /modules/tipo-envio-producto/TipoEnvioProducto.php

namespace GDOS\Modules\TipoEnvioProducto;

use GDOS\Core\ModuleInterface;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class TipoEnvioProducto implements ModuleInterface
{
    /**
     * Evita encolar los estilos múltiples veces en la misma página.
     */
    private static bool $assets_enqueued = false;

    /**
     * Constantes para evitar "Magic Strings" y facilitar mantenimiento.
     */
    private const TAG_FLASH   = 'Flash y Express';
    private const TAG_EXPRESS = 'Express';

    public function boot(): void
    {
        \add_shortcode('tipo_envio_producto', [$this, 'render_shortcode']);
    }

    /**
     * Renderiza el bloque si el producto tiene las etiquetas correspondientes.
     */
    public function render_shortcode(): string
    {
        // 1. Fail fast: Si no es producto, salir.
        if (! \function_exists('is_product') || ! \is_product()) {
            return '';
        }

        $product_id = \get_the_ID();
        if (! $product_id) {
            return '';
        }

        // 2. Obtener etiquetas (WordPress cachea esto automáticamente, no requiere Transient extra)
        $tags = \wp_get_post_terms($product_id, 'product_tag', ['fields' => 'names']);

        if (\is_wp_error($tags) || empty($tags)) {
            return '';
        }

        // 3. Determinar configuración según prioridad
        // (Flash tiene prioridad sobre Express si ambos están presentes)
        $config = null;

        if (\in_array(self::TAG_FLASH, $tags, true)) {
            $config = $this->get_config('flash');
        } elseif (\in_array(self::TAG_EXPRESS, $tags, true)) {
            $config = $this->get_config('express');
        }

        if (! $config) {
            return '';
        }

        // 4. Encolar assets (Just-in-Time)
        $this->enqueue_assets();

        // 5. Renderizado Seguro
        $icono_html = \sprintf(
            '<img src="%s" alt="" aria-hidden="true" class="gdos-tipo-envio__icon" width="24" height="24">',
            \esc_url($config['icono'])
        );

        // Permitimos solo <strong> para negritas semánticas
        $texto_html = \wp_kses($config['texto'], ['strong' => []]);

        return \sprintf(
            '<div class="gdos-tipo-envio %s">%s<span>%s</span></div>',
            \esc_attr($config['clase']),
            $icono_html,
            $texto_html
        );
    }

    /**
     * Devuelve la configuración de datos según el tipo.
     * Centraliza las rutas y textos.
     */
    private function get_config(string $type): array
    {
        // Obtenemos assets usando el Helper
        if ($type === 'flash') {
            $icon = Assets::get('assets/images/flash.svg', __FILE__);
            return [
                'texto' => '<strong>Envío Flash</strong> en menos de 2 horas dentro de Montevideo',
                'icono' => $icon['url'],
                'clase' => 'envio-flash'
            ];
        }

        if ($type === 'express') {
            $icon = Assets::get('assets/images/express.svg', __FILE__);
            return [
                'texto' => '<strong>Envío Express</strong> en el día dentro de Montevideo y Canelones',
                'icono' => $icon['url'],
                'clase' => 'envio-express'
            ];
        }

        return [];
    }

    /**
     * Encola el CSS solo si el shortcode se ejecuta con éxito.
     */
    private function enqueue_assets(): void
    {
        if (self::$assets_enqueued) {
            return;
        }

        $css = Assets::get('assets/css/frontend.css', __FILE__);

        \wp_enqueue_style(
            'gdos-tipo-envio',
            $css['url'],
            [],
            $css['ver']
        );

        self::$assets_enqueued = true;
    }
}
