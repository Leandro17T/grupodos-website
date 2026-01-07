<?php

namespace GDOS\Core;

// Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase Admin
 *
 * Gestiona el panel de administración, menús y páginas de opciones del Core.
 */
class Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Agrega la página de opciones al menú de administración.
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			__( 'GDOS Core Settings', 'gdos-core' ),
			'GDOS Core',
			'manage_options',
			'gdos-core-settings',
			[ $this, 'render_settings_page' ],
			'dashicons-admin-generic',
			25
		);
	}

	/**
	 * Renderiza la página de ajustes principal.
	 */
	public function render_settings_page(): void {
		// No necesitamos comprobar capacidades aquí porque ya lo hace `add_menu_page`.
		
		// Pasar datos a la vista.
		$data = [
			'modules' => Core::instance()->get_modules(),
		];

		// Incluir la plantilla de la vista.
		include_once GDOS_CORE_PATH . 'views/admin/settings-page.php';
	}

	/**
	 * Encola los assets (CSS/JS) para el panel de administración.
	 *
	 * @param string $hook Hook de la página actual.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_gdos-core-settings' !== $hook ) {
			return;
		}

		$css_file = 'assets/admin/css/admin.css';
		if ( file_exists( GDOS_CORE_PATH . $css_file ) ) {
			wp_enqueue_style(
				'gdos-core-admin',
				GDOS_CORE_URL . $css_file,
				[],
				filemtime( GDOS_CORE_PATH . $css_file )
			);
		}

		$js_file = 'assets/admin/js/admin.js';
		if ( file_exists( GDOS_CORE_PATH . $js_file ) ) {
			wp_enqueue_script(
				'gdos-core-admin',
				GDOS_CORE_URL . $js_file,
				[ 'jquery' ],
				filemtime( GDOS_CORE_PATH . $js_file ),
				true
			);
		}
	}
}