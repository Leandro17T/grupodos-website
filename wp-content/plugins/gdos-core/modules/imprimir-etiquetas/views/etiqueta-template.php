<?php
// REFACTORIZADO: 2025-12-03

if (! \defined('ABSPATH')) {
    \exit;
}

/** @var array $data Datos pasados desde la clase principal. */
?>
<!DOCTYPE html>
<html lang="<?php echo \esc_attr(\get_locale()); ?>">

<head>
    <meta charset="<?php echo \esc_attr(\get_bloginfo('charset')); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta #<?php echo \esc_html($data['order_number']); ?></title>
    <style>
        /* Estilos Base */
        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f1;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .shipping-label {
            border: 1px dashed #999;
            box-sizing: border-box;
            width: 105mm;
            height: 148.5mm;
            padding: 6mm;
            background-color: #fff;
            position: relative;
            margin: 10mm auto;
            /* Centrado en pantalla para vista previa */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section {
            padding-bottom: 4mm;
        }

        .section-title {
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #444;
            margin: 0 0 2mm 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 1mm;
        }

        .section p {
            margin: 0;
            font-size: 11pt;
            line-height: 1.4;
            color: #1d2327;
        }

        .sender {
            margin-bottom: 4mm;
        }

        .order-details {
            text-align: right;
            font-size: 10pt;
            color: #646970;
            margin-bottom: 4mm;
        }

        .shipping-method {
            font-weight: 700;
            font-size: 12pt;
            margin-top: 5mm;
            border-top: 2px solid #000;
            padding-top: 3mm;
        }

        .company-logo {
            position: absolute;
            bottom: 6mm;
            right: 6mm;
            max-width: 35mm;
            max-height: 20mm;
            object-fit: contain;
        }

        /* Botón Flotante para Pantalla */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #2271b1;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: background 0.2s;
            z-index: 9999;
        }

        .print-button:hover {
            background: #135e96;
        }

        /* Estilos específicos de impresión */
        @media print {
            body {
                background: #fff;
                margin: 0;
            }

            .shipping-label {
                border: none;
                margin: 0;
                box-shadow: none;
                float: none;
                page-break-inside: avoid;
            }

            .print-button {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="shipping-label">
        <div class="order-details">
            <strong><?php echo \esc_html__('Pedido', 'gdos-core'); ?> #<?php echo \esc_html($data['order_number']); ?></strong>
        </div>

        <div class="section sender">
            <p class="section-title"><?php echo \esc_html__('Remitente', 'gdos-core'); ?></p>
            <p><strong><?php echo \esc_html($data['sender']['name']); ?></strong></p>
            <p><?php echo \esc_html($data['sender']['line_1']); ?></p>
            <?php if (! empty($data['sender']['line_2'])) : ?>
                <p><?php echo \esc_html($data['sender']['line_2']); ?></p>
            <?php endif; ?>
            <p><?php echo \esc_html__('Tel:', 'gdos-core'); ?> <?php echo \esc_html($data['sender']['phone']); ?></p>
        </div>

        <div class="section recipient">
            <p class="section-title"><?php echo \esc_html__('Destinatario', 'gdos-core'); ?></p>
            <address style="font-style: normal; font-size: 13pt; line-height: 1.5; color: #000;">
                <?php
                // wp_kses_post permite el HTML seguro (como <br>) que devuelve WooCommerce
                echo \wp_kses_post($data['full_address']);
                ?>
            </address>

            <?php if (! empty($data['phone'])) : ?>
                <p style="font-size: 12pt; margin-top: 3mm;">
                    <strong><?php echo \esc_html__('Tel:', 'gdos-core'); ?> <?php echo \esc_html($data['phone']); ?></strong>
                </p>
            <?php endif; ?>
        </div>

        <div class="shipping-method">
            <?php echo \esc_html__('Envío:', 'gdos-core'); ?> <?php echo \esc_html($data['shipping_method']); ?>
        </div>

        <?php if (! empty($data['logo_url'])) : ?>
            <img src="<?php echo \esc_url($data['logo_url']); ?>" class="company-logo" alt="<?php echo \esc_attr__('Logo', 'gdos-core'); ?>">
        <?php endif; ?>
    </div>

    <button class="print-button" onclick="window.print();">
        <?php echo \esc_html__('Imprimir Etiqueta', 'gdos-core'); ?>
    </button>

    <script>
        // Esperamos a que carguen todos los recursos (imágenes) antes de imprimir
        window.addEventListener("load", function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>

</html>