<?php
/**
 * Gutenberg Blocks class handler
 *
 * @package YITH\Membership\Builders\Gutenberg
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Gutenberg' ) ) {
	/**
	 * Gutenberg class
	 * handle Gutenberg blocks
	 *
	 * @since 1.4.0
	 */
	class YITH_WCMBS_Gutenberg {

		use YITH_WCMBS_Singleton_Trait;

		/**
		 * YITH_WCMBS_Gutenberg constructor.
		 */
		private function __construct() {
			global $wp_version;
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'init', array( $this, 'handle_iframe_preview' ) );

			$categories_hook = version_compare( $wp_version, '5.8-beta', '>=' ) ? 'block_categories_all' : 'block_categories';
			add_filter( $categories_hook, array( $this, 'block_category' ), 100, 1 );

			add_filter( 'pre_load_script_translations', array( $this, 'script_translations' ), 10, 4 );
		}

		/**
		 * Init Gutenberg blocks
		 */
		public function init() {
			$asset_file = include( YITH_WCMBS_DIR . 'dist/gutenberg/index.asset.php' );

			wp_register_script( 'yith-wcmbs-gutenberg-blocks', YITH_WCMBS_URL . 'dist/gutenberg/index.js', $asset_file['dependencies'], $asset_file['version'] );
			wp_register_style( 'yith-wcmbs-gutenberg-members-only-content-start-editor', YITH_WCMBS_ASSETS_CSS_URL . 'gutenberg/members-only-content-start-editor.css', array(), YITH_WCMBS_VERSION );
			wp_register_style( 'yith-wcmbs-membership-blocks-editor', YITH_WCMBS_ASSETS_CSS_URL . 'gutenberg/membership-blocks-editor.css', array( 'yith-plugin-ui' ), YITH_WCMBS_VERSION );
			wp_register_style( 'yith-wcmbs-frontent-styles', YITH_WCMBS_ASSETS_CSS_URL . 'frontend.css', array(), YITH_WCMBS_VERSION );

			$blocks = array(
				'yith/wcmbs-members-only-content-start'          => array(
					'render_callback' => array( $this, 'render_members_only_content_start' ),
					'editor_script'   => 'yith-wcmbs-gutenberg-blocks',
					'editor_style'    => 'yith-wcmbs-gutenberg-members-only-content-start-editor',
				),
				'yith/wcmbs-membership-protected-media'          => array(
					'render_callback' => array( $this, 'render_protected_media_link' ),
					'editor_style'    => 'yith-wcmbs-membership-blocks-editor',
					'attributes'      => array(
						'mediaId'    => array(
							'type'    => 'number',
							'default' => 0,
						),
						'mediaUrl'   => array(
							'type'    => 'string',
							'default' => '',
						),
						'linkedText' => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				),
				'yith/wcmbs-membership-protected-links'          => array(
					'editor_style'    => 'yith-wcmbs-membership-blocks-editor',
					'render_callback' => array( $this, 'render_membership_protected_links' ),
					'attributes'      => array(
						'postID'    => array(
							'type'    => 'number',
							'default' => 0,
						),
						'postType'  => array(
							'type'    => 'string',
							'default' => 'current-post',
						),
						'linkClass' => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				),
				'yith/wcmbs-membership-protected-content'        => array(
					'editor_style'    => 'yith-wcmbs-membership-blocks-editor',
					'render_callback' => array( $this, 'render_membership_protected_content' ),
				),
				'yith/wcmbs-membership-items'                    => array(
					'style'           => 'yith-wcmbs-frontent-styles',
					'editor_style'    => 'yith-wcmbs-membership-blocks-editor',
					'render_callback' => array( $this, 'render_membership_items' ),
					'attributes'      => array(
						'order'   => array(
							'type'    => 'string',
							'default' => 'DESC',
						),
						'orderby' => array(
							'type'    => 'string',
							'default' => 'ID',
						),
						'plan'    => array(
							'type'    => 'number',
							'default' => 0,
						),
					),
				),
				'yith/wcmbs-membership-history'                  => array(
					'render_callback' => array( $this, 'render_membership_history' ),
					'editor_style'    => 'yith-wcmbs-membership-blocks-editor',
					'attributes'      => array(
						'noMembershipMessage' => array(
							'type'    => 'string',
							'default' => '',
						),
						'id'                  => array(
							'type'    => 'number',
							'default' => 0,
						),
						'plansToPrint'        => array(
							'type'    => 'string',
							'default' => 'all',
							'enum'    => array( 'all', 'specific' ),
						),
						'type'                => array(
							'type'    => 'string',
							'default' => '',
							'enum'    => array( '', 'membership', 'subscription' ),
						),
					),
				),
				'yith/wcmbs-membership-downloaded-product-links' => array(
					'render_callback' => array( $this, 'render_downloaded_products_links' ),
					'editor_style'    => 'yith-wcmbs-membership-blocks-editor',
				),
			);

			wp_localize_script(
				'yith-wcmbs-gutenberg-blocks',
				'mbsBlocks',
				array(
					'siteURL'                    => get_site_url(),
					'previewNonce'               => wp_create_nonce( 'yith-wcmbs-block-preview' ),
					'isSubscriptionPluginActive' => YITH_WCMBS_Compatibility::has_plugin( 'subscription' ),
					'postsCount'                 => wp_count_posts(),
					'productsCount'              => wp_count_posts( 'product' ),
					'pagesCount'                 => wp_count_posts( 'page' ),
					'plansCount'                 => wp_count_posts( YITH_WCMBS_Post_Types::$plan ),
					'alternativeContentsOptions' => yith_wcmbs_get_alternative_contents_options(),
					'mediaIcons'                 => array(
						'image'       => wp_mime_type_icon( 'image' ),
						'audio'       => wp_mime_type_icon( 'audio' ),
						'video'       => wp_mime_type_icon( 'video' ),
						'document'    => wp_mime_type_icon( 'document' ),
						'spreadsheet' => wp_mime_type_icon( 'spreadsheet' ),
						'interactive' => wp_mime_type_icon( 'interactive' ),
						'text'        => wp_mime_type_icon( 'text' ),
						'archive'     => wp_mime_type_icon( 'archive' ),
						'code'        => wp_mime_type_icon( 'code' ),
					),
				)
			);

			foreach ( $blocks as $block_type => $args ) {
				register_block_type( $block_type, $args );
			}

			wp_set_script_translations( 'yith-wcmbs-gutenberg-blocks', 'yith-woocommerce-membership', YITH_WCMBS_LANGUAGES_PATH );
		}

		/**
		 * Handle preview through iFrame to load theme scripts and styles.
		 */
		public function handle_iframe_preview() {
			if ( empty( $_GET['yith-wcmbs-block-preview'] ) ) {
				return;
			}
			$block = wc_clean( wp_unslash( $_GET['block'] ?? '' ) );
			check_admin_referer( 'yith-wcmbs-block-preview', 'yith-wcmbs-block-preview-nonce' );

			$attributes = wc_clean( wp_unslash( $_GET['attributes'] ?? array() ) );

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! defined( 'IFRAME_REQUEST' ) ) {
				define( 'IFRAME_REQUEST', true );
			}
			if ( ! defined( 'YITH_WCMBS_BLOCK_PREVIEW' ) ) {
				define( 'YITH_WCMBS_BLOCK_PREVIEW', true );
			}

			$renders = array(
				'membership-items'           => array( $this, 'render_membership_items' ),
				'membership-protected-links' => array( $this, 'render_membership_protected_links' ),
			);

			if ( array_key_exists( $block, $renders ) ) {
				do_action( 'wp_loaded' ); // Trigger wp_loaded to allow loading font-families and styles from theme.json.
				wp_enqueue_style( 'yith-wcmbs-frontent-styles' );

				$block_html = $renders[ $block ]( $attributes );

				if ( $block_html ) {
					?>
					<!doctype html>
					<html <?php language_attributes(); ?>>
					<head>
						<meta charset="<?php bloginfo( 'charset' ); ?>"/>
						<meta name="viewport" content="width=device-width, initial-scale=1"/>
						<link rel="profile" href="https://gmpg.org/xfn/11"/>
						<?php wp_head(); ?>
						<style>
							html, body, #page, #content {
								padding    : 0 !important;
								margin     : 0 !important;
								min-height : 0 !important;
							}

							#hidden-footer {
								display : none !important;
							}
						</style>
					</head>
					<body <?php body_class(); ?>>
					<div id="page" class="site">
						<div id="content" class="site-content">
							<?php echo $block_html; ?>
						</div><!-- #content -->
					</div><!-- #page -->
					<div id="hidden-footer">
						<?php
						// The footer is wrapped in a hidden element to prevent issues if any plugin prints something there.
						wp_footer();
						?>
					</div>
					</body>
					</html>
					<?php
				}
			}

			exit;
		}

		/**
		 * Render membership items block
		 *
		 * @param array $attributes The block attributes.
		 *
		 * @return string
		 */
		public function render_protected_media_link( $attributes ): string {
			$id          = ! empty( $attributes['mediaId'] ) ? absint( $attributes['mediaId'] ) : false;
			$linked_text = ! empty( $attributes['linkedText'] ) ? $attributes['linkedText'] : '';

			return YITH_WCMBS_Shortcodes()->render_protected_media_link( compact( 'id' ), $linked_text );
		}

		/**
		 * Render membership items block
		 *
		 * @param array $attributes The block attributes.
		 *
		 * @return string
		 */
		public function render_membership_items( $attributes ): string {
			return trim( YITH_WCMBS_Shortcodes()->render_list_items_in_plan( $attributes ) );
		}

		/**
		 * Render membership history block
		 *
		 * @param array $attributes The block attributes.
		 *
		 * @return string
		 */
		public function render_membership_history( $attributes ): string {
			$attributes['no_membership_message'] = $attributes['noMembershipMessage'] ?? '';

			if ( ( $attributes['type'] ?? '' ) !== '' && ! YITH_WCMBS_Compatibility::has_plugin( 'subscription' ) ) {
				$attributes['type'] = '';
			}

			return YITH_WCMBS_Shortcodes()->print_membership_history( $attributes );
		}

		/**
		 * Render membership downloaded products links
		 *
		 * @return string
		 */
		public function render_downloaded_products_links(): string {
			return YITH_WCMBS_Shortcodes()->render_membership_downloaded_product_links();
		}

		/**
		 * Render the "Members-only content start" block
		 *
		 * @param $attributes
		 * @param $content
		 *
		 * @return string
		 */
		public function render_members_only_content_start( $attributes, $content ) {
			$defaults   = array(
				'hideAlternativeContent' => false,
				'alternativeContent'     => 0,
			);
			$attributes = wp_parse_args( $attributes, $defaults );
			$tags       = array();

			if ( ! $attributes['hideAlternativeContent'] ) {
				$tags[] = '<!--yith_wcmbs_alternative_content' . ( ! empty( $attributes['alternativeContent'] ) ? '-' . $attributes['alternativeContent'] : '' ) . '-->';
			}

			$tags[] = '<!--yith_wcmbs_members_only_content_start-->';

			return implode( "\n", $tags );
		}

		/**
		 * Render the "Membership protected link" block
		 *
		 * @param array $attributes The block attributes.
		 *
		 * @return string
		 */
		public function render_membership_protected_links( $attributes ) {
			$post_type  = $attributes['postType'] ?? 'current-post';
			$link_class = $attributes['linkClass'] ?? '';
			$post_id    = absint( $attributes['postID'] ?? 0 );
			if ( 'current-post' === $post_type ) {
				global $post;
				$post_id = $post ? $post->ID : false;
			}

			if ( defined( 'YITH_WCMBS_BLOCK_PREVIEW' ) && YITH_WCMBS_BLOCK_PREVIEW && 'current-post' === $post_type && ! empty( $attributes['currentPostId'] ) ) {
				$post_id   = absint( $attributes['currentPostId'] );
				$post_type = get_post_type( $post_id );
			}

			$html = $post_id ? YITH_WCMBS_Shortcodes::get_instance()->render_protected_links( compact( 'post_id', 'link_class' ) ) : '';

			return $html && apply_filters( 'yith_wcmbs_add_wrapper_to_protected_link_block_content', true, $attributes, $post_id ) ? '<div class="yith-wcmbs-membership-protected-links-wrapper">' . $html . '</div>' : $html;
		}

		/**
		 * Render the "Membership protected content" block
		 *
		 * @param array  $attributes The block attributes.
		 * @param string $content    The block content.
		 *
		 * @return string
		 */
		public function render_membership_protected_content( $attributes, $content ) {
			$visible_to = $attributes['user'] ?? 'guest';
			$show       = false;
			switch ( $visible_to ) {
				case 'member':
				case 'non-member':
					$member = yith_wcmbs_get_member( get_current_user_id() );
					$plans  = $attributes['plans'] ?? array();
					if ( is_array( $plans ) ) {
						if ( ! count( $plans ) ) {
							$show = $member && count( $member->get_plans() );
						} else {
							$has_selected_plans = count( array_filter( $plans, array( $member, 'has_active_plan' ) ) );
							$show               = ! $member || ( ! $has_selected_plans && 'non-member' === $visible_to ) || ( $has_selected_plans && 'member' === $visible_to );
						}
					}
					break;
				case 'guest':
					$show = ! is_user_logged_in();
					break;
			}
			return $show || yith_wcmbs_has_full_access( get_current_user_id() ) ? $content : '';
		}

		/**
		 * Add YITH Category
		 *
		 * @param $categories array Block categories
		 *
		 * @return array Block categories
		 */
		public function block_category( $categories ) {

			$found_key = array_search( 'yith-blocks', array_column( $categories, 'slug' ) );

			if ( ! $found_key ) {
				$categories[] = array(
					'slug'  => 'yith-blocks',
					'title' => _x( 'YITH', '[gutenberg]: Category Name', 'yith-plugin-fw' ),
				);
			}

			return $categories;
		}

		/**
		 * Create the json translation through the PHP file
		 * so it's possible using normal translations (with PO files) also for JS translations
		 *
		 * @param string|null $json_translations
		 * @param string      $file
		 * @param string      $handle
		 * @param string      $domain
		 *
		 * @return string|null
		 */
		public function script_translations( $json_translations, $file, $handle, $domain ) {
			if ( 'yith-woocommerce-membership' === $domain && in_array( $handle, array( 'yith-wcmbs-gutenberg-blocks' ) ) ) {
				$path = YITH_WCMBS_LANGUAGES_PATH . 'yith-woocommerce-membership.php';
				if ( file_exists( $path ) ) {
					$translations = include $path;

					$json_translations = json_encode(
						array(
							'domain'      => 'yith-woocommerce-membership',
							'locale_data' => array(
								'messages' =>
									array(
										'' => array(
											'domain'       => 'yith-woocommerce-membership',
											'lang'         => get_locale(),
											'plural-forms' => 'nplurals=2; plural=(n != 1);',
										),
									)
									+
									$translations,
							),
						)
					);

				}
			}

			return $json_translations;
		}

	}
}
