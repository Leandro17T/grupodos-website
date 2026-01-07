<?php

/**
 * Funciones referentes a los estados de pedido personalizados.
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 				Enrique J. Ros
 * @link 				https://www.enriquejros.com
 * @since 				1.0.0
 * @package 			EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Estados_Pedido_Personalizados')) :

	#[AllowDynamicProperties]

	Class Estados_Pedido_Personalizados {

		const ACCION = 'mark_';
		const ENVIO  = 'estado_personalizado_';

		public function __construct () {

			foreach ($this->estados = Estados_Pedido_CPT::pide_query() as $estado) {

				$titulo = $estado->post_title ? : esc_html (sprintf (__('Estado personalizado #%s', 'estados-pedido'), $estado->ID));

				//Registramos los nuevos estados de pedido.
				register_post_status ('wc-' . $estado->post_name,
					[
						'label'						=> $titulo,
						'public'					=> true,
						'exclude_from_search'		=> false,
						'show_in_admin_all_list'	=> true,
						'show_in_admin_status_list'	=> true,
						'label_count'				=> _n_noop ($titulo . ' <span class="count">(%s)</span>', $titulo . ' <span class="count">(%s)</span>'),
						]
					);

				//Disparamos las notificaciones desde la metabox de acciones del pedido.
				add_action ('woocommerce_order_action_notificaciones_' . $estado->post_name, function ($pedido) use ($estado) {

					$envio = WC()->mailer()->get_emails();

					if (null !== $envio[self::ENVIO . $estado->post_name]) //Mejor prevenir por si algún otro está filtrando mal.
						$envio[self::ENVIO . $estado->post_name]->trigger($this->devuelve_post_id($pedido->get_id()), true, $estado); //El true va a permitir enviarlo manualmente aunque la notificación esté deshabilitada en los ajustes de correo electrónico de WooCommerce.
					}, 10, 1);
				}

			//Añadimos los nuevos estados a la lista de estados de pedidos de Woo.
			add_filter ('wc_order_statuses', [$this, 'add_lista_estados'], 10, 1);

			//Los hacemos editables en caso necesario.
			add_filter ('wc_order_is_editable', [$this, 'estado_editable'], 10, 2);

			//Y añadimos un mensaje de ayuda indicándolo en los pedidos en este estado.
			add_action ('woocommerce_order_item_add_action_buttons', [$this, 'mensaje_pedido_editar'], 10, 1);

			//Permitimos las descargas de archivos en caso necesario.
			add_filter ('woocommerce_order_is_download_permitted', [$this, 'descarga_permitida'], 10, 2);

			//Añadimos un botón de acción para cambiar al estado correspondiente.
			add_filter ('woocommerce_admin_order_actions', [$this, 'boton_cambia_estado'], 10, 2);

			//Añadimos una opción en la metabox de acciones para enviar/reenviar las notificaciones.
			add_filter ('woocommerce_order_actions', [$this, 'metabox_acciones'], 10, 2);

			//Vamos a ponerles unos iconos.
			add_action ('wp_print_scripts', [$this, 'estilos_iconos'], 10);

			//Añadimos las acciones en lote.
			add_filter ('bulk_actions-edit-shop_order', [$this, 'add_acciones_lote'], 10, 1); //Sin HPOS.
			add_filter ('bulk_actions-woocommerce_page_wc-orders', [$this, 'add_acciones_lote_hpos'], 10, 1); //Con HPOS.

			//Definimos los manejadores para las acciones en lote.
			add_filter ('handle_bulk_actions-edit-shop_order', [$this, 'manejadores_acciones_lote'], 10, 3); //Sin HPOS.
			add_filter ('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'manejadores_acciones_lote'], 10, 3); //Con HPOS.

			//Añadimos un aviso para informar al usuario de los cambios en lote.
			add_action ('admin_notices', [$this, 'aviso_acciones_lote'], 10, 1);

			//Añadimos hooks de acción para los distintos estados personalizados.
			//Permitimos la descarga de archivos descargables cuando proceda.
			//Disparamos las notificaciones al pasar a un estado personalizado.
			//Tenemos que lanzar la notificación de Procesando si el pedido viene de un estado personalizado.
			//Guardamos los meta de completado si el pedido en ese estado se considera como tal
			add_action ('woocommerce_order_status_changed', [$this, 'cambio_estado'], 10, 4);

			//Incluimos los pedidos en estados personalizados en los informes de WooCommerce (método heredado) si es necesario.
			add_filter ('woocommerce_reports_order_statuses', [$this, 'estado_informes'], 10, 1);
			}

		public function add_lista_estados ($estados) {

			foreach ($this->estados as $estado)
				$estados['wc-' . $estado->post_name] = $estado->post_title ? : sprintf (__('Personalizado #%s', 'estados-pedido'), $estado->ID);

			return $estados;
			}

		/**
		 * Hace editables los pedidos en el estado personalizado si está configurado para ello.
		 *
		 * @since 	4.0.0
		 *
		 */
		public function estado_editable ($editable, $pedido) {

			foreach ($this->estados as $estado)
				if ($pedido->get_status() == $estado->post_name)
					if (($caracteristicas = get_field ('informes', $estado->ID)) && is_array ($caracteristicas) && in_array ('editable', $caracteristicas))
						return true; //Ya no hace falta seguir.

			return $editable;
			}

		/**
		 * Muestra un mensaje de ayuda en el pedido si está en un estado en el que no es editable.
		 *
		 * @since 	3.1.3
		 *
		 */
		public function mensaje_pedido_editar ($pedido) {

			foreach ($this->estados as $estado) {

				if ($pedido->get_status() == $estado->post_name) {

					if (($caracteristicas = get_field ('informes', $estado->ID)) && (!is_array ($caracteristicas) || !in_array ('editable', $caracteristicas)))
						printf ('<br><span class="description">%s</span>', sprintf (__('Para editar los pedidos en estado %s cambia la %sconfiguración del estado de pedido%s.', 'estados-pedido'), '&laquo;' . $estado->post_title . '&raquo;', '<a target="_blank" href="post.php?post=' . $estado->ID . '&action=edit">', '</a>'));

					return; //Ya no hace falta seguir.
					}
				}
			}

		/**
		 * Permite acceso a los archivos descargables del pedido en un estado personalizado.
		 *
		 * @since 	4.0.0
		 *
		 */
		public function descarga_permitida ($permitida, $pedido) {

			foreach ($this->estados as $estado)
				if ($pedido->get_status() == $estado->post_name)
					if (($caracteristicas = get_field ('informes', $estado->ID)) && is_array ($caracteristicas) && in_array ('descargas', $caracteristicas))
						return true; //Ya no hace falta seguir.

			return $permitida;
			}

		public function boton_cambia_estado ($acciones, $pedido) {
			
			$id_pedido = $this->devuelve_post_id($pedido->get_order_number()); //Por si hay activo algún plugin de numeración secuencial de pedidos.

			if (!$pedido->has_status('processing') && !isset ($acciones['processing']))
				$acciones['processing'] = [
					'url'		=> wp_nonce_url (admin_url ('admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $id_pedido), 'woocommerce-mark-order-status'),
					'name'		=> __('Processing', 'woocommerce'),
					'action'	=> 'processing',
					];

			if (!$pedido->has_status('completed') && !isset ($acciones['complete']))
				$acciones['complete'] = [
					'url'		=> wp_nonce_url (admin_url ('admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $id_pedido), 'woocommerce-mark-order-status'),
					'name'		=> __('Complete', 'woocommerce'),
					'action'	=> 'complete',
					];

			foreach ($this->estados as $estado) {

				if (!$pedido->has_status($estado->post_name)) //Sólo añadimos la acción a los pedidos que no están ya en ese estado.
					$acciones[$estado->post_name] = [
						'url'		=> wp_nonce_url (admin_url ('admin-ajax.php?action=woocommerce_mark_order_status&status=' . $estado->post_name . '&order_id=' . $id_pedido), 'woocommerce-mark-order-status'),
						'name'		=> sprintf (__('Marcar como %s', 'estados-pedido'), mb_strtolower ($estado->post_title ? : sprintf (__('Estado personalizado #%s', 'estados-pedido'), $estado->ID)), 'UTF-8'),
						'action'	=> $estado->post_name,
						];

				else //El estado en el que está el pedido.
					$sigestado = get_field ('sigestado', $estado->ID);
				}

			if (isset ($sigestado) && strlen ($sigestado)) {

				'wc-' == substr ($sigestado, 0, 3) and
					$sigestado = substr ($sigestado, 3);

				foreach ($this->estados as $estado)
					if ($estado->post_name == $sigestado)
						$siguiente = $estado->post_name;

				switch ($sigestado) {

					case 'completed':

						$accion_sig = $acciones['complete'];
						$accion_key = 'complete';

						break;

					case 'processing':

						$accion_sig = $acciones['processing'];
						$accion_key = 'processing';

						break;

					default:

						$accion_sig = $acciones[$siguiente];
						$accion_key = $siguiente;
						
						break;
					}

				unset ($acciones[$accion_key]); //Quitamos el botón de acción correspondiente al siguiente estado.
				array_unshift ($acciones, $accion_sig); //para ponerlo el primero.
				}

			/**
			 * Para ocultar el botón de acción de "Procesando":
			 *
			 * add_filter ('estados_pedido_oculta_accion_procesando', '__return_true');
			 *
			 */
			if (apply_filters ('estados_pedido_oculta_accion_procesando', false))
				unset ($acciones['processing']);

			/**
			 * Para ocultar el botón de acción de "Completado":
			 *
			 * add_filter ('estados_pedido_oculta_accion_completado', '__return_true');
			 *
			 */
			if (apply_filters ('estados_pedido_oculta_accion_completado', false))
				unset ($acciones['complete']);

			return $acciones;
			}

		public function metabox_acciones ($acciones) {

			if (!isset ($_GET['post']) && !isset ($_GET['id'])) //Sin HPOS es $_GET['post'], con HPOS es $_GET['id'].
				return $acciones;

			$acciones_dos = $acciones_final = [];
			$pedido       = new WC_Order(isset ($_GET['post']) ? $_GET['post'] : $_GET['id']); //Sin HPOS es $_GET['post'], con HPOS es $_GET['id'].
			$status       = $pedido->get_status();

			foreach ($this->estados as $estado)
				if ($cuantas = count (get_field ('notificaciones', $estado->ID)))
					$acciones_dos['notificaciones_' . $estado->post_name] = ($status == $estado->post_name) ? sprintf (_n('Volver a enviar el aviso de %s', 'Volver a enviar los avisos de %s', $cuantas, 'estados-pedido'), $estado->post_title ? mb_strtolower ($estado->post_title, 'UTF-8') : __('estado personalizado #', 'estados-pedido') . $estado->ID) : sprintf (_n('Enviar el aviso de %s', 'Enviar los avisos de %s', $cuantas, 'estados-pedido'), $estado->post_title ? mb_strtolower ($estado->post_title, 'UTF-8') : __('estado personalizado #', 'estados-pedido') . $estado->ID);

			foreach ($acciones as $accion => $etiqueta) {

				$acciones_final[$accion] = $etiqueta;

				if ('send_order_details_admin' == $accion)
					foreach ($acciones_dos as $accion_dos => $etiqueta_dos)
						$acciones_final[$accion_dos] = $etiqueta_dos;
				}

			return $acciones_final;
			}

		public function estilos_iconos () {
			
			if (((isset ($_GET['post_type']) && 'shop_order' == $_GET['post_type']) || (isset ($_GET['page']) && 'wc-orders' == $_GET['page'])) && count ($this->estados)) { //Sin HPOS es $_GET['post_type'], con HPOS es $_GET['page'].

				echo '<style type="text/css">';

				foreach ($this->estados as $estado) {

					$color = get_field ('color', $estado->ID) ? : '#1e73be';
					$campo = get_field ('icono', $estado->ID);
					$icono = isset ($campo) ? $campo['value'] : '159';

						?>
							.wc-action-button-<?php echo $estado->post_name; ?>::after {
								content:"\f<?php echo $icono; ?>";
								color:<?php echo $color; ?>!important;
							}
								
							.status-<?php echo $estado->post_name; ?>:not(.type-shop_order) {
								background-color:<?php echo $color; ?>;
								color:<?php echo get_field ('color_label', $estado->ID) ? : '#fff'; ?>;
							}
						<?php
					}

				echo '</style>';
				}
			}

		/**
		 * Declaramos las nuevas acciones en lote (método previo a HPOS).
		 *
		 * @since 2.1.1
		 *
		 */
		public function add_acciones_lote ($acciones) {

			if (!isset ($_GET['post_status']) || 'trash' != $_GET['post_status']) //En la papelera de pedidos no añadimos acciones.
				foreach ($this->estados as $estado)
					$acciones[self::ACCION . $estado->post_name] = sprintf (__('Cambiar estado a %s', 'estados-pedido'), mb_strtolower ($estado->post_title ? : sprintf (__('Personalizado #%s', 'estados-pedido'), $estado->ID), 'UTF-8'));

			return $acciones;
			}

		/**
		 * Declaramos las acciones a realizar en lote con HPOS.
		 *
		 * @since 5.0.0
		 *
		 */
		public function add_acciones_lote_hpos ($acciones) {

			if (isset ($_GET['status']) && 'trash' == $_GET['status']) //En la papelera de pedidos no añadimos acciones.
				return $acciones;

			$mis_acciones = [];
						
			foreach ($this->estados as $estado)
				$mis_acciones[self::ACCION . $estado->post_name] = sprintf (__('Cambiar estado a %s', 'estados-pedido'), mb_strtolower ($estado->post_title ? : sprintf (__('Personalizado #%s', 'estados-pedido'), $estado->ID), 'UTF-8'));
				
			foreach ($acciones as $accion => $nombre)
				if ('trash' != $accion)
					$mis_acciones[$accion] = $nombre;

			$mis_acciones['trash'] = $acciones['trash'];

			return $mis_acciones;
			}

		/**
		 * Definimos las acciones a realizar en lote mediante el modo nativo de WordPress 4.7+ (método válido también con HPOS).
		 * https://github.com/woocommerce/woocommerce/pull/35442
		 *
		 * @since 2.1.1
		 *
		 */
		public function manejadores_acciones_lote ($redirect, $accion, $ids) {

			foreach ($this->estados as $estado) {

				if (self::ACCION . $estado->post_name == $accion) {

					foreach ($ids as $id) {

						$pedido = new WC_Order($id);
						$pedido->update_status($estado->post_name, false, true);
						}

					$redirect = add_query_arg (self::ACCION . $estado->post_name, count ($ids), $redirect);
					}

				else
					$redirect = remove_query_arg (self::ACCION . $estado->post_name, $redirect);
				}

			return $redirect;
			}

		/**
		 * Añadimos un aviso para confirmar al usuario cuántos pedidos se han cambiado en lote (previo a HPOS).
		 *
		 * @since 2.1.1
		 *
		 */
		public function aviso_acciones_lote () {

			foreach ($this->estados as $estado) {

				if (isset ($_GET[self::ACCION . $estado->post_name])) {

					$cantidad = $_GET[self::ACCION . $estado->post_name];

					?>
						<div class="updated">
							<p><?php printf (_n('Se ha marcado %s pedido como %s.', 'Se han marcado %s pedidos como %s.', $cantidad, 'estados-pedido'), $cantidad, mb_strtolower ($estado->post_title ? : sprintf (__('Personalizado #%s', 'estados-pedido'), $estado->ID), 'UTF-8')); ?></p>
						</div>
					<?php
					}
				}
			}

		/**
		 * Creamos hooks de acción para el cambio a los diferentes estados personalizados.
		 * Permitimos la descarga de archivos descargables cuando proceda.
		 * Disparamos las notificaciones al pasar a un estado personalizado.
		 * Lanzamos la notificación de procesando si viene de un estado de pedido personalizado.
		 * Guardamos los meta de completado si el pedido en ese estado se considera como tal
		 *
		 * @since 2.5.4
		 *
		 */
		public function cambio_estado ($id_pedido, $desde_estado, $a_estado, $pedido) {

			$id_convertida = $this->devuelve_post_id($id_pedido); //Por si hay activo algún plugin de numeración secuencial de pedidos.

			foreach ($this->estados as $estado) {

				if ($a_estado == $estado->post_name) {

					//Si el estado permite la descarga de archivos descargables, la habilitamos.
					if (($incluir = get_field ('informes', $estado->ID)) && is_array ($incluir) && in_array ('descargas', $incluir))
						wc_downloadable_product_permissions ($id_convertida, true);

					//Guardamos los meta de completado si el pedido en ese estado se considera como tal
					if ($incluir && is_array ($incluir) && in_array ('completado', $incluir)) {

						$momento = time();
						$pedido->set_date_paid($momento, true);
						$pedido->update_meta_data('_completed_date', date ('Y-m-d H:i:s', $momento));
						$pedido->save();
						}

					//Creamos el hook de acción para el cambio al estado personalizado.
					do_action (self::ENVIO . $estado->post_name, $id_convertida);

					//Disparamos las notificaciones al pasar al estado personalizado.
					$envio = WC()->mailer()->get_emails();

					if (null !== $envio[self::ENVIO . $estado->post_name])
						$envio[self::ENVIO . $estado->post_name]->trigger($id_convertida);
					}

				//Lanzamos la notificación de procesando si viene de un estado de pedido personalizado.
				if ($pedido->has_status('processing') && $desde_estado == $estado->post_name) {

					$envio = WC()->mailer()->get_emails();
					$envio['WC_Email_Customer_Processing_Order']->trigger($id_convertida);
					}
				}
			}

		public function estado_informes ($estados) {

			if ($estados) //Si no añadimos este condicional, por algún motivo deja de tener en cuenta los refunds en los informes.
				foreach ($this->estados as $estado)
					if (($incluir = get_field ('informes', $estado->ID)) && is_array ($incluir) && in_array ('incluir', $incluir))
						$estados[] = $estado->post_name;

			return $estados;
			}

		/**
		 * Devuelve el número de post a partir del número de pedido.
		 * Puede no coincidir si hay activo un plugin de números de pedido secuenciales.
		 *
		 * @since 	2.3.0
		 *
		 * @param 	int|string 		ID
		 * @return 	int|string 		ID
		 *
		 */
		private function devuelve_post_id ($id_pedido) {

			/**
			 * Sequential Order Numbers for WooCommerce (SkyVerge).
			 * https://wordpress.org/plugins/woocommerce-sequential-order-numbers/
			 * 
			 */
			if (class_exists ('WC_Seq_Order_Number')) {

				if (class_exists ('wc_sequential_order_numbers'))
					return wc_sequential_order_numbers()->find_order_by_order_number($id_pedido);

				else
					$meta = '_order_number';
				}

			/**
			 * WooCommerce Sequential Order Numbers Pro (SkyVerge).
			 * https://woocommerce.com/products/sequential-order-numbers-pro/
			 * 
			 */
			else if (class_exists ('WC_Seq_Order_Number_Pro')) {
				
				if (class_exists ('wc_seq_order_number_pro'))
					return wc_seq_order_number_pro()->find_order_by_order_number($id_pedido);

				else
					$meta = '_order_number_formatted';
				}

			/**
			 * YITH Sequential Order Number (YITH).
			 * https://yithemes.com/themes/plugins/yith-woocommerce-sequential-order-number/
			 * 
			 */
			else if (function_exists ('YITH_Sequential_Order_Number_Premium_Init'))
				$meta = '_ywson_custom_number_order_complete';

			/**
			 * Sequential Order Number for WooCommerce (WebToffee).
			 * https://wordpress.org/plugins/wt-woocommerce-sequential-order-numbers/
			 * 
			 */
			else if (function_exists ('run_wt_advanced_order_number'))
				$meta = '_order_number';

			else
				return $id_pedido;

			global $wpdb;

			if (HPOS_ACTIVO) { //Con HPOS.

				$order_number = $wpdb->get_results("SELECT order_id FROM $wpdb->wc_orders_meta WHERE meta_key = '$meta' AND meta_value = '$id_pedido'", ARRAY_A);
	
				return isset ($order_number[0]['order_id']) ? $order_number[0]['order_id'] : $id_pedido;
				}

			else { //Sin HPOS.

				$order_number = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '$meta' AND meta_value = '$id_pedido'", ARRAY_A);

				return isset ($order_number[0]['post_id']) ? $order_number[0]['post_id'] : $id_pedido;
				}
			}

		}

endif;