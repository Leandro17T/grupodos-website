<?php

namespace GDOS\Core;

// Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface ModuleInterface
 *
 * Contrato que todos los módulos deberían implementar para asegurar
 * que tienen un método de arranque estandarizado.
 */
interface ModuleInterface {

	/**
	 * Método de arranque del módulo.
	 * Aquí es donde se deben registrar los hooks, filtros y demás lógica.
	 *
	 * @return void
	 */
	public function boot(): void;
}