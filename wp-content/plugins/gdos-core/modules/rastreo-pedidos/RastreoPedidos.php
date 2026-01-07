<?php
// REFACTORIZADO: 2025-05-21
// /modules/rastreo-pedidos/RastreoPedidos.php

namespace GDOS\Modules\RastreoPedidos;

use GDOS\Core\ModuleInterface;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class RastreoPedidos implements ModuleInterface
{
    public function boot(): void
    {
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        \add_shortcode('rastreo_pedido', [$this, 'render_shortcode']);
    }

    public function enqueue_assets(): void
    {
        // Optimización: Solo cargar si es una entrada/página y tiene el shortcode
        if (! \is_singular() || ! \has_shortcode(\get_post()->post_content, 'rastreo_pedido')) {
            return;
        }

        $css = Assets::get('assets/css/frontend.css', __FILE__);

        \wp_enqueue_style(
            'gdos-rastreo-pedidos-style',
            $css['url'],
            [],
            $css['ver']
        );
    }

    public function render_shortcode(): string
    {
        // Dependencia dura de WooCommerce
        if (! \function_exists('wc_get_order')) {
            return '<div class="gdos2-alert gdos2-alert-error">WooCommerce es requerido para usar este formulario.</div>';
        }

        // Evitar caché de página en este endpoint (ya que muestra info dinámica)
        if (\function_exists('nocache_headers')) {
            \nocache_headers();
        }

        $result_html       = '';
        $has_error         = false;
        $order_id_value    = '';
        $order_email_value = '';
        $order             = null;

        // Procesamiento del Formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gdos_rp2_nonce'])) {

            // Verificación de Seguridad
            if (! \wp_verify_nonce(\sanitize_key($_POST['gdos_rp2_nonce']), 'gdos_rp2')) {
                return '<div class="gdos2-alert gdos2-alert-error">Error de seguridad. Intenta recargar la página.</div>';
            }

            $submitted_id_raw = isset($_POST['order_id']) ? \sanitize_text_field(\wp_unslash($_POST['order_id'])) : '';
            $submitted_email  = isset($_POST['order_email']) ? \sanitize_email(\wp_unslash($_POST['order_email'])) : '';

            // Limpiar ID (solo números)
            $order_id = \absint(\preg_replace('/\D+/', '', $submitted_id_raw));
            $order    = $order_id ? \wc_get_order($order_id) : false;

            if ($order) {
                // Comparación estricta de email (lowercase)
                $billing_email = \strtolower((string)$order->get_billing_email());
                $input_email   = \strtolower($submitted_email);

                if ($billing_email === $input_email) {
                    $result_html = $this->get_order_details_html($order);
                } else {
                    $has_error         = true;
                    $order_id_value    = $submitted_id_raw;
                    $order_email_value = $submitted_email;
                    $result_html       = '<div class="gdos2-alert gdos2-alert-error">❌ El correo no coincide con el de este pedido.</div>';
                }
            } else {
                $has_error         = true;
                $order_id_value    = $submitted_id_raw;
                $order_email_value = $submitted_email;
                $result_html       = '<div class="gdos2-alert gdos2-alert-error">❌ No encontramos ningún pedido con ese número.</div>';
            }
        }

        \ob_start();
?>
        <div class="gdos2-wrap">
            <form class="gdos2-form" method="post" action="" autocomplete="off">
                <h2 class="gdos2-form-title">RASTREADOR DE VELOCIDAD</h2>
                <p class="gdos2-form-desc">Ingresa el <strong>número de pedido</strong> y el <strong>correo</strong> para ver el detalle en tiempo real.</p>

                <?php \wp_nonce_field('gdos_rp2', 'gdos_rp2_nonce'); ?>

                <input type="text" name="gdos_hp" value="" style="display:none" tabindex="-1" autocomplete="off">

                <label for="gdos2_order_id">Número de pedido</label>
                <input type="text" id="gdos2_order_id" name="order_id" placeholder="Ej: 12345"
                    <?php if ($has_error) : ?>value="<?php echo \esc_attr($order_id_value); ?>" <?php endif; ?>
                    autocomplete="off" required>

                <label for="gdos2_order_email">Correo electrónico</label>
                <input type="email" id="gdos2_order_email" name="order_email" placeholder="tucorreo@ejemplo.com"
                    <?php if ($has_error) : ?>value="<?php echo \esc_attr($order_email_value); ?>" <?php endif; ?>
                    autocomplete="off" required>

                <button type="submit" class="gdos2-btn">Consultar</button>
            </form>

            <?php
            // Lógica de Debug (Solo Admins)
            if (\current_user_can('manage_woocommerce') && isset($_GET['gdos_debug']) && $order) {
                $this->render_debug_info($order);
            }

            // Resultado (Error o Éxito)
            echo $result_html; // PHPCS: ignore WordPress.Security.EscapeOutput.OutputNotEscaped (Ya escapado internamente)
            ?>
        </div>
    <?php
        return \ob_get_clean();
    }

    private function render_debug_info(\WC_Order $order): void
    {
        echo '<div class="gdos2-card" style="margin-top:12px; border:1px solid #ffba00;">';
        echo '<div class="gdos2-box-title" style="background:#fff8e5; padding:5px;">DEBUG (Solo Admin)</div>';
        echo '<pre style="white-space:pre-wrap;font-size:11px;line-height:1.3;background:#fff;padding:10px;overflow:auto;max-height:300px;">';

        echo "<strong>Order Metas:</strong>\n";
        foreach ($order->get_meta_data() as $m) {
            $d = $m->get_data();
            echo \esc_html($d['key']) . ' => ' . \esc_html(\wp_json_encode($d['value'])) . "\n";
        }

        echo "\n<strong>Shipping Methods:</strong>\n";
        foreach ($order->get_shipping_methods() as $ship_item) {
            echo "- " . \esc_html($ship_item->get_name()) . "\n";
            foreach ($ship_item->get_meta_data() as $m) {
                $d = $m->get_data();
                echo "    " . \esc_html($d['key']) . ' => ' . \esc_html(\wp_json_encode($d['value'])) . "\n";
            }
        }
        echo "</pre></div>";
    }

    private function get_order_details_html(\WC_Order $order): string
    {
        list($status_label, $status_class) = $this->get_status_details($order->get_status());

        $date_created     = $order->get_date_created() ? $order->get_date_created()->date_i18n(\get_option('date_format') . ' H:i') : '';
        $full_name        = \trim($order->get_formatted_shipping_full_name()) ?: \trim($order->get_formatted_billing_full_name());
        $ship_addr        = $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address();
        $shipping_methods = $order->get_shipping_methods();
        $shipping_title   = !empty($shipping_methods) ? \reset($shipping_methods)->get_name() : '';
        $trackings        = $this->get_tracking_info($order);
        $currency         = $order->get_currency();

        \ob_start();
    ?>
        <div class="gdos2">
            <div class="gdos2-hero">
                <h2 class="gdos2-h2">ESTADO DEL PEDIDO</h2>
                <p class="gdos2-desc">Tu pedido se encuentra en el estado: <strong><?php echo \esc_html($status_label); ?></strong></p>
            </div>

            <div class="gdos2-card">
                <div class="gdos2-head">
                    <div>
                        <div class="gdos2-title">Pedido #<?php echo \esc_html($order->get_order_number()); ?></div>
                        <?php if ($date_created): ?>
                            <div class="gdos2-sub">Realizado el <?php echo \esc_html($date_created); ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="gdos2-badge <?php echo \esc_attr($status_class); ?>"><?php echo \esc_html($status_label); ?></span>
                </div>

                <div class="gdos2-grid">
                    <div class="gdos2-box">
                        <div class="gdos2-box-title">Datos de envío</div>
                        <?php if ($full_name): ?>
                            <div class="gdos2-row"><span class="gdos2-label">Nombre</span><span class="gdos2-value"><?php echo \esc_html($full_name); ?></span></div>
                        <?php endif; ?>

                        <?php if ($ship_addr): ?>
                            <div class="gdos2-row"><span class="gdos2-label">Dirección</span><span class="gdos2-value"><?php echo \wp_kses($ship_addr, ['br' => []]); ?></span></div>
                        <?php endif; ?>

                        <?php if ($shipping_title): ?>
                            <div class="gdos2-row"><span class="gdos2-label">Envío</span><span class="gdos2-value"><?php echo \esc_html($shipping_title); ?></span></div>
                        <?php endif; ?>

                        <?php if ($order->get_billing_email()): ?>
                            <div class="gdos2-row">
                                <span class="gdos2-label">Email</span>
                                <span class="gdos2-value" title="<?php echo \esc_attr($order->get_billing_email()); ?>"><?php echo $this->mask_email($order->get_billing_email()); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($order->get_billing_phone()): ?>
                            <div class="gdos2-row"><span class="gdos2-label">Teléfono</span><span class="gdos2-value"><?php echo \esc_html($order->get_billing_phone()); ?></span></div>
                        <?php endif; ?>
                    </div>

                    <div class="gdos2-box">
                        <div class="gdos2-box-title">Resumen</div>
                        <?php if ($order->get_payment_method_title()): ?>
                            <div class="gdos2-row"><span class="gdos2-label">Pago</span><span class="gdos2-value"><?php echo \esc_html($order->get_payment_method_title()); ?></span></div>
                        <?php endif; ?>

                        <div class="gdos2-row"><span class="gdos2-label">Total</span><span class="gdos2-value"><?php echo \wp_kses_post($order->get_formatted_order_total()); ?></span></div>

                        <?php if (!empty($trackings)): ?>
                            <div class="gdos2-divider"></div>
                            <div class="gdos2-box-title">Seguimiento del envío</div>
                            <?php foreach ($trackings as $t): ?>
                                <div class="gdos2-track">
                                    <?php if ($t['provider']): ?>
                                        <div class="gdos2-track-line"><span class="gdos2-label">Agencia</span><span class="gdos2-value"><?php echo \esc_html($t['provider']); ?></span></div>
                                    <?php endif; ?>

                                    <?php if ($t['number']): ?>
                                        <div class="gdos2-track-line"><span class="gdos2-label">Código</span><span class="gdos2-value gdos2-mono"><?php echo \esc_html($t['number']); ?></span></div>
                                    <?php endif; ?>

                                    <?php if (!empty($t['url'])): ?>
                                        <a class="gdos2-btn-link" href="<?php echo \esc_url($t['url']); ?>" target="_blank" rel="noopener">Ver seguimiento</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="gdos2-items">
                    <div class="gdos2-box-title">Productos comprados</div>
                    <?php
                    foreach ($order->get_items() as $item) :
                        $product = $item->get_product();
                        $thumb   = $product ? $product->get_image('woocommerce_thumbnail', ['style' => 'width:56px;height:56px;object-fit:cover;border-radius:6px;']) : '';
                    ?>
                        <div class="gdos2-item">
                            <div class="gdos2-item-media"><?php echo \wp_kses_post($thumb); ?></div>
                            <div class="gdos2-item-main">
                                <div class="gdos2-item-name">
                                    <a href="<?php echo \esc_url($product ? $product->get_permalink() : '#'); ?>"><?php echo \esc_html($item->get_name()); ?></a>
                                </div>
                                <?php if (\wc_display_item_meta($item, ['echo' => false])): ?>
                                    <div class="gdos2-item-meta"><?php echo \wc_display_item_meta($item, ['echo' => false, 'separator' => ' | ']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="gdos2-item-qty">×<?php echo \esc_html($item->get_quantity()); ?></div>
                            <div class="gdos2-item-price">
                                <div><?php echo \wc_price($order->get_item_subtotal($item, false, true), ['currency' => $currency]); ?></div>
                                <div class="gdos2-item-sub"><?php echo \wc_price($item->get_total(), ['currency' => $currency]); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
<?php
        return \ob_get_clean();
    }

    private function mask_email(string $email): string
    {
        $email = \trim($email);
        if (! $email || \strpos($email, '@') === false) return \esc_html($email);

        list($user, $domain) = \explode('@', $email, 2);

        if (\mb_strlen($user) <= 2) return \esc_html($email);

        return \esc_html(\mb_substr($user, 0, 2) . \str_repeat('•', \max(0, \mb_strlen($user) - 2)) . '@' . $domain);
    }

    private function get_status_details(string $status_slug): array
    {
        // Corrección de ortografía en los textos originales
        $map = [
            'pending'            => ['Pendiente de pago', 'is-pending'],
            'processing'         => ['En preparación', 'is-processing'],
            'on-hold'            => ['En espera', 'is-onhold'],
            'completed'          => ['Completado', 'is-completed'],
            'cancelled'          => ['Cancelado', 'is-cancelled'],
            'refunded'           => ['Reembolsado', 'is-refunded'],
            'failed'             => ['Pago fallido', 'is-failed'],
            '898entregadoped'    => ['Tu pedido fue entregado por Pedidos Ya', 'is-entregado-pedidosya'],
            'entregado'          => ['Tu pedido fue entregado en nuestro local', 'is-entregado-local'],
            '216listopararet'    => ['Tu pedido está listo para retirar en nuestro local', 'is-listo-retirar'],
            'en-preparacion'     => ['Tu pedido se encuentra en preparación', 'is-en-preparacion-custom'],
            '34374'              => ['Tu pedido fue enviado. Revisa los datos del envío', 'is-enviado'],
            'transferencia'      => ['Estamos aguardando que nos envíes el comprobante', 'is-transferencia'],
            'pago-recibido'      => ['Recibimos tu pago, en breve trabajaremos en tu pedido', 'is-pago-recibido'],
            '616enviadosoyde'    => ['Tu pedido fue enviado por Soy Delivery', 'is-en-reparto-soyde'],
        ];
        return $map[$status_slug] ?? [\ucfirst($status_slug), 'is-default'];
    }

    private function meta_first(\WC_Order $order, array $keys): string
    {
        foreach ($keys as $k) {
            $v = $order->get_meta($k, true);
            if ($v !== '' && $v !== null) return (string) $v;
        }
        return '';
    }

    private function resolve_placeholders(?string $text, string $agency_guess, string $code_guess): string
    {
        if (empty($text)) return '';
        $out = \str_ireplace('%%agencia%%', $agency_guess, $text);
        $out = \str_ireplace('%%codigo%%', $code_guess, $out);
        return $out;
    }

    private function get_tracking_info(\WC_Order $order): array
    {
        $trackings    = [];
        $agency_guess = '';
        $code_guess   = '';

        // 1. Buscar en items de Advanced Shipment Tracking (AST)
        $ast_items = $order->get_meta('_wc_shipment_tracking_items');
        if (\is_array($ast_items)) {
            foreach ($ast_items as $it) {
                if (!empty($it['tracking_number']) || !empty($it['tracking_provider'])) {
                    $provider = \sanitize_text_field($it['tracking_provider'] ?? '');
                    $number   = \sanitize_text_field($it['tracking_number'] ?? '');

                    $trackings[] = [
                        'provider' => $provider,
                        'number'   => $number,
                        'url'      => \esc_url_raw($it['tracking_link'] ?? '')
                    ];

                    // Guardar para placeholders
                    if ($provider) $agency_guess = $agency_guess ?: $provider;
                    if ($number)   $code_guess   = $code_guess ?: $number;
                }
            }
        }

        // 2. Buscar en Meta Keys personalizadas (Fallback)
        $agency_meta = $this->meta_first($order, ['agencia', '_agencia', 'carrier', 'empresa', 'courier']);
        $code_meta   = $this->meta_first($order, ['codigo', '_codigo', 'tracking_code', 'tracking_number', 'guia', 'awb']);
        $link_meta   = $this->meta_first($order, ['tracking_link', '_tracking_link', 'envio_link']);

        // Resolver variables %%agencia%% si existen en los metas
        $agency_meta = $this->resolve_placeholders($agency_meta, $agency_guess, $code_guess);
        $code_meta   = $this->resolve_placeholders($code_meta, $agency_guess, $code_guess);

        if (!empty($agency_meta) || !empty($code_meta)) {
            $trackings[] = [
                'provider' => \sanitize_text_field($agency_meta),
                'number'   => \sanitize_text_field($code_meta),
                'url'      => \esc_url_raw($link_meta)
            ];

            $agency_guess = $agency_guess ?: $agency_meta;
            $code_guess   = $code_guess ?: $code_meta;
        }

        // 3. Limpieza y Deduplicación
        $out  = [];
        $seen = [];

        foreach ($trackings as $t) {
            $prov = \trim($this->resolve_placeholders($t['provider'], $agency_guess, $code_guess));
            $num  = \trim($this->resolve_placeholders($t['number'], $agency_guess, $code_guess));

            if (empty($prov) && empty($num)) continue;

            $key = \strtolower($prov . '|' . $num);
            if (isset($seen[$key])) continue;

            $seen[$key] = true;
            $out[] = [
                'provider' => $prov ?: 'Seguimiento',
                'number'   => $num,
                'url'      => $t['url']
            ];
        }

        return $out;
    }
}
