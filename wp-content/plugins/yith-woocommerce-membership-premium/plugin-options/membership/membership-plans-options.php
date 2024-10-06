<?php
/**
 * Membership Plans WP List Tab
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\PuginOptions
 */

defined( 'YITH_WCMBS' ) || exit(); // Exit if accessed directly.

return array(
	'membership-membership-plans' => array(
		'membership-membership-plans-list' => array(
			'type'          => 'post_type',
			'post_type'     => YITH_WCMBS_Post_Types::$plan,
			'wp-list-style' => 'classic',
		),
	),
);
