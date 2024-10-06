<?php

use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Widget_Base;

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Elementor_Members_Only_Content_Start_Widget' ) ) {
	/**
	 * "Members-only content start" widget
	 *
	 * @since 1.4.0
	 */
	class YITH_WCMBS_Elementor_Members_Only_Content_Start_Widget extends \Elementor\Widget_Base {
		/**
		 * Get element name.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'yith_wcmbs_members_only_content_start';
		}

		/**
		 * Get the element title.
		 *
		 * @return string
		 */
		public function get_title() {
			return _x( 'Members-only content start', 'Elementor Widget - title', 'yith-woocommerce-membership' );
		}

		/**
		 * Get the element icon.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-lock-user';   // Ir 'fa fa-bomb' if it is a font awesome icon.
		}

		/**
		 * Get widget categories.
		 * Retrieve the list of categories the YITH_WCAF_Elementor_Link_Generator widget belongs to.
		 *
		 * @return array Widget categories.
		 * @since  1.0.0
		 * @access public
		 */
		public function get_categories() {
			return array( 'general', 'yith' );
		}

		/**
		 * Register the widget controls.
		 */
		public function _register_controls() {

			$this->start_controls_section(
				'options',
				array(
					'label' => _x( 'Options', 'Elementor Widget - section title', 'yith-woocommerce-membership' ),
					'tab'   => Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'widget_alert',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => _x( 'Note: This widget will work only if the alternative content is enabled in "YITH > Membership > General Options".', 'Elementor Widget', 'yith-woocommerce-membership' ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				)
			);

			$default_options      = array(
				'no'  => __( 'Default', 'yith-woocommerce-membership' ),
				'yes' => __( 'Hide alternative content', 'yith-woocommerce-membership' ),
			);
			$alternative_contents = yith_wcmbs_get_alternative_contents_options();

			$this->add_control(
				'hide_alternative_content',
				array(
					'label'   => _x( 'Hide the alternative content', 'Elementor Widget - option label', 'yith-woocommerce-membership' ),
					'type'    => Controls_Manager::SELECT2,
					'options' => $default_options + array_combine( array_column( $alternative_contents, 'value' ), array_column( $alternative_contents, 'label' ) ),
					'default' => 'default',
				)
			);

			$this->end_controls_section();
		}

		/**
		 * Render the content of the widget
		 */
		protected function render() {
			if ( Plugin::$instance->editor->is_edit_mode() ) {
				$this->render_content_for_editor();
			}
			/**
			 * Do nothing when rendering on frontend, since it renders the shortcode in the
			 * "root" document, to prevent layout issues.
			 *
			 * @since 1.11.0
			 * @see   \YITH_WCMBS_Elementor_Members_Only_Content_Start_Widget::render_content_in_document_root
			 */
		}

		/**
		 * Render content in document root (out of a "section")
		 * to prevent layout issues.
		 *
		 * @since 1.11.0
		 */
		public function render_content_in_document_root() {
			if ( ! Plugin::$instance->editor->is_edit_mode() ) {
				$settings                 = $this->get_settings_for_display();
				$hide_alternative_content = $settings['hide_alternative_content'];

				$shortcode  = 'yith_wcmbs_members_only_content_start';
				$attributes = 'hide-alternative-content="' . $hide_alternative_content . '"';

				$shortcode = "[{$shortcode} {$attributes}]";

				echo do_shortcode( $shortcode );
			}
		}

		/**
		 * Render the widget output in the editor.
		 */
		protected function content_template() {
			$this->render_content_for_editor();
		}

		/**
		 * Render the content for the editor
		 */
		private function render_content_for_editor() {
			?>
			<div class="yith-wcmbs-elementor-members-only-content-start-widget">
				<span class="yith-wcmbs-elementor-members-only-content-start-widget__title">
					<?php echo esc_html( _x( 'Members-only content starts here', 'Elementor Widget', 'yith-woocommerce-membership' ) ); ?>
				</span>
			</div>
			<?php
		}
	}
}