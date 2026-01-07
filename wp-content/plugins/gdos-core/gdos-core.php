<?php
/**
 * Plugin Name:       GDOS Core
 * Plugin URI:        https://example.com/gdos-core
 * Description:       Core plugin para la gestión centralizada de módulos y snippets.
 * Version:           1.0.0
 * Author:            Tu Nombre
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gdos-core
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 6.0
 */

// Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Definición de Constantes.
define( 'GDOS_CORE_VERSION', '1.0.0' );
define( 'GDOS_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'GDOS_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'GDOS_CORE_BASENAME', plugin_basename( __FILE__ ) );

// 2. Autocargador PSR-4 simple.
spl_autoload_register(
	function ( $class ) {
		// Solo procesar clases de nuestro namespace.
		$prefix = 'GDOS\\Core\\';
		$len    = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		// Obtener la ruta relativa de la clase.
		$relative_class = substr( $class, $len );

		// Construir la ruta al archivo.
		$file = GDOS_CORE_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

		// Si el archivo existe, requerirlo.
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// 3. Inicialización del Core del Plugin.
if ( class_exists( 'GDOS\\Core\\Core' ) ) {
	/**
	 * Devuelve la instancia principal de GDOS Core.
	 *
	 * @return GDOS\Core\Core
	 */
	function gdos_core() {
		return GDOS\Core\Core::instance();
	}

	// ¡Despegamos!
	gdos_core();
}