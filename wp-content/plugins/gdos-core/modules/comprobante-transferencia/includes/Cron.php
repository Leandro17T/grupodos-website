<?php
// REFACTORIZADO: 2025-12-06
// /modules/comprobante-transferencia/includes/Cron.php

namespace GDOS\Modules\ComprobanteTransferencia\includes;

use GDOS\Modules\ComprobanteTransferencia\ComprobanteTransferencia as Config;

if (! \defined('ABSPATH')) {
    exit;
}

class Cron
{

    public function __construct()
    {
        // Programación del evento si no existe
        if (! \wp_next_scheduled('gdos_daily_check_expired_slips')) {
            \wp_schedule_event(\time(), 'daily', 'gdos_daily_check_expired_slips');
        }

        \add_action('gdos_daily_check_expired_slips', [$this, 'cancel_expired_orders']);
    }

    /**
     * Cancela órdenes que no han subido comprobante tras 7 días.
     * Optimizado para filtrar directamente desde la DB.
     */
    public function cancel_expired_orders(): void
    {
        // Fecha límite: hace 7 días (timestamp)
        $date_limit = \strtotime('-7 days');

        // WPO: Filtramos directamente las órdenes que NO tienen el meta key del comprobante.
        // Esto evita cargar órdenes en memoria innecesariamente.
        $args = [
            'limit'          => 20, // Lote pequeño por seguridad en procesos background
            'status'         => 'transferencia', // Nota: Asegúrate de que este slug sea correcto en tu tienda
            'payment_method' => Config::TRANSFER_GATEWAY_ID,
            'date_created'   => '<' . $date_limit,
            'return'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => Config::SLIP_META_ID,
                    'compare' => 'NOT EXISTS' // Solo traemos las que NO tienen comprobante
                ]
            ]
        ];

        $order_ids = \wc_get_orders($args);

        if (empty($order_ids)) {
            return;
        }

        $logger = \wc_get_logger();
        $log_context = ['source' => 'gdos-cron-cancelaciones'];

        foreach ($order_ids as $order_id) {
            $order = \wc_get_order($order_id);

            if (! $order) {
                continue;
            }

            // Doble chequeo de seguridad (por si la query fallara en algún edge case)
            // HPOS Compatible: Usamos el método del objeto, no get_post_meta
            if ($order->meta_exists(Config::SLIP_META_ID)) {
                continue;
            }

            // Cancelamos la orden
            $order->update_status(
                'cancelled',
                \__('Cancelado automáticamente por sistema: El cliente no subió el comprobante de transferencia en el plazo de 7 días.', 'gdos-core')
            );

            $logger->info(\sprintf('Orden #%d cancelada por falta de comprobante.', $order_id), $log_context);

            // Liberar memoria en bucles
            $order->save();
        }
    }
}
