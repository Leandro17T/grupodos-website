<?php
/**
 * Plantilla para la página de ajustes de GDOS Core.
 *
 * @var array $data Datos pasados desde la clase Admin.
 */

// Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div id="gdos-core-admin">
        <h2><?php esc_html_e( 'Módulos Detectados', 'gdos-core' ); ?></h2>
        <p><?php esc_html_e( 'Estos son los módulos encontrados en la carpeta /modules/.', 'gdos-core' ); ?></p>
		
		<?php // TODO: Agregar un formulario y un nonce field para futuras acciones.
		// wp_nonce_field( 'gdos_core_manage_modules', 'gdos_core_nonce' ); ?>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Módulo', 'gdos-core' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Estado', 'gdos-core' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Acciones', 'gdos-core' ); ?></th>
                </tr>
            </thead>
            <tbody>
				<?php if ( empty( $data['modules'] ) ) : ?>
                    <tr>
                        <td colspan="3"><?php esc_html_e( 'No se han detectado módulos.', 'gdos-core' ); ?></td>
                    </tr>
				<?php else : ?>
					<?php foreach ( $data['modules'] as $slug => $module_data ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $slug ); ?></strong></td>
                            <td>
                                <span class="dashicons dashicons-yes-alt" style="color: #4CAF50;"></span>
								<?php echo esc_html( 'enabled' === $module_data['status'] ? __( 'Habilitado', 'gdos-core' ) : __( 'Deshabilitado', 'gdos-core' ) ); ?>
                            </td>
                            <td>
								<em><?php esc_html_e( 'Controles no disponibles todavía.', 'gdos-core' ); ?></em>
								<?php // TODO: Implementar botones de Activar/Desactivar. ?>
                            </td>
                        </tr>
					<?php endforeach; ?>
				<?php endif; ?>
            </tbody>
        </table>

    </div>
</div>