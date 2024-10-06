<?php
/**
 * Memberships Options menu tabs and sub-tabs
 *
 * @package YITH\Membership\PluginOptions
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'YITH_WCMBS' ) || exit(); // Exit if accessed directly.

$sub_tabs = array(
	'membership-all-memberships'      => array(
		'title' => _x( 'All Memberships', 'Tab title in plugin settings panel', 'yith-woocommerce-membership' ),
	),
	'membership-membership-plans'     => array(
		'title' => _x( 'Membership Plans', 'Tab title in plugin settings panel', 'yith-woocommerce-membership' ),
	),
	'membership-alternative-contents' => array(
		'title' => _x( 'Alternative Content Blocks', 'Tab title in plugin settings panel', 'yith-woocommerce-membership' ),
	),
	'membership-reports'              => array(
		'title' => _x( 'Reports', 'Tab title in plugin settings panel', 'yith-woocommerce-membership' ),
	),
);

$options = array(
	'membership' => array(
		'membership-tabs' => array(
			'type'     => 'multi_tab',
			'sub-tabs' => $sub_tabs,
		),
	),
);

return apply_filters( 'yith_wcmbs_panel_membership_tab', $options );
