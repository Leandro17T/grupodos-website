<?php
// REFACTORIZADO: 2025-12-03 (Versión Final v3 - Clean Buffer)

namespace GDOS\Modules\ImprimirEtiquetas;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) {
    \exit;
}

class ImprimirEtiquetas implements ModuleInterface
{

    // Cambiamos a V3 para asegurar caché limpia nueva
    private const CACHE_KEY_PREFIX = 'gdos_label_data_v3_';
    private const CACHE_EXPIRATION = \HOUR_IN_SECONDS;

    public function boot(): void
    {
        if (! \class_exists('WooCommerce')) {
            return;
        }

        \add_action('add_meta_boxes', [$this, 'add_metabox']);
        \add_action('wp_ajax_generate_woo_shipping_label', [$this, 'generate_label_output']);
    }

    public function add_metabox(): void
    {
        \add_meta_box(
            'gdos_shipping_label_printer',
            'Imprimir Etiqueta de Envío',
            [$this, 'render_metabox_content'],
            'shop_order',
            'side',
            'default'
        );
    }

    public function render_metabox_content($post): void
    {
        $base_url = \admin_url('admin-ajax.php?action=generate_woo_shipping_label&order_id=' . $post->ID);
        $url_gen  = \wp_nonce_url($base_url, 'generate_woo_shipping_label_nonce');
        $url_refresh = \add_query_arg('refresh', '1', $url_gen);

        echo '<div class="gdos-label-printer-box">';
        echo '<p>' . \esc_html__('Genera la etiqueta de envío con los datos del pedido.', 'gdos-core') . '</p>';
        echo '<p><a href="' . \esc_url($url_gen) . '" target="_blank" class="button button-primary" style="width: 100%; text-align: center; margin-bottom: 10px;"><strong>' . \esc_html__('Generar Etiqueta', 'gdos-core') . '</strong></a></p>';
        echo '<p style="text-align: center; margin: 0;"><a href="' . \esc_url($url_refresh) . '" target="_blank" style="color: #b32d2e; text-decoration: none; font-size: 11px;">' . \esc_html__('↻ Regenerar (Sin Caché)', 'gdos-core') . '</a></p>';
        echo '</div>';
    }

    public function generate_label_output(): void
    {
        // 1. LIMPIEZA DE BÚFER: Borramos cualquier salida previa (errores, headers de WP, etc.)
        while (\ob_get_level()) {
            \ob_end_clean();
        }

        // 2. SUPRESIÓN DE ERRORES EN PANTALLA: Para que no salgan en el PDF
        @\ini_set('display_errors', '0');
        \error_reporting(0);

        try {
            // Verificamos nonce (silenciosamente si falla para no romper buffer)
            if (! isset($_REQUEST['_wpnonce']) || ! \wp_verify_nonce($_REQUEST['_wpnonce'], 'generate_woo_shipping_label_nonce')) {
                throw new \Exception('Permiso denegado (Nonce).');
            }

            if (! \current_user_can('edit_shop_orders')) {
                throw new \Exception('Acceso denegado.');
            }

            $order_id = isset($_GET['order_id']) ? \intval($_GET['order_id']) : 0;
            if (! $order_id) {
                throw new \Exception('ID de pedido inválido.');
            }

            $cache_key   = self::CACHE_KEY_PREFIX . $order_id;
            $force_fresh = isset($_GET['refresh']) && '1' === $_GET['refresh'];

            $data = \get_transient($cache_key);

            // Validación extra de integridad de caché
            if ($data && (! is_array($data) || ! isset($data['logo_url']) || is_array($data['logo_url']))) {
                $data = false;
            }

            if (false === $data || $force_fresh) {

                $order = \wc_get_order($order_id);
                if (! $order) {
                    throw new \Exception('Pedido no encontrado.');
                }

                $sender_data = [
                    'name'   => 'Leandro Duarte y Diego Duarte (Grupo Dos)',
                    'line_1' => 'Dr. Salvador Ferrer Serra 2340',
                    'line_2' => 'Tres Cruces, Montevideo',
                    'phone'  => '24003035'
                ];

                // Aseguramos que la dirección sea String
                $raw_address = $order->get_formatted_shipping_address();
                $full_address = is_string($raw_address) ? $raw_address : '';

                // Aseguramos que el logo sea String
                $logo_url_string = (string) (\plugin_dir_url(__FILE__) . 'assets/images/logo.jpg');

                $data = [
                    'order_number'    => $order->get_order_number(),
                    'full_address'    => $full_address,
                    'phone'           => $order->get_billing_phone(),
                    'shipping_method' => $order->get_shipping_method(),
                    'sender'          => $sender_data,
                    'logo_url'        => $logo_url_string,
                ];

                \set_transient($cache_key, $data, self::CACHE_EXPIRATION);
            }

            $view_path = __DIR__ . '/views/etiqueta-template.php';
            if (! \file_exists($view_path)) {
                throw new \Exception('Plantilla no encontrada.');
            }

            // Renderizamos la vista
            include $view_path;
        } catch (\Throwable $e) {
            // Si hay error fatal, generamos un HTML mínimo
            echo '<div style="font-family:sans-serif; color:red; padding:20px; border:1px solid red;">ERROR: ' . \esc_html($e->getMessage()) . '</div>';
        }

        // 3. FINALIZACIÓN ESTRICTA: Evita que WP añada nada más al final
        exit;
    }
}
