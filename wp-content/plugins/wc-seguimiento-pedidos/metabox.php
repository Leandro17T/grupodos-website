<?php

/**
 * Metabox en la pantalla de edición del pedido
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			1.0.0
 * @package 		SeguimientoPedidos
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Metabox_Seguimiento_Pedidos')) :

	#[AllowDynamicProperties]

	Class Metabox_Seguimiento_Pedidos {

		public function __construct () {

			add_action ('add_meta_boxes', [$this, 'metabox'], 10);

			if (HPOS_ACTIVO)
				add_action ('woocommerce_process_shop_order_meta', [$this, 'guarda_datos'], 1); //Con HPOS.

			else
				add_action ('save_post', [$this, 'guarda_datos'], 1); //Sin HPOS.
			
			add_action ('admin_enqueue_scripts', [$this, 'estilos_metabox'], 10);
			}

		public function recupera_opciones ($pedido) {

			$ajustes  = ['transportista', 'codigo', 'fecha'];
			$opciones = $pedido->get_meta('seguimiento');

			foreach ($ajustes as $ajuste)
				$this->$ajuste = isset ($opciones[$ajuste]) ? $opciones[$ajuste]: false;
			}

		public function metabox () {

			$screen = HPOS_ACTIVO ? wc_get_page_screen_id('shop-order') : 'shop_order';

			add_meta_box ('seguimiento', __('Información de seguimiento', 'seguimiento-pedidos'), [$this, 'callback_metabox'], $screen, 'side', 'high');
			}

		public function callback_metabox ($objeto) {

			$pedido = ($objeto instanceof WP_Post) ? wc_get_order ($objeto->ID) : $objeto;

			$this->recupera_opciones($pedido);

			$obj_agencias = new Agencias_Seguimiento_Pedidos;
			$agencias     = $obj_agencias->get_agencias();
			$num_agencias = count ($agencias);

			?>

				<p>
					<select name="seguimiento[transportista]" id="transportista" class="seguimiento">

						<?php if (1 != $num_agencias) : ?>

							<option disabled="disabled" <?php selected ($this->transportista, false); ?> class="wide"><?php _e('Elige la agencia', 'seguimiento-pedidos'); ?></option>

						<?php endif; ?>

						<?php

							foreach ($agencias as $key => $agencia)
								echo '<option value="' . $key . '"' . selected ($this->transportista, $key) . '>' . $agencia['nombre'] . '</option>';

						?>

					</select>
				</p>

				<p>
					<label for="seguimiento[codigo]"><?php _e('Número de seguimiento'); ?></label><br />
					<input type="text" name="seguimiento[codigo]" id="seguimiento" class="seguimiento" value="<?php echo $this->codigo; ?>" />
				</p>

				<p>
					<label for="seguimiento[fecha]"><?php _e('Fecha de envío'); ?></label><br />
					<input type="date" name="seguimiento[fecha]" id="fecha_envio" class="seguimiento" value="<?php echo $this->fecha; ?>" />
				</p>

			<?php
			}

		public function guarda_datos ($id_pedido) {	

			if (isset ($_POST['seguimiento']) && is_array ($_POST['seguimiento']) && is_object ($pedido = wc_get_order ($id_pedido))) {

				$pedido->update_meta_data('seguimiento', $_POST['seguimiento']);
				$pedido->save();
				}
			}

		public function estilos_metabox () {

			if ('shop_order' == get_current_screen()->id || wc_get_page_screen_id('shop-order') == get_current_screen()->id)
				wp_enqueue_style ('seguimiento', plugins_url ('assets/css/pedido.min.css', __FILE__));
			}

		}

endif;