<?php
/**
 * Plantilla para la página de ajustes de GDOS Core.
 *
 * @var array $data Datos pasados desde la clase Admin.
 */

// Evitar acceso directo.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div id="gdos-core-admin">
        <h2><?php esc_html_e('Módulos Detectados', 'gdos-core'); ?></h2>
        <p><?php esc_html_e('Estos son los módulos encontrados en la carpeta /modules/.', 'gdos-core'); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field('gdos_core_manage_modules', 'gdos_core_nonce'); ?>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th scope="col" class="check-column"><input type="checkbox" id="cb-select-all-1"></th>
                        <th scope="col"><?php esc_html_e('Módulo', 'gdos-core'); ?></th>
                        <th scope="col"><?php esc_html_e('Estado', 'gdos-core'); ?></th>
                        <th scope="col"><?php esc_html_e('Ruta', 'gdos-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['modules'])): ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e('No se han detectado módulos.', 'gdos-core'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['modules'] as $slug => $module_data): ?>
                            <?php $is_active = ('enabled' === $module_data['status']); ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="modules[<?php echo esc_attr($slug); ?>]" value="1" <?php checked($is_active); ?>>
                                </th>
                                <td>
                                    <strong><?php echo esc_html($slug); ?></strong>
                                </td>
                                <td>
                                    <?php if ($is_active): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: #4CAF50;"></span> <span
                                            style="color: #4CAF50; font-weight:bold;"><?php esc_html_e('Activo', 'gdos-core'); ?></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-no-alt" style="color: #F44336;"></span> <span
                                            style="color: #F44336;"><?php esc_html_e('Inactivo', 'gdos-core'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code
                                        style="font-size: 10px;"><?php echo esc_html(str_replace(ABSPATH, '', $module_data['path'])); ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="gdos_core_save_modules" id="submit" class="button button-primary"
                    value="<?php esc_attr_e('Guardar Cambios', 'gdos-core'); ?>">

                &nbsp;
                <a href="<?php echo esc_url(add_query_arg('gdos_flush_modules', '1')); ?>"
                    class="button button-secondary">
                    <span class="dashicons dashicons-update" style="line-height:1.3; margin-right:5px;"></span>
                    <?php esc_html_e('Refrescar Caché', 'gdos-core'); ?>
                </a>
            </p>
        </form>

    </div>
</div>