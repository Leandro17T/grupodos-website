<?php

/**
 * Función de ayuda para recuperar los datos de seguimiento de un pedido
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @param 			int 	ID del pedido
 * @return 			array 	['agencia', 'nombre', 'codigo', 'url']
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			2.0.0
 * @package 		SeguimientoPedidos
 * 
 * Usado en:
 * 
 * 		email.php
 * 		orders.php
 * 		preview.php
 *
 */

defined ('ABSPATH') or exit;

if (!function_exists ('ejr_datos_seguimiento')) :

	function ejr_datos_seguimiento ($pedido) {

		$opciones = $pedido->get_meta('seguimiento');

		if ($opciones && isset ($opciones['transportista']) && isset ($opciones['codigo'])) {

			$obj_agencia = new Agencias_Seguimiento_Pedidos;
			$agencia     = $obj_agencia->get_agencias($opciones['transportista']); //Array con nombre, id y url en crudo
			$nombre      = isset ($agencia['nombre']) ? $agencia['nombre'] : false;
			$url         = $obj_agencia->url_seguimiento($agencia, $opciones['codigo'], $pedido->get_id());

			$datos = array(
				'transportista'	=> $opciones['transportista'],
				'nombre'		=> $nombre,
				'codigo'		=> $opciones['codigo'],
				'url'			=> $url,
				'fecha'			=> $opciones['fecha'] ? : false,
				);

			return $datos;
			}

		return false;
		}

endif;