<?php
// REFACTORIZADO: 2025-12-06
// /modules/comprobante-transferencia/includes/Frontend.php

namespace GDOS\Modules\ComprobanteTransferencia\includes;

use GDOS\Modules\ComprobanteTransferencia\ComprobanteTransferencia as Config;

if (! \defined('ABSPATH')) {
    exit;
}

class Frontend
{

    public function __construct()
    {
        // Mostramos en la Thank You Page (y en View Order si quisieras)
        \add_action('woocommerce_thankyou', [$this, 'display_upload_box'], 5);
        \add_action('woocommerce_view_order', [$this, 'display_upload_box'], 5);
    }

    public function display_upload_box($order_id): void
    {
        if (! $order_id) {
            return;
        }

        $order = \wc_get_order($order_id);

        // Validación de orden y método de pago
        if (! $order || $order->get_payment_method() !== Config::TRANSFER_GATEWAY_ID) {
            return;
        }

        // --- CARGA DE ASSETS CONDICIONAL ---
        // Rutas base: __DIR__ es /includes/, subimos un nivel a /comprobante-transferencia/
        $module_path = \dirname(__DIR__);
        $module_url  = \plugins_url('', \dirname(__FILE__)); // URL base del módulo padre

        // CSS
        $css_rel  = '/assets/css/frontend.css';
        $css_file = $module_path . $css_rel;
        if (\file_exists($css_file)) {
            \wp_enqueue_style('gdos-comprobante-css', $module_url . $css_rel, [], \filemtime($css_file));
        }

        // JS
        $js_rel  = '/assets/js/frontend.js';
        $js_file = $module_path . $js_rel;
        if (\file_exists($js_file)) {
            \wp_enqueue_script('gdos-comprobante-js', $module_url . $js_rel, ['jquery'], \filemtime($js_file), true);
        }

        // --- LÓGICA DE HTML ---
        // HPOS Compatible
        $has_slip = (bool) $order->get_meta(Config::SLIP_META_ID);
?>

        <?php if (! $has_slip) : ?>
            <div class="gdos-alert-box">
                <div class="gdos-alert-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="gdos-alert-content">
                    <strong><?php \esc_html_e('Importante:', 'gdos-core'); ?></strong> <?php \esc_html_e('Tenés un máximo de', 'gdos-core'); ?> <strong>7 <?php \esc_html_e('días', 'gdos-core'); ?></strong> <?php \esc_html_e('para enviar el comprobante o el pedido se cancelará automáticamente.', 'gdos-core'); ?>
                </div>
            </div>

            <div class="gdos-transfer-total">
                <p><?php \esc_html_e('El importe a transferir por tu pedido es:', 'gdos-core'); ?></p>
                <div class="gdos-amount-value"><?php echo \wp_kses_post($order->get_formatted_order_total()); ?></div>
            </div>
        <?php endif; ?>

        <div class="gdos-slip-card">
            <div class="gdos-slip-head">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="#1e293b" stroke-width="2" fill="none" />
                    <polyline points="14 2 14 8 20 8" stroke="#1e293b" stroke-width="2" fill="none" />
                    <line x1="16" y1="13" x2="8" y2="13" stroke="#1e293b" stroke-width="2" />
                    <line x1="16" y1="17" x2="8" y2="17" stroke="#1e293b" stroke-width="2" />
                    <polyline points="10 9 9 9 8 9" stroke="#1e293b" stroke-width="2" />
                </svg>
                <h3 class="gdos-slip-title"><?php \esc_html_e('Comprobante de transferencia', 'gdos-core'); ?></h3>
            </div>

            <div class="gdos-slip-body">
                <?php if ($has_slip) :
                    // Instancia Uploader para obtener URLs
                    $uploader = new Uploader();
                    $date = $order->get_meta(Config::SLIP_META_DT);
                ?>

                    <div class="gdos-success-state">
                        <div class="gdos-success-icon">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="1.5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                <polyline points="22 4 12 14.01 9 11.01" />
                            </svg>
                        </div>
                        <div class="gdos-success-text">
                            <strong><?php \esc_html_e('¡Comprobante recibido!', 'gdos-core'); ?></strong><br>
                            <span style="font-size:13px; color:#64748b;">
                                <?php \printf(\esc_html__('Enviado el %s', 'gdos-core'), \esc_html($date)); ?>
                            </span>
                        </div>

                        <div class="gdos-action-buttons">
                            <a class="gdos-btn gdos-btn-outline" href="<?php echo \esc_url($uploader->get_download_url($order)); ?>" target="_blank">
                                <?php \esc_html_e('Ver archivo', 'gdos-core'); ?>
                            </a>

                            <a class="gdos-btn gdos-btn-danger" href="<?php echo \esc_url($uploader->get_delete_url($order)); ?>" onclick="return confirm('<?php \esc_attr_e('¿Estás seguro de que querés borrar este comprobante?', 'gdos-core'); ?>');">
                                <?php \esc_html_e('Borrar / Subir otro', 'gdos-core'); ?>
                            </a>
                        </div>
                    </div>

                <?php else : ?>
                    <form action="<?php echo \esc_url(\admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data" id="gdos-slip-form">

                        <?php \wp_nonce_field(Config::UPLOAD_ACTION, Config::UPLOAD_NONCE); ?>
                        <input type="hidden" name="action" value="<?php echo \esc_attr(Config::UPLOAD_ACTION); ?>">
                        <input type="hidden" name="order_id" value="<?php echo \absint($order_id); ?>">
                        <input type="hidden" name="order_key" value="<?php echo \esc_attr($order->get_order_key()); ?>">

                        <div class="gdos-dropzone" id="gdos-dropzone">
                            <input type="file" id="gdos_slip" name="gdos_slip" accept=".pdf,image/*" required style="display:none;">

                            <div class="gdos-dz-icon">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                            </div>

                            <p class="gdos-dz-main"><?php \esc_html_e('Arrastrá tu comprobante acá', 'gdos-core'); ?></p>
                            <p class="gdos-dz-sub">
                                <?php \printf(\esc_html__('Formatos: PDF, JPG, PNG — Máx: %d MB', 'gdos-core'), Config::MAX_MB); ?>
                            </p>

                            <label for="gdos_slip" class="gdos-btn gdos-btn-outline gdos-btn-sm">
                                <?php \esc_html_e('Seleccionar archivo', 'gdos-core'); ?>
                            </label>

                            <div id="gdos-file-name" class="gdos-file-name"></div>
                        </div>

                        <button type="submit" class="gdos-btn gdos-btn-primary">
                            <?php \esc_html_e('ENVIAR COMPROBANTE', 'gdos-core'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
<?php
    }
}
