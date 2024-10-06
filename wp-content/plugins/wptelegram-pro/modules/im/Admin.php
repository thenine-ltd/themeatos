<?php
/**
 * The admin-specific functionality of the module.
 *
 * @link       https://wptelegram.pro
 * @since      1.3.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\im
 */

namespace WPTelegram\Pro\modules\im;

use WPTelegram\Pro\modules\BaseClass;
use WPTelegram\Pro\shared\Shared;
use WPTelegram\Pro\includes\AssetManager;
use WPTelegram\Pro\modules\p2tg\Utils as P2TGUTils;
use WPTelegram\Pro\includes\Helpers;
use WPTelegram\Pro\includes\Utils;
use WP_Post;

/**
 * The admin-specific functionality of the module.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\im
 * @author     WP Socio
 */
class Admin extends BaseClass {

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.3.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_styles( $hook_suffix ) {

		$handle = AssetManager::ADMIN_INSTANT_MESSAGES_HANDLE;

		// Load only on settings page.
		if ( $this->is_settings_page( $hook_suffix ) && wp_style_is( $handle, 'registered' ) ) {
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.3.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		// Load only on settings page.
		if ( $this->is_settings_page( $hook_suffix ) ) {
			$handle = AssetManager::ADMIN_INSTANT_MESSAGES_HANDLE;

			// needed for file uploads.
			wp_enqueue_media();

			wp_enqueue_script( $handle );

			// Pass data to JS.
			$data = AssetManager::instance()->get_dom_data( 'INSTANT_MESSAGES' );

			AssetManager::add_dom_data( $handle, $data );
		}
	}

	/** Updates the DOM data related to p2tg.
	 *
	 * @param array  $data The existing DOM data.
	 * @param string $for  The domain for which the DOM data is to be rendered.
	 *
	 * @return array
	 */
	public function update_dom_data( $data, $for ) {

		if ( 'INSTANT_MESSAGES' === $for ) {
			$data['uiData']               = array_merge(
				$data['uiData'],
				[
					'bot_options'          => Helpers::get_bot_options(),
					'send_files_by_url'    => Shared::send_files_by_url(),
					'p2tg_channels'        => $this->get_p2tg_channels(),
					'is_wptg_login_active' => defined( 'WPTELEGRAM_LOGIN_LOADED' ),
				]
			);
			$data['pluginInfo']['title'] .= ' (' . __( 'Instant Messages', 'wptelegram-pro' ) . ')';

			$data['pluginInfo']['description'] = __( 'Using this module, you can send instant messages to users, channels and groups.', 'wptelegram-pro' );
		}

		return $data;
	}

	/**
	 * Register the admin menu.
	 *
	 * @since 1.3.0
	 */
	public function add_plugin_admin_menu() {
		add_submenu_page(
			WPTG_Pro()->name(),
			__( 'Instant Messages', 'wptelegram-pro' ),
			__( 'Instant Messages', 'wptelegram-pro' ),
			'manage_options',
			WPTG_Pro()->name() . '_' . $this->module()->name(),
			[ $this, 'display_plugin_admin_page' ]
		);
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since 1.3.0
	 */
	public function display_plugin_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
			<div id="wptelegram-pro-im-settings"></div>
		<?php
	}

	/**
	 * Get the registered post types.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function get_p2tg_channels() {

		$options = [];

		if ( defined( 'WPTELEGRAM_PRO_P2TG_LOADED' ) ) {
			$instances = P2TGUTils::get_saved_instances();

			if ( ! empty( $instances ) ) {
				$added_channels = [];
				foreach ( $instances as $id => $instance ) {
					$title = Utils::decode_html( get_the_title( $id ) );
					foreach ( $instance['channels'] as $channel ) {
						if ( ! in_array( $channel, $added_channels, true ) ) {
							$options[] = [
								'value' => $channel,
								'label' => "{$channel} ({$title})",
							];

							$added_channels[] = $channel;
						}
					}
				}
			}
		}

		return apply_filters( 'wptelegram_pro_im_p2tg_channels', $options );
	}

	/**
	 * Adds file path to the attachment for JS.
	 *
	 * @since 1.3.0
	 *
	 * @param array   $response   Array of prepared attachment data.
	 * @param WP_Post $attachment Attachment object.
	 */
	public function prepare_attachment_for_js( $response, $attachment ) {

		// append filepath.
		$response['filepath'] = get_attached_file( $attachment->ID );

		return $response;
	}

	/**
	 * Whether the current page is the plugin settings page.
	 *
	 * @since 1.4.0
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function is_settings_page( $hook_suffix ) {
		return preg_match( '/' . WPTG_Pro()->name() . '_' . $this->module()->name() . '$/', $hook_suffix );
	}
}
