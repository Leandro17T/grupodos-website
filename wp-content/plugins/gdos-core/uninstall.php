<?php
/**
 * Fichero de desinstalación para GDOS Core.
 *
 * Este fichero se ejecuta cuando un usuario hace clic en "Borrar"
 * en la página de plugins de WordPress.
 *
 * @package GDOS_Core
 */

// Si no se está desinstalando el plugin, salir.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// TODO: Lógica de limpieza al desinstalar.
// Por ejemplo, borrar opciones de la base de datos:
// delete_option( 'gdos_core_settings' );
// delete_transient( 'gdos_core_transient' );

// Es importante no borrar datos del usuario sin su consentimiento explícito.
// Considerar agregar una opción en el panel para "Borrar datos al desinstalar".