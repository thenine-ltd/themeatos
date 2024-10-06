<?php
if( !defined('ABSPATH')){
	exit;
}

if( !class_exists('YITH_WC_Dynamic_Options')){

	class YITH_WC_Dynamic_Options{


		/**
		 * get option to check if shop manager is enabled
		 * @since 1.7.0
		 * @author YITH
		 * @return bool
		 */
		public static function is_shop_manager_enabled(){

			$value = get_option( 'ywdpd_enable_shop_manager', 'no' );
			return yith_plugin_fw_is_true( $value );

		}

		/**
		 * get option to get the price format
		 * @since 1.7.0
		 * @author YITH
		 * @return string
		 */
		public static function get_price_format(){

			$value = get_option('ywdpd_price_format',  '<del>%original_price%</del> %discounted_price%' );

			return $value;
		}

		/**
		 *
		 * @author YITH
		 * @since 1.7.0
		 * @return bool
		 */
		public static function show_note_on_products(){

			$value = get_option( 'ywdpd_show_note_on_products', 'yes' );
			return yith_plugin_fw_is_true( $value );
		}

		/**
		 * get the position where show the notice
		 * @author YITH
		 * @since 1.7.0
		 * @return string
		 */
		public static function get_show_note_on_products_place(){

			$value = get_option( 'ywdpd_show_note_on_products_place', 'before_add_to_cart' );
			return $value;
		}

		/**
		 * check if show quantity table
		 * @author YITH
		 * @since 1.7.0
		 * @return bool
		 */
		public static function show_quantity_table(){

			$value = get_option( 'ywdpd_show_quantity_table', 'yes' );
			return yith_plugin_fw_is_true( $value );
		}


		/**
		 * get the quantity table layout
		 * @author YITH
		 * @since 1.7.0
		 * @return string
		 */
		public static function get_quantity_table_layout (){

			$value = apply_filters( 'ywdpd_table_orientation', get_option( 'ywdpd_quantity_table_orientation','horizontal' ) );

			return $value;
		}

		/**
		 * get the position where show the quantity table
		 * @author YITH
		 * @since  1.7.0
		 * @return string
		 */
		public static function get_quantity_table_position(){

			$value = get_option( 'ywdpd_show_quantity_table_place','before_add_to_cart' );

			return $value;
		}

		/**
		 * get the quantity table title
		 * @author YITH
		 * @since 1.7.0
		 * @return string
		 */
		public static function get_quantity_table_title(){
			$value = get_option( 'ywdpd_show_quantity_table_label', __( 'Quantity Discount', 'ywdpd' ) );

			return $value;
		}

		/**
		 * get the columns table label for the quantity table
		 * @author YITH
		 * @since 1.7.0
		 * @return array
		 */
		public static function get_quantity_columns_table_title(){

			$value = get_option( 'ywdpd_quantity_table_labels', array( 'quantity' =>__( 'Quantity', 'ywdpd' ), 'price' => __( 'Price', 'ywdpd' ) ) );

			return $value;
		}

		/**
		 * check if show the schedule dates on quantity table
		 * @author YITH
		 * @since 1.7.0
		 * @return bool
		 */
		public static function show_quantity_table_schedule(){

			$value = get_option( 'ywdpd_show_quantity_table_schedule', 'yes' );
			return yith_plugin_fw_is_true( $value );
		}

		/**
		 *
		 * return what price show on products
		 * @author YITH
		 * @since 1.7.0
		 * @return string
		 */
		public static function get_default_price(){

			$value = get_option ('ywdpd_show_as_default', 'min' );
			return $value;
		}

		/**
		 * check if the default qty is auto selected on the quantity table
		 * @author YITH
		 * @since 1.7.0
		 * @return bool
		 */
		public static function is_default_qty_selected(){

			$value = get_option( 'ywdpd_default_qty_selected', 'no' );

			return yith_plugin_fw_is_true( $value );
		}

		/**
		 * check if update the product price if the quantity change
		 * @author YITH
		 * @since 1.7.0
		 * @return bool
		 */
		public static function update_price_on_qty_changes(){

			$value = get_option( 'ywdpd_update_price_on_qty', 'yes' );
			return yith_plugin_fw_is_true( $value );
		}

		/**
		 * get the coupon label
		 * @author YITH
		 * @since 1.7.0
		 * @return string
		 */
		public static function get_coupon_label(){

			$value = get_option( 'ywdpd_coupon_label', 'DISCOUNT' );

			return $value;
		}

		/**
		 * get how calculate the discount in the cart
		 * @author YITH
		 * @since 1.7.0
		 * @return string
		 */
		public static function how_calculate_discounts(){
			$value = get_option( 'ywdpd_calculate_discounts_tax', 'tax_excluded' );

			return $value;
		}

		/**
		 * check if is possible extend the rules with wpml
		 * @author YITH
		 * @since 1.7.0
		 * @return bool
		 */
		public static function can_wpml_extend_to_translated_object(){

			$value = get_option( 'ywdpd_wpml_extend_to_translated_object', 'no' );

			return defined('ICL_SITEPRESS_VERSION') && ( true === $value || 1 === $value || '1' === $value || 'yes' === $value || 'true' === $value );

		}

		/**
		 * show how the cart item price if the special offer is applied
		 * @author YITH
		 * @since 2.0
		 * @return string
		 */
		public static function how_show_special_offer_subtotal(){

			$value  = get_option( 'ywdp_cart_special_offer_show_subtotal_mode', 'unit_price' );

			return $value;
		}

		public static function show_discount_info_in_cart (){

			$value = get_option( 'ywdpd_enable_cart_notices', 'no' );

			return 'yes' === $value ;
		}

		public static function  get_discount_info_message(){

			$value = get_option( 'ywdpd_cart_notice_message', __( 'Please note: you\'ve saved %total_discount_percentage% on this order today', 'ywdpd' ) );

			return $value;
		}
	}
}
