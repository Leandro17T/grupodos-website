<?php

/**
 * Crea las principales agencias y sus URL de seguimiento en la activaci�n del plugin
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			1.1.0
 * @package 		SeguimientoPedidos
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Setup_Seguimiento_Pedidos')) :

	#[AllowDynamicProperties]

	Class Setup_Seguimiento_Pedidos {

		public function __construct () {

			$this->agencias = array(

				'asm' => array(
					'nombre' => 'ASM',
					'url'	 => 'https://m.asmred.com/e/%ref%/%cp%',
					),

				'correos' => array(
					'nombre' => 'Correos',
					'url'	 => 'https://www.correos.es/ss/Satellite/site/aplicacion-4000003383089-localiza_busca_encuentra/detalle_app-sidioma=es_ES?numero=%ref%&mostrarResultadoForm=true',
					),

				'correos-x' => array(
					'nombre' => 'Correos Express',
					'url'	 => 'https://s.correosexpress.com/search?s=%ref%',
					),

				'dhl' => array(
					'nombre' => 'DHL',
					'url'	 => 'https://clientesparcel.dhl.es/seguimientoenvios/integra/SeguimientoDocumentos.aspx?codigo=%ref%+&anno=2020&lang=sp',
					),

				'envialia' => array(
					'nombre' => 'Envialia',
					'url'	 => 'https://www.envialia.com/seguimiento/',
					),
				
				'mrw' => array(
					'nombre' => 'MRW',
					'url'	 => 'https://www.mrw.es/seguimiento_envios/MRW_resultados_consultas.asp?modo=nacional&envio=%ref%',
					),
				
				'nacex' => array(
					'nombre' => 'NACEX',
					'url'	 => 'https://www.nacex.es/irSeguimiento.do',
					),
				
				'redyser' => array(
					'nombre' => 'Redyser',
					'url'	 => 'https://www.redyser.com/seguimiento',
					),
				
				'seur' => array(
					'nombre' => 'SEUR',
					'url'	 => 'https://www.seur.com/livetracking/',
					),
				
				'ups' => array(
					'nombre' => 'UPS',
					'url'	 => 'https://www.ups.com/WebTracking/track?loc=es_ES',
					),
				
				'zeleris' => array(
					'nombre' => 'Zeleris',
					'url'	 => 'https://m.zeleris.com/infoexp.aspx?i=%ref%',
					),
				);

			$this->crea_agencias();
			}

		public static function existe_agencia ($agencia, $clave) {

			$post_agencia = null;

			if ($posts = get_posts (
				array(
					'name'				=> $clave,
					'post_type'			=> CPT_Seguimiento_Pedidos::CPT,
					'post_status'		=> 'any',
					'posts_per_page'	=> 1,
					)
				)) $post_agencia = $posts[0];

			return is_null ($post_agencia) ? false : $post_agencia->ID;
			}

		public function crea_agencias () {

			foreach ($this->agencias as $clave => $agencia) {

				$args = array(
					'post_title' 	=> $agencia['nombre'],
					'post_type'		=> CPT_Seguimiento_Pedidos::CPT,
					'post_status'	=> 'publish',
					'post_name'		=> $clave,
					);

				if (!$this->existe_agencia($agencia, $clave))
					if (is_int ($id_agencia = wp_insert_post ($args, false)))
						update_field ('url_seguimiento', $agencia['url'], $id_agencia);
				}
			}

		}

endif;