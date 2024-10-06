<?php
/**
 * The admin-specific functionality of the module.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\notify
 */

namespace WPTelegram\Pro\modules\notify;

use WPTelegram\Pro\modules\BaseClass;

/**
 * The admin-specific functionality of the module.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\notify
 * @author     WP Socio
 */
class Admin extends BaseClass {

	/** Updates the DOM data related to p2tg.
	 *
	 * @param array  $data The existing DOM data.
	 * @param string $for  The domain for which the DOM data is to be rendered.
	 *
	 * @return array
	 */
	public function update_dom_data( $data, $for ) {

		if ( 'SETTINGS_PAGE' === $for ) {
			$data['uiData'] = array_merge(
				$data['uiData'],
				[
					'admin_email' => get_option( 'admin_email' ),
				]
			);
		}

		return $data;
	}
}
