<?php
/**
 * Membership Class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\Objects
 * @class   YITH_WCMBS_Membership
 */

defined( 'ABSPATH' ) || exit;  // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Membership' ) ) {
	/**
	 * Membership Class
	 *
	 * @class   YITH_WCMBS_Membership
	 * @since   1.0.0
	 */
	class YITH_WCMBS_Membership extends WC_Data {

		/**
		 * Membership ID
		 *
		 * @since 1.0.0
		 * @var int
		 */
		public $id;

		/**
		 * Membership Post Object
		 *
		 * @since      1.0.0
		 * @var WP_Post|bool
		 * @deprecated since 2.0
		 */
		public $post;

		/**
		 * Membership plan
		 *
		 * @since 1.4.0
		 * @var false|YITH_WCMBS_Plan
		 */
		private $plan;

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 * @param int   $membership_id the membership id
		 * @param array $args          array of meta for creating membership
		 *
		 */
		public function __construct( $membership_id = 0, $args = array() ) {
			$notify = true;

			// Populate the membership if $membership_id is defined.
			if ( $membership_id ) {
				$this->id = $membership_id;
				$this->populate();
			}

			// Add a new membership if $args is passed.
			if ( $membership_id == 0 && ! empty( $args ) ) {
				$this->add_membership( $args );
				// check and loads credits
				$this->check_credits( true );
				$notify = false;
			} else {
				$this->check_credits();
			}

			// Check if status is expired or in expiring.
			$this->check_is_expiring( $notify );
			$this->check_is_expired( $notify );
		}

		/**
		 * __get function.
		 *
		 * @param string $key
		 *
		 * @return mixed
		 */
		public function __get( $key ) {
			$value = get_post_meta( $this->id, '_' . $key, true );

			if ( ! empty( $value ) ) {
				$this->$key = $value;
			}

			return $value;
		}

		/**
		 * __set function.
		 *
		 * @param string $property
		 * @param mixed  $value
		 *
		 * @return bool|int
		 */
		public function set( $property, $value ) {
			$this->$property = $value;

			return update_post_meta( $this->id, '_' . $property, $value );
		}

		/**
		 * Populate the membership
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function populate() {

			$this->post = get_post( $this->id );

			foreach ( $this->get_membership_meta() as $key => $value ) {
				$this->$key = $value;
			}

			do_action( 'yith_wcmbs_membership_loaded', $this );
		}

		/**
		 * Check if the Membership is valid, controlling if this post exist
		 *
		 * @since  1.0.0
		 * @return bool
		 */
		public function is_valid() {
			return ! ! $this->post;
		}

		/**
		 * Add new membership
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function add_membership( $args ) {

			$plan_title = $args['title'] ?? '';

			$membership_id = wp_insert_post(
				array(
					'post_status' => 'publish',
					'post_type'   => 'ywcmbs-membership',
					'post_title'  => $plan_title,
				)
			);

			if ( $membership_id ) {
				$this->id = $membership_id;
				$meta     = wp_parse_args( $args, $this->get_default_meta_data() );
				$this->update_membership_meta( $meta );
				$this->populate();

				// TODO: move this before populate; do improve after refactoring this class
				$plan = $this->get_plan();
				if ( $plan->has_discount() ) {
					$this->set( 'discount_enabled', $plan->get_discount_enabled() );
					$this->set( 'discount', $plan->get_discount() );
				}

				do_action( 'yith_wcmbs_membership_created', $this );

				$this->add_activity( 'new', $this->status, __( 'Membership successfully created.', 'yith-woocommerce-membership' ) );

				$this->notify( 'new_member' );
			}

			do_action( 'yith_wcmbs_delete_transients' );
		}

		/**
		 * Update post meta in membership
		 *
		 * @since  1.0.0
		 * @param array $meta the meta
		 *
		 * @return void
		 */
		function update_membership_meta( $meta ) {
			foreach ( $meta as $key => $value ) {
				update_post_meta( $this->id, '_' . $key, $value );
			}
		}

		/**
		 * Updates status of membership
		 *
		 * @param string $new_status
		 * @param string $activity
		 * @param string $additional_note
		 * @param bool   $notify
		 */
		public function update_status( $new_status, $activity = 'change_status', $additional_note = '', $notify = true ) {
			if ( ! $this->id ) {
				return;
			}

			$old_status = $this->status;

			// cannot update status if it's expired or cancelled
			if ( in_array( $old_status, array( 'expired', 'cancelled' ) ) ) {
				return;
			}

			$allowed = apply_filters( 'yith_wcmb_update_membership_status_allowed', true, $old_status, $new_status, $activity, $additional_note, $notify );
			if ( ! $allowed ) {
				return;
			}

			if ( $new_status !== $old_status || ! in_array( $new_status, array_keys( yith_wcmbs_get_membership_statuses() ) ) ) {

				// Status was changed
				do_action( 'yith_wcmbs_membership_status_' . $new_status, $this->id, $this );
				do_action( 'yith_wcmbs_membership_status_' . $old_status . '_to_' . $new_status, $this->id, $this );
				do_action( 'yith_wcmbs_membership_status_changed', $this->id, $old_status, $new_status, $this );

				switch ( $new_status ) {
					case 'active' :
						// Update the membership status
						$this->set( 'status', $new_status );
						$note = __( 'Membership has now been activated.', 'yith-woocommerce-membership' ) . ' ' . $additional_note;
						$this->add_activity( $activity, $new_status, $note );
						break;

					case 'paused' :
						if ( ! $this->can_be_paused() ) {
							return;
						}
						// Update the membership status
						$this->set( 'status', $new_status );
						$note = __( 'Membership paused.', 'yith-woocommerce-membership' ) . ' ' . $additional_note;
						$this->add_activity( $activity, $new_status, $note );
						break;
					case 'resumed':
						if ( ! $this->can_be_resumed() ) {
							return;
						}

					$last_activity = $this->get_last_activity();
					$resumed_note  = __( 'Membership resumed.', 'yith-woocommerce-membership' );
					$paused_days   = 0;

					// Calculate and set paused days, if the latest activity is related to a "paused" status and the current status is "paused".
					if ( 'paused' === $this->status && 'change_status' === $last_activity->activity && 'paused' === $last_activity->status ) {
						$paused_days_in_sec = time() - $last_activity->timestamp;
						$paused_days        = intval( ( $paused_days_in_sec ) / ( 24 * 60 * 60 ) );
						$paused_days_tot    = $paused_days + $this->paused_days;
						$this->set( 'paused_days', $paused_days_tot );
					}

						// Update expiring date.
						if ( $paused_days && ! $this->is_unlimited() ) {
							$new_end_date = $this->end_date + ( $paused_days * DAY_IN_SECONDS );
							$this->set( 'end_date', $new_end_date );
							// translators: %s is the new expiration date.
							$resumed_note .= "\n" . sprintf( __( 'Expiration date set to %s.', 'yith-woocommerce-membership' ), date_i18n( wc_date_format(), $new_end_date ) );
						}

						// Update next credits update.
						if ( $paused_days && $this->has_credit_management() && $this->next_credits_update ) {
							$next_update = absint( $this->next_credits_update ) + ( $paused_days * DAY_IN_SECONDS );
							$this->set( 'next_credits_update', $next_update );

							// translators: %s is the new "next credits update" date.
							$resumed_note .= "\n" . sprintf( __( '"Next credits update" date set to %s.', 'yith-woocommerce-membership' ), $this->get_formatted_date( 'next_credits_update' ) );
						}

						// Update the membership status.
						$this->set( 'status', 'resumed' );

						$note = $resumed_note . ' ' . $additional_note;
						$this->add_activity( $activity, $new_status, $note );

						break;
					case 'cancelled' :
						if ( ! $this->can_be_cancelled() ) {
							return;
						}
						$this->set( 'status', $new_status );
						$cancelled_note = sprintf( __( 'Membership status updated to %s.', 'yith-woocommerce-membership' ), strtolower( strtr( $new_status, yith_wcmbs_get_membership_statuses() ) ) );
						$note           = $cancelled_note . ' ' . $additional_note;
						$this->add_activity( $activity, $new_status, $note );
						break;
					default:
						$this->set( 'status', $new_status );
						$update_status_note = sprintf( __( 'Membership status updated to %s.', 'yith-woocommerce-membership' ), strtolower( strtr( $new_status, yith_wcmbs_get_membership_statuses() ) ) );
						$note               = $update_status_note . ' ' . $additional_note;
						$this->add_activity( $activity, $new_status, $note );
						break;
				}

				if ( $notify ) {
					$this->notify( 'status_changed' );
				}
			}

			// check if status is expired or in expiring
			$this->check_is_expiring();
			$this->check_is_expired();

			do_action( 'yith_wcmbs_delete_transients' );
		}

		/**
		 * send email when status changed
		 *
		 * @param string $type type of notification. it can be: status_changed | new_member
		 */
		public function notify( $type ) {
			$notification_args = array(
				'user_id'    => $this->user_id,
				'membership' => $this,
			);

			$notify = apply_filters( 'yith_wcmbs_membership_notify', true, $this, $type );

			do_action( 'yith_wcms_before_send_notification', $this );

			if ( $notify ) {
				switch ( $type ) {
					case 'status_changed':
						$allowed_status_changed_notifier = array( 'cancelled', 'expiring', 'expired' );
						if ( in_array( $this->status, $allowed_status_changed_notifier ) ) {
							$mailer = WC()->mailer();
							do_action( 'yith_wcmbs_membership_' . $this->status . '_notification', $notification_args );
						}
						break;
					case 'new_member':
						$mailer = WC()->mailer();
						do_action( 'yith_wcmbs_new_member_notification', $notification_args );
						break;
					default:
						do_action( 'yith_wcmbs_' . $type . '_notification', $notification_args );
				}
			}
		}

		/**
		 * Fill the default metadata with the post meta stored in db
		 *
		 * @since  1.0.0
		 * @return array
		 */
		function get_membership_meta() {
			$membership_meta = array();
			foreach ( $this->get_default_meta_data() as $key => $value ) {
				$membership_meta[ $key ] = get_post_meta( $this->id, '_' . $key, true );
			}

			return $membership_meta;
		}

		/**
		 * Return an array of all custom fields membership
		 *
		 * @since  1.0.0
		 * @return array
		 */
		private function get_default_meta_data() {
			$membership_meta_data = array(
				'plan_id'             => 0,
				'title'               => '',
				'start_date'          => '',
				'end_date'            => '',
				'order_id'            => 0,
				'order_item_id'       => 0,
				'user_id'             => 0,
				'status'              => 'active',
				'paused_days'         => 0,
				'activities'          => array(),
				'credits'             => -1,
				'credits_update'      => 0,
				'next_credits_update' => 0,
				'discount_enabled'    => 'no',
				'discount'            => 0,
			);

			return $membership_meta_data;
		}

		/**
		 * check and load credits
		 *
		 * @param bool $first_activation
		 */
		public function check_credits( $first_activation = false ) {
			if ( ! function_exists( 'YITH_WCMBS_Products_Manager' ) ) {
				return;
			}

			if ( YITH_WCMBS_Products_Manager()->is_allowed_download() && $this->is_active() ) {
				$plan = $this->get_plan();

				$download_limit = $plan && $plan->has_credits() ? $plan->get_download_number() : 0;
				if ( ! $download_limit ) {
					return;
				}

				/* Get initial credits if this is the first activation */
				if ( $first_activation && $plan && $plan->is_different_download_number_first_term_enabled() ) {
					$download_limit = $plan->get_download_number_first_term();
				}

				$today       = yith_wcmbs_local_strtotime_midnight_to_utc();
				$next_update = absint( $this->next_credits_update );

				if ( $next_update <= $today ) {
					$new_credits = $download_limit;
					if ( $this->get_remaining_credits() >= 0 ) {
						if ( $plan && $plan->can_credits_be_accumulated() ) {
							$new_credits = $download_limit + $this->get_remaining_credits();
						}
					}
					$this->set( 'credits', $new_credits );
					$this->set( 'credits_update', $today );
					$this->set( 'next_credits_update', $this->get_next_update_credits_date() );
				}
			}
		}

		public function get_next_update_credits_date() {
			$plan                       = $this->get_plan();
			$download_limit_period      = $plan ? $plan->get_download_term_duration() : 0;
			$download_limit_period_unit = $plan ? $plan->get_download_term_unit() : 'days';

			if ( $this->credits_update ) {
				$next_update = strtotime( '+' . $download_limit_period . $download_limit_period_unit, $this->credits_update );
			} else {
				$next_update = yith_wcmbs_local_strtotime_midnight_to_utc();
			}

			return $next_update;
		}

		/**
		 * check if this membership has credits management
		 */
		public function has_credit_management() {
			return $this->get_remaining_credits() >= 0;
		}

		/**
		 * get the remaining credits
		 *
		 * @return int
		 */
		public function get_remaining_credits() {
			$credits = max( intval( $this->credits ), -1 );

			return apply_filters( 'yith_wcmbs_membership_get_remaining_credits', $credits, $this );
		}

		/**
		 * remove credit
		 *
		 * @param int $credits number of credits to remove
		 */
		public function remove_credit( $credits = 1 ) {
			$remaining_credits = $this->credits - $credits;
			$remaining_credits = absint( $remaining_credits );
			$this->set( 'credits', $remaining_credits );
		}

		/**
		 * Add Activity to membership
		 *
		 * @since  1.0.0
		 * @param string $status
		 * @param string $note
		 *
		 * @access public
		 * @param string $activity
		 */
		public function add_activity( $activity, $status, $note = '' ) {
			$timestamp = time();

			$act = new YITH_WCMBS_Activity( $activity, $status, $timestamp, $note );

			$activities   = $this->get_activities();
			$activities[] = $act;
			$this->set( 'activities', $activities );
		}

		/**
		 * Set the end date in base of duration
		 *
		 * @since  1.0.0
		 * @param int $duration
		 *
		 * @access public
		 */
		public function set_end_date( $duration ) {
			if ( $duration < 1 ) {
				$this->set( 'end_date', 'unlimited' );
			} else {
				$this->set( 'end_date', $duration + $this->start_date );
			}
		}

		/**
		 * Get the last timestamp date in activities
		 *
		 * @access public
		 * @since  1.0.0
		 * @return string|bool
		 */
		public function get_last_timestamp_date() {
			$last_activity = $this->get_last_activity();

			return ( $last_activity ) ? $last_activity->timestamp : false;
		}

		/**
		 * get activities
		 *
		 * @return array
		 */
		public function get_activities() {
			return is_array( $this->activities ) ? $this->activities : array();
		}

		/**
		 * Get the last activity
		 *
		 * @access public
		 * @since  1.0.0
		 * @return YITH_WCMBS_Activity
		 */
		public function get_last_activity( $args = array() ) {
			$defaults = array(
				'activity' => false,
				'status'   => false,
			);
			$args     = wp_parse_args( $args, $defaults );

			$activities = $this->get_activities();
			if ( $args['activity'] || $args['status'] ) {
				$activities = array_filter(
					$activities,
					function ( $activity ) use ( $args ) {
						/**
						 * The activity
						 *
						 * @var YITH_WCMBS_Activity $activity
						 */
						if ( $args['activity'] && $activity->activity !== $args['activity'] ) {
							return false;
						}

						if ( $args['status'] && $activity->status !== $args['status'] ) {
							return false;
						}

						return true;
					}
				);
			}

			return end( $activities );
		}

		/**
		 * Get the expiration date. False if unlimited
		 *
		 * @since  1.0.0
		 */
		public function get_expire_date() {
			return ! $this->is_unlimited() ? absint( $this->end_date ) : false;
		}

		/**
		 * Return html containing the start and expiration dates
		 *
		 * @access public
		 * @since  1.0.0
		 * @return string
		 */
		public function get_dates_html() {
			$data = __( 'Starting Date', 'yith-woocommerce-membership' ) . ':<br />' . $this->get_formatted_date( 'start_date' ) . '<br />';
			$data .= __( 'Expiration Date', 'yith-woocommerce-membership' ) . ':<br />' . $this->get_formatted_date( 'end_date' ) . '<br />';

			return $data;
		}

		/**
		 * Return html containing all info about plan
		 *
		 * @access public
		 * @since  1.0.0
		 * @return string
		 */
		public function get_plan_info_html() {
			$html = $this->get_dates_html();
			$html .= __( 'Status', 'yith-woocommerce-membership' ) . ':<br />' . $this->get_status_text() . '<br />';

			return apply_filters( 'yith_wcmbs_membership_get_plan_info_html', $html, $this );
		}

		/**
		 * Return html containing membership info span
		 *
		 * @access public
		 * @since  1.0.0
		 * @return string
		 */
		public function get_plan_info_span() {
			$p_name          = $this->get_plan_title();
			$p_info          = $this->get_plan_info_html();
			$p_edit_url      = get_edit_post_link( $this->id );
			$membership_info = "<span class='yith-wcmbs-users-membership-info {$this->status}'>{$p_name}";
			$membership_info .= "<span class='dashicons dashicons-info tips' data-tip='{$p_info}'></span>";
			if ( defined( 'YITH_WCMBS_PREMIUM' ) && YITH_WCMBS_PREMIUM ) {
				$membership_info .= "<a href='$p_edit_url' target='_blank'><span class='dashicons dashicons-edit'></span></a>";
			}
			$membership_info .= '</span>';

			return $membership_info;
		}

		/**
		 * Return string for status
		 *
		 * @access public
		 * @since  1.0.0
		 * @return string
		 */
		public function get_status_text() {
			$text = strtr( $this->status, yith_wcmbs_get_membership_statuses() );

			return $text;
		}

		/**
		 * Return string for dates
		 *
		 * @since  1.0.0
		 * @param bool   $with_time if it's true include time in date format
		 *
		 * @access public
		 * @param string $date_type the type of date
		 * @return string
		 */
		public function get_formatted_date( $date_type, $with_time = false ) {
			$format = wc_date_format();
			$format .= $with_time ? ( ' ' . wc_time_format() ) : '';

			$date = '';

			switch ( $date_type ) {
				case 'end_date':
					if ( $this->is_unlimited() ) {
						return __( 'Unlimited', 'yith-woocommerce-membership' );
					}

					$date = $this->get_expire_date();
					break;
				case 'last_update':
					$date = $this->get_last_timestamp_date();
					break;
				default:
					$date = $this->$date_type;
					break;
			}

			if ( ! is_numeric( $date ) ) {
				return '';
			}

			$date = yith_wcmbs_local_strtotime( 'now', intval( $date ) );

			return date_i18n( $format, $date );
		}

		/**
		 * Get the related plan
		 *
		 * @return false|YITH_WCMBS_Plan
		 */
		public function get_plan() {
			if ( is_null( $this->plan ) ) {
				$this->plan = yith_wcmbs_get_plan( absint( $this->plan_id ) );
			}

			return $this->plan;
		}

		/**
		 * get the linked plans ids
		 * return false if the plan don't have linked plans
		 *
		 * @access public
		 * @since  1.0.0
		 * @return array
		 */
		public function get_linked_plans() {
			$plan = $this->get_plan();

			return $plan ? $plan->get_linked_plans() : array();
		}

		/**
		 * Return true if status is active, resumed or expiring
		 *
		 * @since  1.0.0
		 * @return bool
		 * @access public
		 */
		public function is_active() {
			return in_array( $this->status, array( 'active', 'resumed', 'expiring' ) );
		}

		/**
		 * Return true if membership is unlimited
		 *
		 * @since  1.0.0
		 * @return bool
		 * @access public
		 */
		public function is_unlimited() {
			return $this->end_date == 'unlimited';
		}

		/**
		 * Check if this is in expired
		 *
		 * @since  1.0.0
		 * @param bool $notify
		 *
		 * @return void
		 * @access public
		 */
		public function check_is_expired( $notify = true ) {
			if ( in_array( $this->status, array( 'active', 'resumed', 'expiring' ) ) && ! $this->is_unlimited() ) {
				if ( $this->get_remaining_days() <= 0 ) {
					$this->update_status( 'expired', 'change_status', '', $notify );
				}
			}
		}

		/**
		 * Check if this is in expiring
		 *
		 * @since  1.0.0
		 * @param bool $notify
		 *
		 * @return void
		 * @access public
		 */
		public function check_is_expiring( $notify = true ) {
			if ( apply_filters( 'yith_wcmbs_check_is_expiring', true, $this ) && in_array( $this->status, array( 'active', 'resumed' ) ) && ! $this->is_unlimited() ) {
				if ( $this->get_remaining_days() <= apply_filters( 'yith_wcmbs_membership_max_days_number_to_send_expiring_email', 10 ) ) {
					$this->update_status( 'expiring', 'change_status', '', $notify );
				}
			}
		}

		/**
		 * Return the remaining days
		 *
		 * @since  1.0.0
		 * @return int
		 * @access public
		 */
		public function get_remaining_days() {
			if ( $this->is_unlimited() ) {
				$remaining_days = -1;
			} else {
				$remaining_days = ( yith_wcmbs_local_strtotime_midnight_to_utc( 'now', absint( $this->get_expire_date() ) ) - yith_wcmbs_local_strtotime_midnight_to_utc() ) / ( 60 * 60 * 24 );
				$remaining_days = ( $remaining_days > 0 ) ? absint( $remaining_days ) : 0;
			}

			return apply_filters( 'yith_wcmbs_membership_get_remaining_days', $remaining_days, $this );
		}

		/**
		 * return true if the membership can be cancelled
		 *
		 * @since  1.0.0
		 * @return bool
		 * @access public
		 */
		public function can_be_cancelled() {
			return ! in_array( $this->status, array( 'expired', 'cancelled' ) );
		}

		/**
		 * return true if the membership can be paused
		 *
		 * @since  1.0.0
		 * @return bool
		 * @access public
		 */
		public function can_be_paused() {
			return $this->is_active();
		}

		/**
		 * return true if the membership can be resumed
		 *
		 * @since  1.0.0
		 * @return bool
		 * @access public
		 */
		public function can_be_resumed() {
			return in_array( $this->status, array( 'not_active', 'paused' ) );
		}

		/**
		 * get the current name of plan
		 *
		 * @since  1.0.0
		 * @return string
		 * @access public
		 */
		public function get_plan_title() {
			$title = get_the_title( $this->plan_id );
			if ( empty( $title ) ) {
				$title = $this->title;
			}

			return apply_filters( 'yith_wcmbs_membership_get_plan_title', $title, $this );
		}

		/**
		 * control if thi membership has subscription plan linked
		 *
		 * @since  1.0.0
		 * @return bool
		 * @access public
		 */
		public function has_subscription() {
			$subscription_id = $this->subscription_id;

			return ! empty( $subscription_id );
		}

		/**
		 * Get products in this membership
		 * include linked plans
		 *
		 * @since  1.0.0
		 * @param array $args              {
		 *                                 Optional Arguments to retrieve products
		 *
		 * @type string $return            the type of return values. Allowed 'ids', 'posts', 'products'
		 * @type bool   $only_downloadable do you want retrieve only downloadable products?
		 *                                 }
		 * @return int[]|WC_Product[]|WP_Post[] List of products ids or product objects or post objects
		 * @access public
		 */
		public function get_products( $args = array() ) {
			$default_args = array(
				'return'            => 'ids',
				'only_downloadable' => YITH_WCMBS_Products_Manager()->is_allowed_download(),
			);

			$args              = wp_parse_args( $args, $default_args );
			$return            = 'ids';
			$only_downloadable = false;
			extract( $args );

			$plan_ids   = $this->get_linked_plans();
			$plan_ids[] = $this->plan_id;

			$products = array();
			// get products in plan
			foreach ( $plan_ids as $plan_id ) {
				$args = array(
					'post_type'                  => 'product',
					'posts_per_page'             => -1,
					'post_status'                => 'publish',
					'yith_wcmbs_suppress_filter' => true,
					'meta_query'                 => array(
						array(
							'key'     => '_yith_wcmbs_restrict_access_plan',
							'value'   => serialize( (string) $plan_id ),
							'compare' => 'LIKE',
						),
					),
				);

				$products = array_unique( array_merge( $products, get_posts( $args ) ), SORT_REGULAR );
			}

			foreach ( $plan_ids as $plan_id ) {
				$plan = yith_wcmbs_get_plan( $plan_id );
				if ( ! $plan ) {
					continue;
				}
				$plan_cats      = $plan->get_product_categories();
				$plan_prod_tags = $plan->get_product_tags();

				$cat_tag_args = array(
					'post_type'                  => 'product',
					'posts_per_page'             => -1,
					'post_status'                => 'publish',
					'yith_wcmbs_suppress_filter' => true,
					'tax_query'                  => array(
						'relation' => 'OR',
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => $plan_cats,
							'operator' => 'IN',
						),
						array(
							'taxonomy' => 'product_tag',
							'field'    => 'term_id',
							'terms'    => $plan_prod_tags,
							'operator' => 'IN',
						),
					),
				);
				$products     = array_unique( array_merge( $products, get_posts( $cat_tag_args ) ), SORT_REGULAR );
			}

			$r = array();
			if ( ! empty( $products ) ) {
				foreach ( $products as $product_post ) {
					$product = wc_get_product( $product_post->ID );

					$delay = get_post_meta( $product_post->ID, '_yith_wcmbs_plan_delay', true );
					$delay = ! $delay ? array() : $delay;

					$plans_delay_intersect = array_intersect( $plan_ids, array_keys( $delay ) );

					if ( ! empty( $delay ) && ! empty( $plans_delay_intersect ) ) {

						// get the minimum delay [between linked plans]
						$delay_for_plans = 0;
						if ( isset( $delay[ $this->plan_id ] ) ) {
							$delay_for_plans = $delay[ $this->plan_id ];
						} else {
							$first = true;
							foreach ( $plan_ids as $plan_id ) {
								if ( $first ) {
									if ( isset( $delay[ $plan_id ] ) ) {
										$delay_for_plans = $delay[ $plan_id ];
										$first           = false;
									}
								} else {
									if ( isset( $delay[ $plan_id ] ) && $delay_for_plans > $delay[ $plan_id ] ) {
										$delay_for_plans = $delay[ $plan_id ];
									}
								}
							}
						}

						if ( $delay_for_plans > 0 ) {
							$delay_days = $delay_for_plans;
							$date       = $this->start_date + ( $this->paused_days * 60 * 60 * 24 );

							$passed_days = intval( ( time() - $date ) / ( 24 * 60 * 60 ) );
							if ( $passed_days <= $delay_days ) {
								continue;
							}
						}
					}

					if ( $product ) {
						// Add downloadable products only.
						if ( ! $only_downloadable || yith_wcmbs_is_downloadable_product( $product ) ) {
							switch ( $return ) {
								case 'ids':
									$r[] = $product_post->ID;
									break;
								case 'products':
									$r[] = $product;
									break;
								case 'posts':
									$r[] = $product_post;
									break;
							}
						}
					}
				}
			}

			return $r;
		}

		/**
		 * Get the discount enabled value
		 *
		 * @since 1.4.0
		 * @param string $context The context.
		 *
		 * @return string
		 */
		public function get_discount_enabled( $context = 'view' ) {
			$enabled = $this->discount_enabled;

			if ( 'view' === $context ) {
				if ( 'plan' === yith_wcmbs_settings()->get_option( 'yith-wcmbs-retrieve-membership-discount-settings' ) ) {
					$plan = $this->get_plan();
					if ( $plan ) {
						$enabled = $plan->get_discount_enabled();
					}
				}
			}

			return 'yes' === $enabled ? 'yes' : 'no';
		}

		/**
		 * Get the discount enabled value
		 *
		 * @since 1.4.0
		 * @param string $context The context.
		 *
		 * @return int
		 */
		public function get_discount( $context = 'view' ) {
			$discount = $this->discount;

			if ( 'view' === $context ) {
				if ( 'plan' === yith_wcmbs_settings()->get_option( 'yith-wcmbs-retrieve-membership-discount-settings' ) ) {
					$plan = $this->get_plan();
					if ( $plan ) {
						$discount = $plan->get_discount();
					}
				}
			}

			return min( absint( $discount ), 100 );
		}

		/**
		 * This membership has a discount?
		 *
		 * @since 1.4.0
		 * @return bool
		 */
		public function has_discount() {
			return 'yes' === $this->get_discount_enabled() && $this->get_discount();
		}

		/**
		 * Get the HTML to be shown for the discount
		 *
		 * @since 1.4.0
		 * @return string
		 */
		public function get_discount_html() {
			$format = '%s%%';

			return $this->has_discount() ? sprintf( $format, $this->get_discount() ) : '';
		}

		/**
		 * Get the ID
		 *
		 * @since 1.4.0
		 * @return int
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Get the User ID
		 *
		 * @since 1.4.0
		 * @return int
		 */
		public function get_user_id() {
			return absint( $this->user_id );
		}

		/**
		 * Has access to specific item without delay?
		 * Important: this will check for delay only, so use it only after checking if the user can access to the item
		 *
		 * @param $item_id
		 *
		 * @return bool
		 */
		public function has_access_without_delay( $item_id ) {
			if ( yith_wcmbs_has_full_access( $this->get_user_id() ) ) {
				return true;
			}
			$delay      = get_post_meta( $item_id, '_yith_wcmbs_plan_delay', true );
			$start_date = $this->start_date + ( $this->paused_days * DAY_IN_SECONDS );
			$plan_id    = $this->plan_id;
			if ( ! ! $delay ) {
				if ( ! isset( $delay[ $plan_id ] ) ) {
					$linked         = $this->get_linked_plans();
					$min_delay_time = 0;
					$first          = true;
					foreach ( $linked as $plan_id ) {
						if ( isset( $delay[ $plan_id ] ) ) {
							if ( $first ) {
								$min_delay_time = $delay[ $plan_id ];
								$first          = false;
							} else {
								if ( $delay[ $plan_id ] < $min_delay_time ) {
									$min_delay_time = $delay[ $plan_id ];
								}
							}
						}
					}

					if ( $min_delay_time > 0 ) {
						if ( yith_wcmbs_local_strtotime_midnight_to_utc( '+' . $min_delay_time . ' days', $start_date ) <= yith_wcmbs_local_strtotime_midnight_to_utc() ) {
							return true;
						}
					} else {
						return true;
					}
				} else if ( $delay[ $plan_id ] < 1 || yith_wcmbs_local_strtotime_midnight_to_utc( '+' . $delay[ $plan_id ] . ' days', $start_date ) <= yith_wcmbs_local_strtotime_midnight_to_utc() ) {
					return true;
				}

				return false;
			} else {
				return true;
			}
		}
	}
}
