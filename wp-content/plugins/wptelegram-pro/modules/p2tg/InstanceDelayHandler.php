<?php
/**
 * Post Handling functionality of the plugin.
 *
 * @link        https://wptelegram.pro
 * @since       1.0.0
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\includes\Options;
use WP_List_Util;

/**
 * The Post Handling functionality of the plugin.
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 * @author      WP Socio
 */
class InstanceDelayHandler {

	/**
	 * The post to be handled
	 *
	 * @var WP_Post $post   Post object.
	 */
	protected $post;

	/**
	 * The send post trigger.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var string $trigger The trigger name.
	 */
	protected $trigger;

	/**
	 * The data from post edit page.
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var Options $form_data The submitted form data.
	 */
	private $form_data;

	/**
	 * Name of the delay hook
	 *
	 * @var string $hook   Hook name.
	 */
	public static $hook = 'wptelegram_pro_p2tg_delayed_instance';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post    The post being processed.
	 * @param string  $trigger The trigger name.
	 */
	public function __construct( $post, $trigger ) {

		$this->post = $post;

		$this->trigger = $trigger;
	}

	/**
	 * Process instances that are set for some delay.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $p2tg_instances The instances to be processed.
	 * @param Options $form_data      The data from post edit page.
	 */
	public function process_instances_for_delay( &$p2tg_instances, $form_data ) {

		$this->form_data = $form_data;

		$delayed_instances = [];

		$options = new Options();

		foreach ( $p2tg_instances as $instance_id => $instance ) {

			$options->set_data( $instance );

			$delay = $this->get_instance_delay( $options );

			// if delay is set.
			if ( $delay ) {

				$this->set_instance_for_delay( $delay, $instance_id );

				// Remove from current instances.
				unset( $p2tg_instances[ $instance_id ] );
				$delayed_instances[ $instance_id ] = $instance;
			}
		}

		if ( ! empty( $delayed_instances ) ) {
			$util = new WP_List_Util( $delayed_instances );
			// sort by delay in ascending order.
			$sorted_delayed_instances = $util->sort( 'delay' );

			// This is an array of instance Ids with the delay that was set
			// either in the instance settings or override settings on the post edit page.
			// It can be used to show the correct delay for override settings.
			$delay_for_override = wp_list_pluck( $sorted_delayed_instances, 'delay', 'id' );
			if ( ! add_post_meta( $this->post->ID, Main::PREFIX . 'delay_for_override', $delay_for_override, true ) ) {
				update_post_meta( $this->post->ID, Main::PREFIX . 'delay_for_override', $delay_for_override );
			}

			// this will be the delay of the instance with least delay.
			$least_delay = 0;
			foreach ( $sorted_delayed_instances as $delayed_instance ) {
				if ( ! $least_delay ) { // assumed to be the first instance
					// set least delay from the first one.
					$least_delay = $delayed_instance['delay'];
					// set the delay of first instance to 0.
					$delayed_instance['delay'] = 0;
				} else {
					// subtract least delay from instance delay.
					$delayed_instance['delay'] -= $least_delay;
				}

				// override the instance.
				$delayed_instances[ $delayed_instance['id'] ] = $delayed_instance;
			}

			$this->may_be_save_instances_to_meta( $delayed_instances );
		} else {
			// Since there are no instances set for delay, lets do housekeeping.
			delete_post_meta( $this->post->ID, Main::PREFIX . 'delay_for_override' );
		}

		do_action( 'wptelegram_pro_p2tg_after_process_instances_for_delay', $this->post, $delayed_instances, $p2tg_instances );
	}

	/**
	 * Save instances meta for the delayed post.
	 *
	 * @since   1.0.0
	 * @param array $delayed_instances The instances to be delayed.
	 */
	private function may_be_save_instances_to_meta( $delayed_instances ) {
		PostSender::save_data_to_meta( $this->post, $delayed_instances, $this->form_data );
	}

	/**
	 * Set the instance for delay.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $delay       Delay in posting.
	 * @param object $instance_id The instances ID.
	 * @return void
	 */
	public function set_instance_for_delay( $delay, $instance_id ) {

		// delay only if we are not processing another delay.
		// to avoid infinite delay in case of multiple instances.
		$delay = absint( $delay * MINUTE_IN_SECONDS );
		// strval to match the exact event.
		$args = [ (string) $this->post->ID, (string) $instance_id ];

		// clear the previous event, if set.
		wp_clear_scheduled_hook( self::$hook, $args );

		wp_schedule_single_event( time() + $delay, self::$hook, $args );

		do_action( 'wptelegram_pro_p2tg_set_instance_for_delay', $delay, $instance_id, $this->post );
	}

	/**
	 * Clear an existing scheduled event.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $instance_id The instance ID.
	 * @return void
	 */
	public function clear_instance_delay( $instance_id ) {

		// strval to match the exact event.
		$args = [ (string) $this->post->ID, (string) $instance_id ];

		// clear the previous event, if set.
		wp_clear_scheduled_hook( self::$hook, $args );
	}

	/**
	 * Clear an existing scheduled events.
	 *
	 * @since 1.0.0
	 */
	public function clear_scheduled_hooks() {

		$instance_ids = Utils::get_field_from_all_instances( 'id' );

		foreach ( $instance_ids as $instance_id ) {
			$this->clear_instance_delay( $instance_id );
		}
	}

	/**
	 * Get the delay duration.
	 *
	 * @since 1.0.0
	 *
	 * @param Options $instance_options The instance settings.
	 *
	 * @return  int
	 */
	public function get_instance_delay( $instance_options ) {
		$trigger = 'delayed_instance_' . $instance_options->get( 'id' );

		// avoid infinite loop.
		if ( $this->trigger === $trigger ) {
			return 0;
		}

		$delay = $instance_options->get( 'delay' ); // minutes.

		$delay = apply_filters( 'wptelegram_pro_p2tg_instance_delay', $delay, $this->post, $instance_options, $this->trigger );

		return abs( (float) $delay );
	}
}
