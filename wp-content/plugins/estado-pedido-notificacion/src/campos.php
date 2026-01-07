<?php

/**
 * Campos personalizados.
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			1.0.0
 * @package 		EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Campos_Estados_Pedido')) :

	Class Campos_Estados_Pedido {

		public function __construct () {

			//Si no se puede cargar ACF da error y sale.
			if (!function_exists ('acf_add_local_field_group') && !function_exists ('register_field_group')) {

				add_action ('admin_notices', [$this, 'error_acf'], 10);
				return;
				}

			$this->inserta_campos();

			add_filter ('acf/settings/remove_wp_meta_box', '__return_false');
			}

		public function error_acf () {

			?>

				<div class="notice notice-error is-dismissible">
					<p><?php printf (__('El plugin %s no ha podido recuperar la lista de campos personalizados. Por favor, contacta con el %ssoporte%s.', 'estados-pedido'), '<i>' . __('Estados de pedido con notificación', 'estados-pedido') . '</i>', '<a target="_blank" href="https://www.enriquejros.com/soporte/">', '</a>'); ?></p>
				</div>

			<?php
			}

		public function inserta_campos () {

			$fields = array_merge (
				$this->campos_tab_aspecto(),
				$this->campos_tab_notificaciones(),
				$this->campos_tab_caracteristicas()
				);

			apply_filters ('estados_pedido_carga_automatizaciones', true) and 
				$fields = array_merge ($fields, $this->campos_tab_automatizaciones());

			$campos = [
				'key' 		=> 'group_59e21e03d72ee',
				'title' 	=> sprintf ('<span style="text-align:left!important"><span class="dashicons dashicons-admin-tools"></span> %s</span>', __('Configuración del estado de pedido', 'estados-pedido')),
				'fields' 	=> $fields,
				'location'	=> [
					[
						[
							'param' 	=> 'post_type',
							'operator' 	=> '==',
							'value' 	=> Estados_Pedido_CPT::CPT,
							],
						],
					],
				'menu_order' 			=> 0,
				'position' 				=> 'normal',
				'style' 				=> 'default',
				'label_placement' 		=> 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' 		=> '',
				'active' 				=> 1,
				'description' 			=> '',
				];

			function_exists ('acf_add_local_field_group') ? acf_add_local_field_group ($campos) : register_field_group ($campos);
			}

		private function campos_tab_aspecto () {

			return [

				[//Pestaña "Aspecto".
					'key' 				=> 'field_508f351db2cb9',
					'label' 			=> __('Aspecto', 'estados-pedido'),
					'name' 				=> '',
					'type' 				=> 'tab',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'placement' 		=> 'top',
					'endpoint' 			=> 0,
					],

				
				[//Campo de color del estado.
					'key' 				=> 'field_59e21e12b35d0',
					'label' 			=> __('Color del estado de pedido', 'estados-pedido'),
					'name' 				=> 'color',
					'type' 				=> 'color_picker',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'default_value' 	=> '#1e73be',
					],

				[//Campo de color del texto.
					'key' 				=> 'field_59c23e12e36a0',
					'label' 			=> __('Color del texto de la etiqueta', 'estados-pedido'),
					'name' 				=> 'color_label',
					'type' 				=> 'color_picker',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'default_value' 	=> '#fff',
					],

				[//Campo de icono.
					'key' 				=> 'field_56e212fcf3ca0',
					'label' 			=> __('Icono', 'estados-pedido'),
					'name' 				=> 'icono',
					'type' 				=> 'radio',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'choices' 			=> $this->devuelve_iconos(),
					'allow_custom' 		=> 0,
					'save_custom' 		=> 0,
					'default_value' 	=> [],
					'layout' 			=> 'horizontal',
					'toggle' 			=> 0,
					'return_format' 	=> 'array',
					],

				];
			}

		private function campos_tab_notificaciones () {

			return [

				[//Pestaña "Notificaciones".
					'key' 				=> 'field_531f391eb2ca9',
					'label' 			=> __('Notificaciones', 'estados-pedido'),
					'name' 				=> '',
					'type' 				=> 'tab',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'placement' 		=> 'top',
					'endpoint' 			=> 0,
					],

				[//Campo de notificaciones.
					'key' 				=> 'field_59e232bcf0cf0',
					'label' 			=> __('Activar notificaciones para:', 'estados-pedido'),
					'name' 				=> 'notificaciones',
					'type' 				=> 'checkbox',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'choices' 			=> [
						'admin' 	=> __('Administrador', 'estados-pedido'),
						'cliente' 	=> __('Cliente', 'estados-pedido'),
						],
					'allow_custom' 		=> 0,
					'save_custom' 		=> 0,
					'default_value' 	=> [],
					'layout' 			=> 'horizontal',
					'toggle' 			=> 0,
					'return_format' 	=> 'value',
					],

				[//Campo de direcciones de administración.
					'key' 				=> 'field_56c212bad3cf5',
					'label' 			=> __('Dirección de administración', 'estados-pedido'),
					'name' 				=> 'email_admin',
					'type' 				=> 'text',
					'instructions' 		=> __('Dirección de correo electrónico a la que se enviará la notificación al administrador. Admite varias direcciones separadas por punto y coma (;).', 'estados-pedido'),
					'required' 			=> 0,
					'conditional_logic' => [
						[
							[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								],
							],
						],
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'allow_custom' 		=> 0,
					'save_custom' 		=> 0,
					'default_value' 	=> get_option ('admin_email'),
					'layout' 			=> 'horizontal',
					'toggle' 			=> 0,
					'return_format' 	=> 'value',
					],

				[//Campo de asunto.
					'key' 				=> 'field_59e234508e037',
					'label' 			=> __('Asunto', 'estados-pedido'),
					'name'				=> 'asunto',
					'type' 				=> 'text',
					'instructions' 		=> __('Asunto del correo electrónico. Admite variables.', 'estados-pedido'),
					'required' 			=> 1,
					'conditional_logic' => [
						[
							[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								],
							],
						[
							[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'cliente',
								],
							],
						],
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'default_value' 	=> '',
					'placeholder' 		=> '',
					'prepend' 			=> '',
					'append' 			=> '',
					'maxlength' 		=> '',
					],

				[//Campo de encabezado.
					'key' 				=> 'field_54b234038f037',
					'label' 			=> __('Encabezado', 'estados-pedido'),
					'name'				=> 'heading',
					'type' 				=> 'text',
					'instructions' 		=> __('Si no se establece ninguno se usará el asunto del correo electrónico. Admite variables.', 'estados-pedido'),
					'required' 			=> 0,
					'conditional_logic' => [
						[
							[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								],
							],
							[
								[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'cliente',
								],
							],
						],
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'default_value' 	=> '',
					'placeholder' 		=> '',
					'prepend' 			=> '',
					'append' 			=> '',
					'maxlength' 		=> '',
					],

				[//Campo de plantilla.
					'key' 				=> 'field_59e233517c3e9',
					'label' 			=> __('Plantilla', 'estados-pedido'),
					'name' 				=> 'plantilla',
					'type' 				=> 'wysiwyg',
					'instructions' 		=> $this->devuelve_instrucciones(),
					'required' 			=> 1,
					'conditional_logic' => [
							[
								[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								],
							],
							[
								[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'cliente',
								],
							],
						],
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'default_value' => '',
					'placeholder' 	=> '',
					'maxlength' 	=> '',
					'rows' 			=> '',
					'new_lines' 	=> 'wpautop',
					],

				[//Campos de adjuntos.
					'key' 				=> 'field_5ccc4c18b5b1b',
					'label' 			=> __('Adjuntar archivos', 'estados-pedido'),
					'name' 				=> 'adjuntos',
					'type' 				=> 'repeater',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => [
						[
							[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								],
							],
						[
							[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'cliente',
								],
							],
						],
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'collapsed' 	=> 'field_5ccc4c2cb5b1c',
					'min' 			=> 1,
					'max' 			=> 0,
					'layout' 		=> 'table',
					'button_label' 	=> '',
					'sub_fields' 	=> [
						[
							'key' 				=> 'field_5ccc4c2cb5b1c',
							'label' 			=> __('Adjunto', 'estados-pedido'),
							'name' 				=> 'adjunto',
							'type' 				=> 'file',
							'instructions' 		=> '',
							'required' 			=> 0,
							'conditional_logic' => 0,
							'wrapper' 			=> [
								'width' => '',
								'class' => '',
								'id' 	=> '',
								],
							'return_format' 	=> 'url',
							'library' 			=> 'all',
							'min_size' 			=> '',
							'max_size' 			=> '',
							'mime_types' 		=> '',
							]
						]
					],

				[//Campo de estilos.
					'key' 				=> 'field_39e234007f031',
					'label' 			=> __('Estilos personalizados', 'estados-pedido'),
					'name'				=> 'estilos',
					'type' 				=> 'textarea',
					'instructions' 		=> __('CSS personalizado para aplicar a esta notificación.', 'estados-pedido'),
					'required' 			=> 0,
					'conditional_logic' => [
						[
							[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								],
							],
						[
							[
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'cliente',
								],
							],
						],
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'default_value' 	=> '',
					'placeholder' 		=> sprintf ("h1 {\n\tcolor: %s;\n}", get_option ('woocommerce_email_base_color')),
					'prepend' 			=> '',
					'append' 			=> '',
					'maxlength' 		=> '',
					],
				];
			}

		private function campos_tab_caracteristicas () {

			return [

				[//Pestaña "Características".
					'key' 				=> 'field_541e36a1b2ca9',
					'label' 			=> __('Características', 'estados-pedido'),
					'name' 				=> '',
					'type' 				=> 'tab',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'placement' 		=> 'top',
					'endpoint' 			=> 0,
					],

				[//Campo de siguiente estado.
					'key' 				=> 'field_5c62a17632be7',
					'label' 			=> __('Siguiente estado en el flujo de pedidos', 'estados-pedido'),
					'name' 				=> 'sigestado',
					'type' 				=> 'select',
					'instructions' 		=> __('Establece el botón de acción que se mostrará en primer lugar para los pedidos en este estado.', 'estados-pedido'),
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'choices' 			=> $this->devuelve_estados(), //wc_get_order_statuses(),
					'default_value' 	=> [],
					'allow_null' 		=> 1,
					'multiple' 			=> 0,
					'ui' 				=> 1,
					'ajax' 				=> 0,
					'return_format' 	=> 'value',
					'placeholder' 		=> '',
					],

				[//Checkboxes de características.
					'key' 				=> 'field_59e232bcf1cf0',
					'name' 				=> 'informes',
					'type' 				=> 'checkbox',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'choices' 			=> [
						'editable'		=> __('Hacer que los pedidos en este estado se puedan editar.', 'estados-pedido'),
						'completado'	=> __('Considerar los pedidos en este estado como completados.', 'estados-pedido'),
						'descargas'		=> __('Permitir la descarga de archivos descargables para los pedidos en este estado.', 'estados-pedido'),
						'incluir'		=> __('Incluir este estado de pedido en los informes de ventas (modo heredado para los antiguos informes de WooCommerce).', 'estados-pedido'),
						'dashboard'		=> __('Mostrar un resumen de este estado de pedido en el widget de escritorio de WooCommerce.', 'estados-pedido'),
						],
					'allow_custom' 		=> 0,
					'save_custom' 		=> 0,
					'default_value' 	=> [],
					'layout' 			=> 'vertical',
					'toggle' 			=> 0,
					'return_format' 	=> 'value',
					]
				];
			}

		private function campos_tab_automatizaciones () {

			return [

				[//Pestaña "Automatizaciones".
					'key' 				=> 'field_501b39a6b2ca9',
					'label' 			=> __('Automatizaciones', 'estados-pedido'),
					'name' 				=> '',
					'type' 				=> 'tab',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'placement' 		=> 'top',
					'endpoint' 			=> 0,
					],

				[//Campo de método de pago OR.
					'key' 				=> 'field_5f32a17132fa3',
					'label' 			=> __('Método de pago', 'estados-pedido'),
					'name' 				=> 'autopago',
					'type' 				=> 'select',
					'instructions' 		=> __('Poner automáticamente en este estado los pedidos pagados mediante los métodos seleccionados.', 'estados-pedido'),
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'choices' 			=> $this->devuelve_pasarelas(),
					'default_value' 	=> [],
					'allow_null' 		=> 1,
					'multiple' 			=> 1,
					'ui' 				=> 1,
					'ajax' 				=> 0,
					'return_format' 	=> 'value',
					'placeholder' 		=> '',
					],

				[//Campo de rol OR.
					'key' 				=> 'field_5e30c17931fa3',
					'label' 			=> __('Rol de usuario', 'estados-pedido'),
					'name' 				=> 'autorol',
					'type' 				=> 'select',
					'instructions' 		=> __('Poner automáticamente en este estado los pedidos de usuarios pertenecientes a los roles seleccionados.', 'estados-pedido'),
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> [
						'width' => '',
						'class' => '',
						'id' 	=> '',
						],
					'choices' 			=> $this->devuelve_roles(),
					'default_value' 	=> [],
					'allow_null' 		=> 1,
					'multiple' 			=> 1,
					'ui' 				=> 1,
					'ajax' 				=> 0,
					'return_format' 	=> 'value',
					'placeholder' 		=> '',
					],
				];
			}

		private function devuelve_estados () {

			$estados = [];

			foreach (Estados_Pedido_CPT::pide_query() as $estado)
				if (!isset ($_GET['post']) || $estado->ID != $_GET['post']) //Excluimos el estado que estamos editando actualmente.
					$estados[$estado->post_name] = $estado->post_title ? : __('Personalizado #', 'estados-pedido') . $estado->ID;

			$estados['wc-processing'] = __('Processing', 'woocommerce');
			$estados['wc-completed']  = __('Completed', 'woocommerce');

			return $estados;
			}

		/**
		 * Usaremos el método payment_gateways() y comprobaremos para cada pasarela si está activa
		 * Si se usa el método get_available_payment_gateways() la pasarela 'cod' sólo aparece si está activada la opción
		 * woocommerce_cod_enable_for_virtual
		 * 
		 */
		private function devuelve_pasarelas () {

			$pasarelas = [];

			/**
			 * Algunas instalaciones dan problemas en el checkout en función del método elegido para recuperar las pasarelas.
			 * Para cambiar el método:
			 * 
			 * add_filter ('estados_pedido_devuelve_pasarelas_disponibles', '__return_true');
			 * 
			 */
			if (apply_filters ('estados_pedido_devuelve_pasarelas_disponibles', false)) {

				$gateways  = new WC_Payment_Gateways();
			
				foreach ($gateways->payment_gateways() as $key => $pasarela)
					if ('yes' == $pasarela->enabled)
						$pasarelas[$key] = $pasarela->title;
				}

			else
				foreach (WC()->payment_gateways->payment_gateways() as $key => $pasarela)
					if ('yes' == $pasarela->enabled)
						$pasarelas[$key] = $pasarela->title;

			return $pasarelas;
			}

		/**
		 * Devuelve la lista de roles de usuario.
		 *
		 * @since 	3.3.0
		 * @return 	array
		 *
		 */
		private function devuelve_roles () {

			global $wp_roles;

			isset ($wp_roles) or
				$wp_roles = new WP_Roles();

			$lista = [];

			foreach ($wp_roles->get_names() as $rol => $nombre)
				$lista[esc_attr($rol)] = translate_user_role ($nombre);

			if ('yes' == get_option ('woocommerce_enable_guest_checkout')) //Si se permiten pedidos de invitados.
				$lista['invitado'] = __('Invitado', 'estados-pedido');

			return $lista;
			}

		/**
		 * Devuelve la lista de marcadores de posición de la plantilla
		 *
		 * @since 	5.1.6
		 * @return 	string
		 *
		 */
		private function devuelve_instrucciones () {

			$instrucciones  = '<p>';
			$instrucciones .= __('Puedes utilizar las siguientes variables tanto en el cuerpo del mensaje como en el asunto y el encabezado:', 'estados-pedido');
			$instrucciones .= '</p>';

			$instrucciones .= '<table><tr><td width="50%" valign="top">';

			$instrucciones .= '<code>%%cliente%%</code> ';
			$instrucciones .= __('para el nombre del cliente.', 'estados-pedido');

			$instrucciones .= '<br><code>%%apellidos%%</code> ';
			$instrucciones .= __('para los apellidos del cliente.', 'estados-pedido');

			$instrucciones .= '<br><code>%%empresa%%</code> ';
			$instrucciones .= __('para la empresa del cliente.', 'estados-pedido');

			$instrucciones .= '<br><code>%%telefono%%</code> ';
			$instrucciones .= __('para el teléfono del cliente.', 'estados-pedido');

			$instrucciones .= '<br><code>%%email%%</code> ';
			$instrucciones .= __('para la dirección de email del cliente.', 'estados-pedido');

			$instrucciones .= '<br><code>%%pedido%%</code> ';
			$instrucciones .= __('para el número de pedido.', 'estados-pedido');

			$instrucciones .= '<br><code>%%tabla%%</code> ';
			$instrucciones .= __('para la tabla resumen del pedido.', 'estados-pedido');

			$instrucciones .= '<br><code>%%total%%</code> ';
			$instrucciones .= __('para el total del pedido.', 'estados-pedido');

			$instrucciones .= '</td><td width="50%" valign="top">';

			$instrucciones .= '<code>%%fecha_pedido%%</code> ';
			$instrucciones .= __('para la fecha de creación del pedido.', 'estados-pedido');

			$instrucciones .= '<br><code>%%datos_facturacion%%</code> ';
			$instrucciones .= __('para los datos de facturación.', 'estados-pedido');

			$instrucciones .= '<br><code>%%datos_envio%%</code> ';
			$instrucciones .= __('para la dirección de envío.', 'estados-pedido');

			$instrucciones .= '<br><code>%%metodo_envio%%</code> ';
			$instrucciones .= __('para el método de envío seleccionado.', 'estados-pedido');

			$instrucciones .= '<br><code>%%metodo_pago%%</code> ';
			$instrucciones .= __('para el método de pago utilizado.', 'estados-pedido');

			$instrucciones .= '<br><code>%%instrucciones%%</code> ';
			$instrucciones .= __('para las instrucciones del método de pago.', 'estados-pedido');

			$instrucciones .= '<br><code>%%notas%%</code> ';
			$instrucciones .= __('para las notas del pedido.', 'estados-pedido');
			
			$instrucciones .= '<br><code>%%descargas%%</code> ';
			$instrucciones .= __('para las descargas del pedido (si las hay).', 'estados-pedido');

			$instrucciones .= '</td></tr></table>';

			//Integración con otros plugins activos

			$extra = $pidenif = $seguimiento = $euvat = false;

			if (class_exists ('Plugin_Pide_NIF') || class_exists ('APG_Campo_NIF')) //WC – APG Campo NIF/CIF/NIE
				$extra = $pidenif = true;

			if (class_exists ('Plugin_Seguimiento_Pedidos'))
				$extra = $seguimiento = true;

			if (class_exists ('WC_EU_VAT_Number_Init') || class_exists ('YITH_WooCommerce_EU_VAT')) //WooCommerce EU VAT Number y YITH WooCommerce EU VAT, OSS & IOSS Premium
				$extra = $euvat = true;

			if ($extra) {

				$br = false;

				$instrucciones .= '<p>';
				$instrucciones .= __('Además, otros plugins que tienes activos te permiten utilizar estas variables adicionales:', 'estados-pedido');
				$instrucciones .= '</p><p>';
			
				if ($pidenif) {

					$instrucciones .= '<code>%%nif%%</code> ';
					$instrucciones .= __('para el NIF/CIF/NIE del cliente.', 'estados-pedido');

					$br = true;
					}

				if ($euvat) {

					$instrucciones .= $br ? '<br>' : '';

					$instrucciones .= '<code>%%euvat%%</code> ';
					$instrucciones .= __('para el número de IVA europeo del cliente.', 'estados-pedido');

					$br = true;
					}

				if ($seguimiento) {

					$instrucciones .= $br ? '<br>' : '';

					$instrucciones .= '<code>%%agencia%%</code> ';
					$instrucciones .= __('para el nombre de la agencia de transportes.', 'estados-pedido');

					$instrucciones .= '<br><code>%%codigo%%</code> ';
					$instrucciones .= __('para el código de seguimiento.', 'estados-pedido');

					$instrucciones .= '<br><code>%%url_envio%%</code> ';
					$instrucciones .= __('para la URL con la información de seguimiento.', 'estados-pedido');

					$instrucciones .= '<br><code>%%boton_envio%%</code> ';
					$instrucciones .= __('para crear un botón que lleve a la información de seguimiento.', 'estados-pedido');
					}

				$instrucciones .= '</p>';
				}

			return $instrucciones;
			}

		public static function devuelve_iconos () {

			return [
				'159' => '<span class="dashicons dashicons-marker"></span>', //Icono por defecto
				'10f' => '<span class="dashicons dashicons-insert"></span>',
				'11d' => '<span class="dashicons dashicons-admin-site-alt"></span>',
				'12a' => '<span class="dashicons dashicons-yes-alt"></span>',
				'12d' => '<span class="dashicons dashicons-instagram"></span>',
				'13b' => '<span class="dashicons dashicons-cloud-upload"></span>',
				'14f' => '<span class="dashicons dashicons-remove"></span>',
				'15f' => '<span class="dashicons dashicons-airplane"></span>',
				'16d' => '<span class="dashicons dashicons-bell"></span>',
				'16e' => '<span class="dashicons dashicons-calculator"></span>',
				'18b' => '<span class="dashicons dashicons-google"></span>',
				'18c' => '<span class="dashicons dashicons-hourglass"></span>',
				'18d' => '<span class="dashicons dashicons-linkedin"></span>',
				'18e' => '<span class="dashicons dashicons-money-alt"></span>',
				'19a' => '<span class="dashicons dashicons-whatsapp"></span>',
				'19b' => '<span class="dashicons dashicons-youtube"></span>',
				'100' => '<span class="dashicons dashicons-admin-appearance"></span>',
				'101' => '<span class="dashicons dashicons-admin-comments"></span>',
				'102' => '<span class="dashicons dashicons-admin-home"></span>',
				'103' => '<span class="dashicons dashicons-admin-links"></span>',
				'106' => '<span class="dashicons dashicons-admin-plugins"></span>',
				'107' => '<span class="dashicons dashicons-admin-tools"></span>',
				'108' => '<span class="dashicons dashicons-admin-settings"></span>',
				'109' => '<span class="dashicons dashicons-admin-post"></span>',
				'110' => '<span class="dashicons dashicons-admin-users"></span>',
				'111' => '<span class="dashicons dashicons-admin-generic"></span>',
				'112' => '<span class="dashicons dashicons-admin-network"></span>',
				'118' => '<span class="dashicons dashicons-welcome-learn-more"></span>',
				'119' => '<span class="dashicons dashicons-welcome-write-blog"></span>',
				'128' => '<span class="dashicons dashicons-format-image"></span>',
				'129' => '<span class="dashicons dashicons-camera-alt"></span>',
				'154' => '<span class="dashicons dashicons-star-empty"></span>',
				'155' => '<span class="dashicons dashicons-star-filled"></span>',
				'160' => '<span class="dashicons dashicons-lock"></span>',
				'162' => '<span class="dashicons dashicons-amazon"></span>',
				'174' => '<span class="dashicons dashicons-cart"></span>',
				'177' => '<span class="dashicons dashicons-visibility"></span>',
				'179' => '<span class="dashicons dashicons-search"></span>',
				'182' => '<span class="dashicons dashicons-trash"></span>',
				'192' => '<span class="dashicons dashicons-pinterest"></span>',
				'196' => '<span class="dashicons dashicons-spotify"></span>',
				'223' => '<span class="dashicons dashicons-editor-help"></span>',
				'226' => '<span class="dashicons dashicons-dashboard"></span>',
				'227' => '<span class="dashicons dashicons-flag"></span>',
				'230' => '<span class="dashicons dashicons-location"></span>',
				'231' => '<span class="dashicons dashicons-location-alt"></span>',
				'233' => '<span class="dashicons dashicons-images-alt2"></span>',
				'234' => '<span class="dashicons dashicons-video-alt"></span>',
				'242' => '<span class="dashicons dashicons-share-alt2"></span>',
				'301' => '<span class="dashicons dashicons-twitter"></span>',
				'304' => '<span class="dashicons dashicons-facebook"></span>',
				'308' => '<span class="dashicons dashicons-hammer"></span>',
				'312' => '<span class="dashicons dashicons-products"></span>',
				'316' => '<span class="dashicons dashicons-download"></span>',
				'317' => '<span class="dashicons dashicons-upload"></span>',
				'322' => '<span class="dashicons dashicons-portfolio"></span>',
				'323' => '<span class="dashicons dashicons-tag"></span>',
				'331' => '<span class="dashicons dashicons-book-alt"></span>',
				'334' => '<span class="dashicons dashicons-shield-alt"></span>',
				'348' => '<span class="dashicons dashicons-info"></span>',
				'450' => '<span class="dashicons dashicons-buddicons-topics"></span>',
				'451' => '<span class="dashicons dashicons-buddicons-replies"></span>',
				'454' => '<span class="dashicons dashicons-buddicons-friends"></span>',
				'456' => '<span class="dashicons dashicons-buddicons-groups"></span>',
				'457' => '<span class="dashicons dashicons-buddicons-pm"></span>',
				'464' => '<span class="dashicons dashicons-edit"></span>',
				'466' => '<span class="dashicons dashicons-email-alt"></span>',
				'473' => '<span class="dashicons dashicons-testimonial"></span>',
				'480' => '<span class="dashicons dashicons-archive"></span>',
				'481' => '<span class="dashicons dashicons-clipboard"></span>',
				'483' => '<span class="dashicons dashicons-universal-access"></span>',
				'486' => '<span class="dashicons dashicons-tickets"></span>',
				'488' => '<span class="dashicons dashicons-megaphone"></span>',
				'508' => '<span class="dashicons dashicons-calendar-alt"></span>',
				'511' => '<span class="dashicons dashicons-carrot"></span>',
				'513' => '<span class="dashicons dashicons-store"></span>',
				'515' => '<span class="dashicons dashicons-controls-repeat"></span>',
				'518' => '<span class="dashicons dashicons-controls-back"></span>',
				'519' => '<span class="dashicons dashicons-controls-forward"></span>',
				'523' => '<span class="dashicons dashicons-controls-pause"></span>',
				'524' => '<span class="dashicons dashicons-tickets-alt"></span>',
				'525' => '<span class="dashicons dashicons-phone"></span>',
				'527' => '<span class="dashicons dashicons-palmtree"></span>',
				'529' => '<span class="dashicons dashicons-thumbs-up"></span>',
				'530' => '<span class="dashicons dashicons-thumbs-down"></span>',
				'531' => '<span class="dashicons dashicons-image-rotate"></span>',
				'533' => '<span class="dashicons dashicons-image-filter"></span>',
				'534' => '<span class="dashicons dashicons-warning"></span>',
				'537' => '<span class="dashicons dashicons-sticky"></span>',
				'540' => '<span class="dashicons dashicons-admin-customizer"></span>',
				'541' => '<span class="dashicons dashicons-admin-multisite"></span>',
				'546' => '<span class="dashicons dashicons-paperclip"></span>',
				];
			}

		}

endif;