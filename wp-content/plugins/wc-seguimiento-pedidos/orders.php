<?php

/**
 * Columna con el número de seguimiento en el listado de pedidos
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			2.0.0
 * @package 		SeguimientoPedidos
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Orders_Seguimiento_Pedidos')) :

	Class Orders_Seguimiento_Pedidos {

		public function __construct () {

			add_action ('manage_shop_order_posts_columns', [$this, 'columnas_orders'], PHP_INT_MAX, 1); //Sin HPOS.
			add_action ('manage_woocommerce_page_wc-orders_columns', [$this, 'columnas_orders'], PHP_INT_MAX, 1); //Con HPOS.
			add_action ('manage_shop_order_posts_custom_column', [$this, 'rellena_columna'], 10, 2); //Sin HPOS.
			add_action ('manage_woocommerce_page_wc-orders_custom_column', [$this, 'rellena_columna'], 10, 2); //Con HPOS.
			}

		public function columnas_orders ($columnas) {

			$nuevas_columnas = [];

			foreach ($columnas as $key => $nombre) {

				$nuevas_columnas[$key] = $nombre;

				if ('order_status' == $key)
					$nuevas_columnas['envio'] = __('Seguimiento', 'seguimiento-pedidos');
				}

			return $nuevas_columnas;
			}

		public function rellena_columna ($columna, $id_pedido) {

			if ('envio' == $columna && $datos = ejr_datos_seguimiento (wc_get_order ($id_pedido))) //En helper.php
				printf ('<a target="_blank" href="%s">%s</a>', $datos['url'], $datos['codigo']);
			}

		}

endif;