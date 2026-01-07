<?php
// REFACTORIZADO: 2025-12-06
// /modules/comprobante-transferencia/includes/Admin.php

namespace GDOS\Modules\ComprobanteTransferencia\includes;

use GDOS\Modules\ComprobanteTransferencia\ComprobanteTransferencia as Config;

if (! \defined('ABSPATH')) {
    exit;
}

class Admin
{

    public function __construct()
    {
        // Detectar entorno para hooks correctos (HPOS vs CPT)
        $hpos_hook = 'manage_woocommerce_page_wc-orders_columns';
        $cpos_hook = 'manage_edit-shop_order_columns';
        $hook_to_use = Config::is_hpos_enabled() ? $hpos_hook : $cpos_hook;

        \add_filter($hook_to_use, [$this, 'add_order_list_columns'], 20);

        if (Config::is_hpos_enabled()) {
            \add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'render_order_list_columns_hpos'], 10, 2);
        } else {
            \add_action('manage_shop_order_posts_custom_column', [$this, 'render_order_list_columns_cpos'], 10, 2);
        }

        \add_action('admin_head', [$this, 'add_column_styles']);
        \add_action('woocommerce_admin_order_data_after_order_details', [$this, 'add_slip_metabox']);
    }

    /**
     * Inserta las columnas personalizadas después de la columna de Estado.
     */
    public function add_order_list_columns($columns): array
    {
        $new_columns = [];

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;

            // Insertamos justo después del estado de la orden
            if ($key === 'order_status') {
                $new_columns['gdos_slip_status'] = \__('Comprobante', 'gdos-core');
                $new_columns['gdos_slip_view']   = \__('Acción', 'gdos-core');
            }
        }
        return $new_columns;
    }

    /**
     * Renderiza el contenido de las columnas.
     * Centralizado para HPOS y Legacy.
     */
    private function render_column_content($column, $order): void
    {
        if (! ($order instanceof \WC_Order)) {
            return;
        }

        if (! \in_array($column, ['gdos_slip_status', 'gdos_slip_view'], true)) {
            return;
        }

        // 1. Verificamos si el método de pago es Transferencia
        if ($order->get_payment_method() !== Config::TRANSFER_GATEWAY_ID) {
            echo '<span style="color:#ccc;">—</span>';
            return;
        }

        // 2. HPOS COMPATIBILITY FIX:
        // Usamos $order->get_meta() en lugar de get_post_meta().
        // Esto asegura que funcione tanto con tablas personalizadas como con wp_postmeta.
        $has_slip = (bool) $order->get_meta(Config::SLIP_META_ID);

        // Columna de Estado (Icono)
        if ($column === 'gdos_slip_status') {
            if ($has_slip) {
                echo '<span title="' . \esc_attr__('Comprobante cargado', 'gdos-core') . '">✅</span>';
            } else {
                echo '<span title="' . \esc_attr__('Pendiente de carga', 'gdos-core') . '">❌</span>';
            }
        }

        // Columna de Acción (Botón)
        if ($column === 'gdos_slip_view' && $has_slip) {
            // Instanciamos Uploader solo si es necesario (Lazy load)
            $uploader = new Uploader();
            $url = $uploader->get_download_url($order);

            echo '<a href="' . \esc_url($url) . '" class="button button-small" target="_blank">' . \esc_html__('Ver', 'gdos-core') . '</a>';
        }
    }

    // Wrappers para hooks específicos
    public function render_order_list_columns_hpos($column, $order): void
    {
        $this->render_column_content($column, $order);
    }

    public function render_order_list_columns_cpos($column, $post_id): void
    {
        $this->render_column_content($column, \wc_get_order($post_id));
    }

    public function add_column_styles(): void
    {
        $screen = \get_current_screen();
        if (! $screen || ! \in_array($screen->id, ['edit-shop_order', 'woocommerce_page_wc-orders'], true)) {
            return;
        }
?>
        <style>
            .column-gdos_slip_status {
                width: 80px;
                text-align: center !important;
                font-size: 16px;
            }

            .column-gdos_slip_view {
                width: 80px;
                text-align: center !important;
            }
        </style>
<?php
    }

    public function add_slip_metabox(\WC_Order $order): void
    {
        // HPOS Compatible Check
        $att_id = $order->get_meta(Config::SLIP_META_ID);

        echo '<div class="order_data_column" style="margin-top:12px; border-top:1px solid #eee; padding-top:10px;">';
        echo '<h4>' . \esc_html__('Comprobante de transferencia', 'gdos-core') . '</h4>';

        if ($att_id) {
            $uploader = new Uploader();
            $url = $uploader->get_download_url($order);

            echo '<p>';
            echo '<a class="button button-secondary" href="' . \esc_url($url) . '" target="_blank">';
            echo '<span class="dashicons dashicons-media-document" style="vertical-align:text-bottom;"></span> ';
            echo '<strong>' . \esc_html__('Ver comprobante de pago', 'gdos-core') . '</strong>';
            echo '</a>';
            echo '</p>';

            // Info adicional si existe (fecha de subida)
            $date = $order->get_meta(Config::SLIP_META_DT);
            if ($date) {
                echo '<p class="description" style="font-size:11px;">' . \sprintf(\esc_html__('Subido el: %s', 'gdos-core'), \esc_html($date)) . '</p>';
            }
        } else {
            echo '<p style="color:#888;"><em>' . \esc_html__('El cliente no ha subido el comprobante aún.', 'gdos-core') . '</em></p>';
        }

        echo '</div>';
    }
}
