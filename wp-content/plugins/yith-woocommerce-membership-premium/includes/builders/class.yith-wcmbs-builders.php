<?php
/**
 * Builders class
 *
 * @package YITH\Membership\Builders
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Builders' ) ) {
	/**
	 * Builders class
	 * handle Builders
	 *
	 * @since 1.4.0
	 */
	class YITH_WCMBS_Builders {

		use YITH_WCMBS_Singleton_Trait;

		/**
		 * @var YITH_WCMBS_Elementor
		 */
		public $elementor;

		/**
		 * @var YITH_WCMBS_Gutenberg
		 */
		public $gutenberg;

		/**
		 * YITH_WCMBS_Elementor constructor.
		 */
		private function __construct() {
			$this->load();
		}

		/**
		 * Include and instance the builders classes
		 *
		 * @return void
		 */
		private function load() {
			require_once YITH_WCMBS_INCLUDES_PATH . '/builders/gutenberg/class.yith-wcmbs-gutenberg.php';
			require_once YITH_WCMBS_INCLUDES_PATH . '/builders/elementor/class.yith-wcmbs-elementor.php';

			$this->gutenberg = YITH_WCMBS_Gutenberg::get_instance();
			$this->elementor = YITH_WCMBS_Elementor::get_instance();
		}

	}
}
