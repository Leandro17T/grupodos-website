<?php

/**
 * Envío de las notificaciones.
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * https://woocommerce.github.io/code-reference/classes/WC-Email.html
 *
 * @author 			Enrique J. Ros
 * @link			https://www.enriquejros.com
 * @since			3.0.0
 * @package			EstadosPedido
 * 
 * TO DO: Compatibilidad con el filtro woocommerce_get_order_item_totals (wc-peso-total)
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Notificacion_Estados_Pedido')) :

	#[AllowDynamicProperties]

	Class Notificacion_Estados_Pedido extends WC_Email {

		public function __construct ($estado, $notificaciones) {

			$this->estado         = $estado;
			$this->notificaciones = $notificaciones;

			$this->id             = 'estado_personalizado_' . $this->estado->post_name;
			$this->enabled        = $this->enabled && is_array ($this->notificaciones);
			$this->plain          = false; //¿Es en texto plano?
			$this->customer_email = in_array ('cliente', $this->notificaciones);
			$this->title          = sprintf ('%s %s', esc_html (__('Order', 'woocommerce')), $this->estado->post_title ? mb_strtolower ($this->estado->post_title, 'UTF-8') : esc_html (__('en estado Personalizado #', 'estados-pedido')) . $this->estado->ID);
			$this->description    = sprintf (__('Notificación del estado de pedido %s', 'estados-pedido'), $this->estado->post_title ? : __('personalizado #', 'estados-pedido') . $this->estado->ID);
			$this->subject        = esc_html (get_field ('asunto', $this->estado->ID));
			$this->heading        = $this->get_heading() ? : esc_html (get_field ('heading', $this->estado->ID)) ? : $this->subject;
			$this->text_align     = is_rtl() ? 'right' : 'left';

			if (in_array ('admin', $this->notificaciones)) {

				if ($emails_admin = get_field ('email_admin', $this->estado->ID))
					$this->recipient = str_replace (';', ',', $emails_admin);

				else
					$this->recipient = get_option ('admin_email');
				}

			WC_Email::__construct(); //parent::__construct() está obsoleto desde PHP5.3.
			}

		public function trigger ($id_pedido, $manual = false, $estado = false) {

			if (!$this->is_enabled() && !$manual) //Si se ha forzado manualmente la enviamos, aunque la notificación esté deshabilitada.
				return;

			$this->id_pedido = $id_pedido;
			$this->object    = wc_get_order ($this->id_pedido); //El pedido.
			$this->fecha     = date_i18n (wc_date_format(), strtotime ($this->object->get_date_created()));

			if ($this->customer_email) {

				$this->enviar_cliente = true;
				$this->send($this->object->get_billing_email(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->adjunta_archivos());
				}

			if ($this->recipient) {

				$this->enviar_cliente = false;

				foreach (explode (',', $this->recipient) as $email_admin)
					$this->send($email_admin, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->adjunta_archivos());
				}

			$manual and //Si se envían manualmente las notificaciones, añadimos una nota al pedido.
				$this->object->add_order_note(esc_html (sprintf (__('Enviadas manualmente las notificaciones del estado %s', 'estados-pedido'), $estado->post_title ? : esc_html (sprintf (__('personalizado #%s', 'estados-pedido'), $estado->ID)))));
			}

		public function get_subject () {

			return $this->establece_variables($this->subject);
			}

		public function get_content_html () {

			ob_start();
			do_action ('woocommerce_email_header', $this->heading, $this);
			echo $this->plantilla = get_field ('plantilla', $this->estado->ID);

			if ($this->settings['additional_content']) //Opción de contenido adicional de los ajustes de correo electrónico de WC.
				printf ('<p>%s</p>', $this->settings['additional_content']);

			do_action ('woocommerce_email_footer', $this);

			return $this->establece_variables(ob_get_clean());
			}

		public function get_content_plain () {

			$this->plain = true;

			ob_start();

			$linea = '';

			for ($i = 0; $i < floor (strlen ($this->establece_variables($this->heading)) / 2); $i++)
				$linea .= '=-';

			echo $linea . "=\n";
			echo $this->heading . "\n";
			echo $linea . "=\n";

			echo $this->plantilla = get_field ('plantilla', $this->estado->ID);

			//Compatibilidad con el plugin de seguimiento de envío.
			if (class_exists ('Agencias_Seguimiento_Pedidos') && is_array ($seguimiento = get_field ('seguimiento', $this->estado->ID)) && in_array ('incluir', $seguimiento) && !strpos ($this->plantilla, '%%seguimiento%%'))
				echo $this->seguimiento();

			if ($this->settings['additional_content']) //Opción de contenido adicional de los ajustes de correo electrónico de WC.
				echo "\n" . $this->settings['additional_content'] . "\n";

			echo apply_filters ('woocommerce_email_footer_text', get_option ('woocommerce_email_footer_text'));

			return html_entity_decode (strip_tags (str_replace (['<br>', '<p>', '€', '&euro;'], ["\n", "\n\n", 'EUR', 'EUR'], $this->establece_variables(ob_get_clean()))));
			}

		private function adjunta_archivos () {

			$array_adjuntos = $this->get_attachments();

			if ($adjuntos = get_field ('adjuntos', $this->estado->ID))
				foreach ($adjuntos as $adjunto)
					$array_adjuntos[] = $adjunto['adjunto'];

			return $array_adjuntos;
			}

		private function establece_variables ($texto) {

			$num_pedido = $this->devuelve_num_pedido($this->id_pedido);
			$direccion  = get_bloginfo ('url');
			$variables  = [
				'%%cliente%%'			=> $this->object->get_billing_first_name(),
				'%%apellidos%%'			=> $this->object->get_billing_last_name(),
				'%%empresa%%'			=> $this->object->get_billing_company(),
				'%%pedido%%'  			=> $num_pedido,
				'%%email%%'   			=> $this->object->get_billing_email(),
				'%%tabla%%'	  			=> $this->crea_tabla_pedido(),
				'%%notas%%'				=> $this->object->get_customer_note() ? : __('No hay notas del pedido.', 'estados-pedido'),
				'%%descargas%%'			=> $this->crea_tabla_descargas(),
				'%%total%%'   			=> html_entity_decode (strip_tags (wc_price ($this->object->get_total()))),
				'%%datos_facturacion%%'	=> $this->crea_bloque_datos('facturacion'),
				'%%datos_envio%%'		=> $this->crea_bloque_datos('envio') ? : $this->crea_bloque_datos('facturacion'), //Si no se han establecido, son los mismos que los de facturación.
				'%%telefono%%'			=> $this->plain ? $this->object->get_billing_phone() : sprintf ('<span class="telefono"><a href="tel:%s">%s</a></span>', $this->object->get_billing_phone(), $this->object->get_billing_phone()),
				'%%metodo_envio%%'		=> $this->object->get_shipping_method(),
				'%%metodo_pago%%'		=> $this->object->get_payment_method_title(),
				'%%instrucciones%%'		=> $this->instrucciones_metodo_pago(),
				'%%fecha_pedido%%'		=> $this->fecha,

				//También añadimos los marcadores de posición que establece WooCommerce.
				'{order_date}'			=> $this->fecha,
				'{order_number}'		=> $num_pedido,
				'{site_title}'			=> get_bloginfo ('name'),
				'{site_address}'		=> $direccion,
				'{site_url}'			=> $direccion,
				];

			/**
			 * Incluye un marcador de posición para el NIF.
			 * 
			 * @since	5.1.6
			 * 
			 */
			if (class_exists ('Plugin_Pide_NIF') || class_exists ('APG_Campo_NIF'))
				$variables['%%nif%%'] = $this->object->get_meta('_billing_nif');

			/**
			 * Incluye un marcador de posición para el EU VAT.
			 * 
			 * @since	5.1.9
			 * 
			 */
			if (class_exists ('WC_EU_VAT_Number_Init')) //WooCommerce EU VAT Number
				$variables['%%euvat%%'] = $this->object->get_meta('_billing_vat_number');

			else if (class_exists ('YITH_WooCommerce_EU_VAT')) //YITH WooCommerce EU VAT, OSS & IOSS Premium
				$variables['%%euvat%%'] = $this->object->get_meta('yweu_billing_vat');

			/**
			 * Incluye los marcadores de posición para el plugin de seguimiento de pedidos.
			 * 
			 */
			if (class_exists ('Agencias_Seguimiento_Pedidos')) {

				$this->crea_info_seguimiento();

				$array_seguimiento = [
					'%%agencia%%'	  => $this->agencia,
					'%%codigo%%'	  => $this->codigo,
					'%%fecha_envio%%' => $this->f_envio,
					'%%url_envio%%'   => $this->url_envio,
					'%%boton_envio%%' => $this->b_envio,
					];

				if (strpos ($texto, '%%seguimiento%%')) { //Retrocompatibilidad con 3.2.0.

					$plantilla_seguimiento = get_field ('plantillaseguimiento', $this->estado->ID);

					foreach ($array_seguimiento as $var_seguimiento => $dato_seguimiento)
						$plantilla_seguimiento = str_replace ($var_seguimiento, $dato_seguimiento, $plantilla_seguimiento);

					$texto = str_replace ('%%seguimiento%%', $plantilla_seguimiento, $texto);
					}

				else
					$variables = array_merge ($variables, $array_seguimiento);
				}

			/**
			 * Para añadir nuevas variables:
			 *
			 * add_filter ('estados_pedido_variables_email', function ($variables, $pedido, $texto_plano) {
			 *
			 * 		$id_pedido = $pedido->get_id();
			 * 		$variables['%%nueva_variable%%']  = $texto_plano ? $valor_variable_texto_plano : $valor_variable_html;
			 * 		$variables['%%nueva_variable2%%'] = $valor_variable2;
			 *
			 * 		return $variables;
			 * 		}, 10, 3);
			 *
			 */
			foreach (apply_filters ('estados_pedido_variables_email', $variables, $this->object, $this->plain) as $variable => $valor)
				if (isset ($variable) && isset ($valor))
					$texto = str_replace ($variable, $valor, $texto);

			return $texto;
			}

		/**
		 * Devuelve la tabla resumen del pedido.
		 *
		 * @since 3.0.0
		 *
		 */
		private function crea_tabla_pedido () {

			$args = apply_filters ('woocommerce_email_order_items_args',
				[
					'order'					=> $this->object,
					'items'					=> $this->object->get_items(),
					'show_download_links'	=> $this->object->is_download_permitted() && $this->customer_email,
					'show_sku'				=> false,
					'show_image'			=> false,
					'image_size'			=> [32, 32],
					'plain_text'			=> $this->plain,
					'sent_to_admin'			=> in_array ('admin', $this->notificaciones),
					]
				);

			ob_start();
			do_action ('woocommerce_email_before_order_table', $this->object, in_array ('admin', $this->notificaciones), $this->plain, $this);

			if ($this->plain) : //Notificaciones en texto plano.

				/**
				 * Para cambiar el título de la sección de tabla resumen:
				 *
				 * add_filter ('estados_pedido_titulo_tabla', function ($titulo, $id_pedido, $texto_plano) {
				 *		return 'Nuevo título';
				 *		}, 10, 3);
				 *
				 */
				echo apply_filters ('estados_pedido_titulo_tabla', sprintf ('[%s #%s] (%s)', strtoupper (__('Order', 'woocommerce')), $this->devuelve_num_pedido($this->id_pedido), strtoupper ($this->fecha)), $this->devuelve_num_pedido($this->id_pedido), $this->plain);

				echo "\n";
				echo wc_get_email_order_items ($this->object, $args);
				echo "==========\n";

				foreach ($this->object->get_order_item_totals() as $linea_total => $datos_linea)
					printf ('%s %s%s', $datos_linea ['label'], $datos_linea['value'], "\n");

				echo "\n----------------------------------------\n";

			else : //Notificaciones en HTML.

				$admin = sprintf (HPOS_ACTIVO ? 'admin.php?page=wc-orders&action=edit&id=%s' : 'post.php?post=%s&action=edit', $this->id_pedido);
				$link  = $this->enviar_cliente ? $this->object->get_view_order_url() : admin_url ($admin);

				/**
				 * Para cambiar el título de la sección de tabla resumen:
				 *
				 * add_filter ('estados_pedido_titulo_tabla', function ($titulo, $id_pedido, $texto_plano) {
				 *		return 'Nuevo título';
				 *		}, 10, 3);
				 *
				 */
				?>

					<h2><?php echo apply_filters ('estados_pedido_titulo_tabla', sprintf ('<a target="_blank" href="%s" style="font-weight:bold">[%s #%s]</a> (%s)', $link, __('Order', 'woocommerce'), $this->devuelve_num_pedido($this->id_pedido), $this->fecha), $this->devuelve_num_pedido($this->id_pedido), $this->plain); ?></h2>

					<div style="margin-bottom:40px">

						<table class="td" cellspacing="0" cellpadding="6" border="1" style="border-width:1px;border-style:solid;vertical-align:middle;width:100%">
							<thead>
								<tr>
									<th class="td" scope="col" style="border-width:1px;border-style:solid;vertical-align:middle;padding:12px;text-align:<? echo $this->text_align; ?>"><?php esc_html_e('Product', 'woocommerce'); ?></th>
									<th class="td" scope="col" style="border-width:1px;border-style:solid;vertical-align:middle;padding:12px;text-align:<? echo $this->text_align; ?>"><?php esc_html_e('Quantity', 'woocommerce'); ?></th>
									<th class="td" scope="col" style="border-width:1px;border-style:solid;vertical-align:middle;padding:12px;text-align:<? echo $this->text_align; ?>"><?php esc_html_e('Price', 'woocommerce'); ?></th>
								</tr>
							</thead>

							<tbody>

								<?php echo wc_get_email_order_items ($this->object, $args); ?>

							</tbody>

							<tfoot>

								<?php

									foreach ($this->object->get_order_item_totals() as $linea_total => $datos_linea)
										printf ('
											<tr>
												<th class="td" scope="row" colspan="2" style="border-width:1px;border-style:solid;vertical-align:middle;padding:12px;text-align:%s;border-top-width:4px">
													%s
												</th>
												<td class="td" style="border-width:1px;border-style:solid;vertical-align:middle;padding:12px;text-align:%s;border-top-width:4px">
													%s
												</td>
											</tr>',
											$this->text_align,
											$datos_linea ['label'],
											$this->text_align,
											$datos_linea['value']
											);

								?>

							</tfoot>
						</table>

					</div>

				<?php

			endif;

			do_action ('woocommerce_email_after_order_table', $this->object, in_array ('admin', $this->notificaciones), $this->plain, $this);

			return ob_get_clean();
			}

		/**
		 * Devuelve la descripción del método de pago.
		 * Si se paga mediante transferencia, devuelve también el número de cuenta.
		 *
		 * @since 5.0.0
		 *
		 */
		private function instrucciones_metodo_pago () {

			$metodo        = $this->object->get_payment_method();
			$metodos       = WC()->payment_gateways()->payment_gateways();
			$instrucciones = isset ($metodos[$metodo]->instructions) ? sprintf ('<p>%s</p>', $metodos[$metodo]->instructions) : '';

			if ('bacs' == $metodo) { //Sólo si el método de pago para el pedido es la transferencia bancaria.

				//$instrucciones = json_encode ($metodos[$metodo]);

				$datos = [
					'account_number' => __('Account number', 'woocommerce'),
					'bank_name'		 => __('Bank name', 'woocommerce'),
					'sort_code'		 => __('Sort code', 'woocommerce'),
					'iban'			 => __('IBAN', 'woocommerce'),
					'bic'			 => __('BIC / Swift', 'woocommerce'),
					];

				foreach ($metodos['bacs']->account_details as $cuenta) { //Puede haber más de una cuenta bancaria.

					$relleno = false;

					foreach ($datos as $dato => $texto_dato) {

						if (isset ($cuenta[$dato]) && strlen ($cuenta[$dato])) {

							$relleno = true;
							break; //Con que haya uno relleno ya no comprobamos más, hay que mostrar datos de esta cuenta.
							}
						}

					if (!$relleno)
						continue; //Si no se ha rellenado ningún dato para esta cuenta, seguimos con la siguiente.

					$instrucciones .= '<p>';

					foreach ($datos as $dato => $texto_dato)
						if (isset ($cuenta[$dato]) && strlen ($cuenta[$dato]))
							$instrucciones .= sprintf ('%s: %s<br>',  $texto_dato, $cuenta[$dato]);

					$instrucciones .= '</p>';
					}
				}

			return $this->plain ? str_replace (['<p>', '</p>', '<br>'], ["\n", "\n", "\n"], $instrucciones) : $instrucciones;
			}

		/**
		 * Devuelve la tabla de descargas.
		 * https://woocommerce.wp-a2z.org/oik_api/wc_orderget_downloadable_items/
		 * woocommerce/templates/emails/plain/email-downloads.php
		 *
		 * @since 4.0.0
		 *
		 */
		private function crea_tabla_descargas () {

			if (!$this->object->has_downloadable_item() || !$this->object->is_download_permitted()) //Si el pedido no tiene archivos descargables o la descarga no está permitida nos vamos de aquí.
				return '';

			$descargas = $this->object->get_downloadable_items();
			$columnas  = apply_filters ('woocommerce_email_downloads_columns',
				[
					'download-product' => __('Product', 'woocommerce'),
					'download-expires' => __('Expires', 'woocommerce'),
					'download-file'    => __('Download', 'woocommerce'),
					]
				);

			ob_start();

			if ($this->plain) : //Notificaciones en texto plano.

				/**
				 * Para cambiar el título de la sección de descargas:
				 *
				 * add_filter ('estados_pedido_titulo_descargas', function ($titulo, $id_pedido, $texto_plano) {
				 *		return 'Nuevo título';
				 *		}, 10, 3);
				 *
				 */
				echo apply_filters ('estados_pedido_titulo_descargas', strtoupper (esc_html (__('Downloads', 'woocommerce'))), $this->devuelve_num_pedido($this->id_pedido), $this->plain);
				echo "\n";

				foreach ($descargas as $descarga) {

					foreach ($columnas as $columna_id => $columna_nombre) {

						printf ('%s: ', $columna_nombre);

						if (has_action ('woocommerce_email_downloads_column_' . $columna_id))
							do_action ('woocommerce_email_downloads_column_' . $columna_id, $descarga, true); //$this->plain

						else {

							switch ($columna_id) {

								case 'download-product':

									echo $descarga['product_name'];

									break;

								case 'download-file':

									printf ('%s - %s', $descarga['download_name'], $descarga['download_url']);

									break;

								case 'download-expires':

									if (isset ($descarga['access_expires']))
										echo date_i18n (wc_date_format(), strtotime ($descarga['access_expires']));

									else
										esc_html_e('Never', 'woocommerce');

									break;
								}
							}

						echo "\n"; //Fin de columna.
						}

					echo "\n"; //Fin de descarga.
					}

			else : //Notificaciones en HTML.

				/**
				 * Para cambiar el título de la sección de descargas:
				 *
				 * add_filter ('estados_pedido_titulo_descargas', function ($titulo, $id_pedido, $texto_plano) {
				 *		return 'Nuevo título';
				 *		}, 10, 3);
				 *
				 */
				printf ('<h2 class="woocommerce-order-downloads__title">%s</h2>', apply_filters ('estados_pedido_titulo_descargas', esc_html (__('Downloads', 'woocommerce')), $this->devuelve_num_pedido($this->id_pedido), $this->plain));
				echo '<table class="td" cellspacing="0" cellpadding="6" style="width:100%;font-family:\'Helvetica Neue\',Helvetica,Roboto,Arial,sans-serif;margin-bottom:40px" border="1"><thead><tr>';

				foreach ($columnas as $columna_id => $columna_nombre)
					printf ('<th class="td" scope="col" style="text-align:%s">%s</th>', $this->text_align, esc_html ($columna_nombre));

				echo '</tr></thead><tbody>';

				foreach ($descargas as $descarga) {

					echo '<tr>';

					foreach ($columnas as $columna_id => $columna_nombre) {

						printf ('<td class="td" style="text-align:%s">', $this->text_align);

						if (has_action ('woocommerce_email_downloads_column_' . $columna_id))
							do_action ('woocommerce_email_downloads_column_' . $columna_id, $descarga, false); //$this->plain

						else {

							switch ($columna_id) {

								case 'download-product':

									printf ('<a href="%s">%s</a>', esc_url (get_permalink ($descarga['product_id'])), wp_kses_post ($descarga['product_name']));

									break;

								case 'download-file':

									printf ('<a href="%s" class="woocommerce-MyAccount-downloads-file button alt">%s</a>', esc_url ($descarga['download_url']), esc_html ($descarga['download_name']));
									
									break;

								case 'download-expires':

									if (isset ($descarga['access_expires']))
										printf ('<time datetime="%s" title="%s">%s</time>', date ('Y-m-d', strtotime ($descarga['access_expires'])), strtotime ($descarga['access_expires']), esc_html (date_i18n (wc_date_format(), strtotime ($descarga['access_expires']))));

									else
										esc_html_e ('Never', 'woocommerce');

									break;
								}
							}

						echo '</td>';
						}

					echo '</tr>';
					}

				echo '</tbody></table>';

			endif;

			return ob_get_clean();
			}

		/**
		 * Devuelve el bloque de datos de facturación/envío.
		 *
		 * @since 	3.1.4
		 *
		 * @param 	string ('facturacion' || 'envio')
		 * @return 	string
		 *
		 */
		private function crea_bloque_datos ($datos) {

			$bloque = '';
			
			if ('facturacion' == $datos) {

				$direccion = $this->object->get_formatted_billing_address() and
					$bloque = sprintf ('%s<br>', $direccion);

				if (null == ($nif = $this->object->get_meta('_billing_nif')))
					$nif = $this->object->get_meta('NIF');
					
				if (isset ($nif) && strlen ($nif))
					$bloque .= sprintf ('%s: %s<br>', null != ($campo_nif = get_option ('nif_texto_campo')) ? $campo_nif : __('NIF/CIF', 'estados-pedido'), esc_html ($nif));

				if (null !== ($euvat = $this->object->get_meta('_billing_vat_number')) && strlen ($euvat)) //WooCommerce EU VAT Number
					$bloque .= sprintf ('%s: %s<br>', null != ($campo_euvat = get_option ('woocommerce_eu_vat_number_field_label')) ? $campo_euvat : __('IVA europeo', 'estados-pedido'), $euvat);

				if (null !== ($euvat = $this->object->get_meta('yweu_billing_vat')) && strlen ($euvat)) //YITH WooCommerce EU VAT, OSS & IOSS Premium
					$bloque .= sprintf ('%s: %s<br>', null != ($campo_euvat = get_option ('ywev_eu_vat_field_label')) ? $campo_euvat : __('IVA europeo', 'estados-pedido'), $euvat);
				}

			else if ('envio' == $datos)
				$direccion = $this->object->get_formatted_shipping_address() and
					$bloque = sprintf ('%s<br>', $direccion);

			return $this->plain ? str_replace ('<br>', "\n", $bloque) : sprintf ('<p class="direccion-%s">%s</p>', $datos, $bloque);
			}

		/**
		 * Devuelve la información de seguimiento del envío.
		 *
		 * @since 	3.1.2 
		 * 
		 */
		private function crea_info_seguimiento () {

			$opciones = $this->object->get_meta('seguimiento');
			$falta    =  esc_html (__('[Consultar]', 'estados-pedido'));

			if (!$opciones || !isset ($opciones['transportista'])) {

				$this->agencia = $this->codigo = $this->f_envio = $this->url_envio = $falta;
				$this->b_envio = '';
				return false;
				}

			$obj_envio = new Agencias_Seguimiento_Pedidos;
			$agencia   = $obj_envio->get_agencias($opciones['transportista']);

			$this->agencia = isset ($agencia['nombre']) ? $agencia['nombre'] : $falta;
			$this->codigo  = (isset ($opciones['codigo']) && strlen ($opciones['codigo'])) ? $opciones['codigo'] : $falta;
			$this->f_envio = (isset ($opciones['fecha']) && strlen ($opciones['fecha'])) ? date_i18n (wc_date_format(), strtotime ($opciones['fecha'])) : $falta;

			$this->url_envio = $obj_envio->url_seguimiento($agencia, $opciones['codigo'], $this->id_pedido) and
				$this->b_envio = $this->plain ? $this->url_envio : sprintf ('<a target="_blank" href="%s" class="button" style="text-decoration:none">%s</a>', $this->url_envio, esc_html (__('Ver el estado del envío', 'estados-pedido')));

			return true;
			}

		/**
		 * Integración con el plugin de seguimiento de envíos.
		 * Método obsoleto desde la versión 3.1.2, se mantiene por razones de retrocompatibilidad.
		 * 
		 */
		private function seguimiento () {

			if (!$opciones = $this->object->get_meta('seguimiento'))
				return '';

			if (!isset ($opciones['transportista']) || !strlen ($opciones['codigo'])) //No juntar los dos if
				return '';

			$obj_envio = new Agencias_Seguimiento_Pedidos;
			$agencia   = $obj_envio->get_agencias($opciones['transportista']);
			$nombre    = isset ($agencia['nombre']) ? $agencia['nombre'] : false;
			$url       = $obj_envio->url_seguimiento($agencia, $opciones['codigo'], $this->id_pedido);

			if ($this->plain) {

				$titulo      = esc_html (__('INFORMACIÓN DE SEGUIMIENTO DEL ENVÍO', 'estados-pedido'));
				$seguimiento = "\n" . $titulo . "\n";

				for ($i = 0; $i < strlen ($titulo); $i++)
					$seguimiento .= '=';

				$seguimiento .= "\n" . sprintf (__('Su pedido ha sido enviado a través de %s, con el código de seguimiento %s. Puede conocer el estado del envío a través del siguiente enlace: %s', 'estados-pedido'), $nombre, $opciones['codigo'], $url) . "\n";
				}

			else {

				$seguimiento   = sprintf ('<h2>%s</h2>', esc_html (__('Información de seguimiento del envío', 'estados-pedido')));
				$seguimiento  .= '<p>' . sprintf (__('Su pedido ha sido enviado a través de %s, con el código de seguimiento %s. Puede conocer el estado del envío a través del siguiente enlace:', 'estados-pedido'), '<b>' . $nombre . '</b>', '<b>' . $opciones['codigo'] . '</b>') . '</p>';
				$seguimiento  .= sprintf ('<p><a target="_blank" href="%s" class="button" style="text-decoration:none">%s</a></p>', $url, __('Ver el estado del envío', 'estados-pedido'));
				}

			return $seguimiento;
			}

		/**
		 * Devuelve el número de pedido configurado a partir del número de pedido real.
		 * Puede no coincidir si hay activo un plugin de números de pedido secuenciales.
		 *
		 * @param 	int|string 		ID
		 * @return 	int|string 		ID
		 *
		 * @since 	2.5.0
		 *
		 */
		private function devuelve_num_pedido ($id_pedido) {

			/**
			 * Sequential Order Numbers for WooCommerce (SkyVerge).
			 * https://wordpress.org/plugins/woocommerce-sequential-order-numbers/
			 * 
			 */
			if (class_exists ('WC_Seq_Order_Number'))
				$meta = '_order_number';

			/**
			 * WooCommerce Sequential Order Numbers Pro (SkyVerge).
			 * https://woocommerce.com/products/sequential-order-numbers-pro/
			 * 
			 */
			else if (class_exists ('WC_Seq_Order_Number_Pro'))
				$meta = '_order_number_formatted';

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

			return $this->object->get_meta($meta);
			}

		}

endif;