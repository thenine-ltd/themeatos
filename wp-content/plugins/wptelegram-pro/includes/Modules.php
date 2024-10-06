<?php
/**
 * Loads and includes all the active modules
 *
 * @link       https://wptelegram.pro
 * @since     1.0.0
 *
 * @package WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes;

/**
 * Loads and includes all the active modules
 *
 * @package WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 * @author   WP Socio
 */
class Modules extends BaseClass {

	/**
	 * Retrieve all modules.
	 *
	 * @since   1.0.0
	 * @return array
	 */
	public static function get_all_modules() {
		return [
			'bots',
			'im',
			'notify',
			'p2tg',
			'proxy',
		];
	}

	/**
	 * Load the active modules
	 *
	 * @since   1.0.0
	 * @access   public
	 */
	public function load() {
		// If an upgrade is going on.
		if ( defined( 'WPTELEGRAM_PRO_DOING_UPGRADE' ) && WPTELEGRAM_PRO_DOING_UPGRADE ) {
			return;
		}

		$namespace = 'WPTelegram\Pro\modules';

		foreach ( self::get_all_modules() as $module ) {

			$main = "{$namespace}\\{$module}\Main";

			$main::instance()->init();

			define( 'WPTELEGRAM_PRO_' . strtoupper( $module ) . '_LOADED', true );
		}
	}
}
