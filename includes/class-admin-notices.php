<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GF_Billingo_Admin_Notices', false ) ) :

	class GF_Billingo_Admin_Notices {

		//Notices
		private static $notices = array(
			'welcome' => array(
				'hide' => 'no',
			),
			'request_review' => array(
				'hide'     => 'yes',
				'interval' => '+5 days',
			),
      'error' => array(
				'hide' => 'no',
			),
		);

		//Init notices
		public static function init() {
			add_action( 'admin_init', array( __CLASS__, 'init_notices' ), 1 );
			add_action( 'admin_init', array( __CLASS__, 'hide_notice' ) );
			add_action( 'admin_head', array( __CLASS__, 'enqueue_notices' ) );
			add_action( 'wp_ajax_gf_billingo_hide_notice', array( __CLASS__, 'ajax_hide_notice' ) );
		}

		//Init notices array
		public static function init_notices() {
			$store_notices = get_user_meta( get_current_user_id(), 'gf_billingo_admin_notices', true );
			self::$notices = wp_parse_args( empty( $store_notices ) ? array() : $store_notices, self::$notices );
		}

		//Add notices to admin_notices hook
		public static function enqueue_notices() {

			if ( ! current_user_can( 'gform_full_access' ) ) {
				return;
			}


			foreach ( self::$notices as $key => $notice ) {

				if ( 'yes' === $notice['hide'] && ! isset( $notice['display_at'] ) && ! empty( $notice['interval'] ) ) {
					self::add_notice( $key, true );
				}

				if ( ! empty( $notice['display_at'] ) && time() > $notice['display_at'] ) {
					$notice['hide'] = 'no';
				}

				if ( 'no' === $notice['hide'] && $key != 'error') {
					add_action( 'admin_notices', array( __CLASS__, 'display_' . $key . '_notice' ) );
				}
			}

			//Always enque error notice
			add_action( 'admin_notices', array( __CLASS__, 'display_error_notice' ) );
		}

		//Add a notice to display/
		public static function add_notice( $notice, $delay = false ) {
			if ( ! empty( self::$notices[ $notice ] ) ) {
				if ( empty( $delay ) ) {
					self::$notices[ $notice ]['hide'] = 'no';
				} elseif ( ! empty( self::$notices[ $notice ]['interval'] ) ) {
					self::$notices[ $notice ]['hide']       = 'yes';
					self::$notices[ $notice ]['display_at'] = strtotime( self::$notices[ $notice ]['interval'] );
				}

				update_user_meta( get_current_user_id(), 'gf_billingo_admin_notices', self::$notices );
			}
		}

		//Remove a notice
		public static function remove_notice( $notice ) {

			self::$notices[ $notice ]['hide']       = 'yes';
			self::$notices[ $notice ]['interval']   = '';
			self::$notices[ $notice ]['display_at'] = '';

			if($notice == 'error') {
				delete_option( '_gf_billingo_error' );
			}

			update_user_meta( get_current_user_id(), 'gf_billingo_admin_notices', self::$notices );
		}

		//Hide a notice via ajax.
		public static function ajax_hide_notice() {

			check_ajax_referer( 'gf-billingo-hide-notice', 'security' );

			if ( isset( $_POST['notice'] ) ) {

				if ( ! current_user_can( 'gform_full_access' ) ) {
					wp_die( esc_html__( 'Cheatin&#8217; huh?', 'gf-billingo' ) );
				}

				$notice = sanitize_text_field( wp_unslash( $_POST['notice'] ) );

				if ( ! empty( $_POST['remind'] ) && 'yes' === $_POST['remind'] ) {
					self::add_notice( $notice, true );
				} else {
					self::remove_notice( $notice );
				}
			}

			wp_die();
		}

		//Hide welcome notice
		public static function hide_notice() {
			// Welcome notice.
			if ( ! empty( $_GET['welcome'] ) && ! empty( $_GET['page'] ) && $_GET['page'] == 'gf_settings' && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'gf-billingo-hide-notice' ) ) {
				self::remove_notice( 'welcome' );
			}
		}

		//If we have just installed, show a welcome message
		public static function display_welcome_notice() {
			include( dirname( __FILE__ ) . '/views/html-notice-welcome.php' );
		}

		//Request review notice
		public static function display_request_review_notice() {
			include( dirname( __FILE__ ) . '/views/html-notice-request-review.php' );
		}

		//Curl required notice
    public static function display_error_notice() {
			if(get_option('_gf_billingo_error') && current_user_can( 'gform_full_access' )) {
				$order_number = get_option('_gf_billingo_error');
				$entry = GFAPI::get_entry( $order_number );
				$order_link = admin_url('admin.php?page=gf_entries&view=entry&id='.$entry['form_id'].'&lid='.$order_number);
				include( dirname( __FILE__ ) . '/views/html-notice-error.php' );
			}
		}

	}

	GF_Billingo_Admin_Notices::init();

endif;
