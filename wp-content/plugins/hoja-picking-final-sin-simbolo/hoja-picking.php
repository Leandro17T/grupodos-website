<?php
/*
Plugin Name: Hoja de Picking PDF Final Limpia
Description: Hoja de picking con SKU, texto más chico y columna de checklist vacía sin símbolos.
Version: 1.5
Author: Grupo Dos
*/

add_action('add_meta_boxes', function() {
    add_meta_box('gdos_picking', 'Hoja de Picking', 'gdos_mostrar_boton_picking', 'shop_order', 'side', 'high');
});

function gdos_mostrar_boton_picking($post) {
    echo '<button type="button" class="button button-primary" onclick="gdosSeleccionarArmador(' . $post->ID . ')">Imprimir hoja de picking</button>';
}

add_action('admin_footer', function() {
    $screen = get_current_screen();
    if ($screen->id !== 'shop_order') return;
    ?>
    <div id="gdos-popup-armador" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:#fff; padding:20px; border:1px solid #ccc; z-index:9999;">
        <h3>Seleccionar Armador</h3>
        <select id="gdos-armador-seleccionado">
            <option value="">Seleccionar...</option>
            <option value="Leandro Duarte">Leandro Duarte</option>
            <option value="Diego Duarte">Diego Duarte</option>
            <option value="Florencia Barrio">Florencia Barrio</option>
        </select>
        <br><br>
        <button class="button button-primary" onclick="gdosGenerarPDF()">Generar PDF</button>
        <button class="button" onclick="document.getElementById('gdos-popup-armador').style.display='none'">Cancelar</button>
    </div>
    <script>
    let pedidoActual = 0;
    function gdosSeleccionarArmador(orderId) {
        pedidoActual = orderId;
        document.getElementById('gdos-popup-armador').style.display = 'block';
    }
    function gdosGenerarPDF() {
        const armador = document.getElementById('gdos-armador-seleccionado').value;
        if (!armador) {
            alert('Por favor selecciona un armador.');
            return;
        }
        const url = '<?php echo admin_url('admin-ajax.php'); ?>?action=gdos_generar_picking_pdf&order_id=' + pedidoActual + '&armador=' + encodeURIComponent(armador);
        window.open(url, '_blank');
        document.getElementById('gdos-popup-armador').style.display = 'none';
    }
    </script>
    <?php
});

add_action('wp_ajax_gdos_generar_picking_pdf', 'gdos_generar_picking_pdf');

function gdos_generar_picking_pdf() {
    $order_id = intval($_GET['order_id']);
    $armador = sanitize_text_field($_GET['armador']);
    $order = wc_get_order($order_id);
    if (!$order) { echo 'Pedido no encontrado.'; exit; }

    update_post_meta($order_id, '_gdos_armador', $armador);

    require_once __DIR__ . '/dompdf/autoload.inc.php';
    $dompdf = new Dompdf\Dompdf();
    ob_start();
    ?>
    <style>
    body {
        font-family: sans-serif;
        font-size: 12px;
    }
    .encabezado { text-align: center; margin-bottom: 20px; }
    .encabezado h1 { font-size: 20px; margin: 0; }
    .info-cliente, .productos { margin-bottom: 15px; }
    .productos table { width: 100%; border-collapse: collapse; }
    .productos th, .productos td { border: 1px solid #ddd; padding: 6px; text-align: left; }
    .productos th { background-color: #f4f4f4; }
    .cantidad { font-size: 14px; font-weight: bold; }
    .sku { font-size: 10px; color: #666; }
    </style>
    <div class="encabezado">
        <h1>Hoja de Picking</h1>
        <p><strong>Pedido #<?php echo $order->get_id(); ?></strong></p>
        <p>Fecha: <?php echo date('d/m/Y H:i'); ?></p>
        <p>Armador: <strong><?php echo $armador; ?></strong></p>
    </div>
    <div class="info-cliente">
        <h3>Datos del Cliente</h3>
        <p><strong>Nombre:</strong> <?php echo $order->get_formatted_billing_full_name(); ?></p>
        <p><strong>Teléfono:</strong> <?php echo $order->get_billing_phone(); ?></p>
        <p><strong>Dirección:</strong> <?php echo $order->get_formatted_shipping_address(); ?></p>
        <p><strong>Email:</strong> <?php echo $order->get_billing_email(); ?></p>
    </div>
    <div class="productos">
        <h3>Productos</h3>
        <table>
            <thead><tr><th></th><th>Producto</th><th>SKU</th><th>Cantidad</th></tr></thead>
            <tbody>
            <?php foreach ($order->get_items() as $item): 
                $product = $item->get_product();
                $sku = $product ? $product->get_sku() : '';
            ?>
                <tr>
                    <td></td>
                    <td><?php echo $item->get_name(); ?></td>
                    <td class="sku"><?php echo $sku; ?></td>
                    <td class="cantidad"><?php echo $item->get_quantity(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    $html = ob_get_clean();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4');
    $dompdf->render();
    $dompdf->stream('hoja-picking-' . $order->get_id() . '.pdf', ['Attachment' => false]);
    exit;
}

add_action('woocommerce_admin_order_data_after_order_details', function($order) {
    $armador = get_post_meta($order->get_id(), '_gdos_armador', true);
    if ($armador) {
        echo '<p><strong>Armador asignado:</strong> ' . esc_html($armador) . '</p>';
    }
});
