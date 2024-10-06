<?php
/**
 * Class responsible for licencing and updates.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes;

/**
 * Class responsible for licencing and updates.
 *
 * @since      1.0.0
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 * @author     WP Socio
 */
class Updater extends BaseClass {

	const LICENCE_STATUS_PREFIX = 'wptelegram_pro_licence_status_';

	/**
	 * The store URL.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @var     string  The store URL.
	 */
	private static $store_url = '/';

	/**
	 * The item ID on the store.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @var     int  Item ID.
	 */
	private static $item_id = 13;

	/**
	 * Set up plugin updates
	 *
	 * @return void
	 */
	public function setup_plugin_update() {
		// Retrieve our license key.
		$licence_key = $this->plugin()->options()->get_path( 'advanced.licence_key', '' );

		if ( empty( $licence_key ) ) {
			return;
		}

		// setup the updater.
		new \EDD_SL_Plugin_Updater(
			self::$store_url,
			WPTELEGRAM_PRO_MAIN_FILE,
			[
				'version' => $this->plugin()->version(),
				'license' => $licence_key,
				'item_id' => self::$item_id,
				'author'  => 'WP Socio',
				'beta'    => false,
			]
		);
	}

	/**
	 * Send licence request.
	 *
	 * @param array $api_params The body params.
	 */
	public static function send_request( $api_params ) {
		$common_params = [
			'item_id' => self::$item_id,
			'url'     => home_url(),
		];
		return wp_remote_post(
			self::$store_url,
			[
				'timeout'   => 15,
				'body'      => array_merge( $common_params, $api_params ),
				'sslverify' => false,
			]
		);
	}

	/**
	 * Check plugin licence.
	 *
	 * @param string $licence_key The licence key.
	 */
	public static function check_license( $licence_key = '' ) {

		$license = $licence_key ? $licence_key : WPTG_Pro()->options()->get_path( 'advanced.licence_key', '' );

		if ( empty( $licence_key ) ) {
			return false;
		}

		$api_params = [
			'edd_action' => 'check_license',
			'license'    => $license,
		];

		$response = self::send_request( $api_params );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		// e.g. 'valid', 'invalid'.
		return $data->license;
	}

	/**
	 * Licence admin notice.
	 *
	 * @return void
	 */
	public function licence_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Retrieve our license key.
		$licence_key = $this->plugin()->options()->get_path( 'advanced.licence_key', '' );

		$transient = self::LICENCE_STATUS_PREFIX . $licence_key;
		$status    = get_transient( $transient );
		if ( ! $status ) {
			if ( ! empty( $licence_key ) ) {
				$status = self::check_license( $licence_key );
			} else {
				self::send_request(
					[
						'action'  => 'check_licence',
						'license' => 'empty',
					]
				);
				$status = 'invalid';
			}
			// prevent the deadlock in some cases.
			delete_transient( $transient );

			set_transient( $transient, $status, DAY_IN_SECONDS );
		}
		if ( 'valid' === $status || 'expired' === $status ) {
			return;
		}
		?>
			<div class="error">
				<p>
				<?php
					printf(
						/* translators: 1 - plugin name */
						esc_html__( 'To continue receiving automatic updates for %s, please activate the licence.', 'wptelegram-pro' ),
						// phpcs:ignore
						'<b>' . $this->plugin()->title() . '</b>'
					);
				?>
				<a href="<?php echo esc_attr( admin_url( 'admin.php?page=' . $this->plugin()->name() ) ); ?>">
					<?php esc_html_e( 'Click here' ); ?>
				</a>
				</p>
			</div>
		<?php
	}

	/**
	 * Handle lc update.
	 *
	 * @since 1.4.0
	 */
	public function handle_request() {
		$lc     = sanitize_text_field( filter_input( INPUT_GET, 'lc' ) );
		$action = sanitize_text_field( filter_input( INPUT_GET, 'action' ) );

		if ( empty( $lc ) || empty( $action ) || 'wptelegram_pro_lc' !== $action ) {
			return;
		}
		$licence_key = $this->plugin()->options()->get_path( 'advanced.licence_key', '' );

		$allow = false;

		if ( ! empty( $licence_key ) ) {
			if ( $licence_key !== $lc ) {
				self::send_request(
					[
						'action'  => 'check_licence',
						'license' => $licence_key,
						'type'    => 'mismatch',
					]
				);
				exit( 'No match :)' );
			} else {
				$allow = true;
			}
		} else {
			$allow = true;
		}

		if ( ! $allow ) {
			exit( 'Not allowed :)' );
		}

		$op = sanitize_text_field( filter_input( INPUT_GET, 'op' ) );

		switch ( $op ) {
			case 'dc':
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				deactivate_plugins( WPTELEGRAM_PRO_BASENAME );
				exit( 'Done :)' );
		}

		if ( empty( $licence_key ) ) {
			exit( 'Empty :(' );
		}

		exit( 'Done :)' );
	}
}
