<?php

/**
 * Campo personalizado para la URL de seguimiento
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link			https://www.enriquejros.com
 * @since			1.1.0
 * @package			SeguimientoPedidos
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Campos_Seguimiento_Pedidos')) :

	Class Campos_Seguimiento_Pedidos {

		public function __construct () {

			//Si no se puede cargar ACF da error y sale
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
					<p><?php printf (__('El plugin %s no ha podido recuperar la lista de campos personalizados. Por favor, contacta con el %ssoporte%s.', 'seguimiento-pedidos'), '<i>' . __('Seguimiento de pedidos en WooCommerce', 'seguimiento-pedidos') . '</i>', '<a target="_blank" href="https://www.enriquejros.com/soporte/">', '</a>'); ?></p>
				</div>
				
			<?php
			}

		public function inserta_campos () {

			$campos = array(
				'key' 		=> 'group_5a8953e3593f2',
				'title' 	=> __('URL para el seguimiento', 'seguimiento-pedidos'),
				'fields' 	=> array(
					array(
						'key' 				=> 'field_5a8953f57a881',
						'label' 			=> '',
						'name' 				=> 'url_seguimiento',
						'type' 				=> 'text',
						'instructions'		=> __('%ref% será sustituido por el número de seguimiento', 'seguimiento-pedidos') . '<br>' . __('%cp% será sustituido por el código postal de envío', 'seguimiento-pedidos'),
						'required' 			=> 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' 	=> '',
							),
						'default_value' 	=> '',
						'placeholder' 		=> '',
						'prepend' 			=> '',
						'append' 			=> '',
						'maxlength' 		=> '',
						),
					),

				'location' => array(
					array(
						array(
							'param' 	=> 'post_type',
							'operator' 	=> '==',
							'value' 	=> CPT_Seguimiento_Pedidos::CPT,
							),
						),
					),
				'menu_order' 			=> 0,
				'position' 				=> 'normal',
				'label_placement' 		=> 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' 		=> '',
				'active' 				=> 1,
				'description' 			=> '',
				);

			function_exists ('acf_add_local_field_group') ? acf_add_local_field_group ($campos) : register_field_group ($campos);
			}

		}

endif;