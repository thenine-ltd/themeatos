<?php
/**
 * Created by PhpStorm.
 * User: toido
 * Date: 11/1/2018
 * Time: 11:12 AM
 */

namespace WooVR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Settings {

	protected static $instance = null;

	private function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'save_option' ) );
	}

	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function admin_enqueue_scripts() {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( empty( $_GET['page'] ) ) {
			return;
		}
		switch ( $_GET['page'] ) {
			case 'wvr_settings':
				$this->delete_script();
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_style( 'wp-color-picker' );

				$style_arr = array(
					'icon.min',
					'menu.min',
					'segment.min',
					'checkbox.min',
					'tab.min',
					'form.min',
					'button.min',
					'tab.min',
					'settings',
				);

				foreach ( $style_arr as $style ) {
					wp_enqueue_style( "wvr-" . $style, WVR_PLUGIN_URL . "/assets/css/" . $style . ".css", '', VI_WOO_VIRTUAL_REVIEWS_VERSION );
				}

				$js_arr = array(
					'checkbox.min',
					'jquery.address.min',
					'tab.min',
					'settings',
				);

				foreach ( $js_arr as $js ) {
					wp_enqueue_script( "wvr-" . $js, WVR_PLUGIN_URL . "/assets/js/" . $js . ".js", array( 'jquery' ), VI_WOO_VIRTUAL_REVIEWS_VERSION );
				}

				break;

			case 'virtual-reviews':
				$this->delete_script();

				$style_arr = array(
					'menu.min',
					'segment.min',
					'checkbox.min',
					'tab.min',
					'form.min',
					'button.min',
					'select2.min',
					'admin-review',
				);

				foreach ( $style_arr as $style ) {
					wp_enqueue_style( "wvr-" . $style, WVR_PLUGIN_URL . "/assets/css/" . $style . ".css", '', VI_WOO_VIRTUAL_REVIEWS_VERSION );
				}

				$js_arr = array(
					'checkbox.min',
					'tab.min',
					'form.min',
					'select2',
					'admin-review',
				);

				foreach ( $js_arr as $js ) {
					wp_enqueue_script( "wvr-" . $js, WVR_PLUGIN_URL . "/assets/js/" . $js . ".js", array( 'jquery' ), VI_WOO_VIRTUAL_REVIEWS_VERSION );
				}

				$localize = array( 'ajaxUrl' => admin_url( "admin-ajax.php" ), 'nonce' => wp_create_nonce( 'wvr_nonce' ) );
				wp_localize_script( "wvr-admin-review", "wvrObject", $localize );
				break;
		}
	}

	public function delete_script() {
		global $wp_scripts;
		$scripts = $wp_scripts->registered;
		foreach ( $scripts as $k => $script ) {
			preg_match( '/^\/wp-/i', $script->src, $result );
			if ( count( array_filter( $result ) ) < 1 ) {
				if ( $script->handle != 'query-monitor' ) {
					wp_dequeue_script( $script->handle );
				} //delete script not belong to wp
			}
		}
	}

	public function admin_menu() {
		add_menu_page(
			esc_html__( 'Virtual Reviews', 'woo-virtual-reviews' ),
			esc_html__( 'Virtual Reviews', 'woo-virtual-reviews' ),
			'manage_woocommerce',
			'virtual-reviews',
			array( $this, 'add_reviews_page' ),
			'dashicons-star-filled',
			40
		);

		add_submenu_page(
			'virtual-reviews',
			esc_html__( 'Manual', 'woo-virtual-reviews' ),
			esc_html__( 'Manual', 'woo-virtual-reviews' ),
			'manage_woocommerce',
			'virtual-reviews',
			array( $this, 'add_reviews_page' )
		);
		add_submenu_page(
			'virtual-reviews',
			esc_html__( 'Schedule', 'woo-virtual-reviews' ),
			esc_html__( 'Schedule', 'woo-virtual-reviews' ),
			'manage_woocommerce',
			'virtual-reviews-schedule',
			array( $this, 'schedule_page' )
		);

		add_submenu_page(
			'virtual-reviews',
			esc_html__( 'Settings', 'woo-virtual-reviews' ),
			esc_html__( 'Settings', 'woo-virtual-reviews' ),
			'manage_woocommerce',
			'wvr_settings',
			array( $this, 'page_settings_content' ) );
	}

	public function schedule_page() {
		get_pro_button();
	}

	public function page_settings_content() {
		$general = [
			[ 'type' => 'title' ],

			[
				'id'          => 'names',
				'title'       => esc_html__( 'Author', 'woo-virtual-reviews' ),
				'type'        => 'textarea',
				'separator'   => "\n",
				'placeholder' => __( "Add your list virtual names, example:&#10Alex&#10Anna&#10Ben", 'woo-virtual-reviews' )
			],

			[
				'id'          => 'cmt',
				'title'       => esc_html__( 'Reviews', 'woo-virtual-reviews' ),
				'type'        => 'textarea',
				'separator'   => "\n",
				'placeholder' => __( "Add your list virtual comments, example:&#10I like it&#10Best product&#10Shipping fast", 'woo-virtual-reviews' )
			],

			[
				'id'      => 'rating',
				'title'   => esc_html__( 'Rating', 'woo-virtual-reviews' ),
				'type'    => 'select',
				'options' => Data::instance()->get_star_option(),
				'desc'    => esc_html__( 'Random rating for generate multiple reviews', 'woo-virtual-reviews' ),
			],
			[
				'type'  => 'pro_feature',
				'title' => esc_html__( 'Quantity of bought product', 'woo-virtual-reviews' ),
			],
			[
				'type'  => 'pro_feature',
				'title' => esc_html__( 'Unique author', 'woo-virtual-reviews' ),
			],
			[
				'type'  => 'pro_feature',
				'title' => esc_html__( 'Unique comment content', 'woo-virtual-reviews' ),
			],
			[
				'type'  => 'pro_feature',
				'title' => esc_html__( 'Assign comment to product group', 'woo-virtual-reviews' ),
			],
			[
				'type'  => 'pro_feature',
				'title' => esc_html__( 'Add review with no comment', 'woo-virtual-reviews' ),
			],

			[ 'type' => 'sectionend' ]
		];

		$style_option = [ 'select' => __( 'Dropdown list', 'woo-virtual-reviews' ), 'slide' => __( 'Slide', 'woo-virtual-reviews' ) ];

		$reply = [
			[ 'type' => 'title' ],

			[
				'type'  => 'pro_feature',
				'title' => esc_html__( 'Use for virtual review', 'woo-virtual-reviews' ),
			],
			[
				'type'  => 'pro_feature',
				'title' => esc_html__( 'Use for real review', 'woo-virtual-reviews' ),
			],
			[
				'type'  => 'pro_feature',
				'title' => esc_html__( 'Reply author', 'woo-virtual-reviews' ),
			],
			[
				'type'  => 'pro_feature',
				'title' => esc_html__( 'Reply content', 'woo-virtual-reviews' ),
			],

			[ 'type' => 'sectionend' ]
		];

		$frontend = [
			[ 'type' => 'title' ],

			[
				'id'    => 'auto_rating',
				'title' => esc_html__( 'Auto select 5 star', 'woo-virtual-reviews' ),
				'type'  => 'checkbox',
				'desc'  => esc_html__( 'Auto select rating 5 star', 'woo-virtual-reviews' ),
			],

			[
				'id'    => 'auto_fill_review',
				'title' => esc_html__( 'Auto fill review', 'woo-virtual-reviews' ),
				'type'  => 'text',
			],

			[
				'id'    => 'show_canned',
				'title' => esc_html__( 'Show canned reviews', 'woo-virtual-reviews' ),
				'type'  => 'checkbox',
			],

			[
				'id'          => 'cmt_frontend',
				'title'       => esc_html__( 'Canned reviews', 'woo-virtual-reviews' ),
				'type'        => 'textarea',
				'separator'   => "\n",
				'placeholder' => __( "Add your list comments display on front of your website (max = 50 sentences), example:&#10I like it&#10Best product&#10Shipping fast", 'woo-virtual-reviews' )
			],

			[
				'id'      => 'canned_style_desktop',
				'title'   => esc_html__( 'Canned style for Desktop', 'woo-virtual-reviews' ),
				'type'    => 'select',
				'options' => $style_option,
				'desc'    => esc_html__( 'Canned style for front-end display (width device > 800px)', 'woo-virtual-reviews' ),
			],

			[
				'id'      => 'canned_style_mobile',
				'title'   => esc_html__( 'Canned style for Mobile', 'woo-virtual-reviews' ),
				'type'    => 'select',
				'options' => $style_option,
				'desc'    => esc_html__( 'Canned style for front-end display (width device < 800px)', 'woo-virtual-reviews' ),
			],

			[
				'id'    => 'canned_text_color',
				'title' => esc_html__( 'Canned text color', 'woo-virtual-reviews' ),
				'type'  => 'color',
				'desc'  => esc_html__( 'Canned text color for slide style', 'woo-virtual-reviews' ),
			],

			[
				'id'    => 'canned_bg_color',
				'title' => esc_html__( 'Canned background color', 'woo-virtual-reviews' ),
				'type'  => 'color',
				'desc'  => esc_html__( 'Canned background color for slide style', 'woo-virtual-reviews' ),
			],

			[
				'id'    => 'canned_text_hover_color',
				'title' => esc_html__( 'Canned text hover color', 'woo-virtual-reviews' ),
				'type'  => 'color',
				'desc'  => esc_html__( 'Canned text hover color for slide style', 'woo-virtual-reviews' ),
			],

			[
				'id'    => 'canned_hover_color',
				'title' => esc_html__( 'Canned background hover color', 'woo-virtual-reviews' ),
				'type'  => 'color',
				'desc'  => esc_html__( 'Canned background hover color for slide style', 'woo-virtual-reviews' ),
			],

			[ 'type' => 'sectionend' ]
		];

		$purchase_label = [
			[ 'type' => 'title' ],

			[
				'id'    => 'show_purchased_label',
				'title' => esc_html__( 'Show purchased label', 'woo-virtual-reviews' ),
				'type'  => 'checkbox',
			],
			[
				'id'      => 'purchased_label_icon',
				'title'   => esc_html__( 'Purchased icon', 'woo-virtual-reviews' ),
				'desc'    => esc_html__( 'Purchased icon for front-end display', 'woo-virtual-reviews' ),
				'type'    => 'radio',
				'options' => [
					[
						'value' => 'no_icon',
						'icon'  => 'wvr-icon-no-icon'
					],
					[
						'value' => 'e900',
						'icon'  => 'wvr-icon-shopping-bag'
					],
					[
						'value' => 'e902',
						'icon'  => 'wvr-icon-cart-arrow-down'
					],
					[
						'value' => 'e93f',
						'icon'  => 'wvr-icon-credit-card'
					],
					[
						'value' => 'e903',
						'icon'  => 'wvr-icon-currency-dollar'
					],
					[
						'value' => 'e904',
						'icon'  => 'wvr-icon-location-shopping'
					],
				]
			],

			[
				'id'    => 'purchased_icon_color',
				'type'  => 'color',
				'title' => esc_html__( 'Purchased icon color', 'woo-virtual-reviews' ),
				'desc'  => esc_html__( 'Purchased icon color for front-end display', 'woo-virtual-reviews' ),
			],

			[
				'id'    => 'purchased_text_color',
				'type'  => 'color',
				'title' => esc_html__( 'Purchased label text color', 'woo-virtual-reviews' ),
				'desc'  => esc_html__( 'Purchased label text color for front-end display', 'woo-virtual-reviews' ),
			],

			[
				'id'    => 'purchased_bg_color',
				'type'  => 'color',
				'title' => esc_html__( 'Purchased label background color', 'woo-virtual-reviews' ),
				'desc'  => esc_html__( 'Purchased label background color for front-end display', 'woo-virtual-reviews' ),
			],

			[
				'id'    => 'custom_css',
				'type'  => 'textarea',
				'title' => esc_html__( 'Custom CSS', 'woo-virtual-reviews' ),
			],

			[ 'type' => 'sectionend' ]
		];
		?>
        <div>
            <h2><?php esc_html_e( 'Settings', 'woo-virtual-reviews' ); ?></h2>
            <form method="post" class="vi-ui form">
				<?php wp_nonce_field( 'wvr_nonce', 'wvr_nonce' ) ?>

                <div class="vi-ui top attached tabular menu">
                    <a class="active item" data-tab="general"><?php esc_html_e( 'General', 'woo-virtual-reviews' ); ?></a>
                    <a class="item" data-tab="reply"><?php esc_html_e( 'Reply', 'woo-virtual-reviews' ); ?></a>
                    <a class="item" data-tab="review_form"><?php esc_html_e( 'Review form', 'woo-virtual-reviews' ); ?></a>
                    <a class="item" data-tab="update"><?php esc_html_e( 'Update', 'woo-virtual-reviews' ); ?></a>
                </div>
                <div class="vi-ui bottom attached active tab segment" data-tab="general">
					<?php Setting_Row::output_fields( $general ); ?>
                </div>
                <div class="vi-ui bottom attached tab segment" data-tab="reply">
					<?php Setting_Row::output_fields( $reply ); ?>
                </div>
                <div class="vi-ui bottom attached tab segment" data-tab="review_form">
					<?php Setting_Row::output_fields( $frontend ); ?>
                    <h4><?php esc_html_e( 'Purchased label', 'woo-virtual-reviews' ); ?></h4>
					<?php Setting_Row::output_fields( $purchase_label ); ?>
                </div>
                <div class="vi-ui bottom attached tab segment" data-tab="update">
					<?php get_pro_button(); ?>
                </div>
                <button type="submit" class="vi-ui button primary small" name="wvr_save_settings" value="save">
					<?php esc_html_e( 'Save', 'woo-virtual-reviews' ); ?>
                </button>
            </form>
        </div>
		<?php
		do_action( 'villatheme_support_woo-virtual-reviews' );
	}

	public function add_reviews_page() {
		$timestamp    = current_time( 'U' );
		$current_time = date_i18n( 'Y-m-d', $timestamp );
		$categories   = Utils::get_product_categories( [ 'hide_empty' => true ] );
		?>
        <div class="wvr-wrapper">
            <h2><?php esc_html_e( 'Reviews', 'woo-virtual-reviews' ); ?></h2>

			<?php
			$args = [
				'type'     => 'comment_type',
				'type__in' => 'self_review',
				'number'   => 1,
				'count'    => true
			];
			$r    = get_comments( $args );
			if ( $r ) {
				?>
                <button class="wvr-fix-review vi-ui button small primary">
					<?php esc_html_e( 'Click here to fix bug 0 review for previous version', 'woo-virtual-reviews' ); ?>
                </button>
                <span class="wvr-fix-review-remain"> </span>
				<?php
			}
			?>

            <div id="wvr-review-from-setting" class="vi-ui segment form small">
                <h3><?php esc_html_e( 'Add multiple review', 'woo-virtual-reviews' ); ?></h3>
                <div class="field">
                    <label><?php esc_html_e( 'Reviews per each product', 'woo-virtual-reviews' ) ?></label>
                    <input type="number" class="wvr-review-per-product" value="1">
                </div>
                <div class="two fields">
                    <div class="field">
                        <label><?php esc_html_e( 'From', 'woo-virtual-reviews' ) ?></label>
                        <input type="date" class="wvr-date-from" value="<?php echo esc_attr( $current_time ) ?>">
                    </div>
                    <div class="field">
                        <label><?php esc_html_e( 'To', 'woo-virtual-reviews' ) ?></label>
                        <input type="date" class="wvr-date-to" value="<?php echo esc_attr( $current_time ) ?>">
                    </div>
                </div>
                <div class="field">
                    <label><?php esc_html_e( 'Categories', 'woo-virtual-reviews' ) ?></label>

                    <select class="vi-ui dropdown wvr-product-cat" multiple>
						<?php
						if ( ! empty( $categories ) ) {
							foreach ( $categories as $cat_id => $cat_name ) {
								printf( '<option value="%s" >%s</option>', esc_attr( $cat_id ), esc_html( $cat_name ) );
							}
						}
						?>
                    </select>
                </div>
                <button type="button" class="vi-ui button small wvr-add-multi-reviews">
					<?php esc_html_e( 'Add reviews', 'woo-virtual-reviews' ); ?>
                </button>
                <div class="wvr-processing-bar">
                    <div class="wvr-progress"></div>
                </div>
            </div>

            <div id="wvr-custom-review" class="vi-ui segment form small">
                <h3><?php esc_html_e( 'Add single review', 'woo-virtual-reviews' ); ?></h3>

                <div class="field">
                    <label><?php esc_html_e( 'Date', 'woo-virtual-reviews' ) ?></label>
                    <input type="datetime-local" class="wvr-time" value="<?php echo esc_attr( $current_time . 'T' . date_i18n( 'H:i', $timestamp ) ) ?>">
                </div>

                <div class="field">
                    <label><?php esc_html_e( 'Rating', 'woo-virtual-reviews' ) ?></label>
                    <select class="wvr-rating">
						<?php
						for ( $i = 1; $i <= 5; $i ++ ) {
							printf( '<option value="%d" %s>%d</option>', esc_attr( $i ), esc_attr( $i == 5 ? 'selected' : '' ), esc_attr( $i ) );
						}
						?>
                    </select>
                </div>
                <div class="field">
                    <label><?php esc_html_e( 'Review', 'woo-virtual-reviews' ) ?></label>
                    <textarea class="wvr-review" rows="3"></textarea>
                </div>
                <div class="field">
                    <label><?php esc_html_e( 'Author', 'woo-virtual-reviews' ) ?></label>
                    <input class="wvr-author">
                </div>
                <div class="field">
                    <label><?php esc_html_e( 'Products', 'woo-virtual-reviews' ) ?></label>
                    <select class="wvr-products"> </select>
                </div>
                <button type="button" class="vi-ui button small wvr-add-review">
					<?php esc_html_e( 'Add review', 'woo-virtual-reviews' ); ?>
                </button>

            </div>

			<?php do_action( 'villatheme_support_woo-virtual-reviews' ); ?>

        </div>
		<?php
	}

	public function save_option() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['wvr_nonce'] )
		     || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wvr_nonce'] ) ), 'wvr_nonce' ) || empty( $_POST['wvr_save_settings'] ) ) {
			return;
		}

		$data['names']        = isset( $_POST['wvr_params']['names'] ) ? $this->filter_data( sanitize_textarea_field( wp_unslash( $_POST['wvr_params']['names'] ) ) ) : array();
		$data['cmt']          = isset( $_POST['wvr_params']['cmt'] ) ? $this->filter_data( sanitize_textarea_field( wp_unslash( $_POST['wvr_params']['cmt'] ) ) ) : array();
		$data['cmt_frontend'] = isset( $_POST['wvr_params']['cmt_frontend'] ) ? $this->filter_data( sanitize_textarea_field( wp_unslash( $_POST['wvr_params']['cmt_frontend'] ) ) ) : array();
		$data['custom_css']   = isset( $_POST['wvr_params']['custom_css'] ) ? $this->filter_data( sanitize_textarea_field( wp_unslash( $_POST['wvr_params']['custom_css'] ) ) ) : array();

		$text_data = wc_clean( wp_unslash( $_POST['wvr_params'] ) );
		$data      = wp_parse_args( $data, $text_data );

		update_option( WVR_OPTION, $data, 'yes' );
	}

	public function filter_data( $arg, $limit = 10000 ) {
		$arg = ( array_values( array_unique( array_filter( array_map( 'trim', explode( '<br />', trim( nl2br( $arg ) ) ) ) ) ) ) );
		$arg = array_slice( $arg, 0, $limit );
		sort( $arg );

		return $arg;
	}

}



