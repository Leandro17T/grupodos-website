<?php
namespace GDOS\Core;

// Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase Assets
 * Se encarga de generar URLs para CSS y JS con versionado automático
 * basado en la fecha de modificación del archivo.
 */
class Assets {

	/**
	 * Obtiene la URL y la Versión de un archivo.
	 *
	 * @param string $relative_path Ruta relativa (ej: '../assets/css/estilo.css').
	 * @param string $caller_file   Ruta del archivo que llama (usar __FILE__).
	 * @return array Datos listos para wp_enqueue_style/script.
	 */
	public static function get( string $relative_path, string $caller_file ): array {
		
		// 1. Calculamos la URL pública (para el navegador)
		$url = plugins_url( $relative_path, $caller_file );

		// 2. Calculamos la ruta física en el servidor (para revisar la fecha)
		$path = path_join( dirname( $caller_file ), $relative_path );

		// 3. MAGIA: Si el archivo existe, la versión es su "timestamp" (fecha exacta de guardado).
		// Si no existe, usamos una versión por defecto.
		if ( file_exists( $path ) ) {
			$ver = filemtime( $path ); 
		} else {
			$ver = '1.0.0';
		}

		return [
			'url' => $url,
			'ver' => $ver,
		];
	}
}