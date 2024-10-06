<?php
if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_WCPB_PREMIUM' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Implements features of FREE version of YITH WooCommerce Product Bundles
 *
 * @class   YITH_WCPB_Premium
 * @package YITH WooCommerce Product Bundles
 * @since   1.0.0
 * @author  Yithemes
 */

if ( ! class_exists( 'YITH_WCPB_Premium' ) ) {
	/**
	 * YITH WooCommerce Product Bundles
	 *
	 * @since 1.0.0
	 */
	class YITH_WCPB_Premium extends YITH_WCPB {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WCPB_Premium
		 * @since 1.0.0
		 */
		protected static $_instance;

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			parent::__construct();

			YITH_WCPB_Out_Of_Stock_Sync::get_instance();

			add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
			add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );
		}

		/**
		 * Register plugins for activation tab
		 *
		 * @return void
		 * @since 1.4.0
		 */
		public function register_plugin_for_activation() {
			if ( ! function_exists( 'YIT_Plugin_Licence' ) ) {
				require_once '../plugin-fw/lib/yit-plugin-licence.php';
			}
			YIT_Plugin_Licence()->register( YITH_WCPB_INIT, YITH_WCPB_SECRET_KEY, YITH_WCPB_SLUG );
		}

		/**
		 * Register plugins for update tab
		 *
		 * @return void
		 * @since 1.4.0
		 */
		public function register_plugin_for_updates() {
			if ( ! function_exists( 'YIT_Upgrade' ) ) {
				require_once '../plugin-fw/lib/yit-upgrade.php';
			}
			YIT_Upgrade()->register( YITH_WCPB_SLUG, YITH_WCPB_INIT );
		}
	}
}

/**
 * Unique access to instance of YITH_WCPB_Premium class
 *
 * @return YITH_WCPB_Premium
 * @deprecated since 1.2.0 use YITH_WCPB instead
 * @since      1.0.0
 */
function YITH_WCPB_Premium() {
	return YITH_WCPB();
}