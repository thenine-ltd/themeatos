<?php
/**
 * Inline Handling functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 */

namespace WPTelegram\Pro\modules\bots\handlers;

/**
 * Inline Query Handling functionality of the plugin.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 * @author     WP Socio
 */
class InlineQueryHandler extends BaseHandler {

	/**
	 * Process the inline query
	 *
	 * @since  1.0.0
	 */
	public function process() {

		$query = $this->get();

		// Text of the query.
		$query = $this->get( 'query' );

		// Offset of the results to be returned.
		$offset = $this->get( 'offset' );

		$results = apply_filters( 'wptelegram_pro_bots_inline_query_results', [], $query, $this );

		$inline_query_args = [
			'inline_query_id' => $this->get( 'id' ),
			'results'         => wp_json_encode( $results ),
		];

		$inline_query_args = apply_filters( 'wptelegram_pro_bots_answer_inline_query_args', $inline_query_args, $query, $this );

		$this->get_bot_api()->answerInlineQuery( $inline_query_args );

		do_action( 'wptelegram_pro_bots_process_inline_query', $query, $this );

		do_action( "wptelegram_pro_bots_process_inline_query_{$query}", $this );
	}
}
