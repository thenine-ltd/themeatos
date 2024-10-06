<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

if ( ! class_exists( 'YITH_WCPB_Settings' ) ) {

	/**
	 * Class YITH_WCPB_Out_Of_Stock_Sync
	 *
	 * @since 1.4.0
	 */
	class YITH_WCPB_Settings {
		/**
		 * @var YITH_WCPB_Settings
		 */
		private static $instance;

		/**
		 * Array containing the raw values of options
		 *
		 * @var array
		 */
		private $options = array();

		/**
		 * Singleton implementation.
		 *
		 * @return YITH_WCPB_Settings
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * YITH_WCPB_Settings constructor.
		 */
		private function __construct() {
		}

		/**
		 * Retrieve the default values
		 *
		 * @return array
		 */
		public function get_defaults() {
			return array(
				'yith-wcpb-out-of-stock-bundle'          => 'yes' === $this->get_option( 'yith-wcpb-bundle-out-of-stock-sync', 'no' ) ? 'set-out-of-stock' : 'show',
				'yith-wcpb-add-to-cart-label'            => __( 'Add to cart', 'woocommerce' ),
				'yith-wcpb-add-item-to-bundle-label'     => __( 'Add item to bundle', 'yith-woocommerce-product-bundles' ),
				'yith-wcpb-add-item-to-bundle-for-label' => __( 'Add item to bundle for %s', 'yith-woocommerce-product-bundles' ),
			);
		}

		/**
		 * Retrieve the default values if the options are empty
		 *
		 * @return array
		 */
		public function get_empty_values() {
			return array(
				'yith-wcpb-add-to-cart-label'            => __( 'Add to cart', 'woocommerce' ),
				'yith-wcpb-add-item-to-bundle-label'     => __( 'Add item to bundle', 'yith-woocommerce-product-bundles' ),
				'yith-wcpb-add-item-to-bundle-for-label' => __( 'Add item to bundle for %s', 'yith-woocommerce-product-bundles' ),
			);
		}

		/**
		 * Retrieve an option
		 *
		 * @param string                 $option    The option name.
		 * @param string|array|bool|null $default   The default value.
		 * @param bool                   $raw_value Do you want the raw value will be returned?
		 * @param bool                   $translate Do you want to translate the value?
		 *
		 * @return mixed
		 */
		public function get_option( $option, $default = null, $raw_value = false, $translate = false ) {
			$default = is_null( $default ) ? $this->get_default( $option ) : $default;

			if ( ! array_key_exists( $option, $this->options ) ) {
				$this->options[ $option ] = get_option( $option, null );
			}

			$value = $this->options[ $option ];

			if ( ! ! $translate && $value && is_string( $value ) ) {
				$value = call_user_func( '__', $value, 'yith-woocommerce-product-bundles' );
			}

			if ( ! $raw_value ) {
				if ( is_null( $value ) ) {
					$value = $default;
				}

				if ( ! $value && $this->has_empty_value( $option ) ) {
					$value = $this->get_empty_value( $option );
				}
			}

			return $value;
		}

		/**
		 * Retrieve an option and translate it.
		 * Useful for labels
		 *
		 * @param string                 $option  The option name.
		 * @param string|array|bool|null $default The default value.
		 *
		 * @return array|bool|mixed|string
		 */
		public function get_option_and_translate( $option, $default = null ) {
			return $this->get_option( $option, $default, false, true );
		}


		/**
		 * Retrieve the default value for an option
		 *
		 * @param string $option
		 *
		 * @return mixed
		 */
		public function get_default( $option ) {
			$defaults = $this->get_defaults();

			return isset( $defaults[ $option ] ) ? $defaults[ $option ] : '';
		}

		/**
		 * Retrieve the default value for an option
		 *
		 * @param string $option
		 *
		 * @return mixed
		 */
		public function get_empty_value( $option ) {
			$values = $this->get_empty_values();

			return isset( $values[ $option ] ) ? $values[ $option ] : '';
		}

		/**
		 * Has this option an value in case of empty set?
		 *
		 * @param string $option
		 *
		 * @return mixed
		 */
		public function has_empty_value( $option ) {
			$values = $this->get_empty_values();

			return isset( $values[ $option ] );
		}

		/**
		 * Retrieve the value for the "out of stock bundle" option
		 *
		 * @return string
		 */
		public function get_out_of_stock_bundle_option() {
			return $this->get_option( 'yith-wcpb-out-of-stock-bundle' );
		}

		/**
		 * Is the "out of stock sync" enabled?
		 *
		 * @return bool
		 */
		public function is_out_of_stock_sync_enabled() {
			$enabled = in_array( $this->get_out_of_stock_bundle_option(), array( 'set-out-of-stock' ), true );

			return apply_filters( 'yith_wcpb_settings_is_out_of_stock_sync_enabled', $enabled );
		}
	}
}

if ( ! function_exists( 'yith_wcpb_settings' ) ) {
	/**
	 * Access to the YITH_WCPB_Settings instance
	 *
	 * @return YITH_WCPB_Settings
	 */
	function yith_wcpb_settings() {
		return YITH_WCPB_Settings::get_instance();
	}
}