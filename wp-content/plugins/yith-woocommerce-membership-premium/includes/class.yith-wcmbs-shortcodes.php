<?php
/**
 * Shortcodes Class Handler
 *
 * @package YITH\Membership\Classes
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

if ( ! class_exists( 'YITH_WCMBS_Shortcodes' ) ) {
	/**
	 * Shortcodes Class
	 *
	 * @class   YITH_WCMBS_Shortcodes
	 * @package Yithemes
	 * @since   1.0.0
	 */
	class YITH_WCMBS_Shortcodes {

		use YITH_WCMBS_Singleton_Trait;

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {

			/* Print WooCommerce Login form*/
			add_shortcode( 'loginform', array( $this, 'render_login_form' ) );

			/* Print link for protected media by ID of the media*/
			add_shortcode( 'protected_media', array( $this, 'render_protected_media_link' ) );

			/* Print membership protected links */
			add_shortcode( 'membership_protected_links', array( $this, 'render_protected_links' ) );

			/* Print content in base of membership */
			add_shortcode( 'membership_protected_content', array( $this, 'render_protected_content' ) );

			/* Print the list of items in a membership plan */
			add_shortcode( 'membership_items', array( $this, 'render_list_items_in_plan' ) );

			/* Print link for product download files */
			add_shortcode( 'membership_download_product_links', array( $this, 'render_membership_download_product_links' ) );

			/* Print membership history */
			add_shortcode( 'membership_history', array( $this, 'print_membership_history' ) );

			/* Print link for just-downloaded product files */
			add_shortcode( 'membership_downloaded_product_links', array( $this, 'render_membership_downloaded_product_links' ) );

			add_shortcode( 'yith_wcmbs_members_only_content_start', array( $this, 'render_yith_wcmbs_members_only_content_start' ) );

		}

		/**
		 * Render Login Form Shortcode
		 *
		 * @access   public
		 * @return string
		 * @since    1.0.0
		 */
		public function render_login_form() {
			ob_start();
			if ( ! is_user_logged_in() ) {
				echo '<div class="woocommerce">';
				wc_get_template( 'myaccount/form-login.php' );
				echo '</div>';
			}

			return ob_get_clean();
		}

		/**
		 * Render Protected Media Link for downloading
		 *
		 *
		 * @param      $atts array the attributes of shortcode
		 * @param null $content
		 *
		 * @access   public
		 * @return string
		 * @since    1.0.0
		 */
		public function render_protected_media_link( $atts, $content = null ) {
			$html = '';
			if ( ! empty( $atts['id'] ) && ! empty( $content ) ) {
				$user_id = get_current_user_id();
				$post_id = $atts['id'];

				$manager = YITH_WCMBS_Manager();
				if ( $manager->user_has_access_to_post( $user_id, $post_id ) ) {
					$link = add_query_arg( array( 'protected_media' => $post_id ), home_url( '/' ) );

					$html = "<a href='{$link}'>";
					$html .= $content;
					$html .= "</a>";
				}
			}

			return $html;
		}

		/**
		 * Render Protected Links for downloading
		 *
		 * @access   public
		 *
		 * @param      $atts array the attributes of shortcode
		 * @param null $content
		 *
		 * @return string
		 * @since    1.0.0
		 */
		public function render_protected_links( $atts, $content = null ) {
			global $post;

			$link_class = ! empty( $atts['link_class'] ) ? $atts['link_class'] : 'yith-wcmbs-download-button unlocked';
			$post_id    = $atts['post_id'] ?? 0;
			$html       = '';

			if ( ! $post_id && $post ) {
				$post_id = $post->ID;
			}

			if ( $post_id ) {
				$protected_links = yith_wcmbs_get_protected_links( $post_id );
				if ( $protected_links && is_array( $protected_links ) ) {
					$user_id    = get_current_user_id();
					$has_access = yith_wcmbs_has_full_access( $user_id );

					yith_wcmbs_late_enqueue_assets( 'membership' );

					foreach ( $protected_links as $index => $protected_link ) {
						$name       = $protected_link['name'];
						$membership = $protected_link['membership'];

						if ( ! $has_access ) {
							if ( ! ! $membership && is_array( $membership ) ) {
								$has_access = yith_wcmbs_user_has_membership( $user_id, $membership );
							} else {
								$has_access = yith_wcmbs_user_has_membership( $user_id );
							}
						}

						if ( $has_access ) {
							$link = add_query_arg( array( 'protected_link' => $index, 'of_post' => $post_id ), home_url( '/' ) );

							$html .= "<a class='{$link_class}' href='{$link}'>";
							$html .= $name;
							$html .= "</a>";
						}
					}

				}
			}

			return $html;
		}

		/**
		 * Render Protected Links for downloading
		 *
		 * @access   public
		 *
		 * @param      $atts array the attributes of shortcode
		 * @param null $content
		 *
		 * @return string
		 * @since    1.0.0
		 */
		public function render_protected_content( $atts, $content = null ) {
			$html = '';
			if ( $content && ! apply_filters( 'yith_wcmbs_skip_render_protected_content_shortcode', false ) ) {
				$default_atts     = array(
					'plan_id'             => 0,
					'excluded_plan_id'    => 0,
					'user'                => 'member',
					'alternative_content' => '',
				);
				$atts             = wp_parse_args( $atts, $default_atts );
				$plan_id          = $atts['plan_id'];
				$excluded_plan_id = $atts['excluded_plan_id'];
				$user_type        = $atts['user'];

				switch ( $user_type ) {
					case 'guest':
						$has_access = ! is_user_logged_in();
						break;
					case 'logged':
						$has_access = is_user_logged_in();
						break;
					default:
						$user_id    = get_current_user_id();
						$has_access = yith_wcmbs_has_full_access( $user_id );

						if ( ! $has_access ) {
							if ( ! ! $plan_id ) {
								$ids        = explode( ',', $plan_id );
								$has_access = yith_wcmbs_user_has_membership( $user_id, $ids );
								if ( ! ! $excluded_plan_id ) {
									$ids = explode( ',', $excluded_plan_id );
									if ( yith_wcmbs_user_has_membership( $user_id, $ids ) && is_user_logged_in() ) {
										$has_access = false;
									}
								}
							} else {
								$has_access = yith_wcmbs_user_has_membership( $user_id );
							}
						}
						if ( 'non-member' === $user_type ) {
							$has_access = ! $has_access;
						}
				}

				if ( apply_filters( 'yith_wcmbs_has_access_to_protected_content', false ) || $has_access ) {
					$html = do_shortcode( $content );
				} elseif ( isset( $atts['alternative_content'] ) ) {
					$html = do_shortcode( $atts['alternative_content'] );
				}
			}

			return $html;
		}

		/**
		 * Render Product Link for downloading
		 * EXAMPLE:
		 * <code>
		 *  [membership_download_product_links class="btn btn-class"]Download[/membership_download_product_links]
		 * </code>
		 * this code displays a link for protected product download files
		 *
		 * @access   public
		 *
		 * @param array  $atts the attributes of shortcode
		 * @param string $content
		 *
		 * @return string
		 * @since    1.0.0
		 */
		public function render_membership_download_product_links( $atts, $content = null ) {
			wp_enqueue_script( 'yith_wcmbs_frontend_js' );

			$html         = '';
			$default_atts = array(
				'id'     => false,
				'class'  => 'yith-wcmbs-download-button',
				'layout' => 'buttons',
			);
			$atts         = wp_parse_args( $atts, $default_atts );

			// deprecated link_class param
			if ( isset( $atts['link_class'] ) ) {
				$atts['class'] = $atts['link_class'];
			}

			$id         = ! ! $atts['id'] ? absint( $atts['id'] ) : false;
			$class      = $atts['class'];
			$links      = YITH_WCMBS_Products_Manager()->get_download_links( array( 'return' => 'complete', 'id' => $id ) );
			$credits    = yith_wcmbs_get_product_credits( $id );
			$box_layout = 'box' === $atts['layout'];
			// translators: %s the number of credits
			$credits_text = sprintf( _n( '1 credit', '%s credits', $credits, 'yith-woocommerce-membership' ), $credits );

			do_action( 'yith_wcmbs_before_links_list' );

			if ( ! ! $links && ! empty( $links['links'] ) && ! empty( $links['download_info'] ) ) {
				$info                         = $links['download_info'];
				$can_download_without_credits = $info['can_download_without_credits'];
				$links_html                   = '';

				yith_wcmbs_late_enqueue_assets( 'membership' );

				if ( $info['credits_after'] < 0 ) {
					$links_html = "<div class='yith-wcmbs-product-download-box__non-sufficient-credits'>";
					$links_html .= apply_filters( 'yith_wcmbs_membership_download_non_sufficient_credits_message', esc_html__( "You don't have enough credits to download this product!", 'yith-woocommerce-membership' ) );
					$links_html .= '</div>';
				} else {
					foreach ( $links['links'] as $link ) {
						$url       = $link['link'];
						$name      = ! empty( $content ) ? $content : $link['name'];
						$key       = $link['key'];
						$link_name = strtolower( preg_replace( '~[\\\\/:*?’\' "<>|]~', '', $link['name'] ) );
						$classes   = array( $class . ' ' . $link_name );
						$classes[] = $can_download_without_credits ? 'unlocked' : 'locked';

						$name = apply_filters( 'yith_wcmbs_shortcode_membership_download_product_links_name', $name, $link, $atts, $content );
						$url  = apply_filters( 'yith_wcmbs_shortcode_membership_download_product_links_link', $url, $link, $atts, $content );

						$name = "<span class='yith-wcmbs-download-button__name'>{$name}</span>";

						if ( ! $can_download_without_credits && ! $box_layout ) {
							$name .= "<span class='yith-wcmbs-download-button__credits'>{$credits_text}</span>";
						}

						$classes = implode( ' ', $classes );

						$links_html .= "<a class='{$classes}' href='{$url}' data-key='{$key}' data-product-id='{$id}'>{$name}</a>";
					}
				}

				if ( $box_layout ) {
					$info['links_html'] = $links_html;
					$html               = yith_wcmbs_get_template_html( 'frontend/membership-product-download-box.php', $info );;
				} else {
					$html = $links_html;
				}

			}

			return $html;
		}

		/**
		 * Print the list of items in a membership plan
		 * EXAMPLE:
		 * <code>
		 *  [membership_items plan=237]
		 * </code>
		 * this code displays the list of items in the membership plan with ID = 237
		 *
		 * @access   public
		 *
		 * @param      $atts array the attributes of shortcode
		 * @param null $content
		 *
		 * @return string
		 * @since    1.0.0
		 */
		public function render_list_items_in_plan( $atts, $content = null ) {
			if ( ! empty( $atts['plan'] ) ) {
				$user_id = get_current_user_id();
				$plan_id = $atts['plan'];

				$plan       = false;
				$membership = false;

				if ( yith_wcmbs_has_full_access() ) {
					$plan = yith_wcmbs_get_plan( $plan_id );
				} else {
					$member = YITH_WCMBS_Members()->get_member( $user_id );
					if ( $member instanceof YITH_WCMBS_Member && $member->has_active_plan( $plan_id ) ) {
						$membership = $member->get_oldest_active_plan( $plan_id );
						$plan       = $membership->get_plan();
					}
				}

				if ( $plan ) {
					ob_start();
					$post_types = apply_filters( 'yith_wcmbs_render_list_items_post_type', array( 'post', 'page', 'product' ), $atts );
					foreach ( $post_types as $post_type ) {
						$page = 1;
						yith_wcmbs_get_template( '/membership/membership-plan-post-type-items.php', compact( 'plan', 'membership', 'post_type', 'page' ) );
					}

					return ob_get_clean();
				}
			}

			return '';
		}

		/**
		 * Print the list of items in a membership plan
		 * EXAMPLE:
		 * <code>
		 *  [membership_history]
		 * </code>
		 * this code displays the history for all user memberships
		 * EXAMPLE 2:
		 * <code>
		 *  [membership_history id="123" title="Title"]
		 * </code>
		 * this code displays the history user membership with id 123
		 *
		 *
		 * @param array       $atts    The attributes of shortcode.
		 * @param null|string $content The shortcode content.
		 *
		 * @return string
		 * @since    1.0.0
		 */
		public function print_membership_history( $atts, $content = null ) {
			$user_plans = array();
			$title      = $atts['title'] ?? '';

			$no_membership_message = $atts['no_membership_message'] ?? '';

			if ( empty( $atts['id'] ) ) {
				// ALL MEMBERSHIPS
				$member                          = yith_wcmbs_get_member( $atts['user_id'] ?? get_current_user_id() );
				$membership_plans_status         = apply_filters( 'yith_wcmbs_membership_history_shortcode_membership_plans_status', 'any', $atts );
				$membership_plans_args           = array( 'status' => $membership_plans_status );
				$membership_plans_args           = apply_filters( 'yith_wcmbs_membership_history_shortcode_membership_plans_args', $membership_plans_args, $member );
				$membership_plans_args['return'] = 'complete';
				$user_plans                      = $member ? $member->get_membership_plans( $membership_plans_args ) : array();

				// filter all user membership in base of type (only memberships, only subscriptions)
				$type = $atts['type'] ?? '';
				switch ( $type ) {
					case 'membership':
						foreach ( $user_plans as $key => $membership ) {
							if ( $membership->has_subscription() ) {
								unset( $user_plans[ $key ] );
							}
						}
						if ( ! $no_membership_message ) {
							$no_membership_message = __( 'You don\'t have any membership without a subscription plan yet.', 'yith-woocommerce-membership' );
						}
						break;
					case 'subscription':
						foreach ( $user_plans as $key => $membership ) {
							if ( ! $membership->has_subscription() ) {
								unset( $user_plans[ $key ] );
							}
						}
						if ( ! $no_membership_message ) {
							$no_membership_message = __( 'You don\'t have any membership with a subscription plan yet.', 'yith-woocommerce-membership' );
						}
						break;
					default:
						if ( ! $no_membership_message ) {
							$no_membership_message = __( 'You don\'t have any membership yet.', 'yith-woocommerce-membership' );
						}
						break;
				}
			} else {
				$plan_id      = absint( $atts['id'] );
				$member       = yith_wcmbs_get_member( get_current_user_id() );
				$member_plans = $member ? $member->get_plans() : array();
				if ( is_array( $member_plans ) ) {
					foreach ( $member_plans as $member_plan ) {
						if ( absint( $member_plan->plan_id ) === $plan_id ) {
							$user_plans[] = $member_plan;
							break;
						}
					}
				}
			}

			$no_membership_message = apply_filters( 'yith_wcmbs_membership_history_shortcode_no_membership_message', $no_membership_message, $atts );

			$args = array(
				'user_plans'            => $user_plans,
				'title'                 => $title,
				'no_membership_message' => $no_membership_message,
			);

			return yith_wcmbs_get_template_html( '/membership/membership-plans.php', $args );
		}

		/**
		 * Render membership downloaded product links shortcode
		 *
		 * @return string
		 */
		public function render_membership_downloaded_product_links() {
			return yith_wcmbs_get_template_html( '/frontend/downloaded-product-links.php' );
		}

		/**
		 * Render members-only-content start shortcode
		 *
		 * @param array $attrs The shortcode attributes.
		 *
		 * @return string
		 */
		public function render_yith_wcmbs_members_only_content_start( $attrs = array() ) {
			$defaults = array(
				'hide-alternative-content' => 'no',
			);
			$attrs    = shortcode_atts( $defaults, $attrs, 'yith_wcmbs_members_only_content_start' );
			$tags     = array();

			if ( 'yes' !== $attrs['hide-alternative-content'] ) {
				$tags[] = '<!--yith_wcmbs_alternative_content' . ( absint( $attrs['hide-alternative-content'] ) ? '-' . $attrs['hide-alternative-content'] : '' ) . '-->';
			}

			$tags[] = '<!--yith_wcmbs_members_only_content_start-->';

			return implode( "\n", $tags );
		}

		/**
		 * Add shortcode tab in admin tabs
		 *
		 * @param array $admin_tabs
		 *
		 * @return array
		 * @deprecated since 2.0.0 | The Shortcodes tab will no longer be visible on the plugin panel.
		 */
		public function add_shortcodes_tab( $admin_tabs ) {
			wc_deprecated_function( 'YITH_WCMBS_Shortcodes::add_shortcodes_tab', '2.0.0' );
			$admin_tabs['shortcodes'] = array(
				'title' => __( 'Shortcodes', 'yith-woocommerce-membership' ),
				'icon'  => 'configuration',
			);

			return $admin_tabs;
		}

		/**
		 * Render "Shortcodes" Tab
		 *
		 * @deprecated since 2.0.0 | The Shortcodes tab will no longer be visible on the plugin panel.*
		 */
		public function render_shortcodes_tab() {
			wc_deprecated_function( 'YITH_WCMBS_Shortcodes::render_shortcodes_tab', '2.0.0' );
			wc_get_template( '/tabs/shortcodes.php', array(), YITH_WCMBS_TEMPLATE_PATH, YITH_WCMBS_TEMPLATE_PATH );
		}

		/**
		 * Admin enqueue scripts
		 *
		 * @deprecated since 2.0.0 | The Shortcodes tab will no longer be visible on the plugin panel, so it won't be need to load admin scripts.
		 */
		public function admin_enqueue_scripts() {
			wc_deprecated_function( 'YITH_WCMBS_Shortcodes::admin_enqueue_scripts', '2.0.0' );
			wp_register_style( 'yith-wcmbs-admin-shortcodes-tab', YITH_WCMBS_ASSETS_URL . '/css/shortcodes-tab.css', array(), YITH_WCMBS_VERSION );
		}

	}
}

if ( ! function_exists( 'yith_wcmbs_shortcodes' ) ) {
	/**
	 * Unique access to instance of YITH_WCMBS_Shortcodes class
	 *
	 * @return YITH_WCMBS_Shortcodes
	 * @since 1.0.0
	 */
	function yith_wcmbs_shortcodes() {
		return YITH_WCMBS_Shortcodes::get_instance();
	}
}
