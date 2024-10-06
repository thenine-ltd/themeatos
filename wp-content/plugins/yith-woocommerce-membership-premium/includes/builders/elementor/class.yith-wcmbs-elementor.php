<?php
/**
 * Elementor widgets class handler
 *
 * @package YITH/Membership/Builders/Elementor
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Elementor' ) ) {
	/**
	 * Elementor class
	 * handle Elementor widgets
	 *
	 * @since 1.4.0
	 */
	class YITH_WCMBS_Elementor {

		use YITH_WCMBS_Singleton_Trait;

		/**
		 * YITH_WCMBS_Elementor constructor.
		 */
		private function __construct() {
			if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '3.0.0', '>=' ) ) {
				add_action( 'init', array( $this, 'init' ) );

				add_action( 'elementor/frontend/section/after_render', array( $this, 'after_render_section' ) );
				add_action( 'elementor/frontend/container/after_render', array( $this, 'after_render_section' ) );
			}
		}

		/**
		 * Let's start with Elementor
		 */
		public function init() {
			$this->load_files();
			add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ) );
			add_action( 'elementor/elements/categories_registered', array( $this, 'add_yith_category' ) );
		}

		/**
		 * Load files
		 */
		private function load_files() {
			require_once YITH_WCMBS_INCLUDES_PATH . '/builders/elementor/widgets/class.yith-wcmbs-elementor-members-only-content-start-widget.php';
		}

		/**
		 * Register Elementor Widgets
		 */
		public function register_widgets() {
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new YITH_WCMBS_Elementor_Members_Only_Content_Start_Widget() );
		}

		/**
		 * Add "YITH" group for Elementor widgets
		 *
		 * @param Elementor\Elements_Manager $elements_manager
		 */
		public function add_yith_category( $elements_manager ) {
			$elements_manager->add_category(
				'yith',
				array(
					'title' => 'YITH',
					'icon'  => 'fa fa-plug',
				)
			);
		}

		/**
		 * Return true if the specified element contains the "Members-only content start" widget.
		 *
		 * @since 1.11.0
		 * @param Elementor\Element_Base $element The element.
		 *
		 * @return bool
		 */
		private function element_has_members_only_content_start_widget( $element ) {
			$children = $element->get_children();
			$widget   = false;
			if ( $children ) {
				foreach ( $children as $child ) {
					$widget = $this->element_has_members_only_content_start_widget( $child );
					if ( $widget ) {
						return $widget;
					}
				}
			} else {
				return 'yith_wcmbs_members_only_content_start' === $element->get_name() ? $element : false;
			}

			return $widget;
		}

		/**
		 * If the section contains the "Members-only content start" widget, it'll print the shortcode
		 * in a new "row", to be in the "root" document.
		 * This to prevent layout issues.
		 *
		 * @since 1.11.0
		 * @param Elementor\Element_Section $section The section.
		 *
		 */
		public function after_render_section( $section ) {
			$widget = $this->element_has_members_only_content_start_widget( $section );
			if ( $widget && is_callable( array( $widget, 'render_content_in_document_root' ) ) ) {
				$widget->render_content_in_document_root();
			}
		}
	}
}
