<?php

/**
 * Métodos relativos al área de cuenta
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			1.0.0
 * @package 		SeguimientoPedidos
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Cuenta_Seguimiento_Pedidos')) :

	#[AllowDynamicProperties]

	Class Cuenta_Seguimiento_Pedidos {

		public function __construct () {

			add_action ('woocommerce_view_order', [$this, 'info_seguimiento_pedido'], 10, 1);
			add_filter ('woocommerce_my_account_my_orders_actions', [$this, 'boton_lista_pedidos'], 10, 2);
			}

		public function recupera_opciones ($id_pedido) {

			$pedido   = wc_get_order ($id_pedido);
			$ajustes  = ['transportista', 'codigo', 'fecha'];
			$opciones = $pedido->get_meta('seguimiento');

			foreach ($ajustes as $ajuste)
				$this->$ajuste = isset ($opciones[$ajuste]) ? $opciones[$ajuste]: false;

			$this->agencia = Agencias_Seguimiento_Pedidos::get_agencias($this->transportista);
			}

		public function info_seguimiento_pedido ($id_pedido) {

			$this->recupera_opciones($id_pedido);

			$enviado = $this->fecha ? sprintf (__('el día %s', 'seguimiento-pedidos'), '<b>' . date_i18n (get_option ('date_format'), strtotime ($this->fecha)) . '</b>') : '';

			if ($this->transportista && $this->codigo) :

				?>

					<section class="woocommerce-order-details">

						<h2 class="titulo-seguimiento"><?php _e('Información de seguimiento', 'seguimiento-pedidos'); ?></h2>

						<p>
							<?php printf (__('Su pedido ha sido enviado a través de %s %s', 'seguimiento-pedidos'), '<b>' . $this->agencia['nombre'] . '</b>', $enviado); ?><br />
							<?php printf (__('El código de seguimiento es %s', 'seguimiento-pedidos'), '<b>' . $this->codigo . '</b>'); ?>
						</p>

						<?php if ($url = Agencias_Seguimiento_Pedidos::url_seguimiento($this->agencia, $this->codigo, $id_pedido)) : ?>

							<p>
								<a target="_blank" href="<?php echo $url; ?>" class="button"><?php _e('Ver el estado del envío', 'seguimiento-pedidos'); ?></a>
							</p>

						<?php endif; ?>

					</section>

				<?php

			endif;
			}

		public function boton_lista_pedidos ($acciones, $pedido) {

			$this->recupera_opciones($id_pedido = $pedido->get_id());

			if ($this->transportista && $this->codigo && $url = Agencias_Seguimiento_Pedidos::url_seguimiento($this->agencia, $this->codigo, $id_pedido))
				$acciones['seguimiento'] = array(
					'url'	=> $url,
					'name'	=> __('Seguimiento', 'seguimiento-pedidos'),
					);

			return $acciones;
			}

		}

endif;