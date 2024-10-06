<?php
/**
 * Created by PhpStorm.
 * User: Villatheme-Thanh
 * Date: 04-05-19
 * Time: 2:55 PM
 */

namespace WooVR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Data {

	protected static $instance = null;
	protected $options;

	private function __construct() {
		$this->get_params();
	}

	public static function instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected $default_data = array(
		'names' => array(
			"Aadarsh",
			"Aiden",
			"Alan",
			"Angel",
			"Anthony",
			"Avery",
			"Bryan",
			"Camden",
			"Charles",
			"Daniel",
			"David",
			"Dominic",
			"Dylan",
			"Edward",
			"Hayden",
			"Henry",
			"Isaac",
			"Jackson",
			"John",
			"Julian",
			"Kaden Arabic",
			"Kai",
			"Kayden",
			"Kevin",
			"Leo",
			"Liam",
			"Lucas",
			"Mason",
			"Mateo",
			"Matthew",
			"Max",
			"Michael",
			"Nathaniel",
			"Nicholas",
			"Nolan",
			"Owen",
			"Patrick",
			"Paul",
			"Richard",
			"Riley",
			"Robert",
			"Ryan",
			"Ryder",
			"Ryker",
			"Samuel",
			"Phoenix",
			"Tyler",
			"William",
			"Zane",
			"Zohar",
		),

		'cmt' => array(
			"Good quality.",
			"The product is firmly packed.",
			"Good service.",
			"Very well worth the money.",
			"Very fast delivery.",
		),

		'cmt_frontend' => array(
			"Good quality.",
			"The product is firmly packed.",
			"Good service.",
			"Very well worth the money.",
			"Very fast delivery.",
		),

		'rating'                  => '5-5',
		'auto_rating'             => 0,
		'auto_fill_review'        => 'Good quality.',
		'first_comment'           => 'Good quality.',
		'canned_style_desktop'    => 'select',
		'canned_style_mobile'     => 'slide',
		'canned_text_color'       => '#000000',
		'canned_bg_color'         => '#dddddd',
		'canned_text_hover_color' => '#ffffff',
		'canned_hover_color'      => '#ff0000',
		'purchased_label_icon'    => 'e900',
		'purchased_icon_color'    => '#000000',
		'purchased_text_color'    => '#000000',
		'purchased_bg_color'      => '#eeeeee',
		'custom_css'              => array(),
	);

	public function get_star_option() {
		return array(
			'5-5' => '5 star',
			'4-4' => '4 star',
			'3-3' => '3 star',
			'2-2' => '2 star',
			'1-1' => '1 star',
			'1-5' => 'Random 1-5 star',
			'2-5' => 'Random 2-5 star',
			'3-5' => 'Random 3-5 star',
			'4-5' => 'Random 4-5 star',
		);
	}

	public static function get_icons() {
		return array(
			''     => 'wvr-icon-no-icon',
			'e900' => 'wvr-icon-shopping-bag',
			'e902' => 'wvr-icon-cart-arrow-down',
			'e93f' => 'wvr-icon-credit-card',
			'e903' => 'wvr-icon-currency-dollar',
			'e904' => 'wvr-icon-location-shopping',
		);
	}

	public function get_params() {
		$option        = get_option( WVR_OPTION, true );
		$data          = apply_filters( 'wvr_default_data', $this->default_data );
		$this->options = wp_parse_args( $option, $data );

		return $this->options;
	}

	public function get_param( $option ) {
		return $this->options[ $option ] ?? '';
	}
}
