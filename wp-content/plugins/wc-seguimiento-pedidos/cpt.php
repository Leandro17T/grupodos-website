<?php

/**
 * Custom post type 'agencias'
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link              https://www.enriquejros.com
 * @since             1.1.0
 * @package           SeguimientoPedidos
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('CPT_Seguimiento_Pedidos')) :

	Class CPT_Seguimiento_Pedidos {

		const CPT       = 'agencias';
		const CACHE_KEY = 'agencias_seguimiento';

		public function __construct () {

			$this->crea_cpt();

			add_filter ('post_updated_messages', [$this, 'mensajes_guardado'], 10, 1);
			add_filter ('bulk_post_updated_messages', [$this, 'mensajes_guardados_bulk'], 10, 2);

			add_filter ('manage_' . self::CPT . '_posts_columns', [$this, 'columna_url'], 10, 1);
			add_action ('manage_' . self::CPT . '_posts_custom_column', [$this, 'rellena_columna_url'], 10, 2);

			add_filter ('post_row_actions', [$this, 'quita_ver'], 10, 1);
			add_filter ('pre_get_posts', [$this, 'ordena_admin_cpt'], 10, 1);
			add_action ('wp_ajax_actualiza_orden_cpt', [$this, 'cambia_orden_cpt'], 10);
			add_action ('admin_enqueue_scripts', [$this, 'script_reordenar'], 10);
			}

		public function crea_cpt () {

			$etiquetas = array(
				'name'                  => __('Agencias', 'seguimiento-pedidos'),
				'singular_name'         => __('Agencia', 'seguimiento-pedidos'),
				'menu_name'             => __('Agencias', 'seguimiento-pedidos'),
				'name_admin_bar'        => __('Agencia', 'seguimiento-pedidos'),
				'archives'              => __('Archivos de agencias', 'seguimiento-pedidos'),
				'attributes'            => __('Atributos de agencia', 'seguimiento-pedidos'),
				'all_items'             => __('Agencias', 'seguimiento-pedidos'),
				'add_new_item'          => __('Añadir agencia', 'seguimiento-pedidos'),
				'add_new'               => __('Nueva agencia', 'seguimiento-pedidos'),
				'new_item'              => __('Nueva agencia', 'seguimiento-pedidos'),
				'edit_item'             => __('Editar agencia', 'seguimiento-pedidos'),
				'update_item'           => __('Actualizar agencia', 'seguimiento-pedidos'),
				'view_item'             => __('Ver agencia', 'seguimiento-pedidos'),
				'view_items'            => __('Ver agencias', 'seguimiento-pedidos'),
				'search_items'          => __('Buscar agencia', 'seguimiento-pedidos'),
				'not_found'             => __('No hay agencias', 'seguimiento-pedidos'),
				'not_found_in_trash'    => __('No hay agencias en la papelera', 'seguimiento-pedidos'),
				'items_list'            => __('Lista de agencias', 'seguimiento-pedidos'),
				'items_list_navigation' => __('Navegación por lista de agencias', 'seguimiento-pedidos'),
				'filter_items_list'     => __('Filtrar lista de agencias', 'seguimiento-pedidos'),
				);

			$argumentos = array(
				'label'                 => __('Agencia', 'seguimiento-pedidos'),
				'description'           => __('Gestiona agencias de envío', 'seguimiento-pedidos'),
				'labels'                => $etiquetas,
				'supports'              => ['title'],
				'hierarchical'          => false,
				'public'                => false,
				'show_ui'               => true,
				'show_in_menu'          => 'woocommerce',
				'show_in_admin_bar'     => true,
				'show_in_nav_menus'     => true,
				'can_export'            => true,
				'has_archive'           => false,
				'exclude_from_search'   => true,
				'publicly_queryable'    => false,
				'capability_type'       => 'post',
				);

			register_post_type (self::CPT, $argumentos);
			}

		public function mensajes_guardado ($mensajes) {

			$mensajes[self::CPT] = array(
				1 => __('Agencia actualizada.', 'seguimiento-pedidos'),
				4 => __('Agencia actualizada.', 'seguimiento-pedidos'),
				6 => __('Agencia creada.', 'seguimiento-pedidos'),
				7 => __('Agencia guardada.', 'seguimiento-pedidos'),
				);

			return $mensajes;
			}

		public function mensajes_guardados_bulk ($mensajes, $cuantos) {

			$mensajes[self::CPT] = array(
				'updated'	=> _n('%s agencia actualizada.','%s agencias actualizadas.', $cuantos['updated'], 'seguimiento-pedidos'),
				'locked'	=> _n('%s agencia no se ha actualizado, alguien la está editando.', '%s agencias no se han actualizado, alguien las está editando.', $cuantos['locked'], 'seguimiento-pedidos'),
				'deleted'	=> _n('%s agencia se ha eliminado.', '%s agencias se han eliminado.', $cuantos['deleted'], 'seguimiento-pedidos'),
				'trashed'	=> _n('%s agencia se ha enviado a la papelera.', '%s agencias se han enviado a la papelera.', $cuantos['trashed'], 'seguimiento-pedidos'),
				'untrashed'	=> _n('%s agencia se ha recuperado de la papelera.', '%s agencias se han recuperado de la papelera.', $cuantos['untrashed'], 'seguimiento-pedidos'),
				);

			return $mensajes;
			}

		public static function pide_query () {

			$query = array(
				'post_type'			=> self::CPT,
				'post_status' 		=> 'publish',
				'posts_per_page'	=> -1,
				'orderby'			=> 'menu_order',
				'order'				=> 'ASC',
				);

			if ($agencias = wp_cache_get (self::CACHE_KEY))
				return $agencias;

			else if ($agencias = new WP_Query($query)) {

				wp_cache_set (self::CACHE_KEY, $agencias->posts);
				return $agencias->posts;
				}
			}

		public function columna_url ($columnas) {

			return array(
				'cb' 	=> '<input type="checkbox" />',
				'title' => __('Agencia', 'seguimiento-pedidos'),
				'url'	=> __('URL', 'seguimiento-pedidos'),
				);
			}

		public function rellena_columna_url ($columna, $id) {

			if ('url' == $columna) {

				if (strpos ($url = get_field ('url_seguimiento', $id), '%'))
					echo $url;

				else
					printf ('<a target="_blank" href="%s">%s</a>', $url, $url);
				}
			}

		public function quita_ver ($acciones) {

			if (get_post_type() == self::CPT)
				unset ($acciones['view']);

			return $acciones;
			}

		/**
		 * Mostramos el listado de campos ordenado según el orden personalizado, no por ID
		 * copyright Enrique J. Ros - enrique@enriquejros.com
		 *
		 * @since 2.1.0
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
		 * Actualizamos el orden de los campos según lo hemos recibido de jQuery.sortable
		 * copyright Enrique J. Ros - enrique@enriquejros.com
		 *
		 * @since 2.1.0
		 *
		 */
		public function cambia_orden_cpt () {

			$i = 0;

			foreach (explode ('post[]=', $_POST['orden']) as $post) {

				wp_update_post (
					array(
						'ID'			=> preg_replace ('/[^0-9]/', '', $post), //Tenemos que quitar el '&'
						'menu_order'	=> $i,
						)
					);

				$i++;
				}

			die();
			}

		public function script_reordenar () {

			if (isset ($_GET['post_type']) && self::CPT == $_GET['post_type']) {

				wp_enqueue_style ('reordena-cpt', plugins_url ('assets/css/reordenar.css', __FILE__));
				wp_enqueue_script ('reordena-cpt', plugins_url ('assets/js/reordenar.min.js', __FILE__), ['jquery', 'jquery-ui-sortable']);
				wp_localize_script ('reordena-cpt', 'reordena', ['ajax_url' => admin_url ('admin-ajax.php')]);
				}
			}

		}

endif;