<?php
/**
 * Messages Widget
 *
 * @package YITH|Membership\Classes
 * @author  YITH <plugins@yithemes.com>
 * @version 1.0.0
 */

defined( 'YITH_WCMBS' ) || exit;  // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCBSL_Messages_Widget' ) ) {
	/**
	 * YITH_WCBSL_Messages_Widget
	 *
	 * @since      1.0.0
	 * @deprecated since 2.3.0
	 */
	class YITH_WCBSL_Messages_Widget extends WC_Widget {
		/**
		 * Constructor
		 */
		public function __construct() {
			wc_deprecated_function( 'YITH_WCBSL_Messages_Widget::__construct()', '2.3.0' );
			$this->widget_cssclass    = 'yith_wcmbs_messages_widget';
			$this->widget_description = __( 'Display message widget for members.', 'yith-woocommerce-membership' );
			$this->widget_id          = 'yith_wcmbs_messages_widget';
			$this->widget_name        = __( 'YITH Membership - Messages', 'yith-woocommerce-membership' );

			$this->settings = array(
				'title' => array(
					'type'  => 'text',
					'std'   => __( 'Messages', 'yith-woocommerce-membership' ),
					'label' => __( 'Title', 'yith-woocommerce-membership' ),
				),
			);

			parent::__construct();
		}

		/**
		 * Render the widget.
		 *
		 * @param array $args     The widget arguments.
		 * @param mixed $instance The widget instance.
		 */
		public function widget( $args, $instance ) {
			wc_deprecated_function( 'YITH_WCBSL_Messages_Widget::widget', '2.3.0' );
		}
	}
}
