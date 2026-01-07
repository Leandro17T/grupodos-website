<?php
// REFACTORIZADO: 2025-12-06
// /modules/gestion-cupones/views/stats-view.php

if (! \defined('ABSPATH')) {
    exit;
}

// 1. Validaciones de Seguridad y Entorno
if (! isset($code) || ! $code) {
    echo '<div class="notice notice-error"><p>' . \esc_html__('Falta el código del cupón.', 'gdos-core') . '</p></div>';
    return;
}

if (! \current_user_can('manage_woocommerce') || ! \function_exists('wc_get_orders')) {
    echo '<div class="notice notice-error"><p>' . \esc_html__('No tienes permisos o WooCommerce no está activo.', 'gdos-core') . '</p></div>';
    return;
}

// 2. Procesamiento de Filtros (Estados)
$all_statuses_map = \wc_get_order_statuses();
$all_status_keys  = \array_keys($all_statuses_map);

// Saneamiento estricto de inputs
$raw_statuses = (isset($_GET['statuses']) && \is_array($_GET['statuses'])) ? (array) $_GET['statuses'] : ['completed', 'processing', 'on-hold'];
$normalized   = [];

foreach ($raw_statuses as $s) {
    $s = \wc_clean((string) $s);
    if (\strpos($s, 'wc-') !== 0) {
        $s = 'wc-' . $s;
    }
    if (\in_array($s, $all_status_keys, true)) {
        $normalized[] = $s;
    }
}
$normalized = \array_values(\array_unique($normalized));

// 3. Gestión de Caché (Transient)
// Generamos una key única basada en el cupón y los estados seleccionados
$cache_key = 'gdos_stats_' . \md5($code . \json_encode($normalized));

// Lógica para limpiar caché manualmente
if (isset($_GET['refresh_stats']) && $_GET['refresh_stats'] == '1') {
    \delete_transient($cache_key);
    // Redirección limpia para evitar re-envíos y limpiar la URL
    $clean_url = \remove_query_arg('refresh_stats');
    echo '<script>window.location.href="' . \esc_url($clean_url) . '";</script>';
    exit;
}

// Intentamos obtener datos cacheados
$stats_data = \get_transient($cache_key);

if (false === $stats_data) {
    // === INICIO ZONA PESADA (Cálculo) ===

    $args = [
        'limit'  => -1, // Necesario para estadísticas totales, pero ahora protegido por Transient
        'return' => 'ids', // OPTIMIZACIÓN: Solo traemos IDs inicialmente para ahorrar memoria RAM
        'type'   => 'shop_order' // Compatibilidad
    ];

    if (! empty($normalized)) {
        $args['status'] = $normalized;
    }

    $order_ids = \wc_get_orders($args);

    // Estructuras de datos
    $valid_order_ids = [];
    $total_sales     = 0.0;
    $total_discount  = 0.0;
    $product_stats   = [];
    $customer_uses   = [];

    // Iteramos IDs e hidratamos uno a uno para mantener la memoria controlada
    foreach ($order_ids as $oid) {
        $order = \wc_get_order($oid);

        if (! $order || $order instanceof \WC_Order_Refund) continue;
        if (! \method_exists($order, 'has_coupon')) continue; // Defensa contra versiones antiguas/objetos raros

        // Comprobación de cupón
        if (! $order->has_coupon($code)) continue;

        $valid_order_ids[] = $oid;
        $total_sales      += (float) $order->get_total();

        // Calcular descuento específico de este cupón
        $discount_for_this = 0.0;
        foreach ($order->get_items('coupon') as $item) {
            if (\strcasecmp($item->get_code(), $code) === 0) {
                $discount_for_this += (float) $item->get_discount();
            }
        }
        $total_discount += $discount_for_this;

        // Estadísticas de productos
        foreach ($order->get_items() as $item) {
            $pid = $item->get_product_id();
            if (! $pid) continue;

            if (! isset($product_stats[$pid])) {
                $product_stats[$pid] = [
                    'name'    => $item->get_name(),
                    'qty'     => 0,
                    'revenue' => 0.0,
                ];
            }
            $product_stats[$pid]['qty']     += (int) $item->get_quantity();
            $product_stats[$pid]['revenue'] += (float) $item->get_total();
        }

        // Estadísticas de clientes
        $email = \trim((string) $order->get_billing_email());
        if ($email !== '') {
            $k = \strtolower($email);
            $customer_uses[$k] = ($customer_uses[$k] ?? 0) + 1;
        }

        // Limpiamos memoria del objeto orden actual
        unset($order);
    }

    // Ordenamientos
    \uasort($product_stats, function ($a, $b) {
        if ($a['qty'] === $b['qty']) {
            if ($a['revenue'] === $b['revenue']) return 0;
            return ($a['revenue'] < $b['revenue']) ? 1 : -1;
        }
        return ($a['qty'] < $b['qty']) ? 1 : -1;
    });

    \arsort($customer_uses);

    $stats_data = [
        'total_sales'    => $total_sales,
        'total_discount' => $total_discount,
        'count'          => \count($valid_order_ids),
        'product_stats'  => $product_stats,
        'customer_uses'  => $customer_uses,
        'order_ids'      => $valid_order_ids // Guardamos solo IDs para renderizar vista
    ];

    // Guardamos en caché por 1 hora (3600 segundos)
    \set_transient($cache_key, $stats_data, \HOUR_IN_SECONDS);

    // === FIN ZONA PESADA ===
}

