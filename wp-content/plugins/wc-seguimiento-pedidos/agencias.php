<?php

/**
 * Agencias de transporte y URLs para seguimiento
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			1.0.0
 * @package 		SeguimientoPedidos
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Agencias_Seguimiento_Pedidos')) :

	Class Agencias_Seguimiento_Pedidos {

		public function __construct () {}

		public static function get_agencias ($transportista = false) {

			$agencias = [];

			foreach (CPT_Seguimiento_Pedidos::pide_query() as $agencia) {

				$agencias[$agencia->post_name]['url']    = get_field ('url_seguimiento', $agencia->ID);
				$agencias[$agencia->post_name]['id']     = $agencia->ID;
				$agencias[$agencia->post_name]['nombre'] = $agencia->post_title;
				}

			return $transportista ? $agencias[$transportista] : $agencias;
			}

		public static function url_seguimiento ($agencia, $codigo, $id_pedido) {

			$variables = ['%ref%', '%cp%']; 
			$replace   = [$codigo, self::recupera_cp($id_pedido)];
			$url       = isset ($agencia['url']) ? str_replace ($variables, $replace, $agencia['url']) : false;

			return $url ? : false;
			}

		//Devuelve el código postal de envío (si no está establecido, devuelve el de facturación)
		private static function recupera_cp ($id_pedido) {

			$pedido   = wc_get_order ($id_pedido);
			$datos    = $pedido->get_data();
			$shipping = $datos['shipping']['postcode'];

			return (isset ($shipping) && strlen ($shipping)) ? $shipping : $datos['billing']['postcode'];
			}

		}

endif;