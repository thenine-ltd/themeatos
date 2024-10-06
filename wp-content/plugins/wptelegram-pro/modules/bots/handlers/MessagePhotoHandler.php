<?php
/**
 * Text Handling functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 */

namespace WPTelegram\Pro\modules\bots\handlers;

/**
 * Text Handling functionality of the plugin.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 * @author     WP Socio
 */
class MessagePhotoHandler extends BaseHandler {

	/**
	 * Process the photo
	 *
	 * @since   1.0.0
	 */
	public function process() {

		$photo = $this->get();

		do_action( 'wptelegram_pro_bots_process_message_photo', $photo, $this->get_parent_handler(), $this );
	}
}
