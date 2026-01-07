<?php

/**
 * Custom post type 'estado-pedido'.
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link              https://www.enriquejros.com
 * @since             1.0.0
 * @package           EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists('Estados_Pedido_CPT')) :

	Class Estados_Pedido_CPT {

		const CPT       = 'estado-pedido';
		const CACHE_KEY = 'estados_pedido_personalizados';

		public function __construct () {

			$this->crea_cpt();

			add_action ('save_post', [$this, 'cambia_slug'], 10, 1);
			add_filter ('post_updated_messages', [$this, 'mensajes_guardado'], 10, 1);
			add_filter ('bulk_post_updated_messages', [$this, 'mensajes_guardados_bulk'], 10, 2);

			add_filter ('manage_' . self::CPT . '_posts_columns', [$this, 'columnas'], 10, 1);
			add_action ('manage_' . self::CPT . '_posts_custom_column', [$this, 'rellena_columnas'], 10, 2);

			add_filter ('post_row_actions', [$this, 'quita_ver'], 10, 1);
			add_action ('admin_head', [$this, 'quita_visibilidad'], 10);
			add_filter ('pre_get_posts', [$this, 'ordena_admin_cpt'], 10, 1);

			add_action ('wp_ajax_valida_licencia_estados_pedido', [$this, 'datos_licencia'], 10);
			add_action ('wp_ajax_importa_estados', [$this, 'importa_estados_pedido'], 10);
			add_action ('wp_ajax_actualiza_orden_cpt', [$this, 'cambia_orden_cpt'], 10);

			add_action ('admin_footer', [$this, 'datos_boton_importar'], 10);
			add_action ('admin_enqueue_scripts', [$this, 'carga_scripts'], 10);
			}

		private function crea_cpt () {

			//Creamos el CPT
			$etiquetas = [
				'name'                  => __('Estados de pedido personalizados', 'estados-pedido'),
				'singular_name'         => __('Estado de pedido', 'estados-pedido'),
				'menu_name'             => __('Estados de pedido', 'estados-pedido'),
				'name_admin_bar'        => __('Estado de pedido', 'estados-pedido'),
				'archives'              => __('Archivos de estados de pedido', 'estados-pedido'),
				'attributes'            => __('Atributos de estado de pedido', 'estados-pedido'),
				'all_items'             => __('Estados de pedido', 'estados-pedido'),
				'add_new_item'          => __('Añadir estado de pedido', 'estados-pedido'),
				'add_new'               => __('Añadir estado', 'estados-pedido'),
				'new_item'              => __('Nuevo estado de pedido', 'estados-pedido'),
				'edit_item'             => __('Editar estado de pedido', 'estados-pedido'),
				'update_item'           => __('Actualizar estado de pedido', 'estados-pedido'),
				'view_item'             => __('Ver estado de pedido', 'estados-pedido'),
				'view_items'            => __('Ver estados de pedido', 'estados-pedido'),
				'search_items'          => __('Buscar estado de pedido', 'estados-pedido'),
				'not_found'             => __('No hay estados de pedido', 'estados-pedido'),
				'not_found_in_trash'    => __('No hay estados de pedido en la papelera', 'estados-pedido'),
				'items_list'            => __('Lista de estados de pedido', 'estados-pedido'),
				'items_list_navigation' => __('Navegación por lista de estados de pedido', 'estados-pedido'),
				'filter_items_list'     => __('Filtrar lista de estados de pedido', 'estados-pedido'),
				];

			$argumentos = [
				'label'                 => __('Estado de pedido', 'estados-pedido'),
				'description'           => __('Gestiona estados de pedido', 'estados-pedido'),
				'labels'                => $etiquetas,
				'supports'              => ['title'],
				'hierarchical'          => false,
				'public'                => false,
				'show_ui'               => true,
				'show_in_menu'          => 'woocommerce',
				'show_in_admin_bar'     => true,
				'show_in_nav_menus'     => false,
				'can_export'            => true,
				'has_archive'           => false,		
				'exclude_from_search'   => true,
				'publicly_queryable'    => true,
				'capability_type'       => 'post',
				'register_meta_box_cb'	=> [$this, 'metabox_devinfo'],
				];

			register_post_type (self::CPT, $argumentos);
			}

		/**
		 * Los slugs con más de dos guiones (incluyendo el de 'wc-') o más largos de 15 caracteres generan un bug.
		 *
		 * @since 	1.3.1
		 *
		 * @param 	int 	post ID
		 * @return 	null
		 *
		 */
		public function cambia_slug ($post_id) {

			if (defined ('DOING_AUTOSAVE') && DOING_AUTOSAVE) //Evitamos la ejecución en autoguardados.
				return;

			if (!current_user_can ('edit_post', $post_id)) //Seguridad ante todo.
				return;

			remove_action ('save_post', [$this, 'cambia_slug']); //Evitamos que entre en bucle.

			$post = get_post ($post_id);

			if (self::CPT == $post->post_type && (strlen ($post->post_name) > 15 || substr_count ($post->post_name, '-') > 1))
				wp_update_post (
					[
						'ID'		=> $post->ID,
						'post_name'	=> (string) rand (100, 999) . substr (str_replace ("-", "", $post->post_name), 0, 12),
						]
					);

			return;
			}

		public function mensajes_guardado ($mensajes) {

			$mensajes[self::CPT] = [
				1 => __('Estado de pedido actualizado.', 'estados-pedido'),
				4 => __('Estado de pedido actualizado.', 'estados-pedido'),
				6 => __('Estado de pedido creado.', 'estados-pedido'),
				7 => __('Estado de pedido guardado.', 'estados-pedido'),
				];

			return $mensajes;
			}

		public function mensajes_guardados_bulk ($mensajes, $cuantos) {

			$mensajes[self::CPT] = [
				'updated'	=> _n('%s estado de pedido actualizado.','%s estados de pedido actualizados.', $cuantos['updated'], 'estados-pedido'),
				'locked'	=> _n('%s estado de pedido no se ha actualizado, alguien lo está editando.', '%s estados de pedido no se han actualizado, alguien los está editando.', $cuantos['locked'], 'estados-pedido'),
				'deleted'	=> _n('%s estado de pedido se ha eliminado.', '%s estados de pedido se han eliminado.', $cuantos['deleted'], 'estados-pedido'),
				'trashed'	=> _n('%s estado de pedido se ha enviado a la papelera.', '%s estados de pedido se han enviado a la papelera.', $cuantos['trashed'], 'estados-pedido'),
				'untrashed'	=> _n('%s estado de pedido se ha recuperado de la papelera.', '%s estados de pedido se han recuperado de la papelera.', $cuantos['untrashed'], 'estados-pedido'),
				];

			return $mensajes;
			}

		public function columnas ($columnas) {

			return [
				'cb' 				=> '<input type="checkbox" />',
				'titulo' 			=> __('Estado', 'estados-pedido'),
				'estado'			=> '<span class="status_head">' . __('Icono', 'estados-pedido') . '</span>',
				'notificaciones'	=> '<span class="dashicons dashicons-email-alt"></span>',
				'adjuntos'			=> '<span class="dashicons dashicons-paperclip"></span>',
				];
			}

		public function rellena_columnas ($columna, $id) {

			switch ($columna) {

				case 'titulo':

					$titulo = get_the_title ($id) ? : sprintf (__('Personalizado #%s', 'estados-pedido'), $id);
					echo '<strong><a class="row-title" href="post.php?post=' . $id . '&action=edit" aria-label="&laquo;' . $titulo . '&raquo;">' . $titulo . '</a></strong>';

					break;

				case 'estado':

					$iconos = Campos_Estados_Pedido::devuelve_iconos();
					$campo  = get_field ('icono', $id);
					$icono  = isset ($campo) ? $campo['value'] : '159';
					printf ('%s style="color:%s" title="%s"></span>', substr ($iconos[$icono], 0, strpos ($iconos[$icono], ">")), get_field ('color', $id) ? : '#1e73be', __('Estado de pedido', 'estados-pedido') . ': ' . get_the_title ($id));

					break;

				case 'notificaciones':

					if (is_array ($notificaciones = get_field ('notificaciones', $id))) {

						$destinatarios = [];

						foreach ($notificaciones as $notificacion) {

							if ('admin' == $notificacion) {

								if ($admin_email = get_field ('email_admin', $id))
									$destinatarios = explode (';', $admin_email);

								else
									$destinatarios[] = get_option ('admin_email');
								}

							else if ('cliente' == $notificacion)
								$destinatarios[] = __('Customer', 'woocommerce');
							}

						echo implode ('<br>', $destinatarios);
						}

					break;

				case 'adjuntos':

					if ($adjuntos = get_field ('adjuntos', $id))
						foreach ($adjuntos as $adjunto)
							if ($adjunto['adjunto'])
								printf ('<a target="_blank" href="%s">%s</a><br>', $adjunto['adjunto'], get_the_title (attachment_url_to_postid ($adjunto['adjunto'])) ? : substr ($adjunto['adjunto'], strrpos ($adjunto['adjunto'], '/') + 1));

					break;
				}
			}

		/**
		 * Comprueba y en su caso activa la licencia.
		 *
		 * @since 	2.7.0
		 *
		 */
		public function datos_licencia () {

			if (!defined ('DOING_AJAX') || !DOING_AJAX)
				return false;

			update_option ('estados_pedido_licencia', $_POST['clave']);

			$licencia = new EDDSL_Estados_Pedido($_POST['clave']);

			if (!$datos_licencia = $licencia->comprueba_licencia())
				die();

			switch ($datos_licencia['estado']) {

				case 'inactive':
				case 'site_inactive':

					$datos_licencia = $licencia->activa_licencia();
					//No hay break ya que tras la activación es preciso ver qué mensaje corresponde al resultado.

				case 'valid':

					$datos_licencia['exito'] = sprintf (__('Tu licencia está activada y es válida hasta el %s.', 'estados-pedido'), date_i18n (get_option ('date_format'), $datos_licencia['expira']));

					break;

				case 'invalid':

					$datos_licencia['error'] = __('La clave de licencia no es válida.', 'estados-pedido');

					break;

				case 'expired':

					$datos_licencia['error'] = __('La clave de licencia ha expirado.', 'estados-pedido');

					break;
				}

			echo json_encode ($datos_licencia);

			die();
			}

		/**
		 * Importa mediante AJAX los estados de pedido creados por otros plugins.
		 *
		 * @since 	2.2.0
		 *
		 */
		public function importa_estados_pedido () {

			if (!defined ('DOING_AJAX') || !DOING_AJAX)
				return false;

			$estados  = wp_list_pluck ($this->pide_query(), 'post_name');
			$terceros = $this->estados_pedidos_terceros();

			$campos = [
				'color'		=> 'field_59e21e12b35d0',
				'sigestado'	=> 'field_5c62a17632be7',
				'icono'		=> 'field_56e212fcf3ca0',
				'informes'	=> 'field_59e232bcf1cf0',
				];

			foreach ($terceros as $tercero) {

				if (!in_array ($tercero->post_name, $estados)) {

					$estado = get_post ($tercero->ID);
					$slug   = (strlen ($tercero->post_name) > 15 || substr_count ($tercero->post_name, '-') > 1) ? (string) rand (100, 999) . substr (str_replace ('-', '', $tercero->post_name), 0, 12) : $tercero->post_name;
					$nuevo  = [
						'post_title'	=> $estado->post_title,
						'post_type'		=> Estados_Pedido_CPT::CPT,
						'post_status' 	=> 'publish',
						'post_name'		=> $slug,
						];

					if (is_int ($id = wp_insert_post ($nuevo, false))) { //Generamos el nuevo estado de pedido.

						//Comprobamos si hay pedidos en ese estado y los pasamos al nuevo.
						$pedidos = wc_get_orders (['status' => $tercero->post_name]);

						foreach ($pedidos as $pedido)
							wp_insert_post (
								[
									'ID'			=> $pedido->get_id(),
									'post_status'	=> 'wc-' . $slug,
									]
								);

						/**
						 * WooCommerce Order Status Manager (SkyVerge).
						 * https://woocommerce.com/products/woocommerce-order-status-manager/
						 * 
						 */
						if ($color = get_post_meta ($tercero->ID, '_color', true)) {

							update_post_meta ($id, '_color', $campos['color']);
							update_post_meta ($id, 'color', $color);
							}

						$icono = get_post_meta ($tercero->ID, '_action_icon', true) or
							$icono = get_post_meta ($tercero->ID, '_icon', true);

						if ($icono && $codigo = array_search ('<span class="' . $icono . '"></span>', Campos_Estados_Pedido::devuelve_iconos())) {

							update_post_meta ($id, '_icono', $campos['icono']);
							update_post_meta ($id, 'icono', $codigo);
							}

						if ($siguiente = get_post_meta ($tercero->ID, '_next_statuses', true)) {

							update_post_meta ($id, '_sigestado', $campos['sigestado']);
							update_post_meta ($id, 'sigestado', $siguiente[0]);
							}

						if ('yes' == get_post_meta ($tercero->ID, '_is_paid', true)) {

							update_post_meta ($id, '_informes', $campos['informes']);
							update_post_meta ($id, 'informes', array('incluir'));
							}
						}
					}
				}

			die();
			}

		/**
		 * Establece una cabecera con los datos para generar el botón de importación de estados de pedido:
		 *		Nombre del CPT (para que sea dinámico).
		 *		Texto del botón (para que sea traducible).
		 *		Textos de confirmación (para que sean traducibles).
		 *
		 * Los datos son recuperados y utilizados por estados.js.
		 *
		 * copyright Enrique J. Ros - enrique@enriquejros.com
		 *
		 * @since 2.2.0
		 *
		 */
		public function datos_boton_importar () {

			//Si no existen estados de pedido personalizados que no hayamos importado no hacemos nada.
			if (!isset ($_GET['post_type']) || self::CPT != $_GET['post_type'] || !count ($this->estados_pedidos_terceros()))
				return;

			$textos = [
				'texto'		=> __('Importar estados de pedido', 'estados-pedido'),
				'pregunta'	=> __('¿Estás seguro?', 'estados-pedido'),
				'frase'		=> __('Esto hará que se importen los estados de pedido personalizados creados por otros plugins.', 'estados-pedido'),
				'si'		=> __('Yes'),
				'no'		=> __('No'),
				'exito'		=> __('Importando nuevos estados, espera...', 'estados-pedido'),
				'import'	=> __('Estados de pedido importados, espera la recarga de la página...', 'estados-pedido'),
				];

			printf ('<meta name="boton-importar-estados" id="meta-boton-importar-estados" data-cpt="%s" data-texto="%s" data-pregunta="%s" data-frase="%s" data-si="%s" data-no="%s" data-exito="%s" data-import="%s">', self::CPT, $textos['texto'], $textos['pregunta'], $textos['frase'], $textos['si'], $textos['no'], $textos['exito'], $textos['import']);
			}


		/**
		 * Devuelve los estados de pedido registrados, excepto:
		 * 		* Los estados estándar de WooCommerce.
		 * 		* Los estados generados por la pasarela de pago de Redsys.
		 * 		* Los estados excluidos mediante el filtro 'estados_pedido_excluidos'.
		 *
		 * copyright Enrique J. Ros - enrique@enriquejros.com
		 *
		 * @return 	array
		 * @since 	2.2.0
		 *
		 */
		private function estados_pedidos_terceros () {

			$terceros = [];
			$propios  = $this->pide_query();
			$excluye  = apply_filters ('estados_pedido_excluidos', ['failed', 'refunded', 'cancelled', 'completed', 'on-hold', 'processing', 'pending', 'draft', 'checkout-draft', 'redsys-pre', 'redsys-residentp', 'redsys-pbankt']);

			$registrados = new WP_Query(
				[
					'post_type'			=> ['wc_order_status', 'yith-wccos-ostatus'], //YITH WooCommerce Custom Order Status Premium.
					'post_status'		=> 'publish',
					'posts_per_page'	=> -1,
					]
				);

			foreach ($registrados->posts as $registrado) {

				if (!in_array ($registrado->post_name, $excluye)) {
					
					$propio = get_page_by_path ($registrado->post_name, OBJECT, self::CPT);

					$propio->ID or //Si ya está importado, lo dejamos pasar.
						$terceros[] = $registrado;
					}
				}

			return $terceros;
			}

		public static function pide_query ($menu_order = false) {

			$query = [
				'post_type'			=> self::CPT,
				'post_status' 		=> 'publish',
				'posts_per_page'	=> -1,
				'orderby'			=> $menu_order ? 'menu_order' : 'ID', //Para establecer la prioridad de las automatizaciones.
				'order'				=> $menu_order ? 'ASC' : 'DESC',
				];

			if (!$menu_order && $estados = wp_cache_get (self::CACHE_KEY)) //Si se han pedido por menu_order no devolvemos el resultado cacheado.
				return $estados;

			else if ($estados = new WP_Query($query)) {

				$menu_order or //Ni cacheamos el resultado.
					wp_cache_set (self::CACHE_KEY, $estados->posts);

				return $estados->posts;
				}
			}

		/**
		 * Mostramos el listado de campos ordenado según el orden personalizado, no por ID.
		 * copyright Enrique J. Ros - enrique@enriquejros.com
		 *
		 * @since 2.0.0
		 *
		 * @param 	WP_Query
		 * @return 	WP_Query
		 *
		 */
		public function ordena_admin_cpt ($query) {

			if ($query->is_admin && self::CPT == $query->get('post_type')) {

				$query->set('orderby', 'menu_order');
				$query->set('order', 'ASC');
				}

			return $query;
			}

		/**
		 * Actualizamos el orden de los campos según lo hemos recibido de jQuery.sortable.
		 * copyright Enrique J. Ros - enrique@enriquejros.com
		 *
		 * @since 2.0.0
		 *
		 */
		public function cambia_orden_cpt () {

			$i = 0;

			foreach (explode ('post[]=', $_POST['orden']) as $post) {

				wp_update_post (
					[
						'ID'			=> preg_replace ('/[^0-9]/', '', $post), //Tenemos que quitar el '&'.
						'menu_order'	=> $i,
						]
					);

				$i++;
				}

			die();
			}

		/**
		 * Crea la metabox con la información técnica.
		 *
		 * @since 4.6.0
		 *
		 */
		public function metabox_devinfo () {

			if (!isset ($_GET['post']))
				return;

			add_meta_box ('estados_personalizados_devinfo', sprintf ('<span style="text-align:%s!important"><span class="dashicons dashicons-info-outline"></span> %s</span> ', is_rtl() ? 'right' : 'left', __('Información sobre el estado de pedido', 'estados-pedido')), [$this, 'metabox_devinfo_html'], self::CPT, 'normal', 'low');
			}

		public function metabox_devinfo_html () {

			global $post;

			$cantidad = wc_orders_count ($post->post_name);
			$url      = HPOS_ACTIVO ? 'admin.php?page=wc-orders&status=wc-' : 'edit.php?post_type=shop_order&post_status=wc-';

			printf ('<p><b>%s:</b> %s %s<br><b>%s:</b> %s (wc-%s)<br><b>%s:</b> estado_personalizado_%s<br><b>%s:</b></p>',
				sprintf (__('Nº pedidos en estado %s%s%s', 'estados-pedido'), '&laquo;', $post->post_title, '&raquo;'),
				$cantidad,
				$cantidad ? sprintf ('<a target="_blank" href="%s%s"><span class="dashicons dashicons-external" style="text-decoration:none"></span></a>', $url, $post->post_name) : '',
				__('Identificador de estado', 'estados-pedido'),
				$post->post_name,
				$post->post_name,
				__('Acción (hook)', 'estados-pedido'),
				$post->post_name,
				__('Ejemplo de uso del hook', 'estados-pedido'));

			printf ('<p><code>add_action (\'%s%s\', function ($id_pedido) {<br><span style="margin-left:30px">//%s</span><br><span style="margin-left:30px">}, 10, 1);</span></code></p>', Estados_Pedido_Personalizados::ENVIO, $post->post_name, sprintf (__('Aquí tu código a ejecutar cuando un pedido pase a estado %s', 'estados-pedido'), $post->post_title));
			}

		public function quita_ver ($acciones) {

			if (get_post_type() == self::CPT) {

				unset ($acciones['view']);
				unset ($acciones['inline hide-if-no-js']); //Edición rápida.
				}

			return $acciones;
			}

		public function quita_visibilidad () {

			printf ('<style>.post-type-%s div#visibility.misc-pub-section.misc-pub-visibility,.post-type-%s #minor-publishing-actions .preview{display:none}</style>', self::CPT, self::CPT);
			}

		public function carga_scripts () {

			if (!wp_style_is ('fontawesome') && !wp_style_is ('font-awesome')) {

				wp_enqueue_style ('fontawesome', ASSETS_ESTADOS_PEDIDO . 'css/font-awesome.min.css');
				wp_enqueue_script ('fontawesome', ASSETS_ESTADOS_PEDIDO . 'js/font-awesome.min.js');
				}

			if (isset ($_GET['post_type']) && self::CPT == $_GET['post_type']) {

				wp_enqueue_style ('estados-pedido', ASSETS_ESTADOS_PEDIDO . 'css/estados2.min.css');
				wp_enqueue_style ('sweetalert', ASSETS_ESTADOS_PEDIDO . 'css/sweetalert3.min.css');

				wp_enqueue_script ('sweetalert', ASSETS_ESTADOS_PEDIDO . 'js/sweetalert3.min.js');
				wp_enqueue_script ('estados-pedido', ASSETS_ESTADOS_PEDIDO . 'js/estados3.min.js', ['jquery', 'jquery-ui-sortable', 'sweetalert']);

				wp_localize_script ('estados-pedido', 'estados', ['ajax_url' => admin_url ('admin-ajax.php')]);
				}

			else if (isset ($_GET['page']) && 'wc-settings' == $_GET['page'] && isset ($_GET['tab']) && 'integration' == $_GET['tab'])
				wp_enqueue_style ('estados-pedido-licencia', ASSETS_ESTADOS_PEDIDO . 'css/licencia.css');
			}

		}

endif;