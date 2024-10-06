<?php
/**
 * WP REST API functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.4.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes\restApi;

use WP_REST_Controller;

/**
 * Base class for all the endpoints.
 *
 * @since 1.4.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 * @author     WP Socio
 */
abstract class RESTController extends WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 * @since 1.4.0
	 */
	const REST_NAMESPACE = 'wptelegram-pro/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	const REST_BASE = '';
}
