<?php
// REFACTORIZADO: 2025-12-06
// /modules/etiquetas-envio-dinamicas/views/admin/feriados-page.php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Variables disponibles desde el controlador:
 * @var array  $feriados    Lista de fechas (Y-m-d).
 * @var string $nonce_field Campo HTML del nonce listo para imprimir.
 * @var string|null $message Mensaje de éxito o null.
 */
?>

<div class="wrap">
    <h1><?php \esc_html_e('Feriados Grupo Dos', 'gdos-core'); ?></h1>
    <p>
        <?php \esc_html_e('Los días listados aquí se excluyen de las entregas (junto con los domingos) para Envío Express y Envío Flash.', 'gdos-core'); ?>
    </p>

    <?php if (! empty($message)) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo \esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <div style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;">

        <div style="flex:1; min-width:300px; max-width:400px; background:#fff; padding:20px; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,.04);">
            <form method="post">
                <?php echo $nonce_field; ?>

                <h2><?php \esc_html_e('Agregar Feriado', 'gdos-core'); ?></h2>
                <p>
                    <label>
                        <?php \esc_html_e('Fecha individual:', 'gdos-core'); ?><br>
                        <input type="date" name="gdos_add_date" style="width:100%;">
                    </label>
                </p>
                <p>
                    <button class="button button-primary" type="submit"><?php \esc_html_e('Agregar', 'gdos-core'); ?></button>
                </p>

                <hr style="margin: 20px 0;">

                <h3><?php \esc_html_e('Carga Masiva', 'gdos-core'); ?></h3>
                <p>
                    <label>
                        <?php \esc_html_e('Pegar lista (una fecha por línea, YYYY-MM-DD):', 'gdos-core'); ?><br>
                        <textarea name="gdos_bulk_dates" rows="5" style="width:100%; font-family:monospace;" placeholder="2025-12-25&#10;2026-01-01"></textarea>
                    </label>
                </p>
                <p>
                    <button class="button" name="gdos_submit_bulk" type="submit"><?php \esc_html_e('Agregar fechas masivas', 'gdos-core'); ?></button>
                </p>
            </form>
        </div>

        <div style="flex:2; min-width:300px; background:#fff; padding:20px; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,.04);">
            <h2>
                <?php \printf(\esc_html__('Feriados Guardados (%d)', 'gdos-core'), \count($feriados)); ?>
            </h2>

            <?php if (empty($feriados)) : ?>
                <p><em><?php \esc_html_e('No hay feriados configurados.', 'gdos-core'); ?></em></p>
            <?php else : ?>
                <form method="post">
                    <?php echo $nonce_field; ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th class="check-column"><input type="checkbox" id="cb-select-all-1"></th>
                                <th><?php \esc_html_e('Fecha', 'gdos-core'); ?></th>
                                <th><?php \esc_html_e('Día', 'gdos-core'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feriados as $d) :
                                $ts = \strtotime($d);
                                // Usamos date_i18n para que WP traduzca el día automáticamente
                                $day_name = \date_i18n('l', $ts);
                            ?>
                                <tr>
                                    <td><input type="checkbox" name="gdos_delete[]" value="<?php echo \esc_attr($d); ?>"></td>
                                    <td><strong><?php echo \esc_html($d); ?></strong></td>
                                    <td><?php echo \esc_html(\ucfirst($day_name)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>
                        <button type="submit" class="button button-link-delete" style="color:#b32d2e;" name="gdos_submit_delete" onclick="return confirm('<?php \esc_attr_e('¿Eliminar seleccionados?', 'gdos-core'); ?>');">
                            <?php \esc_html_e('🗑️ Eliminar seleccionados', 'gdos-core'); ?>
                        </button>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>