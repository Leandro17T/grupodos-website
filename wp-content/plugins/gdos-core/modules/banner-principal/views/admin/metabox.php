<?php
// REFACTORIZADO: 2025-12-06
// /modules/banner-principal/views/admin/metabox.php

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('gdos_banner_render_slide_row')) {
    function gdos_banner_render_slide_row($type, $item = [])
    {
        $item = (array) $item;
        $id   = isset($item['id']) ? absint($item['id']) : 0;
        $link = isset($item['link']) ? esc_url($item['link']) : '';
        $df   = isset($item['date_from']) ? esc_attr($item['date_from']) : '';
        $dt   = isset($item['date_to']) ? esc_attr($item['date_to']) : '';
        $tf   = isset($item['time_from']) ? esc_attr($item['time_from']) : '';
        $tt   = isset($item['time_to']) ? esc_attr($item['time_to']) : '';
        $prio = isset($item['priority']) ? absint($item['priority']) : 10;
        $days = isset($item['days']) ? esc_attr($item['days']) : '';
        $cdown = isset($item['countdown']) ? absint($item['countdown']) : 0;

        // NUEVO: Recuperar el label guardado (default: 'end')
        $cd_lbl = isset($item['countdown_label']) ? esc_attr($item['countdown_label']) : 'end';

        $thumb = $id ? wp_get_attachment_image_url($id, 'thumbnail') : '';
        $type_attr = esc_attr($type);
?>
        <div class="gdos-row" data-item>
            <div class="gdos-handle" title="<?php esc_attr_e('Arrastrar para reordenar', 'gdos-core'); ?>"></div>

            <div class="gdos-media-col">
                <div class="gdos-thumb" data-thumb>
                    <?php if ($thumb) : ?>
                        <img src="<?php echo esc_url($thumb); ?>" alt="">
                    <?php else : ?>
                        <span class="placeholder"><?php esc_html_e('Sin imagen', 'gdos-core'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="btns">
                    <input type="hidden" name="gdos_<?php echo $type_attr; ?>_id[]" value="<?php echo esc_attr($id); ?>" data-id>
                    <button type="button" class="button gdos-pick"><?php esc_html_e('Seleccionar', 'gdos-core'); ?></button>
                    <button type="button" class="button gdos-clear"><?php esc_html_e('Quitar', 'gdos-core'); ?></button>
                    <button type="button" class="button gdos-remove"><?php esc_html_e('Eliminar', 'gdos-core'); ?></button>
                </div>
            </div>

            <div class="gdos-data-col">
                <div class="gdos-chip-holder"></div>

                <div class="field full-width">
                    <label><strong><?php esc_html_e('Enlace', 'gdos-core'); ?></strong></label>
                    <input type="url" class="widefat" name="gdos_<?php echo $type_attr; ?>_link[]" value="<?php echo $link; ?>" placeholder="https://...">
                </div>

                <div class="gdos-mini-grid">
                    <div class="field">
                        <label><?php esc_html_e('Fecha desde', 'gdos-core'); ?></label>
                        <input type="date" name="gdos_<?php echo $type_attr; ?>_date_from[]" value="<?php echo $df; ?>">
                    </div>
                    <div class="field">
                        <label><?php esc_html_e('Fecha hasta', 'gdos-core'); ?></label>
                        <input type="date" name="gdos_<?php echo $type_attr; ?>_date_to[]" value="<?php echo $dt; ?>">
                    </div>
                    <div class="field">
                        <label><?php esc_html_e('Hora desde', 'gdos-core'); ?></label>
                        <input type="time" name="gdos_<?php echo $type_attr; ?>_time_from[]" value="<?php echo $tf; ?>">
                    </div>
                    <div class="field">
                        <label><?php esc_html_e('Hora hasta', 'gdos-core'); ?></label>
                        <input type="time" name="gdos_<?php echo $type_attr; ?>_time_to[]" value="<?php echo $tt; ?>">
                    </div>
                    <div class="field small">
                        <label><?php esc_html_e('Prioridad', 'gdos-core'); ?></label>
                        <input type="number" name="gdos_<?php echo $type_attr; ?>_priority[]" value="<?php echo $prio; ?>">
                    </div>
                </div>

                <div class="gdos-advanced-grid">
                    <div class="field days-field">
                        <label><?php esc_html_e('Días (vacío = todos)', 'gdos-core'); ?></label>
                        <div class="gdos-days" data-days-group>
                            <?php
                            $labels = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
                            $set    = array_filter(array_map('intval', explode(',', (string) $days)));

                            foreach ($labels as $n => $lab) {
                                $checked = in_array($n, $set, true) ? 'checked' : '';
                                echo '<label><input type="checkbox" value="' . esc_attr($n) . '" ' . $checked . '> ' . esc_html($lab) . '</label>';
                            }
                            ?>
                        </div>
                        <input type="hidden" name="gdos_<?php echo $type_attr; ?>_days[]" value="<?php echo $days; ?>" data-days-hidden>
                    </div>

                    <div class="field countdown-field">
                        <label><?php esc_html_e('Configuración Contador', 'gdos-core'); ?></label>
                        <div class="gdos-toggles" style="display:flex; gap:10px; align-items:center;">
                            <label style="display:flex;align-items:center;gap:5px;">
                                <input type="hidden" name="gdos_<?php echo $type_attr; ?>_countdown[]" value="0">
                                <input type="checkbox" name="gdos_<?php echo $type_attr; ?>_countdown[]" value="1" <?php checked(1, $cdown); ?>>
                                <?php esc_html_e('Mostrar', 'gdos-core'); ?>
                            </label>

                            <select name="gdos_<?php echo $type_attr; ?>_countdown_label[]" style="font-size:12px; padding:2px 24px 2px 8px; height:28px;">
                                <option value="end" <?php selected($cd_lbl, 'end'); ?>>Texto: "Termina en"</option>
                                <option value="start" <?php selected($cd_lbl, 'start'); ?>>Texto: "Empieza en"</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
}
?>

<div class="gdos-card">
    <div class="gdos-header">
        <div class="gdos-tabs" role="tablist">
            <button type="button" class="gdos-tab active" data-type="desktop" data-target="#gdos-panel-desktop"><?php esc_html_e('Desktop', 'gdos-core'); ?></button>
            <button type="button" class="gdos-tab" data-type="tablet" data-target="#gdos-panel-tablet"><?php esc_html_e('Tablet', 'gdos-core'); ?></button>
            <button type="button" class="gdos-tab" data-type="mobile" data-target="#gdos-panel-mobile"><?php esc_html_e('Mobile', 'gdos-core'); ?></button>
        </div>
        <div class="gdos-actions">
            <button type="button" class="button button-primary gdos-add-multiple"><?php esc_html_e('Agregar Múltiples', 'gdos-core'); ?></button>
            <button type="button" class="button gdos-add-one"><?php esc_html_e('Agregar Vacío', 'gdos-core'); ?></button>
            <span class="gdos-size-pill" data-size-pill></span>
        </div>
    </div>

    <?php foreach (['desktop', 'tablet', 'mobile'] as $type) : ?>
        <div id="gdos-panel-<?php echo esc_attr($type); ?>" class="gdos-panel <?php echo $type === 'desktop' ? 'active' : ''; ?>">
            <div class="gdos-list" data-type="<?php echo esc_attr($type); ?>">
                <?php
                if (! empty($data[$type]) && is_array($data[$type])) {
                    foreach ($data[$type] as $item) {
                        gdos_banner_render_slide_row($type, $item);
                    }
                }
                ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<template id="gdos-row-template">
    <?php gdos_banner_render_slide_row('__TYPE__'); ?>
</template>