<?php

/**
 * Muestra los datos de seguimiento en la previsualización del pedido
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			2.0.0
 * @package 		SeguimientoPedidos
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Preview_Seguimiento_Pedidos')) :

	Class Preview_Seguimiento_Pedidos {

		public function __construct () {

			add_filter ('woocommerce_admin_order_preview_get_order_details', [$this, 'add_datos_seguimiento'], 10, 2);
			add_action ('woocommerce_admin_order_preview_end', [$this, 'mostrar_datos_seguimiento'], 1);
			}

		public function add_datos_seguimiento ($datos, $pedido) {

			if ($datos_seguimiento = ejr_datos_seguimiento ($pedido)) { //En helper.php

				$datos['transportista']   = $datos_seguimiento['nombre'];
				$datos['codigo']          = $datos_seguimiento['codigo'];
				$datos['url_seguimiento'] = $datos_seguimiento['url'];
				}

			return $datos;
			}

		public function mostrar_datos_seguimiento () {

			printf ('<div class="seguimiento" style="margin:20px"><strong>%s</strong><br>', __('Datos de seguimiento', 'seguimiento-pedidos'));
			echo '{{data.transportista}}<br>';
			echo '<a href="{{data.url_seguimiento}}" target="_blank">{{data.codigo}}</a></div>';
			}
		}

endif;