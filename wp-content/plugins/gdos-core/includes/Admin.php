<?php

namespace GDOS\Core;

// Evitar acceso directo.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Clase Admin
 *
 * Gestiona el panel de administración, menús y páginas de opciones del Core.
 */
class Admin
{

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		add_action('admin_menu', [$this, 'add_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
	}

	/**
	 * Agrega la página de opciones al menú de administración.
	 */
	public function add_admin_menu(): void
	{
		add_menu_page(
			__('GDOS Core Settings', 'gdos-core'),
			'GDOS Core',
			'manage_options',
			'gdos-core-settings',
			[$this, 'render_settings_page'],
			'dashicons-admin-generic',
			25
		);
	}

	/**
	 * Renderiza la página de ajustes principal.
	 */
	public function render_settings_page(): void
	{
		// Procesar guardado si se envió el formulario
		if (isset($_POST['gdos_core_save_modules'], $_POST['gdos_core_nonce'])) {
			if (!wp_verify_nonce($_POST['gdos_core_nonce'], 'gdos_core_manage_modules')) {
				wp_die('Error de seguridad');
			}

			// Obtener módulos activos del formulario
			$active_modules = isset($_POST['modules']) ? (array) $_POST['modules'] : [];

			// Sanitizar: asegurar que es un array de slugs => 1
			$sanitized_modules = [];
			foreach ($active_modules as $slug => $value) {
				$sanitized_modules[sanitize_text_field($slug)] = 1;
			}

			// Los que no están en el array $_POST['modules'] se asumen como 0 (desactivados)
			// PERO, cuidado: si hay nuevos módulos que no están en el POST, no deberíamos desactivarlos accidentalmente?
			// Mejor estrategia: Guardar solo los que explícitamente se marcaron como activos/inactivos.
			// Reconstruimos el array completo de estados basándonos en los módulos conocidos.

			$all_modules = Core::instance()->get_modules();
			$final_status = [];

			foreach (array_keys($all_modules) as $module_slug) {
				if (isset($active_modules[$module_slug])) {
					$final_status[$module_slug] = true;
				} else {
					$final_status[$module_slug] = false;
				}
			}

			update_option('gdos_core_modules_status', $final_status);

			// Actualizar el estado en tiempo de ejecución para que la vista muestre los datos reales
			// ya que Core::init() corrió antes que esto.
			foreach ($final_status as $slug => $is_active) {
				if (isset(Core::instance()->modules[$slug])) {
					Core::instance()->modules[$slug]['status'] = $is_active ? 'enabled' : 'disabled';
				}
			}

			// Add admin notice
			add_settings_error('gdos_core_messages', 'gdos_core_message', __('Ajustes guardados.', 'gdos-core'), 'updated');
		}

		// Mostrar errores/mensajes
		settings_errors('gdos_core_messages');

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
	public function enqueue_assets(string $hook): void
	{
		if ('toplevel_page_gdos-core-settings' !== $hook) {
			return;
		}

		$css_file = 'assets/admin/css/admin.css';
		if (file_exists(GDOS_CORE_PATH . $css_file)) {
			wp_enqueue_style(
				'gdos-core-admin',
				GDOS_CORE_URL . $css_file,
				[],
				filemtime(GDOS_CORE_PATH . $css_file)
			);
		}

		$js_file = 'assets/admin/js/admin.js';
		if (file_exists(GDOS_CORE_PATH . $js_file)) {
			wp_enqueue_script(
				'gdos-core-admin',
				GDOS_CORE_URL . $js_file,
				['jquery'],
				filemtime(GDOS_CORE_PATH . $js_file),
				true
			);
		}
	}
}