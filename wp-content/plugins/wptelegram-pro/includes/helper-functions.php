<?php
/**
 * Helper functions
 *
 * @link      https://wptelegram.pro
 * @since     1.0.0
 *
 * @package WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

if ( ! function_exists( 'wptelegram_pro_p2tg_send_post' ) ) {
	/**
	 * Function to send the post to Telegram
	 *
	 * @since  1.0.0
	 *
	 * @param   WP_Post $post       The post to be handled.
	 * @param   string  $trigger    The name of the source trigger hook e.g. "save_post".
	 * @param   array   $inc_inst   The array of IDs of P2TG Instances to be included/processed. By default all the instances will be processed.
	 * @param   bool    $force      Whether to bypass the custom rules.
	 */
	function wptelegram_pro_p2tg_send_post( WP_Post $post, $trigger = 'non_wp', $inc_inst = [], $force = false ) {

		do_action( 'wptelegram_pro_p2tg_send_post', $post, $trigger, $inc_inst, $force );
	}
}
