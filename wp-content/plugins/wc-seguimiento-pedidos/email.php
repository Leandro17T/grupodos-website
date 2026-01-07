<?php

/**
 * Información de seguimiento en el email de pedido completado
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			1.2.0
 * @package 		SeguimientoPedidos
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Email_Seguimiento_Pedidos')) :

	Class Email_Seguimiento_Pedidos {

		public function __construct () {

			add_action ('woocommerce_email_after_order_table', [$this, 'contenido_email'], 10, 4);
			}

		public function contenido_email ($pedido, $enviado_a_admin, $texto_plano, $email) {

			if ($email && 'customer_completed_order' != $email->id && 'customer_invoice' != $email->id)
				return;

			$datos = ejr_datos_seguimiento ($pedido); //En helper.php

			if (!$datos['transportista'] || !$datos['codigo'])
				return;

			printf ('<h2>%s</h2>', __('Información de seguimiento del envío', 'seguimiento-pedidos'));
			printf ('<p>' . __('Su pedido ha sido enviado a través de %s%s, con el código de seguimiento %s. Puede conocer el estado del envío a través del siguiente enlace:', 'seguimiento-pedidos') . '</p>', '<b>' . $datos['nombre'] . '</b>', $datos['fecha'] ? sprintf (__(' el día %s', 'seguimiento-pedidos'), date_i18n (get_option ('date_format'), strtotime ($datos['fecha']))) : '' , '<b>' . $datos['codigo'] . '</b>');

			$datos['url'] and
				printf ('<p><a target="_blank" href="%s" class="button" style="text-decoration:none">%s</a></p>', $datos['url'], __('Ver el estado del envío', 'seguimiento-pedidos'));
			}

		}

endif;