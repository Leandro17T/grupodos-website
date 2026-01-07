<?php
// REFACTORIZADO: 2025-12-06
// /modules/gestion-cupones/views/list-view.php

if (! \defined('ABSPATH')) {
    exit;
}
?>
<table class="widefat striped">
    <thead>
        <tr>
            <th><?php \esc_html_e('Código', 'gdos-core'); ?></th>
            <th><?php \esc_html_e('Tipo', 'gdos-core'); ?></th>
            <th><?php \esc_html_e('Importe', 'gdos-core'); ?></th>
            <th><?php \esc_html_e('Usos / Límite', 'gdos-core'); ?></th>
            <th><?php \esc_html_e('Vigencia', 'gdos-core'); ?></th>
            <th><?php \esc_html_e('Estado', 'gdos-core'); ?></th>
            <th><?php \esc_html_e('Acciones', 'gdos-core'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($coupons)) : ?>
            <tr>
                <td colspan="7"><?php \esc_html_e('No hay cupones creados.', 'gdos-core'); ?></td>
            </tr>
            <?php else :
            // Obtenemos los tipos fuera del loop para rendimiento
            $wc_types = \function_exists('wc_get_coupon_types') ? \wc_get_coupon_types() : [];
            $now      = \current_time('timestamp');

            foreach ($coupons as $c) :
                $cid    = $c->ID;
                $type   = \get_post_meta($cid, 'discount_type', true);
                $amount = \get_post_meta($cid, 'coupon_amount', true);
                $usage  = (int) \get_post_meta($cid, 'usage_count', true);
                $limit  = \get_post_meta($cid, 'usage_limit', true);

                // Fechas
                $from   = \get_post_meta($cid, '_gdos_date_from', true);
                $exp_ts = \get_post_meta($cid, 'date_expires', true);
                $to     = $exp_ts ? \date('Y-m-d', (int) $exp_ts) : '∞';

                // Lógica de Estado
                $start_ts   = $from ? \strtotime($from . ' 00:00:00') : 0;
                $is_started = $start_ts <= $now;
                $is_expired = $exp_ts && $exp_ts < $now;

                if ($is_expired) {
                    $status_text  = \__('Expirado', 'gdos-core');
                    $status_color = '#d63638'; // Rojo Error WP
                } elseif (! $is_started) {
                    $status_text  = \__('Programado', 'gdos-core');
                    $status_color = '#dba617'; // Amarillo Warning WP
                } else {
                    $status_text  = \__('Activo', 'gdos-core');
                    $status_color = '#00a32a'; // Verde Success WP
                }

                // URLs de acción
                $edit_url  = \admin_url('admin.php?page=gdos-coupons&action=edit&id=' . $cid);
                $stats_url = \admin_url('admin.php?page=gdos-coupons&action=stats&code=' . \urlencode($c->post_title));
                $del_url   = \wp_nonce_url(\admin_url('admin.php?page=gdos-coupons&action=delete&id=' . $cid), 'gdos_delete_coupon_' . $cid);

                $type_label = $wc_types[$type] ?? $type;
            ?>
                <tr>
                    <td><strong><?php echo \esc_html($c->post_title); ?></strong></td>
                    <td><?php echo \esc_html($type_label); ?></td>
                    <td>
                        <?php echo \esc_html($amount); ?>
                        <?php echo ($type === 'percent') ? '%' : ''; ?>
                    </td>
                    <td>
                        <?php echo \esc_html($usage); ?> / <?php echo \esc_html($limit ?: '∞'); ?>
                    </td>
                    <td>
                        <?php echo \esc_html(($from ?: '—') . ' → ' . ($to ?: '')); ?>
                    </td>
                    <td>
                        <span style="color: <?php echo \esc_attr($status_color); ?>; font-weight: 600;">
                            <?php echo \esc_html($status_text); ?>
                        </span>
                    </td>
                    <td>
                        <a class="button button-small" href="<?php echo \esc_url($edit_url); ?>">
                            <?php \esc_html_e('Editar', 'gdos-core'); ?>
                        </a>
                        <a class="button button-small" href="<?php echo \esc_url($stats_url); ?>">
                            <?php \esc_html_e('Estadísticas', 'gdos-core'); ?>
                        </a>
                        <a class="button button-small button-link-delete" style="color:#b32d2e; margin-left:6px;"
                            href="<?php echo \esc_url($del_url); ?>"
                            onclick="return confirm('<?php echo \esc_js(\sprintf(\__('¿Seguro que deseas eliminar el cupón \'%s\'?', 'gdos-core'), $c->post_title)); ?>');">
                            <?php \esc_html_e('Eliminar', 'gdos-core'); ?>
                        </a>
                    </td>
                </tr>
        <?php endforeach;
        endif; ?>
    </tbody>
</table>