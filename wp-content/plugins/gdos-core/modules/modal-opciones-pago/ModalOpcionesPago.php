<?php
// /modules/modal-opciones-pago/ModalOpcionesPago.php

namespace GDOS\Modules\ModalOpcionesPago;

use GDOS\Core\ModuleInterface;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class ModalOpcionesPago implements ModuleInterface
{
    private static bool $assets_enqueued = false;

    public function boot(): void
    {
        // Cargar Backend solo si es administrador
        if (\is_admin()) {
            require_once __DIR__ . '/includes/Admin.php';
            new includes\Admin();
        }

        \add_shortcode('modal_opciones_pago', [$this, 'render_shortcode']);

        if (! \has_filter('woocommerce_short_description', 'do_shortcode')) {
            \add_filter('woocommerce_short_description', 'do_shortcode');
        }
    }

    public function render_shortcode(): string
    {
        if (! \function_exists('is_product') || ! \is_product()) return '';

        $this->enqueue_assets();
        \ob_start();
?>
        <a href="#" class="gdos-open-modal-button" data-gdos-modal-trigger="payment-options">
            + Ver todas las opciones de pago
        </a>

        <div id="gdos-modal-payment-options" class="gdos-modal-backdrop" data-gdos-modal aria-hidden="true" style="display: none;">
            <div class="gdos-modal-content" role="dialog" aria-modal="true" aria-labelledby="gdos-modal-title">
                <button type="button" class="gdos-modal-close" data-gdos-modal-close aria-label="Cerrar modal">&times;</button>
                <div class="gdos-modal-body">
                    <?php echo $this->get_payment_options_html(); ?>
                </div>
                <div class="gdos-modal-footer">
                    <button type="button" class="gdos-modal-close-btn" data-gdos-modal-close>Cerrar</button>
                </div>
            </div>
        </div>
    <?php
        return \ob_get_clean();
    }

    private function get_payment_options_html(): string
    {
        global $product;
        if (! \is_a($product, 'WC_Product')) return '';

        $price = (float) $product->get_price();

        // Obtener datos guardados del Admin
        $payments = \get_option('gdos_modal_pagos_options');

        // Fallback por si aún no configuraron nada
        if (empty($payments)) {
            $payments = [
                ['cuotas' => 12, 'desc' => '12 cuotas sin interés con Visa', 'img' => 'visa.svg'],
                ['cuotas' => 12, 'desc' => '12 cuotas sin interés con MasterCard', 'img' => 'master.svg'],
            ];
        }

        \ob_start();
    ?>
        <div class="gdos-cuotas-list">
            <h3 id="gdos-modal-title" class="gdos-cuotas-list__title">Tarjetas de crédito en hasta 12 cuotas*</h3>

            <?php foreach ($payments as $p) :
                $cuotas = \max(1, (int) ($p['cuotas'] ?? 1));
                $mensual_html = \wc_price($price > 0 ? $price / $cuotas : 0);

                // Lógica Híbrida: Imagen de BD (ID) o Local (String)
                $img_url = '';
                if (!empty($p['img_id'])) {
                    $img_url = \wp_get_attachment_url($p['img_id']);
                } elseif (!empty($p['img'])) {
                    // Soporte legacy para imágenes locales en assets
                    $asset = Assets::get('assets/images/' . $p['img'], __FILE__);
                    $img_url = $asset['url'];
                }

                // Placeholder
                if (!$img_url) $img_url = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
            ?>

                <div class="gdos-cuotas-list__item">
                    <img src="<?php echo \esc_url($img_url); ?>" alt="" class="gdos-cuotas-list__icon" width="40" height="25" style="object-fit:contain;">
                    <p class="gdos-cuotas-list__text">
                        <?php echo \esc_html($p['desc']); ?> de <strong><?php echo \wp_kses_post($mensual_html); ?></strong> cada una.
                    </p>
                </div>
            <?php endforeach; ?>

            <small class="gdos-cuotas-list__disclaimer">*Sujetos a aprobación de la entidad emisora.</small>
        </div>
<?php
        return \ob_get_clean();
    }

    private function enqueue_assets(): void
    {
        if (self::$assets_enqueued) return;
        $css = Assets::get('assets/css/frontend.css', __FILE__);
        $js  = Assets::get('assets/js/frontend.js', __FILE__);
        \wp_enqueue_style('gdos-modal-pago', $css['url'], [], $css['ver']);
        \wp_enqueue_script('gdos-modal-pago', $js['url'], [], $js['ver'], true);
        self::$assets_enqueued = true;
    }
}
