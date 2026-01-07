<?php
// REFACTORIZADO: 2025-05-21
// /modules/metodos-envio/MetodosEnvio.php

namespace GDOS\Modules\MetodosEnvio;

use GDOS\Core\ModuleInterface;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class MetodosEnvio implements ModuleInterface
{
    private static bool $assets_enqueued = false;

    public function boot(): void
    {
        \add_shortcode('metodos_envio', [$this, 'render_shortcode']);
    }

    public function render_shortcode(): string
    {
        // Fail-fast: Solo mostrar en productos
        if (! \function_exists('is_product') || ! \is_product()) {
            return '';
        }

        // Obtener producto de forma segura (sin depender de globales inestables)
        $product = \wc_get_product(\get_the_ID());
        if (! $product) return '';

        $product_id = $product->get_id();

        // Verificación de etiquetas (WP Cachea esto eficientemente, no requiere Transient)
        $has_flash   = \has_term('Flash y Express', 'product_tag', $product_id);
        $has_express = \has_term('Express', 'product_tag', $product_id);

        // Definición de textos (HTML permitido: li, strong)
        $txt_express  = '<li><strong>Envío Express en el día:</strong> Montevideo y Canelones. Costo $225. Llega en el día comprando antes de las 16 hs (sábados antes de las 12 hs). Entrega a cargo de Soy Delivery.</li>';
        $txt_interior = '<li><strong>Envío a todo el país desde Tres Cruces:</strong> Despachamos por la agencia que elijas. Abonas al recibir.</li>';
        $txt_retiro   = '<li><strong>Retiro en nuestro local:</strong> Lunes a viernes de 9 a 18 hs y sábados hasta las 13 hs. Dr. Salvador Ferrer Serra 2340.</li>';
        $txt_flash    = '<li><strong>Envío Flash en 2 horas:</strong> Dentro de Montevideo. Costo $225. Comprando de lunes a viernes hasta las 16 hs. Entrega a cargo de Pedidos Ya.</li>';

        $output_html = '';

        // Lógica de visualización
        if ($has_flash) {
            // Prioridad Flash: Incluye Flash + Base
            $output_html = $txt_flash . $txt_express . $txt_interior . $txt_retiro;
        } elseif ($has_express) {
            // Prioridad Express: Solo Base
            $output_html = $txt_express . $txt_interior . $txt_retiro;
        }

        if (empty($output_html)) {
            return '';
        }

        // Carga de assets condicionada (Solo si se va a mostrar algo)
        $this->enqueue_assets();

        // Renderizado seguro
        return '<div class="gdos-metodos-envio"><ul>' . \wp_kses_post($output_html) . '</ul></div>';
    }

    private function enqueue_assets(): void
    {
        if (self::$assets_enqueued) return;

        $css = Assets::get('assets/css/frontend.css', __FILE__);

        \wp_enqueue_style(
            'gdos-metodos-envio',
            $css['url'],
            [],
            $css['ver']
        );

        self::$assets_enqueued = true;
    }
}