// Extraemos variables para la vista
$total_sales     = $stats_data['total_sales'];
$total_discount  = $stats_data['total_discount'];
$count_orders    = $stats_data['count'];
$product_stats   = $stats_data['product_stats'];
$customer_uses   = $stats_data['customer_uses'];
$valid_order_ids = $stats_data['order_ids'];

?>
<div class="wrap">
    <h2>
        📈 <?php \esc_html_e('Estadísticas del cupón:', 'gdos-core'); ?> <code><?php echo \esc_html($code); ?></code>
        <a href="<?php echo \esc_url(\add_query_arg('refresh_stats', '1')); ?>" class="page-title-action">🔄 <?php \esc_html_e('Actualizar datos', 'gdos-core'); ?></a>
    </h2>

    <form method="get" style="margin:20px 0; background:#fff; padding:15px; border:1px solid #ccd0d4; border-left:4px solid #2271b1; box-shadow:0 1px 1px rgba(0,0,0,.04);">
        <input type="hidden" name="page" value="gdos-coupons">
        <input type="hidden" name="action" value="stats">
        <input type="hidden" name="code" value="<?php echo \esc_attr($code); ?>">

        <strong style="margin-right:10px;"><?php \esc_html_e('Filtrar por Estados:', 'gdos-core'); ?></strong>

        <?php foreach ($all_statuses_map as $slug => $label) :
            $pure    = \str_replace('wc-', '', $slug);
            $checked = \in_array($slug, $normalized, true) ? 'checked' : '';
        ?>
            <label style="margin-right:12px; display:inline-block;">
                <input type="checkbox" name="statuses[]" value="<?php echo \esc_attr($pure); ?>" <?php echo $checked; ?>>
                <?php echo \esc_html($label); ?>
            </label>
        <?php endforeach; ?>

        <div style="margin-top:10px;">
            <button class="button button-primary" type="submit"><?php \esc_html_e('Aplicar Filtros', 'gdos-core'); ?></button>
            <a class="button" href="<?php echo \esc_url(\admin_url('admin.php?page=gdos-coupons&action=stats&code=' . \urlencode($code))); ?>"><?php \esc_html_e('Resetear', 'gdos-core'); ?></a>
        </div>
    </form>

    <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:30px;">
        <?php
        // Uso de helper de clase si existe en contexto, si no HTML directo
        if (method_exists($this, 'stat_card')) {
            echo $this->stat_card('Usos (órdenes)', \number_format_i18n($count_orders));
            echo $this->stat_card('Ventas totales', \wc_price($total_sales));
            echo $this->stat_card('Ticket promedio', \wc_price($count_orders ? ($total_sales / $count_orders) : 0));
            echo $this->stat_card('Descuento atribuido', \wc_price($total_discount));
        }
        ?>
    </div>

    <div style="display:flex; gap:20px; flex-wrap:wrap;">

        <div style="flex: 1; min-width: 300px;">
            <h3><?php \esc_html_e('Top productos (por cantidad)', 'gdos-core'); ?></h3>
            <?php if (empty($product_stats)) : ?>
                <p><em><?php \esc_html_e('Sin datos.', 'gdos-core'); ?></em></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php \esc_html_e('Producto', 'gdos-core'); ?></th>
                            <th><?php \esc_html_e('Cant.', 'gdos-core'); ?></th>
                            <th><?php \esc_html_e('Ingresos', 'gdos-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($product_stats as $pid => $row) :
                            $i++;
                            if ($i > 20) break;
                        ?>
                            <tr>
                                <td><a href="<?php echo \esc_url(\get_edit_post_link($pid)); ?>"><?php echo \esc_html($row['name']); ?></a></td>
                                <td><?php echo \number_format_i18n($row['qty']); ?></td>
                                <td><?php echo \wc_price($row['revenue']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="flex: 1; min-width: 300px;">
            <h3><?php \esc_html_e('Usuarios frecuentes', 'gdos-core'); ?></h3>
            <?php if (empty($customer_uses)) : ?>
                <p><em><?php \esc_html_e('Sin datos.', 'gdos-core'); ?></em></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php \esc_html_e('Email', 'gdos-core'); ?></th>
                            <th><?php \esc_html_e('Usos', 'gdos-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($customer_uses as $email => $count) :
                            $i++;
                            if ($i > 20) break;
                        ?>
                            <tr>
                                <td><?php echo \esc_html($email); ?></td>
                                <td><?php echo \number_format_i18n($count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <h3 style="margin-top:30px;"><?php \esc_html_e('Últimas órdenes con el cupón', 'gdos-core'); ?></h3>
    <?php if (empty($valid_order_ids)) : ?>
        <p><em><?php \esc_html_e('Sin datos.', 'gdos-core'); ?></em></p>
    <?php else :
        // WPO: Limitamos a las últimas 100 órdenes para visualización
        $display_limit = 100;
        $total_found   = \count($valid_order_ids);
        $ids_to_show   = \array_slice($valid_order_ids, 0, $display_limit);
    ?>
        <?php if ($total_found > $display_limit) : ?>
            <div class="notice notice-warning inline">
                <p>
                    <?php \printf(\esc_html__('Mostrando las últimas %d órdenes de un total de %d para optimizar el rendimiento.', 'gdos-core'), $display_limit, $total_found); ?>
                </p>
            </div>
        <?php endif; ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php \esc_html_e('Fecha', 'gdos-core'); ?></th>
                    <th><?php \esc_html_e('Cliente', 'gdos-core'); ?></th>
                    <th><?php \esc_html_e('Total', 'gdos-core'); ?></th>
                    <th><?php \esc_html_e('Descuento Cupón', 'gdos-core'); ?></th>
                    <th><?php \esc_html_e('Estado', 'gdos-core'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ids_to_show as $oid) :
                    $order = \wc_get_order($oid);
                    if (! $order) continue;

                    // Recalculamos el descuento visual para esta fila (rápido en objeto único)
                    $discount_for_this = 0.0;
                    foreach ($order->get_items('coupon') as $item) {
                        if (\strcasecmp($item->get_code(), $code) === 0) {
                            $discount_for_this += (float) $item->get_discount();
                        }
                    }
                ?>
                    <tr>
                        <td><a href="<?php echo \esc_url($order->get_edit_post_url()); ?>">#<?php echo \esc_html($order->get_order_number()); ?></a></td>
                        <td><?php echo \esc_html(\wc_format_datetime($order->get_date_created())); ?></td>
                        <td><?php echo \esc_html(\trim($order->get_formatted_billing_full_name()) ?: $order->get_billing_email()); ?></td>
                        <td><?php echo \wp_kses_post($order->get_formatted_order_total()); ?></td>
                        <td><?php echo \wc_price($discount_for_this); ?></td>
                        <td><?php echo \esc_html(\wc_get_order_status_name($order->get_status())); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p style="margin-top:20px;">
        <a class="button" href="<?php echo \esc_url(\admin_url('admin.php?page=gdos-coupons')); ?>">← <?php \esc_html_e('Volver al listado', 'gdos-core'); ?></a>
    </p>
</div>