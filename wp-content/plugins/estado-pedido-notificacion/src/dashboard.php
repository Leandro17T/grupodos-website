<?php

/**
 * Resumen de los estados de pedido personalizados en el widget de dashboard.
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			2.6.0
 * @package 		EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Dashboard_Estados_Pedido')) :

	Class Dashboard_Estados_Pedido {

		public function __construct () {

			if (!is_admin() || !current_user_can ('view_woocommerce_reports')) //Capacidad necesaria para ver estos datos.
				return;

			add_action ('woocommerce_after_dashboard_status_widget', [$this, 'resumen_pedidos_personalizados'], 10);
			add_action ('wp_print_scripts', [$this, 'estilos_iconos'], 10);
			}

		public function resumen_pedidos_personalizados () {

			$celdas = [];
			$i      = 0;
			$url    = HPOS_ACTIVO ? 'admin.php?page=wc-orders&status=wc-' : 'edit.php?post_type=shop_order&post_status=wc-';

			foreach (Estados_Pedido_CPT::pide_query() as $estado) {

				if (($incluir = get_field ('informes', $estado->ID)) && is_array ($incluir) && in_array ('dashboard', $incluir)) {

					$celdas[$i]['estado'] = $estado->post_name;
					$celdas[$i]['nombre'] = $estado->post_title ? : __('Personalizado #', 'estados-pedido') . $estado->ID;
					$celdas[$i]['clases'] = ['estado-pedido', $estado->post_name];
					$celdas[$i]['cant']   = wc_orders_count ($estado->post_name);	
					$i++;
					}
				}

			if ($i%2) //Si son impares, ya que si otro plugin añade casillas después puede quedar una visualización defectuosa.
				$celdas[$i - 1]['clases'][] = 'ancho';

			foreach ($celdas as $celda)
				printf ('<li class="%s" style="border-top:1px solid #ececec;border-right:1px solid #ececec"><a href="%s%s"><strong>%s %s</strong> %s %s</a></li>',
					implode (' ', $celda['clases']),
					$url,
					$celda['estado'],
					$celda['cant'],
					_n('pedido', 'pedidos', $celda['cant'], 'estados-pedido'),
					__('en estado', 'estados-pedido'),
					$celda['nombre']
					);
			}

		public function estilos_iconos () {

			$pantalla = get_current_screen();

			if (!is_object ($pantalla) || 'dashboard' != $pantalla->base || !count ($estados = Estados_Pedido_CPT::pide_query()))
				return;

			$i = 0;

			echo '<style type="text/css">';

			foreach ($estados as $estado) {

				if (($incluir = get_field ('informes', $estado->ID)) && is_array ($incluir) && in_array ('dashboard', $incluir)) {

					$icono = (null !== ($campo = get_field ('icono', $estado->ID)) && is_array ($campo)) ? $campo['value'] : '159';

					printf ('li.estado-pedido.%s a:before{font-family:Dashicons!important;content:"\f%s"!important;color:%s!important}', $estado->post_name, $icono, get_field ('color', $estado->ID));

					$i++;
					}
				}

			echo 'li.estado-pedido.ancho{width:100%!important}';
			echo '</style>';
			}

		}

endif;