<?php
/**
 * Booster for WooCommerce - Plus - Site Key Manager
 *
 * @version 6.0.2
 * @since  1.0.0
 * @author  Pluggabl LLC.
 * @package Booster_Plus_For_WooCommerce/plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Plus_Site_Key_Manager' ) ) :
		/**
		 * WCJ_Plus_Site_Key_Manager.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
	class WCJ_Plus_Site_Key_Manager {

		/**
		 * Constructor.
		 *
		 * @version 6.0.2
		 * @since  1.0.0
		 * @see     http://w-shadow.com/blog/2010/09/02/automatic-updates-for-any-plugin/
		 * @todo    (maybe) fix errors (empty metadata file etc.) `trigger_error` (now it's just disabled)
		 * @todo    (maybe) option to update to dev version
		 * @todo    (maybe) discount coupons
		 */
		public function __construct() {

			$this->update_server_url = 'https://' . wcj_plus_get_update_server();
			$this->site_url          = wcj_plus_get_site_url();

			// Key management.
			add_action( 'woojetpack_after_settings_save', array( $this, 'check_site_key_on_set_key' ), 10, 2 );
			add_action( 'admin_notices', array( $this, 'admin_notice_site_key_status' ) );
			add_action( 'admin_init', array( $this, 'check_site_key_manual' ) );

			// Debug.
			add_action( 'admin_notices', array( $this, 'debug_site_key_data' ), PHP_INT_MAX );

			// Cron.
			add_action( 'init', array( $this, 'schedule_the_events' ) );
			add_action( 'admin_init', array( $this, 'schedule_the_events' ) );
			add_action( 'wcj_check_site_key', array( $this, 'check_site_key_cron' ) );
			add_filter( 'cron_schedules', array( $this, 'site_key_frequently_weekly_cron' ) );
			$get_status = get_option( 'wcj_site_key_status' );
			// Handle plugins update.
			if ( isset( $get_status['server_response'] ) && isset( $get_status['server_response']->error ) && ( 'License is valid.' === $get_status['server_response']->error->message ) && ! empty( wcj_get_option( 'wcj_site_key' ) ) ) {
				require_once 'lib/plugin-update-checker/plugin-update-checker.php';
				$this->update_checker = new PluginUpdateChecker_3_1( $this->update_server_url . '/?alg_update_notification=get_metadata&alg_update_slug=booster-plus-for-woocommerce', WCJ_PLUGIN_FILE );
				$this->update_checker->addQueryArgFilter( array( $this, 'add_updater_query_args' ) );
			} else {
				wp_clear_scheduled_hook( 'check_plugin_updates-booster-plus-for-woocommerce' );
			}
			if ( is_admin() ) {
				add_action( 'after_plugin_row_' . plugin_basename( WCJ_PLUGIN_FILE ), array( $this, 'maybe_add_after_plugin_row_key_error_message' ), 1, 3 );
			}

		}

		/**
		 * Check_site_key_manual.
		 *
		 * @version 6.0.2
		 * @since  1.0.0
		 */
		public function check_site_key_manual() {
			$wpnonce = isset( $_GET['wcj_check_site_key_manual-nonce'] ) ? wp_verify_nonce( sanitize_key( $_GET['wcj_check_site_key_manual-nonce'] ), 'wcj_check_site_key_manual' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_check_site_key_manual'] ) ) {
				$this->check_site_key();
				wp_safe_redirect( remove_query_arg( 'wcj_check_site_key_manual' ) );
				exit;
			}
		}

		/**
		 * Debug_site_key_data.
		 *
		 * @version 6.0.2
		 * @since  1.0.0
		 */
		public function debug_site_key_data() {
			if ( isset( $_GET['wcj_site_key_debug'] ) ) {// phpcs:ignore WordPress.Security.NonceVerification
				$site_key_status = $this->get_site_key_status();
				$message         = '';
				$message        .= '<pre>' . wp_json_encode( $site_key_status, JSON_PRETTY_PRINT ) . '</pre>';
				/* translators: %s: search term */
				$message .= '<pre>' . sprintf( __( 'Status last checked at %s.', 'woocommerce-jetpack' ), gmdate( 'Y-m-d H:i:s', ( $site_key_status['time_checked'] ) ) ) . '</pre>';
				/* translators: %s: search term */
				$message .= '<pre>' . sprintf( __( 'Cron scheduled at %s.', 'woocommerce-jetpack' ), gmdate( 'Y-m-d H:i:s', ( wcj_get_option( 'wcj_check_site_key_cron_time_schedule', time() ) ) ) ) . '</pre>';
				/* translators: %s: search term */
				$message .= '<pre>' . sprintf( __( 'Cron last run at %s.', 'woocommerce-jetpack' ), gmdate( 'Y-m-d H:i:s', ( wcj_get_option( 'wcj_check_site_key_cron_time_last_run', time() ) ) ) ) . '</pre>';
				printf( '<div class="notice notice-info"><p>%s</p></div>', wp_kses_post( $message ) );
			}
		}

		/**
		 * Admin_notice_site_key_status.
		 *
		 * @version 6.0.2
		 * @since  1.0.0
		 */
		public function admin_notice_site_key_status() {
			$wpnonce = isset( $_GET['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_GET['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			if (
			$wpnonce &&
			isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] &&
			isset( $_GET['tab'] ) && 'jetpack' === $_GET['tab'] &&
			isset( $_GET['wcj-cat'] ) && 'dashboard' === $_GET['wcj-cat'] &&
			isset( $_GET['section'] ) && 'site_key' === $_GET['section']
			) {
				$site_key_status = $this->get_site_key_status();
				if ( false !== $site_key_status ) {
					$class   = ( $this->is_site_key_valid() ? 'notice notice-success is-dismissible' : 'notice notice-error' );
					$message = $this->get_site_key_status_message();
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
				}
			}
		}

		/**
		 * Get_site_key_status_message.
		 *
		 * @version 6.0.2
		 * @since  1.0.0
		 */
		public function get_site_key_status_message() {
			$site_key_status = $this->get_site_key_status();
			if ( isset( $site_key_status['server_response']->error->message ) ) {
				if ( 1 === $site_key_status['server_response']->status || true === $site_key_status['server_response']->status ) {
					return $site_key_status['server_response']->error->message;
				} else {
					return $site_key_status['server_response']->error->message . ' Please enter correct site key and save changes to validate it.';
				}
			} else {
				if ( isset( $site_key_status['client_data'] ) ) {
					switch ( $site_key_status['client_data'] ) {
						case 'EMPTY_SITE_KEY':
							return __( 'No key set.', 'woocommerce-jetpack' ) . ' ' .
							sprintf(
								/* translators: %s: search term */
								__( 'To get the key, please visit <a target="_blank" href="%1$s">your account page at %2$s</a>.', 'woocommerce-jetpack' ),
								'https://' . wcj_plus_get_update_server() . '/my-account/downloads/',
								wcj_plus_get_update_server()
							);
						case 'NO_RESPONSE':
							return sprintf(
								/* translators: %s: search term */
								__( 'No response from server. Please <a href="%s">try again</a> later.', 'woocommerce-jetpack' ),
								add_query_arg(
									array(
										'wcj_check_site_key_manual' => '1',
										'wcj_check_site_key_manual-nonce' => wp_create_nonce( 'wcj_check_site_key_manual' ),
									)
								)
							);
						case 'SERVER_ERROR':
							return sprintf(
								/* translators: %s: search term */
								__( 'Server error. Please <a href="%s">try again</a> later.', 'woocommerce-jetpack' ),
								add_query_arg(
									array(
										'wcj_check_site_key_manual' => '1',
										'wcj_check_site_key_manual-nonce' => wp_create_nonce( 'wcj_check_site_key_manual' ),
									)
								)
							);
					}
				}
				return false;
			}
		}

		/**
		 * Maybe_add_after_plugin_row_key_error_message.
		 *
		 * @version 6.0.2
		 * @since  1.0.0
		 * @param   string $plugin_file Get plugin_file.
		 * @param   array  $plugin_data Get plugin_data.
		 * @param   string $status Get status.
		 */
		public function maybe_add_after_plugin_row_key_error_message( $plugin_file, $plugin_data, $status ) {
			$site_key_status = $this->get_site_key_status_message();
			if ( ! $this->is_site_key_valid() && false !== $site_key_status ) {
				echo wp_kses_post(
					'<tr class="plugin-update-tr active" id="booster-plus-for-woocommerce-update-site-key" data-slug="booster-plus-for-woocommerce" data-plugin="' . plugin_basename( WCJ_PLUGIN_FILE ) . '">' .
					'<td colspan="4" class="plugin-update colspanchange">' .
						'<div class="update-message notice inline notice-warning notice-alt">' .
							'<p>' . $site_key_status . '</p>' .
						'</div>' .
					'</td>' .
					'</tr>'
				);
			}
		}

		/**
		 * Met_site_key_status.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
		public function get_site_key_status() {
			return wcj_get_option( 'wcj_site_key_status', false );
		}

		/**
		 * Is_site_key_valid.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
		public function is_site_key_valid() {
			$site_key_status = $this->get_site_key_status();
			return ( isset( $site_key_status['server_response']->status ) && $site_key_status['server_response']->status );
		}

		/**
		 * Update_site_key_status.
		 *
		 * @version 6.0.2
		 * @since  1.0.0
		 * @todo    user changes previously valid key to wrong key, and server error is returned - licence is still marked as valid (wrongly) (same in "WPCF Helper" plugin)
		 * @param   string $server_response Get server_response.
		 * @param   string $client_data Get client_data.
		 */
		public function update_site_key_status( $server_response, $client_data = '' ) {
			if ( in_array( $client_data, array( 'NO_RESPONSE', 'SERVER_ERROR' ), true ) && $this->is_site_key_valid() ) {
				// do not overwrite valid licence status with server error status.
				return;
			}
			update_option(
				'wcj_site_key_status',
				array(
					'server_response' => $server_response,
					'client_data'     => $client_data,
					'time_checked'    => time(),
				)
			);
		}

		/**
		 * Check_site_key.
		 *
		 * @version 6.0.2
		 * @since  1.0.0
		 */
		public function check_site_key() {
			$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			if ( $wpnonce && isset( $_POST['wcj_site_key'] ) ) {
				$site_key = sanitize_key( wp_unslash( $_POST['wcj_site_key'] ) );
			} else {
				$site_key = $this->get_site_key();
			}
			if ( '' !== $site_key && null !== $site_key && ! empty( $site_key ) ) {
				$url = $this->update_server_url . '/?check_site_key=' . $site_key . '&item_slug=booster-plus-for-woocommerce&site_url=' . $this->site_url;
				if ( ! function_exists( 'download_url' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}
				$response_file_name = download_url( $url );
				if ( ! is_wp_error( $response_file_name ) ) {
					$response = wp_remote_get( $url );
					$response = $response['body'];
					if ( $response ) {
						$this->update_site_key_status( json_decode( $response ) );
					} else {
						$this->update_site_key_status( array(), 'NO_RESPONSE' );
					}
					unlink( $response_file_name );
				} else {
					$this->update_site_key_status( array(), 'SERVER_ERROR' );
				}
			} else {
				$this->update_site_key_status( array(), 'EMPTY_SITE_KEY' );
			}
		}

		/**
		 * Check_site_key_on_set_key.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 * @param   mixed $sections Get sections.
		 * @param   mixed $current_section Get current_section.
		 */
		public function check_site_key_on_set_key( $sections, $current_section ) {
			if ( 'site_key' === $current_section ) {
				$this->check_site_key();

				wp_safe_redirect( add_query_arg( '', '' ) );
				exit;
			}
		}

		/**
		 * Add_updater_query_args.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 * @param   array $query Get query.
		 */
		public function add_updater_query_args( $query ) {
			$query['alg_site_key'] = $this->get_site_key();
			$query['alg_site_url'] = $this->site_url;
			return $query;
		}

		/**
		 * Get_site_key.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
		public function get_site_key() {
			return wcj_get_option( 'wcj_site_key', '' );
		}

		/**
		 * Site_key_frequently_weekly_cron.
		 *
		 * @version 6.0.0
		 * @since   1.0.6
		 * @param   array $schedules Get schedules.
		 */
		public function site_key_frequently_weekly_cron( $schedules ) {
			$schedules['weekly'] = array(
				'interval' => 604800, // that's how many seconds in a week, for the unix timestamp.
				'display'  => __( 'weekly' ),
			);
			return $schedules;
		}

		/**
		 * Schedule_the_events.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 */
		public function schedule_the_events() {
			$event_timestamp = wp_next_scheduled( 'wcj_check_site_key', array( 'weekly' ) );
			update_option( 'wcj_check_site_key_cron_time_schedule', $event_timestamp );
			if ( ! $event_timestamp ) {
				// Remove Old Version daily cron.
				wp_clear_scheduled_hook( 'wcj_check_site_key', array( 'daily' ) );
				wp_schedule_event( time(), 'weekly', 'wcj_check_site_key', array( 'weekly' ) );
			}
		}

		/**
		 * On the scheduled action hook, run a function.
		 *
		 * @version 6.0.0
		 * @since  1.0.0
		 * @param  mixed $interval Get interval.
		 */
		public function check_site_key_cron( $interval ) {
			update_option( 'wcj_check_site_key_cron_time_last_run', time() );
			$this->check_site_key();
		}

	}

endif;

return new WCJ_Plus_Site_Key_Manager();
