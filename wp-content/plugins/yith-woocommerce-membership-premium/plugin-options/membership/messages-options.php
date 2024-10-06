<?php
/**
 * Messages WP List Tab
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\PuginOptions
 */

defined( 'YITH_WCMBS' ) || exit(); // Exit if accessed directly.

return array(
	'membership-messages' => array(
		'membership-messages-list' => array(
			'type'          => 'post_type',
			'post_type'     => YITH_WCMBS_Post_Types::$thread,
			'wp-list-style' => 'classic',
		),
	),
);
