<?php
// /modules/alertas-stock/AlertasStock.php

namespace GDOS\Modules\AlertasStock;
use GDOS\Core\ModuleInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

class AlertasStock implements ModuleInterface {
    
    // --- Configuración Centralizada ---
    const EMAIL_TO = 'ventas@grupodos.com.uy';
    const ENABLE_LOW_STOCK = true;
    const LOW_STOCK_FALLBACK = 3;
    const DEDUP_TTL = 15 * MINUTE_IN_SECONDS; // Ventana para evitar emails duplicados

    public function boot(): void {
        if (!class_exists('WooCommerce')) return;

        // Hook para capturar el origen de la reducción de stock
        add_action('woocommerce_reduce_order_stock', [$this, 'capture_stock_reduction_origin']);
        
        // Hooks para notificar stock 0
        add_action('woocommerce_no_stock', [$this, 'notify_stock_zero']);
        add_action('woocommerce_product_set_stock_status', [$this, 'handle_stock_status_change'], 10, 3);
        add_action('woocommerce_product_set_stock', [$this, 'notify_stock_zero_from_set_stock']);

        // Hooks para notificar stock bajo (si está activado)
        if (self::ENABLE_LOW_STOCK) {
            add_action('woocommerce_low_stock', [$this, 'notify_low_stock_native']);
            add_action('woocommerce_product_set_stock', [$this, 'notify_low_stock_fallback'], 11);
        }
    }

    public function capture_stock_reduction_origin($order) {
        if (!$order) return;
        foreach ($order->get_items() as $item) {
            if ($product = $item->get_product()) {
                set_transient('gdos_stock_from_order_' . $product->get_id(), $order->get_id(), 10 * MINUTE_IN_SECONDS);
            }
        }
    }

    // --- Notificación de Stock 0 ---

    public function notify_stock_zero_from_set_stock($product) {
        $this->notify_stock_zero($product);
    }
    
    public function handle_stock_status_change($product_id, $stock_status, $product) {
        if ($stock_status === 'outofstock') {
            $this->notify_stock_zero($product);
        }
    }

    public function notify_stock_zero($product) {
        if (!$product instanceof \WC_Product || get_transient('gdos_notified_zero_' . $product->get_id())) return;

        $qty = $product->get_stock_quantity();
        if (!is_null($qty) && (int)$qty === 0) {
            $order_id = $this->try_get_order_origin($product->get_id());
            $origin = $order_id ? 'Venta (Pedido #' . $order_id . ')' : 'No determinado (manual / importación / API)';

            $subject = '⚠️ Stock 0: ' . $product->get_name();
            $body = '<p>Un producto acaba de llegar a <strong>stock 0</strong>:</p>';
            $body .= '<div style="padding:12px; border:1px solid #eee; border-radius:8px; margin:10px 0;">' . $this->build_product_summary($product) . '</div>';
            $body .= '<p><strong>Posible origen:</strong> ' . esc_html($origin) . '</p>';
            if ($product->get_stock_status() === 'onbackorder') {
                $body .= '<p>⚠️ El estado indica <em>backorder</em>. Revisar configuración de pedidos pendientes.</p>';
            }

            $this->send_mail($subject, $body);
            set_transient('gdos_notified_zero_' . $product->get_id(), 1, self::DEDUP_TTL);
        }
    }

    // --- Notificación de Stock Bajo ---

    public function notify_low_stock_native($product) {
        if (!$product instanceof \WC_Product) return;
        $qty = $product->get_stock_quantity();
        if (is_null($qty) || $qty <= 0) return;

        $subject = '🔎 Stock bajo: ' . $product->get_name();
        $body = '<p>Atención, stock bajo detectado (nativo):</p>';
        $body .= '<div style="padding:12px; border:1px solid #eee; border-radius:8px; margin:10px 0;">' . $this->build_product_summary($product) . '</div>';
        $this->send_mail($subject, $body);
    }

    public function notify_low_stock_fallback($product) {
        if (!$product instanceof \WC_Product) return;
        $qty = $product->get_stock_quantity();
        if (is_null($qty) || $qty <= 0) return;

        $threshold = (int)$product->get_low_stock_amount() ?: self::LOW_STOCK_FALLBACK;

        if ($threshold > 0 && $qty <= $threshold) {
            $subject = '🔎 Stock bajo: ' . $product->get_name();
            $body = '<p>Stock actual por debajo del umbral (' . intval($threshold) . '):</p>';
            $body .= '<div style="padding:12px; border:1px solid #eee; border-radius:8px; margin:10px 0;">' . $this->build_product_summary($product) . '</div>';
            $this->send_mail($subject, $body);
        }
    }

    // --- Helpers ---

    private function try_get_order_origin($product_id) {
        $order_id = get_transient('gdos_stock_from_order_' . $product_id);
        if ($order_id) {
            delete_transient('gdos_stock_from_order_' . $product_id);
            return absint($order_id);
        }
        return 0;
    }
    
    private function build_product_summary($product) {
        $lines = [];
        $name = $product->get_name();
        $is_variation = $product->is_type('variation');
        
        if ($is_variation) {
            $parent_name = get_the_title($product->get_parent_id());
            $attributes = wc_get_formatted_variation($product, true, false, false);
            $name = $parent_name . ' (' . $attributes . ')';
        }
        
        $lines[] = "<strong>Producto:</strong> " . esc_html($name);
        if ($sku = $product->get_sku()) $lines[] = "<strong>SKU:</strong> " . esc_html($sku);
        $lines[] = "<strong>Stock:</strong> " . (is_null($product->get_stock_quantity()) ? '—' : (int)$product->get_stock_quantity());
        $lines[] = '<strong>Ver producto:</strong> <a href="' . esc_url($product->get_permalink()) . '">Ver en la web</a>';
        $lines[] = '<strong>Editar:</strong> <a href="' . esc_url(get_edit_post_link($product->get_id())) . '">Abrir en WP-Admin</a>';

        return implode('<br>', $lines);
    }
    
    private function send_mail($subject, $html_body) {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $html_body .= '<p style="color:#888; font-size:12px;">Este correo se envió automáticamente desde el módulo de Alertas de Stock.</p>';
        wp_mail(self::EMAIL_TO, $subject, $html_body, $headers);
    }
}