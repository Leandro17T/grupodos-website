<?php
// REFACTORIZADO: 2025-12-06
// /modules/comprobante-transferencia/ComprobanteTransferencia.php

namespace GDOS\Modules\ComprobanteTransferencia;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) {
    exit;
}

class ComprobanteTransferencia implements ModuleInterface
{

    // --- Configuración Global del Módulo ---
    const SLIP_META_ID          = '_gdos_payment_slip_id';
    const SLIP_META_URL         = '_gdos_payment_slip_url';
    const SLIP_META_DT          = '_gdos_payment_slip_date';

    const UPLOAD_ACTION         = 'gdos_upload_slip';
    const UPLOAD_NONCE          = 'gdos_upload_slip_nonce';

    const DOWNLOAD_ACTION       = 'gdos_download_slip';
    const DOWNLOAD_NONCE_ACTION = 'gdos_download_slip_nonce';

    const MAX_MB                = 8;
    const TRANSFER_GATEWAY_ID   = 'bacs'; // ID de Transferencia Bancaria en WC

    // Configuración de Email
    const EMAIL_TO  = 'ventas@grupodos.com.uy';
    const EMAIL_CC  = '';
    const EMAIL_BCC = '';

    public function boot(): void
    {
        // Dependencia Crítica: Si no hay WooCommerce, no cargamos nada.
        if (! \class_exists('WooCommerce')) {
            return;
        }

        // WPO: Cargamos los componentes SOLO si el módulo arranca
        require_once __DIR__ . '/includes/Admin.php';
        require_once __DIR__ . '/includes/Frontend.php';
        require_once __DIR__ . '/includes/Uploader.php';
        require_once __DIR__ . '/includes/Cron.php';

        // Instanciamos los sub-módulos
        new includes\Admin();
        new includes\Frontend();
        new includes\Uploader();
        new includes\Cron();
    }

    /**
     * Helper para detectar si High Performance Order Storage (HPOS) está activo.
     * Vital para decidir si usamos get_post_meta o $order->get_meta.
     */
    public static function is_hpos_enabled(): bool
    {
        if (\class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }
}
