<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Members Class
 *
 * @class   YITH_WCMBS_Reports
 * @since   1.0.5
 * @author  YITH <plugins@yithemes.com>
 * @package Yithemes
 */
class YITH_WCMBS_Reports {

	/**
	 * Single instance of the class
	 *
	 * @since 1.0.5
	 * @var \YITH_WCMBS_Reports
	 */
	private static $_instance;

	/**
	 * Returns single instance of the class
	 *
	 * @since 1.0.5
	 * @return \YITH_WCMBS_Reports
	 */
	public static function get_instance() {
		return ! is_null( self::$_instance ) ? self::$_instance : self::$_instance = new self();
	}

	/**
	 * Constructor
	 *
	 * @access public
	 * @since  1.0.5
	 */
	private function __construct() {
		YITH_WCMBS_Downloads_Report();

		if ( is_admin() ) {
			add_action( 'yith_wcmbs_membership_report_tab', array( $this, 'render_reports_page' ) );
			add_action( 'yith_wcmbs_membership_reports', array( $this, 'render_membership_reports' ) );
			add_action( 'yith_wcmbs_download_reports', array( $this, 'render_download_reports' ) );

			add_action( 'wp_ajax_yith_wcmbs_get_download_table_reports', array( $this, 'get_download_table_reports' ) );
			add_action( 'wp_ajax_yith_wcmbs_get_download_report', array( $this, 'get_download_report' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 11 );
			add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_id_to_woocommerce' ), 10, 1 );

			$is_ajax = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : ( defined( 'DOING_AJAX' ) && DOING_AJAX );

			if ( YITH_WCMBS_Products_Manager()->is_allowed_download() && ! $is_ajax ) {
				add_action( 'add_meta_boxes', array( $this, 'add_user_downloads_metabox_in_orders' ) );
			}

			// downloads by user
			add_action( 'wp_ajax_yith_wcmbs_get_download_reports_by_user', array( $this, 'get_download_reports_by_user' ) );
			add_action( 'wp_ajax_yith_wcmbs_get_download_reports_details_by_user', array( $this, 'get_download_reports_details_by_user' ) );
			add_action( 'wp_ajax_yith_wcmbs_get_download_reports_details_by_user_table', array( $this, 'get_download_reports_details_by_user_table' ) );
		}
	}

	/**
	 * Get download reports by user
	 */
	public function get_download_reports_by_user() {
		$current_error_reporting = error_reporting();
		error_reporting( 0 );

		$table = new YITH_WCMBS_Download_Reports_By_User_Table();
		$table->ajax_response();

		// Enable display_errors
		error_reporting( $current_error_reporting );
		die();
	}

	/**
	 * Get download reports details by user
	 */
	public function get_download_reports_details_by_user() {
		$current_error_reporting = error_reporting();
		error_reporting( 0 );

		$table = new YITH_WCMBS_Download_Reports_Details_By_User_Table();
		$table->ajax_response();

		// Enable display_errors
		error_reporting( $current_error_reporting );
		die();
	}

	/**
	 * Get download reports details by user table
	 */
	public function get_download_reports_details_by_user_table() {
		$user_id = isset( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : false;
		if ( ! $user_id ) {
			die();
		}

		?>
		<div id="yith-wcmbs-reports-downloads-content-downloads-by-user-<?php echo esc_attr( $user_id ); ?>" class="yith-wcmbs-reports-downloads-content" style="display:none;">
			<?php yith_wcmbs_get_view( '/reports/download-reports-downloads-details-by-user.php', compact( 'user_id' ) ); ?>
		</div>
		<?php

		die();
	}

	/**
	 * Add user downloads meta-box in orders
	 *
	 * @since    2.0.0
	 * @param string $post_type
	 *
	 */
	public function add_user_downloads_metabox_in_orders( $post_type ) {
		if ( $post_type === yith_wcmbs_get_wc_order_screen_id() ) {
			add_meta_box(
				'yith-wcmbs-user-download-reports',
				__( 'Membership download reports', 'yith-woocommerce-membership' ),
				array( $this, 'render_user_downloads_metabox_in_orders' ),
				null,
				'normal',
				'low'
			);
		}
	}

	/**
	 * Render user downloads meta-box in Order editing page
	 *
	 * @since 2.0.0
	 * @param WP_Post|WC_Order $order The post or the order.
	 * @return void
	 */
	public function render_user_downloads_metabox_in_orders( $order ) {
		$order   = wc_get_order( $order );
		$user_id = $order->get_user_id();

		echo '<div class="yith-wcmbs-reports-content">';
		yith_wcmbs_get_view( '/reports/download-reports-graphics.php', compact( 'user_id' ) );
		echo '</div>';

		echo '<div id="yith-wcmbs-download-reports-downloads-by-product"><div class="yith-wcmbs-reports-download-reports-table">';
		yith_wcmbs_get_view( '/reports/download-reports-table.php', compact( 'user_id' ) );
		echo '</div></div>';
	}

	/**
	 * Render reports page
	 */
	public function render_reports_page() {
		yith_wcmbs_get_view( '/reports/reports.php' );
	}

	/**
	 * Render membership reports
	 */
	public function render_membership_reports() {
		yith_wcmbs_get_view( '/reports/membership-reports.php' );
	}

	/**
	 * Render download reports
	 */
	public function render_download_reports() {
		yith_wcmbs_get_view( '/reports/download-reports.php' );
	}

	/*
	 * get download table reports [AJAX]
	 */
	public function get_download_table_reports() {
		yith_wcmbs_get_view( '/reports/download-reports-table.php' );
		die();
	}

	/**
	 * Get download reports [AJAX]
	 */
	public function get_download_report() {
		$type = isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : 'downloads-by-product';

		yith_wcmbs_get_view( "/reports/download-reports-{$type}.php" );

		die();
	}

	/**
	 * Admin enqueue scripts
	 */
	public function admin_enqueue_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$screen = get_current_screen();

		wp_register_style( 'yith_wcmbs_menu_styles', YITH_WCMBS_ASSETS_URL . '/css/menu.css' );
		wp_register_style( 'yith_wcmbs_reports_styles', YITH_WCMBS_ASSETS_URL . '/css/reports.css' );

		wp_register_script( 'yith_wcmbs_menu_js', YITH_WCMBS_ASSETS_URL . '/js/menu' . $suffix . '.js', array( 'jquery' ), YITH_WCMBS_VERSION, true );
		wp_register_script( 'yith_wcmbs_reports_js', YITH_WCMBS_ASSETS_URL . '/js/reports' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-tiptip', 'jquery-ui-tabs' ), YITH_WCMBS_VERSION, true );

		if ( in_array( $screen->id, array( 'yith-plugins_page_yith_wcmbs_panel', yith_wcmbs_get_wc_order_screen_id() ) ) ) {
			wp_enqueue_style( 'yith_wcmbs_menu_styles' );
			wp_enqueue_style( 'yith_wcmbs_reports_styles' );

			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_script( 'yith_wcmbs_menu_js' );
			wp_enqueue_script( 'yith_wcmbs_reports_js' );
		}

	}

	/**
	 * Add Screen IDs to WooCommerce
	 *
	 * @param string[] $screen_ids The screen IDs.
	 *
	 * @return string[]
	 */
	public function add_screen_id_to_woocommerce( $screen_ids ) {
		$screen_ids[] = 'yith-wcmbs-plan_page_yith-wcmbs-reports';

		return $screen_ids;
	}

	/**
	 * Deprecated
	 */

	/**
	 * Add user downloads meta-box in orders
	 *
	 * @since      1.0
	 * @param string $post_type
	 * @deprecated since 2.0
	 * @see        YITH_WCMBS_Reports::add_user_downloads_metabox_in_orders()
	 */
	public function add_user_donwloads_metabox_in_orders( $post_type ) {
		wc_deprecated_function( 'YITH_WCMBS_Reports::add_user_donwloads_metabox_in_orders', '2.0.0', 'YITH_WCMBS_Reports::add_user_downloads_metabox_in_orders' );
		$this->add_user_downloads_metabox_in_orders( $post_type );
	}

	/**
	 * Render user downloads meta-box in Order editing page
	 *
	 * @param WP_Post|WC_Order $order The post or the order.
	 * @return void
	 */
	public function render_user_donwloads_metabox_in_orders( $order ) {
		wc_deprecated_function( 'YITH_WCMBS_Reports::render_user_donwloads_metabox_in_orders', '2.0.0', 'YITH_WCMBS_Reports::render_user_downloads_metabox_in_orders' );
		$this->render_user_downloads_metabox_in_orders( $order );
	}

}

/**
 * Unique access to instance of YITH_WCMBS_Reports class
 *
 * @since 1.0.5
 * @return YITH_WCMBS_Reports
 */
function YITH_WCMBS_Reports() {
	return YITH_WCMBS_Reports::get_instance();
}