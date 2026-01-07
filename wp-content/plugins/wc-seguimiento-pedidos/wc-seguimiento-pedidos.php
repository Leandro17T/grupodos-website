<?php

/**
 *
 * Plugin Name: 			Seguimiento de envíos en Woo
 * Description: 			Proporciona al cliente la información para el seguimiento del envío del pedido
 * Plugin URI: 				https://www.enriquejros.com/plugins/seguimiento-envios-woocommerce/
 * Author: 					Enrique J. Ros
 * Author URI: 				https://www.enriquejros.com/
 * Version: 				3.1.0
 * License: 				Copyright 2018 - 2023 Enrique J. Ros (email: enrique@enriquejros.com)
 * Text Domain: 			seguimiento-pedidos
 * Domain Path: 			/lang/
 * Requires at least:		5.0
 * Tested up to:			6.4
 * Requires PHP: 			7.3
 * WC requires at least:	6.0
 * WC tested up to: 		8.3
 *
 * @author 					Enrique J. Ros
 * @link					https://www.enriquejros.com
 * @since					1.0.0
 * @package					SeguimientoPedidos
 *
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

defined ('ABSPATH') or exit;

if (!class_exists ('Plugin_Seguimiento_Pedidos')) :

	#[AllowDynamicProperties]

	Class Plugin_Seguimiento_Pedidos {

		private static $instancia;

		private function __construct () {

			$this->nombre   = __('Seguimiento de envíos en Woo', 'seguimiento-pedidos');
			$this->campos   = 'Campos_Seguimiento_Pedidos';
			$this->domain   = 'seguimiento-pedidos';
			$this->json     = 'seguimiento';
			$this->archivos = ['helper', 'metabox', 'cuenta', 'agencias', 'cpt', 'campos', 'email', 'setup', 'orders', 'preview'];
			$this->clases   = ['Metabox_Seguimiento_Pedidos', 'Cuenta_Seguimiento_Pedidos', 'CPT_Seguimiento_Pedidos', 'Email_Seguimiento_Pedidos', 'Orders_Seguimiento_Pedidos', 'Preview_Seguimiento_Pedidos'];
			$this->dirname  = dirname (__FILE__);

			$this->carga_archivos();
			$this->carga_traducciones();
			$this->actualizaciones();

			register_activation_hook (__FILE__, function () {

				new Setup_Seguimiento_Pedidos;
				set_transient ($this->domain . '-activado', true, 5);
				}, 10);

			add_action ('init', [$this, 'arranca_plugin'], 10);
			add_action ('admin_notices' , [$this, 'aviso_ayuda'], 10);
			add_action ('before_woocommerce_init', [$this, 'compatibilidad_hpos'], 10);

			add_filter ('plugin_action_links', [$this, 'enlaces_accion'], 10, 2);

			$this->gestor = 'edit.php?post_type=' . CPT_Seguimiento_Pedidos::CPT;
			}

		public function __clone () {

			_doing_it_wrong (__FUNCTION__, sprintf (__('No puedes clonar instancias de %s.', 'seguimiento-pedidos'), get_class ($this)), '2.0.0');
			}

		private function carga_archivos () {

			foreach ($this->archivos as $archivo)
				require (sprintf ('%s/%s.php', $this->dirname, $archivo));

			if (!class_exists ('acf')) {

				/**
				 * @since 	5.0.0
				 *
				 * Para deshabilitar la constante ACF_LITE definida por el plugin:
				 *
				 * add_filter ('ejr_acf_lite', '__return_false');
				 *
				 */
				if (!defined ('ACF_LITE'))
					add_action ('after_setup_theme', function () {
						define ('ACF_LITE', apply_filters ('ejr_acf_lite', true));
						}, 10);

				require ($this->dirname . '/includes/acf-pro/acf.php');
				}
					
			require ($this->dirname . '/campos.php');
			new $this->campos;
			}

		/**
		 * Declaramos la compatibilidad con HPOS
		 *
		 * @since 	3.0.0
		 *
		 */
		public function compatibilidad_hpos () {

			if (class_exists (\Automattic\WooCommerce\Utilities\FeaturesUtil::class))
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
			}

		public function arranca_plugin () {

			if ($this->woocommerce_activo())
				foreach ($this->clases as $clase)
					new $clase;
			}

		private function woocommerce_activo () {

			if (!class_exists ('WooCommerce')) {

				add_action ('admin_notices', function () {
					?>
						<div class="notice notice-error is-dismissible">
							<p><?php printf (__('El plugin %s necesita que Woo esté activado. Por favor, activa Woo primero.', 'seguimiento-pedidos'), '<i>' . $this->nombre . '</i>'); ?></p>
						</div>
					<?php
					}, 10);

				return false;
				}

			/**
			 * Vamos a comprobar si HPOS está activo.
			 * 
			 * @since 	5.0.0
			 * 
			 * Usado en:
			 * 
			 * 		metabox.php
			 * 
			 */
			if (!defined ('HPOS_ACTIVO')) //Puede estar ya definido por otro de mis plugins.
				define ('HPOS_ACTIVO', OrderUtil::custom_orders_table_usage_is_enabled() ? true : false);

			return true;
			}

		public function aviso_ayuda () {

			if (get_transient ($this->domain . '-activado')) {

				?>
					<div class="updated notice is-dismissible woocommerce-message">
						<p><?php printf ( __('Gracias por usar %s. Se han creado algunas agencias por defecto, puedes gestionarlas (editarlas, eliminarlas, añadir las tuyas...) en la página de gestión de agencias.', 'seguimiento-pedidos'), '<i>' . $this->nombre . '</i>' ); ?></p>
						<p><?php printf ('<a href="%s" class="button button-primary">%s</a>', $this->gestor, __('Gestionar agencias', 'seguimiento-pedidos')); ?></p>
					</div>
				<?php
				}
			}

		public function carga_traducciones () {

			$locale = function_exists ('determine_locale') ? determine_locale() : (is_admin() && function_exists ('get_user_locale') ? get_user_locale() : get_locale());
			$locale = apply_filters ('plugin_locale', $locale, $this->domain);

			unload_textdomain ($this->domain);
			load_textdomain ($this->domain, $this->dirname . '/lang/' . $this->domain . '-' . $locale . '.mo');
			load_plugin_textdomain ($this->domain, false, $this->dirname . '/lang');
			}

		public function enlaces_accion ($damelinks, $plugin) {

			static $seguiped;
			isset ($seguiped) or $seguiped = plugin_basename (__FILE__);

			if ($seguiped == $plugin) {

				$enlaces['settings'] = '<a href="' . $this->gestor . '">' . __('Gestionar agencias', 'seguimiento-pedidos') . '</a>';
				$enlaces['support']  = '<a target="_blank" href="https://www.enriquejros.com/soporte/">' . __('Soporte', 'seguimiento-pedidos') . '</a>';
				$damelinks = array_merge ($enlaces, $damelinks);
				}
			
			return $damelinks;
			}

		public function actualizaciones () {

			include_once ($this->dirname . '/includes/updates/plugin-update-checker.php');
			$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker('https://www.enriquejros.com/wp-content/descargas/updates/' . $this->json . '.json', __FILE__, $this->json);
			}

		public static function instancia () {

			if (null === self::$instancia)
				self::$instancia = new self();

			return self::$instancia;
			}

		}

endif;

Plugin_Seguimiento_Pedidos::instancia();