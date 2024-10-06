<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 12/11/2018
 * Time: 11:00 SA
 */

namespace WooVR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Display_Comment {

	protected static $instance = null;

	public static function instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_filter( 'woocommerce_product_review_comment_form_args', array( $this, 'sv_add_wc_review_notes' ) );
		add_action( 'woocommerce_review_after_comment_text', array( $this, 'show_comments' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		if ( is_product() && is_single() ) {
			wp_enqueue_style( "wvr-front-comment", WVR_PLUGIN_URL . "/assets/css/front-comment.css" . '', '', VI_WOO_VIRTUAL_REVIEWS_VERSION );

			$data                    = Data::instance();
			$canned_text_color       = $data->get_param( 'canned_text_color' );
			$canned_bg_color         = $data->get_param( 'canned_bg_color' );
			$canned_text_hover_color = $data->get_param( 'canned_text_hover_color' );
			$canned_hover_color      = $data->get_param( 'canned_hover_color' );
			$purchased_text_color    = $data->get_param( 'purchased_text_color' );
			$purchased_bg_color      = $data->get_param( 'purchased_bg_color' );
			$purchased_icon_color    = $data->get_param( 'purchased_icon_color' );
			$purchased_label_icon    = $data->get_param( 'purchased_label_icon' );

			$custom_css = $data->get_param( 'custom_css' );

			if ( is_array( $custom_css ) ) {
				$custom_css = implode( '', $custom_css );
			}

			$custom_css .= ".wvr-customer-pick .wvr-select-sample-cmt{color: {$canned_text_color}; background-color:{$canned_bg_color};}";
			$custom_css .= ".wvr-customer-pick .wvr-select-sample-cmt:hover{color: {$canned_text_hover_color}; background-color:{$canned_hover_color};}";
			$custom_css .= ".wvr-product-purchased{color: {$purchased_text_color}; background-color:{$purchased_bg_color};}";
			$custom_css .= ".wvr-icon-purchased{color: {$purchased_icon_color};}";
			$custom_css .= ".wvr-icon-purchased:before{content:'\\" . $purchased_label_icon . "'; margin-right:5px}";

			wp_add_inline_style( 'wvr-front-comment', $custom_css );

			wp_enqueue_script( 'fast-comment', WVR_JS_URL . 'front-script.js', array( 'jquery' ), VI_WOO_VIRTUAL_REVIEWS_VERSION );
			$auto_rating   = $data->get_param( 'auto_rating' );
			$first_comment = $data->get_param( 'auto_fill_review' );

			wp_localize_script( 'fast-comment', 'php_js',
				array(
					'auto_rating'   => $auto_rating,
					'first_comment' => $first_comment
				) );

		}
	}

	public function sv_add_wc_review_notes( $review_form ) {
		// Shown to all reviewers below "Your Review" field
		$data                 = Data::instance();
		$sample_cmts          = $data->get_param( 'cmt_frontend' );
		$show_canned          = $data->get_param( 'show_canned' );
		$canned_style_desktop = $data->get_param( 'canned_style_desktop' );
		$canned_style_mobile  = $data->get_param( 'canned_style_mobile' );
		$text_slide_desktop   = $text_select_desktop = $text_slide_mobile = $text_select_mobile = '';

		if ( $show_canned ) {
			if ( $canned_style_desktop == 'slide' ) {
				foreach ( $sample_cmts as $sample_cmt ) {
					$text_slide_desktop .= "<span class='wvr-select-sample-cmt' data-value='" . esc_attr( stripslashes( $sample_cmt ) ) . "'>" . esc_html( stripslashes( $sample_cmt ) ) . "</span>";
				}
				$review_form['comment_notes_after'] = '<div class="wvr-customer-sample-cmt wvr-desktop-style">';
				$review_form['comment_notes_after'] .= '<div style="display: flex"><div class="wvr-customer-pick">' . $text_slide_desktop . '</div>';
				$review_form['comment_notes_after'] .= '<span class="wvr-clear-comment wvr-icon-bin"></span></div></div>';
			} elseif ( $canned_style_desktop == 'select' ) {
				foreach ( $sample_cmts as $sample_cmt ) {
					$text_select_desktop .= "<option value='" . esc_attr( $sample_cmt ) . "'>" . esc_html( stripslashes( $sample_cmt ) ) . "</option>";
				}
				$review_form['comment_notes_after'] = '<div class="wvr-customer-sample-cmt wvr-desktop-style">';
				$review_form['comment_notes_after'] .= '<div style="display: flex"><select class="wvr-customer-select"><option value="">' . __( "Sample comments", "woo-virtual-reviews" ) . '</option>' . $text_select_desktop . '</select>';
				$review_form['comment_notes_after'] .= '<span class="wvr-clear-comment wvr-icon-bin"></span></div></div>';
			}

			if ( $canned_style_mobile == 'slide' ) {
				foreach ( $sample_cmts as $sample_cmt ) {
					$text_slide_mobile .= "<span class='wvr-select-sample-cmt' data-value='" . esc_attr( stripslashes( $sample_cmt ) ) . "'>" . esc_html( stripslashes( $sample_cmt ) ) . "</span>";
				}
				$review_form['comment_notes_after'] .= '<div class="wvr-customer-sample-cmt wvr-mobile-style">';
				$review_form['comment_notes_after'] .= '<div style="display: flex"><div class="wvr-customer-pick">' . $text_slide_mobile . '</div>';
				$review_form['comment_notes_after'] .= '<span class="wvr-clear-comment wvr-icon-bin"></span></div></div>';
			} elseif ( $canned_style_mobile == 'select' ) {
				foreach ( $sample_cmts as $sample_cmt ) {
					$text_select_mobile .= "<option value='" . esc_attr( $sample_cmt ) . "'>" . esc_html( stripslashes( $sample_cmt ) ) . "</option>";
				}
				$review_form['comment_notes_after'] .= '<div class="wvr-customer-sample-cmt wvr-mobile-style">';
				$review_form['comment_notes_after'] .= '<div style="display: flex"><select class="wvr-customer-select"><option value="">' . __( "Sample comments", "woo-virtual-reviews" ) . '</option>' . $text_select_mobile . '</select>';
				$review_form['comment_notes_after'] .= '<span class="wvr-clear-comment wvr-icon-bin"></span></div></div>';
			}
		}

		return $review_form;
	}

	public function show_comments( $comment ) {

		if ( $comment->comment_type !== 'review' ) {
			return;
		}

		$show_purchased_label = Data::instance()->get_param( 'show_purchased_label' );
		if ( $show_purchased_label ) {
			global $product;
			$current_id        = $product->get_id();
			$comment_author_id = $comment->user_id;
			$string            = '';

			if ( $comment_author_id > 0 ) { //real cmt
				$arg = array(
					'limit'      => - 1,
					'meta_key'   => '_customer_user',
					'meta_value' => 1,
					'post_type'  => wc_get_order_types(),
					'status'     => array_keys( wc_get_is_paid_statuses() ),
					//array_keys( wc_get_is_paid_statuses() , array( 'completed', 'on-hold', 'processing', 'cancelled' ))
				);

				$orders = wc_get_orders( $arg );

				if ( empty( $orders ) ) {
					return;
				}

				$result = array();

				foreach ( $orders as $order ) {
					foreach ( $order->get_items() as $item ) {
						$data = $item->get_data();
						if ( $current_id == $data['product_id'] ) {
							if ( $product->is_type( 'variable' ) && $data['variation_id'] != 0 ) {
								if ( ! isset( $result[ $data['variation_id'] ] ) ) {
									$result[ $data['variation_id'] ] = 0;
								}
								$result[ $data['variation_id'] ] += $data['quantity'];
							} else {
								if ( ! isset( $result[ $data['product_id'] ] ) ) {
									$result[ $data['product_id'] ] = 0;
								}
								$result[ $data['product_id'] ] += $data['quantity'];
							}
						}
					}
				}

				if ( $product->is_type( 'variable' ) ) {
					foreach ( $result as $var_id => $qty ) {
						$var    = wc_get_product( $var_id );
						$attrs  = apply_filters( 'wvr-variation-label', wc_get_formatted_variation( $var->get_variation_attributes(), true ) );
						$string .= ( "<span class='wvr-product-purchased'>" . $attrs . " x " . $qty . "</span>" );
					}
				} else {
					foreach ( $result as $qty ) {
						$unit   = $qty > 1 ? __( 'products', 'woo-virtual-reviews' ) : __( 'product', 'woo-virtual-reviews' );
						$string .= ( "<span class='wvr-product-purchased'>" . $qty . " " . $unit . "</span>" );
					}
				}
			} else { //virtual cmt

				$comment_id = $comment->comment_ID;

				if ( $product->is_type( 'variable' ) ) {
					$var_id = get_comment_meta( $comment_id, 'wvr_variation', true );
					if ( isset( $var_id ) && is_numeric( $var_id ) ) {
						$var = wc_get_product( $var_id );
						if ( is_object( $var ) ) {
							$attrs  = apply_filters( 'wvr-variation-label', wc_get_formatted_variation( $var->get_variation_attributes(), true ) );
							$string .= ( "<span class='wvr-product-purchased'>" . $attrs . " x 1</span>" );
						}
					}
				} else {
					$string .= ( "<span class='wvr-product-purchased'> 1 product</span>" );
				}
			}

			if ( $string ) {
				?>
                <div class="wvr-comments-group">
                    <i class="wvr-icon-purchased wvr-purchased-format"> </i>
					<?php echo wp_kses_post( $string ) ?>
                </div>
				<?php
			}

		}
	}
}
