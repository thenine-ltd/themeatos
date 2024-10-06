<?php
/**
 * Members Class
 *
 * @package YITH\Membership\Classes
 * @since   1.0.5
 * @author  YITH <plugins@yithemes.com>
 * @class   YITH_WCMBS_Downloads_Report
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Downloads_Report' ) ) {
	/**
	 * Members Class
	 *
	 * @class   YITH_WCMBS_Downloads_Report
	 * @package Yithemes
	 * @since   1.0.5
	 */
	class YITH_WCMBS_Downloads_Report {

		use YITH_WCMBS_Singleton_Trait;

		/**
		 * The name of the Download Report table
		 *
		 * @var string $table_name
		 */
		public $table_name = '';

		/**
		 * API endpoints for looking up user IP address.
		 *
		 * @var array
		 */
		private $ip_lookup_apis = array(
			'icanhazip'         => 'http://icanhazip.com',
			'ipify'             => 'http://api.ipify.org/',
			'ipecho'            => 'http://ipecho.net/plain',
			'ident'             => 'http://ident.me',
			'whatismyipaddress' => 'http://bot.whatismyipaddress.com',
		);

		/**
		 * Version of database
		 *
		 * @var string $dv_version
		 */
		protected static $db_version = '1.0.2';

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.5
		 */
		public function __construct() {
			global $wpdb;
			$this->table_name = $wpdb->prefix . 'yith_wcmbs_downloads_log';

			add_action( 'yith_wcmbs_add_download_report', array( $this, 'add_report' ), 10, 3 );
		}

		/**
		 * Add new Report in db
		 *
		 * @param int    $product_id The product ID.
		 * @param int    $user_id    The user ID.
		 * @param string $type       The report type.
		 *
		 * @since  1.0.5
		 */
		public function add_report( $product_id, $user_id, $type = '' ) {
			global $wpdb;

			$ip_address   = $this->get_user_ip();
			$insert_query = "INSERT INTO 
                          $this->table_name (`type`, `product_id`, `user_id`, `user_ip_address`, `timestamp_date`) 
                          VALUES 
                          ('" . $type . "', '" . $product_id . "', '" . $user_id . "', '" . $ip_address . "', CURRENT_TIMESTAMP() )";
			$wpdb->query( $insert_query );
		}

		/**
		 * Retrieve the user IP address
		 *
		 * @return string
		 */
		public function get_user_ip() {
			$use_external_services = 'yes' === yith_wcmbs_settings()->get_option( 'yith-wcmbs-use-external-services-to-get-user-ip-address' );
			$ip_address            = '';
			if ( $use_external_services ) {
				$ip_lookup_services      = $this->ip_lookup_apis;
				$ip_lookup_services_keys = array_keys( $ip_lookup_services );
				shuffle( $ip_lookup_services_keys );

				foreach ( $ip_lookup_services_keys as $service_name ) {
					$service_endpoint = $ip_lookup_services[ $service_name ];
					$response         = wp_safe_remote_get( $service_endpoint, array( 'timeout' => 2 ) );

					if ( ! is_wp_error( $response ) && rest_is_ip_address( $response['body'] ) ) {
						$ip_address = wc_clean( $response['body'] );
						break;
					}
				}
			} elseif ( is_callable( 'WC_Geolocation::get_ip_address' ) ) {
				$ip_address = WC_Geolocation::get_ip_address();
			}

			return $ip_address;
		}


		/**
		 * Get the downloads count
		 *
		 * @param array $args the query arguments.
		 *
		 * @return int
		 */
		public function count_downloads( $args ) {
			global $wpdb;

			$where    = '';
			$distinct = '*';

			$where_array = array();
			if ( isset( $args['where'] ) ) {
				foreach ( $args['where'] as $s_where ) {
					if ( isset( $s_where['key'] ) ) {
						$value   = '';
						$compare = '=';
						if ( isset( $s_where['value'] ) ) {
							$value = $s_where['value'];
						} else {
							$compare = '!=';
						}

						if ( isset( $s_where['compare'] ) ) {
							$compare = $s_where['compare'];
						}

						$where_array[] = $s_where['key'] . ' ' . $compare . ' "' . $value . '"';
					}
				}
			}

			if ( ! empty( $where_array ) ) {
				$where = 'WHERE ' . implode( ' AND ', $where_array );
			}

			if ( isset( $args['distinct'] ) ) {
				$distinct = 'DISTINCT ' . $args['distinct'];
			}

			$results = $wpdb->get_var( "SELECT COUNT($distinct) FROM $this->table_name $where" );

			return absint( $results );
		}

		/**
		 * Get download reports
		 *
		 * @param array $args The query arguments.
		 * @return array|stdClass[]
		 */
		public function get_download_reports( $args ) {
			global $wpdb;

			$where    = '';
			$order_by = '';
			$group_by = '';
			$join     = '';
			$select   = '*';

			if ( isset( $args['select'] ) ) {
				$select = $args['select'];
			}

			if ( isset( $args['group_by'] ) ) {
				$group_by = 'GROUP BY ' . $args['group_by'];
			}

			if ( isset( $args['order_by'] ) ) {
				$order_by = 'ORDER BY ' . $args['order_by'];
				if ( isset( $args['order'] ) ) {
					$order_by .= ' ' . $args['order'];
				}
			}

			if ( isset( $args['join'] ) ) {
				$join = $args['join'];
			}

			$where_array = array();
			if ( isset( $args['where'] ) ) {
				foreach ( $args['where'] as $s_where ) {
					if ( isset( $s_where['key'] ) ) {
						$value   = '';
						$compare = '=';
						if ( isset( $s_where['value'] ) ) {
							$value = $s_where['value'];
						} else {
							$compare = '!=';
						}

						if ( isset( $s_where['compare'] ) ) {
							$compare = $s_where['compare'];
						}

						$where_array[] = $s_where['key'] . ' ' . $compare . ' "' . $value . '"';
					}
				}
			}

			if ( ! empty( $where_array ) ) {
				$where = 'WHERE ' . implode( ' AND ', $where_array );
			}

			$query = "SELECT $select FROM $this->table_name $join $where $group_by $order_by";

			if ( isset( $args['debug'] ) && ! ! $args['debug'] ) {
				echo '<pre>';
				var_dump( $query );
				echo '</pre>';
			}

			$results = $wpdb->get_results( $query );

			return $results;
		}

		/**
		 * Get download IDs for a specific user
		 *
		 * @param int $user_id The user ID.
		 * @return array
		 */
		public function get_download_ids_for_user( $user_id ) {
			global $wpdb;
			$query = "SELECT product_id FROM $this->table_name WHERE user_id = %s";

			$ids = $wpdb->get_col( $wpdb->prepare( $query, absint( $user_id ) ) );

			return array_unique( $ids );
		}

		/**
		 * Check if there are downloads
		 *
		 * @return  bool
		 * @since 1.4.0
		 */
		public function has_downloads() {
			global $wpdb;
			$query = "SELECT 1 FROM $this->table_name LIMIT 1";

			return ! ! $wpdb->get_var( $query );
		}

	}
}
if ( ! function_exists( 'yith_wcmbs_downloads_report' ) ) {

	/**
	 * Unique access to instance of YITH_WCMBS_Downloads_Report class
	 *
	 * @return YITH_WCMBS_Downloads_Report
	 * @since 1.0.5
	 */
	function yith_wcmbs_downloads_report() {
		return YITH_WCMBS_Downloads_Report::get_instance();
	}
}
