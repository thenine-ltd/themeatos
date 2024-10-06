<?php

namespace WooVR;

defined( 'ABSPATH' ) || exit;

class Utils {
	public static function get_product_categories( $args = [] ) {
		$args       = wp_parse_args( $args, [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );
		$categories = get_categories( $args );

		return self::build_dropdown_categories_tree( $categories );
	}

	private static function build_dropdown_categories_tree( $all_cats, $parent_cat = 0, $level = 1 ) {
		$res = [];
		foreach ( $all_cats as $cat ) {
			if ( $cat->parent == $parent_cat ) {
				$prefix               = str_repeat( '&nbsp;-&nbsp;', $level - 1 );
				$res[ $cat->term_id ] = $prefix . $cat->name . " ({$cat->count})";
				$child_cats           = self::build_dropdown_categories_tree( $all_cats, $cat->term_id, $level + 1 );
				if ( $child_cats ) {
					$res += $child_cats;
				}
			}
		}

		return $res;
	}

}