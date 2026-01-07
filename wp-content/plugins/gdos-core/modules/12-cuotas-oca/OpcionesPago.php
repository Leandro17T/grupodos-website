<?php
// /modules/opciones-pago/OpcionesPago.php

namespace GDOS\Modules\OpcionesPago;
use GDOS\Core\ModuleInterface;
use GDOS\Core\Assets; // <--- 1. Importamos el Helper

if ( ! defined( 'ABSPATH' ) ) exit;

class OpcionesPago implements ModuleInterface {

    public function boot(): void {
        add_shortcode('oca_payment_options', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function render_shortcode(): string {
        if (!function_exists('is_product') || !is_product()) return '';
        
        global $product;
        if (!is_a($product, 'WC_Product')) return '';

        $price = (float) $product->get_price();
        if (!$price) return '';

        // --- 2. Lógica de imagen optimizada ---
        // Usamos Assets::get también para imágenes para asegurar rutas correctas
        $img = Assets::get('assets/images/oca.svg', __FILE__);

        $cuotas = 12;
        $description = '12 cuotas sin interés con OCA';
        $monthly_raw = $cuotas > 0 ? $price / $cuotas : 0;
        $monthly = wc_price($monthly_raw);

        ob_start();
        ?>
        <div class="gdos-payment-option gdos-payment-option--oca">
            <img src="<?php echo esc_url($img['url']); ?>" alt="OCA" class="gdos-payment-option__icon">
            <p class="gdos-payment-option__description">
                <?php echo esc_html($description); ?> de 
                <span class="gdos-payment-option__price"><?php echo wp_kses_post($monthly); ?></span> cada una.
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_styles(): void {
        // Cargar el CSS solo en las páginas de producto
        if (function_exists('is_product') && is_product()) {
            
            // --- 3. Carga Inteligente de CSS ---
            // Ruta relativa directa porque este archivo está en la raíz del módulo
            $css = Assets::get('assets/css/frontend.css', __FILE__);

            wp_enqueue_style(
                'gdos-opciones-pago',
                $css['url'],
                [],
                $css['ver'] // <--- Versión automática
            );
        }
    }
}