<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 08/11/2018
 * Time: 10:15 SA
 */

namespace WooVR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Add_Multi_Reviews {

	protected static $instance = null;
	protected $current_time;

	function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'woo_virtual_reviews_asset' ) );
		add_action( 'admin_head', array( $this, 'my_custom_fonts' ) );

		//Admin comment page
		add_filter( 'admin_comment_types_dropdown', array( $this, 'wvr_admin_comment_types_dropdown' ) );
		add_filter( 'comments_list_table_query_args', [ $this, 'filter_virtual_review' ] );

		/* Admin reviews page*/
		add_filter( 'woocommerce_product_reviews_list_table_item_types', [ $this, 'add_review_filter_type' ] );
		add_filter( 'woocommerce_product_reviews_list_table_prepare_items_args', [ $this, 'convert_virtual_to_reviews' ] );

		//Admin product page
		add_action( 'manage_posts_custom_column', array( $this, 'show_virtual_review_count' ), 10, 2 );
		add_filter( 'manage_edit-product_columns', array( $this, 'change_columns_filter' ), 20 );
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'my_sortable_cake_column' ) );
		add_action( 'pre_get_posts', array( $this, 'my_slice_orderby' ) );
		add_filter( 'bulk_actions-edit-product', array( $this, 'register_delete_virtual_reviews' ) );
		add_filter( 'handle_bulk_actions-edit-product', array( $this, 'delete_virtual_reviews' ), 10, 3 );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'html_add_review_section_on_nav' ) );
		add_action( 'admin_action_add_multi_reviews', array( $this, 'add_reviews_by_submit' ) );
		add_action( 'wp_ajax_delete_cmt', array( $this, 'delete_cmt' ) );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );

		add_action( 'wp_ajax_wvr_action', [ $this, 'ajax' ] );

		add_action( 'init', [ $this, 'auto_fix_review_previous_version' ] );
	}

	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function wvr_admin_comment_types_dropdown( $types ) {
		$types['virtual_review'] = __( 'Virtual Review', 'woo-virtual-reviews' );

		return $types;
	}

	public function woo_virtual_reviews_asset() {
		$screen = get_current_screen()->id;
		if ( $screen !== 'edit-product' ) {
			return;
		}

		wp_enqueue_style( 'wvr-product-list-page', WVR_PLUGIN_URL . "/assets/css/product-list-page.css", '', VI_WOO_VIRTUAL_REVIEWS_VERSION );
		wp_enqueue_script( 'wvr-product-list-page', WVR_PLUGIN_URL . "/assets/js/product-list-page.js", [ 'jquery' ], VI_WOO_VIRTUAL_REVIEWS_VERSION );
	}

	public function register_delete_virtual_reviews( $bulk_actions ) {
		$bulk_actions['delete_virtual_reviews'] = __( 'Delete Virtual Reviews', 'woo-virtual-reviews' );

		return $bulk_actions;
	}

	public function delete_virtual_reviews( $redirect_to, $action_name, $post_ids ) {
		if ( 'delete_virtual_reviews' != $action_name ) {
			return $redirect_to;
		}

		foreach ( $post_ids as $post_id ) {
			$arg  = array(
				'post_id'    => $post_id,
				'type'       => 'review',
				'meta_key'   => 'wvr_virtual_review',
				'meta_value' => 1
			);
			$cmts = get_comments( $arg );
			if ( ! empty( $cmts ) ) {
				foreach ( $cmts as $cmt ) {
					wp_delete_comment( $cmt->comment_ID, true );
				}
			}
		}

		return $redirect_to;
	}

	public function html_add_review_section_on_nav( $position ) {
		global $post_type;
		if ( $post_type == 'product' && $position == 'top' ) {
			$current_date = date_i18n( "Y-m-d", current_time( 'U' ) );
			?>
            <div class='alignleft wvr-actions'>
                <input type="button" class="wvr-open-add-review-control-panel button" value="<?php esc_html_e( "Add virtual reviews", "woo-virtual-reviews" ); ?>">
                <div class="wvr-add-review-control-panel">
                    <div class="wvr-row">
                        <div><?php esc_html_e( 'Quantity review', 'woo-virtual-reviews' ); ?></div>

                        <input type="text" name='wvr_select_qty_cmt' class='wvr-select-qty-cmt'
                               placeholder="<?php esc_html_e( "Use '-' for range", 'woo-virtual-reviews' ); ?>"/>
                        <span><?php esc_html_e( 'Quantity or random with range. Max is 10 comment per product.', 'woo-virtual-reviews' ); ?></span>
                    </div>
                    <div class="wvr-row">
                        <div><?php esc_html_e( 'From', 'woo-virtual-reviews' ); ?></div>
                        <input type="date" name="wvr_date_from" value="<?php echo esc_attr( $current_date ) ?>">
                    </div>
                    <div class="wvr-row">
                        <div><?php esc_html_e( 'To', 'woo-virtual-reviews' ); ?></div>
                        <input type="date" name="wvr_date_to" value="<?php echo esc_attr( $current_date ) ?>">
                    </div>
                    <button type="submit" class='vi-ui button button-primary submit-add-reviews' name='action' id='add_multi_reviews'
                            value='add_multi_reviews'>
						<?php esc_html_e( "Add reviews", "woo-virtual-reviews" ); ?>
                    </button>
                </div>
            </div>
			<?php
		}
	}

	public function ajax() {
		if ( ! current_user_can( 'manage_woocommerce' )
		     || empty( $_POST['sub_action'] )
		     || empty( $_POST['nonce'] ) ||
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wvr_nonce' ) ) {
			wp_send_json_error();
		}

		$func = sanitize_text_field( wp_unslash( $_POST['sub_action'] ) );

		if ( in_array( $func, [ 'add_multiple_reviews', 'add_custom_reviews', 'search_product', 'fix_review_previous_version' ] ) ) {
			$this->$func();
		}
		wp_die();
	}

	public function add_multiple_reviews() {
		// phpcs:disable WordPress.Security.NonceVerification
		$cats    = ! empty( $_POST['cats'] ) ? wc_clean( wp_unslash( $_POST['cats'] ) ) : [];
		$step    = ! empty( $_POST['step'] ) ? sanitize_text_field( wp_unslash( $_POST['step'] ) ) : 1;
		$cmt_qty = ! empty( $_POST['qty'] ) ? sanitize_text_field( wp_unslash( $_POST['qty'] ) ) : 1;
		$from    = ! empty( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : 'today';
		$to      = ! empty( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : 'today';
		$from    = strtotime( $from );
		$to      = strtotime( $to ) + DAY_IN_SECONDS - 1;

		$max_insert_cmt = 20; //30
		$product_limit  = floor( $max_insert_cmt / $cmt_qty );

		$args = [
			'post_type'      => 'product',
			'paged'          => $step,
			'posts_per_page' => $product_limit,
		];

		if ( ! empty( $cats ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => $cats,
					'include_children' => false,
					'operator'         => 'IN'
				),
			);
		}

		$query    = new \WP_Query( $args );
		$count    = $query->found_posts;
		$products = $query->get_posts();

		if ( ! empty( $products ) && is_array( $products ) ) {
			foreach ( $products as $product ) {
				$this->add_reviews( $product->ID, $cmt_qty, $from, $to );
			}
			$total      = $step * $product_limit;
			$continue   = $total < $count ? true : false;
			$percentage = $total >= $count ? 100 : ( $total / $count ) * 100;
			wp_send_json_success( [ 'continue' => $continue, 'count' => $count, 'percentage' => $percentage ] );
		} else {
			wp_send_json_success( [ 'continue' => false ] );
		}
	}

	public function add_custom_reviews() {
		$pids   = isset( $_POST['pids'] ) ? wc_clean( wp_unslash( $_POST['pids'] ) ) : '';
		$cmt    = isset( $_POST['cmt'] ) ? wp_kses_post( wp_unslash( $_POST['cmt'] ) ) : '';
		$author = isset( $_POST['author'] ) ? sanitize_text_field( wp_unslash( $_POST['author'] ) ) : '';
		$rating = isset( $_POST['rating'] ) ? sanitize_text_field( wp_unslash( $_POST['rating'] ) ) : 5;
		$time   = isset( $_POST['time'] ) ? strtotime( sanitize_text_field( wp_unslash( $_POST['time'] ) ) ) : current_time( 'U' );

		if ( ! ( $pids && $cmt && $author ) ) {
			wp_send_json_error();
		}

		foreach ( (array) $pids as $pid ) {
			$this->add_single_review( $pid, $cmt, $author, $rating, $time );
		}

		wp_send_json_success();
	}

	public function add_single_review( $pid, $cmt, $author, $rating, $time ) {
		$time = date_i18n( 'Y-m-d H:i:s', $time );
		$data = array(
			'comment_post_ID'      => $pid,
			'comment_author'       => $author,
			'comment_author_email' => 'virtual review',
			'comment_author_url'   => '',
			'comment_content'      => $cmt,
			'comment_type'         => 'review',
			'comment_parent'       => 0,
			'user_id'              => 0,
			'comment_agent'        => 'admin',
			'comment_date'         => $time,
			'comment_date_gmt'     => $time,
			'comment_approved'     => 1,
			'comment_meta'         => array(
				'verified'           => 1,
				'rating'             => $rating,
				'wvr_virtual_review' => 1
			)
		);

		$comment_id = wp_insert_comment( $data );
		if ( ! $comment_id ) {
			return;
		}

		$product = wc_get_product( $pid );

		if ( $product->is_type( 'variable' ) ) {
			$variation = $product->get_children();
			if ( $variation ) {
				$key = array_rand( $variation, 1 );
				update_comment_meta( $comment_id, 'wvr_variation', $variation[ $key ] );
			} else {
				wp_delete_comment( $comment_id );
			}
		}

		\WC_Comments::clear_transients( $pid );
	}

	public function add_reviews( $pid, $qty, $from, $to ) {
		$count           = ( $to - $from ) / HOUR_IN_SECONDS;
		$random_time_arr = [];
		for ( $i = 0; $i < $qty; $i ++ ) {
			$random_time_arr[] = $from + wp_rand( 0, $count ) * HOUR_IN_SECONDS;
		}
		sort( $random_time_arr );

		$settings = Data::instance();
		$names    = $settings->get_param( 'names' );
		$comments = (array) $settings->get_param( 'cmt' );
		$rating   = $settings->get_param( 'rating' );
		$rating   = explode( '-', $rating );

		if ( empty( $names ) || empty( $comments ) ) {
			return;
		}

		for ( $i = 0; $i < $qty; $i ++ ) {
			$time            = $random_time_arr[ $i ];
			$random_key_name = array_rand( $names, 1 );
			$name            = $names[ $random_key_name ];

			$random_key_cmt = array_rand( $comments, 1 );
			$cmt            = $comments[ $random_key_cmt ];

			$random_rating = wp_rand( $rating[0], $rating[1] );

			$this->add_single_review( $pid, $cmt, $name, $random_rating, $time );
		}
	}

	public function admin_notices() {
		if ( isset( $_GET['wvr_error'] ) && $_GET['wvr_error'] === 'quantity' ) {
			printf( "<div id='message' class='error '><p>%s</p></div>", esc_html__( 'Quantity is invalid', 'woo-virtual-reviews' ) );
		}
	}

	function add_reviews_by_submit() {
		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		$_REQUEST['_wp_http_referer'] = remove_query_arg( 'wvr_error', sanitize_text_field( wp_unslash( $_REQUEST['_wp_http_referer'] ?? '' ) ) );

		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		$post_ids = array_map( 'absint', wp_unslash( $_REQUEST['post'] ) );
		$qty      = isset( $_REQUEST['wvr_select_qty_cmt'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wvr_select_qty_cmt'] ) ) : null;

		if ( strpos( $qty, '-' ) !== false ) {
			$range = explode( '-', $qty );
			$min   = intval( min( $range ) );
			$max   = intval( max( $range ) );
		} else {
			$min = $max = intval( $qty );
		}

		$limit = apply_filters( 'wvr_limit_quantity', 10 );

		if ( $max > $limit  ) {
			$_REQUEST['_wp_http_referer'] = add_query_arg( 'wvr_error', 'quantity', sanitize_text_field( wp_unslash( $_REQUEST['_wp_http_referer'] ?? '' ) ) );

			return;
		}

		if ( empty( $qty ) ) {
			return;
		}

		$from = ! empty( $_REQUEST['wvr_date_from'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wvr_date_from'] ) ) : 'today';
		$to   = ! empty( $_REQUEST['wvr_date_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wvr_date_to'] ) ) : 'today';
		$from = strtotime( $from );
		$to   = strtotime( $to ) + DAY_IN_SECONDS - 1;

		if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {
			foreach ( $post_ids as $pid ) {
				$this->add_reviews( $pid, wp_rand( $min, $max ), $from, $to );
			}
		}
	}

	public function add_multi_reviews( $post_ids, $qty_cmt ) {
		$post_ids           = ! is_array( $post_ids ) ? array( $post_ids ) : $post_ids;
		$list_opts          = Data::get_data_option();
		$name               = isset( $list_opts['names'] ) ? $list_opts['names'] : '';
		$comment_content    = isset( $list_opts['cmt'] ) ? $list_opts['cmt'] : '';
		$rating             = isset( $list_opts['rating'] ) ? $list_opts['rating'] : '';
		$rating             = explode( '-', $rating );
		$this->current_time = current_time( 'U' );

		if ( ! empty( $name ) && ! empty( $comment_content ) && $qty_cmt > 0 ) {
			foreach ( $post_ids as $post_id ) {
				for ( $i = 0; $i < $qty_cmt; $i ++ ) {
					$time            = $this->random_time();
					$random_rating   = wp_rand( $rating[0], $rating[1] );
					$random_key_cmt  = wp_rand( 0, count( $list_opts['cmt'] ) - 1 );
					$random_key_name = wp_rand( 0, count( $list_opts['names'] ) - 1 );
					$comment_content = ( $list_opts['cmt'][ $random_key_cmt ] );
					$name            = ( $list_opts['names'][ $random_key_name ] );
					$data            = array(
						'comment_post_ID'      => $post_id,
						'comment_author'       => $name,
						'comment_author_email' => 'comment by admin',
						'comment_author_url'   => '',
						'comment_content'      => $comment_content,
						'comment_type'         => 'review',
						'comment_parent'       => 0,
						'user_id'              => 0,
						'comment_author_IP'    => '127.0.0.1',
						'comment_agent'        => 'admin',
						'comment_date'         => $time,
						'comment_date_gmt'     => $time,
						'comment_approved'     => 1,
						'comment_meta'         => array(
							'verified'           => 1,
							'rating'             => $random_rating,
							'wvr_virtual_review' => 1
						)
					);

					$comment_id = wp_insert_comment( $data );
					if ( ! $comment_id ) {
						return false;
					}

					$product = wc_get_product( $post_id );

					if ( $product->is_type( 'variable' ) ) {
						$variation = $product->get_children();
						if ( $variation ) {
							$key = array_rand( $variation, 1 );
							update_comment_meta( $comment_id, 'wvr_variation', $variation[ $key ] );
						} else {
							wp_delete_comment( $comment_id );
							continue;
						}
					}

					\WC_Comments::clear_transients( $post_id );
				}
			}

			return true;
		}
	}

	public function random_time() {
		$now  = $this->current_time;
		$rand = wp_rand( $now - 172800, $now );

		return date_i18n( 'Y-m-d H:i:s', $rand );
	}

	public function change_columns_filter( $columns ) {
		$new_columns                   = array();
		$new_columns['virtual_review'] = __( 'Virtual review', 'woo-virtual-reviews' );
		$new_columns['wvr_rating']     = __( 'Rating', 'woo-virtual-reviews' );

		return $columns = array_merge( $columns, $new_columns );
	}

	public function show_virtual_review_count( $column_name, $pid ) {
		if ( $column_name == 'virtual_review' ) {
			$cmts = get_comments( array(
				'post_id'    => $pid,
				'type'       => 'review',
				'meta_key'   => 'wvr_virtual_review',
				'meta_value' => 1,
				'count'      => true
			) );
			echo esc_html( $cmts );
		} elseif ( $column_name == 'wvr_rating' ) {
			echo esc_html( get_post_meta( $pid, '_wc_average_rating', true ) );
		}
	}

	public function my_sortable_cake_column( $columns ) {
		$columns['wvr_rating']     = 'wvr_rating';
		$columns['virtual_review'] = 'virtual_review';

		return $columns;
	}

	public function my_slice_orderby( $query ) {
		if ( ! is_admin() ) {
			return;
		}
		$orderby = $query->get( 'orderby' );

		if ( 'wvr_rating' == $orderby ) {
			$query->set( 'meta_key', '_wc_average_rating' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( 'virtual_review' == $orderby ) {
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	public function my_custom_fonts() {
		echo '<style> .column-virtual_review, .column-wvr_rating {width: 70px !important;text-align: center !important;} </style>';
	}

	public function delete_cmt() {
		if ( empty( $_POST['id'] ) ) {
			return;
		}

		$pid = absint( $_POST['id'] );

		$cmt_arg = array(
			'post_id' => $pid,
			'type'    => 'self_review',
		);

		$cmts = get_comments( $cmt_arg );
		if ( count( $cmts ) ) {
			foreach ( $cmts as $cmt ) {
				wp_delete_comment( $cmt->comment_ID );
			}
		}
		wp_send_json( $pid );
		wp_die();
	}

	public function filter_virtual_review( $args ) {
		if ( isset( $args['type'] ) && $args['type'] == 'virtual_review' ) {
			$args['type']       = 'review';
			$args['meta_key']   = 'wvr_virtual_review';
			$args['meta_value'] = 1;
		}

		return $args;
	}

	public function search_product() {
		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';

		if ( ! $keyword ) {
			die();
		}

		$arg            = array(
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'posts_per_page' => 50,
			's'              => $keyword
		);
		$json           = array();
		$found_products = get_posts( $arg );

		foreach ( $found_products as $product ) {
			$json[] = [ 'id' => $product->ID, 'text' => $product->post_title ];
		}
		wp_send_json( $json );
	}

	public function fix_review_previous_version() {
		$clear    = [];
		$comments = get_comments( [
			'type'     => 'comment_type',
			'type__in' => 'self_review',
			'number'   => 30, //30
		] );

		if ( ! empty( $comments ) && is_array( $comments ) ) {
			foreach ( $comments as $comment ) {
				$_args = [
					'comment_ID'           => $comment->comment_ID,
					'comment_type'         => 'review',
					'comment_author_email' => 'virtual review',
					'comment_meta'         => [ 'wvr_virtual_review' => 1 ]
				];
				wp_update_comment( $_args );
				$clear[] = $comment->comment_post_ID;
			}
		}

		$clear = array_unique( $clear );

		if ( ! empty( $clear ) ) {
			foreach ( $clear as $pid ) {
				\WC_Comments::clear_transients( $pid );
			}
		}

		$count = get_comments( [ 'type' => 'comment_type', 'type__in' => 'self_review', 'count' => true ] );

		$next = $count ? true : false;
		wp_send_json_success( [ 'next' => $next, 'remain' => $count ] );
	}

	public function auto_fix_review_previous_version() {
		$comments = get_comments( [
			'type'     => 'comment_type',
			'type__in' => 'self_review',
			'number'   => 10,
		] );

		if ( empty( $comments ) ) {
			return;
		}

		$clear = [];

		if ( is_array( $comments ) ) {
			foreach ( $comments as $comment ) {
				$_args = [
					'comment_ID'           => $comment->comment_ID,
					'comment_type'         => 'review',
					'comment_author_email' => 'virtual review',
					'comment_meta'         => [ 'wvr_virtual_review' => 1 ]
				];
				wp_update_comment( $_args );
				$clear[] = $comment->comment_post_ID;
			}
		}

		if ( ! empty( $clear ) ) {
			$clear = array_unique( $clear );
			foreach ( $clear as $pid ) {
				\WC_Comments::clear_transients( $pid );
			}
		}
	}

	public function add_review_filter_type( $type ) {
		$type['virtual_review'] = esc_html__( 'Virtual reviews', 'faview-virtual-reviews-for-woocommerce' );

		return $type;
	}

	public function convert_virtual_to_reviews( $args ) {
		if ( ! empty( $args['type'] ) && $args['type'] == 'virtual_review' ) {
			$args['type']       = 'review';
			$args['meta_key']   = 'wvr_virtual_review';
			$args['meta_value'] = 1;
		}

		return $args;
	}
}
