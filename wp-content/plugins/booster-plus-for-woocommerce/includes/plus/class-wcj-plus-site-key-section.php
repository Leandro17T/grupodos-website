<?php
/**
 * Booster for WooCommerce - Plus - Site Key Section
 *
 * @version 6.0.2
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Plus_Site_Key_Section' ) ) :
		/**
		 * WCJ_Plus_Site_Key_Section.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
	class WCJ_Plus_Site_Key_Section {

		/**
		 * Constructor.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
		public function __construct() {
			if ( is_admin() ) {

				$this->update_server = wcj_plus_get_update_server();
				$this->site_url      = wcj_plus_get_site_url();

				add_filter( 'plugin_action_links_' . plugin_basename( WCJ_PLUGIN_FILE ), array( $this, 'add_manage_key_action_link' ), 10, 4 );
				add_filter( 'wcj_admin_bar_dashboard_nodes', array( $this, 'add_site_key_admin_bar_dashboard_node' ) );
				add_filter( 'wcj_modules', array( $this, 'add_site_key_module' ) );
				add_filter( 'wcj_custom_dashboard_modules', array( $this, 'add_site_key_section' ) );
			}
		}

		/**
		 * Add_manage_key_action_link.
		 *
		 * @version 6.0.2
		 * @since  1.0.0
		 * @param   mixed $actions Define actions..
		 * @param   mixed $plugin_file Get plugin files.
		 * @param   mixed $plugin_data Get plugin data.
		 * @param   mixed $context Get context.
		 * @return  array
		 */
		public function add_manage_key_action_link( $actions, $plugin_file, $plugin_data, $context ) {
			$custom_actions = array(
				'<a href="' . admin_url( wcj_admin_tab_url() . '&wcj-cat=dashboard&section=site_key' ) . '">' .
					__( 'Manage site key', 'woocommerce-jetpack' ) . '</a>',
			);
			return array_merge( $actions, $custom_actions );
		}

		/**
		 * Add_site_key_admin_bar_dashboard_node.
		 *
		 * @version 6.0.2
		 * @since  1.0.0
		 * @param   Array $nodes Get dashboard nodes.
		 */
		public function add_site_key_admin_bar_dashboard_node( $nodes ) {
			$nodes['site_key'] = array(
				'title' => __( 'Site Key', 'woocommerce-jetpack' ),
				'href'  => admin_url( wcj_admin_tab_url() . '&wcj-cat=dashboard&section=site_key' ),
			);
			return $nodes;
		}

		/**
		 * Add_site_key_section.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 * @param   Array $custom_dashboard_modules Get custom dashboard modules.
		 */
		public function add_site_key_section( $custom_dashboard_modules ) {
			$custom_dashboard_modules['site_key'] = array(
				'title'    => '<span><img src="' . wcj_plugin_url() . '/assets/images/key.png"></span>' . __( 'Site Key', 'woocommerce-jetpack' ),
				'desc'     => __( 'This section lets you manage site key for paid Booster Plus for WooCommerce plugin.', 'woocommerce-jetpack' ),
				'settings' => array(
					array(
						'title'   => __( 'Site Key', 'woocommerce-jetpack' ),
						/* translators: %s: translators Added */
						'desc'    => sprintf( __( 'Site URL: %s', 'woocommerce-jetpack' ), '<code>' . $this->site_url . '</code>' ),
						'type'    => 'text',
						'id'      => 'wcj_site_key',
						'default' => '',
						'class'   => 'widefat',
					),
				),
			);
			return $custom_dashboard_modules;
		}

		/**
		 * Add_site_key_module.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 * @param   Array $modules_array Get modules.
		 */
		public function add_site_key_module( $modules_array ) {
			$modules_array['dashboard']['all_cat_ids'][] = 'site_key';
			return $modules_array;
		}

	}

endif;

return new WCJ_Plus_Site_Key_Section();
