<?php
/**
 * Memberships WP List Tab
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\PuginOptions
 */

defined( 'YITH_WCMBS' ) || exit(); // Exit if accessed directly.

return array(
	'membership-all-memberships' => array(
		'membership-all-memberships-list' => array(
			'type'          => 'post_type',
			'post_type'     => YITH_WCMBS_Post_Types::$membership,
			'wp-list-style' => 'classic',
		),
	),
);
