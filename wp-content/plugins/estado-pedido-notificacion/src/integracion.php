<?php

/**
 * Opción para la clave de licencia en la pestaña de integración de los ajustes de WooCommerce.
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			2.0.0
 * @package 		EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Integracion_Estados_Pedido')) :

	Class Integracion_Estados_Pedido {

		public function __construct () {

			add_action ('woocommerce_settings_tabs_integration', [$this, 'crea_campo_licencia'], 10);
			add_action ('woocommerce_update_options_integration', [$this, 'actualiza_campo_licencia'], 10);
			}

		public function crea_campo_licencia () {

			woocommerce_admin_fields ($this->campo_licencia());
			}

		public function actualiza_campo_licencia () {

			woocommerce_update_options ($this->campo_licencia());
			}

		private function campo_licencia () {

			return [
				'estados_pedido_titulo' => [
					'name'	=> __('Estados de pedido con notificaciones', 'estados-pedido'),
					'type'	=> 'title',
					'desc'	=> '',
					'id'	=> 'estados_pedido_titulo'
					],

				'estados_pedido_licencia' => [
					'name'		=> __('Clave de licencia', 'estados-pedido'),
					'type'		=> 'text',
					'desc'		=> $this->comprueba_licencia(),
					'id'		=> 'estados_pedido_licencia',
					],

				'fin_seccion_estados_pedido' => [
					'type'	=> 'sectionend',
					'id'	=> 'fin_seccion_estados_pedido',
					],
				];
			}

		private function comprueba_licencia () {

			if ($clave = get_option ('estados_pedido_licencia')) {

				$licencia       = new EDDSL_Estados_Pedido($clave);
				$datos_licencia = $licencia->comprueba_licencia();

				if ('inactive' == $datos_licencia['estado'])
					$datos_licencia = $licencia->activa_licencia();

				if ('valid' == $datos_licencia['estado'])
					return sprintf ('<span class="valida">%s</span>', sprintf (__('Tu licencia está activada y es válida hasta el %s.', 'estados-pedido'), date_i18n (get_option ('date_format'), $datos_licencia['expira'])));

				else if ('expired' == $datos_licencia['estado'])
					return sprintf ('<span class="no-valida">%s</span>', sprintf (__('La licencia ha expirado y ya no es posible renovarla. Consigue una nueva licencia %saquí%s.', 'estados-pedido'), '<a target="_blank" href="' . URL_LICENCIA_ESTADOS_PEDIDO . '">', '</a>'));

				else
					return sprintf ('<span class="no-valida">%s</span>', sprintf (__('Activa tu licencia para poder recibir soporte y actualizaciones. Si no tienes una clave de licencia válida, consíguela %saquí%s.', 'estados-pedido'), '<a target="_blank" href="' . URL_LICENCIA_ESTADOS_PEDIDO . '">', '</a>'));
				}

			else
				return sprintf ('<span class="no-valida">%s</span>', sprintf (__('Activa tu licencia para poder recibir soporte y actualizaciones. Si no tienes una clave de licencia válida, consíguela %saquí%s.', 'estados-pedido'), '<a target="_blank" href="' . URL_LICENCIA_ESTADOS_PEDIDO . '">', '</a>'));

			}

		}

endif;