<?php
/**
 * Alternative Contents WP List Tab
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\PuginOptions
 */

defined( 'YITH_WCMBS' ) || exit(); // Exit if accessed directly.

return array(
	'membership-alternative-contents' => array(
		'membership-alternative-contents-list' => array(
			'type'          => 'post_type',
			'post_type'     => YITH_WCMBS_Post_Types::$alternative_contents,
			'wp-list-style' => 'classic',
		),
	),
);
