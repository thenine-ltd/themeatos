<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Dynamic Pricing Compatibility Class
 *
 * @class   YITH_WCMBS_Dynamic_Pricing_Compatibility
 * @package Yithemes
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */
class YITH_WCMBS_Dynamic_Pricing_Compatibility {

	/**
	 * Single instance of the class
	 *
	 * @since 1.0.0
	 * @var \YITH_WCMBS_Dynamic_Pricing_Compatibility
	 */
	protected static $_instance;

	/**
	 * Returns single instance of the class
	 *
	 * @return \YITH_WCMBS_Dynamic_Pricing_Compatibility
	 */
	public static function get_instance() {
		return ! is_null( self::$_instance ) ? self::$_instance : self::$_instance = new self();
	}

	/**
	 * Constructor
	 *
	 * @access public
	 */
	protected function __construct() {

		if ( defined( 'YITH_YWDPD_VERSION' ) ) {
			// Handle price rules.
			add_filter(
				'ywdpdp_price_rule_user_options',
				array(
					$this,
					'add_membership_include_option_in_price_rule',
				)
			);
			add_filter(
				'ywdpdp_price_rule_user_exclude_options',
				array(
					$this,
					'add_membership_exclude_option_in_price_rule',
				)
			);
			add_filter(
				'ywdpd_membership_plan_included_field',
				array(
					$this,
					'add_membership_include_field_in_price_rule',
				)
			);
			add_filter(
				'ywdpd_membership_plan_excluded_field',
				array(
					$this,
					'add_membership_exclude_field_in_price_rule',
				)
			);
			if ( version_compare( YITH_YWDPD_VERSION, '4.0.0', '>=' ) ) {

				add_filter( 'ywdpd_is_valid_for_user', array( $this, 'is_valid_for_member' ), 10, 2 );

				// Handle cart rules.
				add_filter(
					'ywdpd_customers_condition_is_valid',
					array(
						$this,
						'validate_customer_condition_rule',
					),
					10,
					3
				);
				add_filter(
					'ywdpd_advanced_conditions_user_include_fields',
					array(
						$this,
						'add_membership_include_field_in_cart_rule',
					)
				);
				add_filter(
					'ywdpd_advanced_conditions_user_exclude_fields',
					array(
						$this,
						'add_membership_exclude_field_in_cart_rule',
					)
				);
			} else {
				add_filter( 'yit_ywdpd_validate_user', array( $this, 'validate_price_rule_user' ), 10, 3 );

				// Handle cart rules.
				add_filter(
					'ywdpd_customers_condition_in_cart_is_valid',
					array(
						$this,
						'validate_customer_condition_rule',
					),
					10,
					3
				);
				add_filter(
					'ywdpd_cart_rules_user_include_fields',
					array(
						$this,
						'add_membership_include_field_in_cart_rule',
					)
				);
				add_filter(
					'ywdpd_cart_rules_user_exclude_fields',
					array(
						$this,
						'add_membership_exclude_field_in_cart_rule',
					)
				);
			}
		}
	}

	/**
	 * Add membership "include" field in cart rule
	 *
	 * @param array $options The cart rule options.
	 * @return array
	 */
	public function add_membership_include_field_in_cart_rule( $options ) {
		$plan_ids = yith_wcmbs_get_plans( array( 'fields' => 'ids' ) );
		$plans    = array_combine( $plan_ids, array_map( 'get_the_title', $plan_ids ) );

		$membership_fields = array(
			array(
				'id'        => 'rules_type_memberships_list',
				'name'      => __( 'Apply discount to users of this membership', 'yith-woocommerce-membership' ),
				'type'      => 'select',
				'class'     => 'wc-enhanced-select',
				'data'      => array(
					'placeholder'          => esc_attr( __( 'Search for a membership', 'yith-woocommerce-membership' ) ),
					'allow_clear'          => true,
					'ywdpd-condition-deps' => wp_json_encode(
						array(
							array(
								'id'    => 'ywdpd_condition_for',
								'value' => 'customers',
							),
							array(
								'id'    => 'ywdpd_user_discount_to',
								'value' => 'specific_user_role',
							),
						),
					),
				),
				'multiple'  => true,
				'options'   => $plans,
				'desc'      => __( 'Choose which membership plan will provide users with this discount', 'yith-woocommerce-membership' ),
				'class_row' => 'customers specific_user_role',
				'default'   => array(),

			),
		);

		return array_merge( $options, $membership_fields );
	}

	/**
	 * Add membership "exclude" field in cart rule
	 *
	 * @param array $options The cart rule options.
	 * @return mixed
	 */
	public function add_membership_exclude_field_in_cart_rule( $options ) {
		$plan_ids = yith_wcmbs_get_plans( array( 'fields' => 'ids' ) );
		$plans    = array_combine( $plan_ids, array_map( 'get_the_title', $plan_ids ) );

		$membership_fields = array(
			array(
				'id'        => 'rules_type_excluded_memberships_list',
				'name'      => __( 'Users memberships excluded', 'yith-woocommerce-membership' ),
				'type'      => 'select',
				'class'     => 'wc-enhanced-select',
				'data'      => array(
					'placeholder'          => esc_attr( __( 'Search for a membership', 'yith-woocommerce-membership' ) ),
					'allow_clear'          => true,
					'ywdpd-condition-deps' => wp_json_encode(
						array(
							array(
								'id'    => 'ywdpd_condition_for',
								'value' => 'customers',
							),
							array(
								'id'    => 'ywdpd_user_discount_to',
								'value' => 'all',
							),
							array(
								'id'    => 'ywdpd_enable_exclude_users',
								'value' => 'yes',
							),
						),
					),
				),
				'multiple'  => true,
				'options'   => $plans,
				'desc'      => __( 'Choose which memberships to exclude from this discount', 'yith-woocommerce-membership' ),
				'class_row' => 'customers all customers_list_excluded',
				'default'   => array(),

			),
		);

		return array_merge( $options, $membership_fields );
	}

	/**
	 * Add Membership in pricing rules options
	 *
	 * @param array $options The pricing rule options.
	 *
	 * @return array
	 * @see called in init.php
	 */
	public static function add_membership_in_pricing_rules_options( $options ) {
		if ( defined( 'YITH_YWDPD_PREMIUM' ) && YITH_YWDPD_PREMIUM && defined( 'YITH_YWDPD_VERSION' ) && version_compare( YITH_YWDPD_VERSION, '1.1.0', '>=' ) ) {
			$options['user_rules']['memberships_list'] = __( 'Include users with membership plans', 'yith-woocommerce-membership' );
		}

		return $options;
	}

	/**
	 * Add membership option in price rule for Dynamic 2.0
	 *
	 * @param array $options The price rule options.
	 *
	 * @return array
	 */
	public function add_membership_include_option_in_price_rule( $options ) {

		$options['specific_membership'] = __( 'Only to users with membership plans', 'yith-woocommerce-membership' );

		return $options;
	}

	/**
	 * Add membership option in price rule for Dynamic 2.0
	 *
	 * @param array $options The price rule options.
	 *
	 * @return array
	 */
	public function add_membership_exclude_option_in_price_rule( $options ) {

		$options['specific_membership'] = __( 'Specific users with the following membership plans', 'yith-woocommerce-membership' );

		return $options;
	}

	/**
	 * Add search membership plans field for Dynamic 2.0
	 *
	 * @retrun array
	 */
	public function add_membership_include_field_in_price_rule() {
		$plan_ids = yith_wcmbs_get_plans( array( 'fields' => 'ids' ) );
		$plans    = array_combine( $plan_ids, array_map( 'get_the_title', $plan_ids ) );

		return array(
			'label'    => __( 'Membership plans included', 'yith-woocommerce-membership' ),
			'type'     => 'select',
			'class'    => 'wc-enhanced-select',
			'data'     => array(
				'placeholder' => esc_attr( __( 'Search for a membership', 'yith-woocommerce-membership' ) ),
				'allow_clear' => false,
				'ywdpd-deps'  => wp_json_encode(
					array(
						array(
							'id'    => '_user_rules',
							'value' => 'specific_membership',
						),
					)
				),
			),
			'multiple' => true,
			'options'  => $plans,
			'desc'     => __( 'Choose which memberships to include with this discount', 'yith-woocommerce-membership' ),
			'default'  => array(),
		);
	}

	/**
	 * Add search membership plans field for Dynamic 2.0
	 *
	 * @retrun array
	 */
	public function add_membership_exclude_field_in_price_rule() {
		$plan_ids = yith_wcmbs_get_plans( array( 'fields' => 'ids' ) );
		$plans    = array_combine( $plan_ids, array_map( 'get_the_title', $plan_ids ) );

		return array(
			'label'    => __( 'Choose membership plans to exclude', 'yith-woocommerce-membership' ),
			'type'     => 'select',
			'class'    => 'wc-enhanced-select',
			'data'     => array(
				'placeholder' => esc_attr( __( 'Search for a membership', 'yith-woocommerce-membership' ) ),
				'allow_clear' => false,
				'ywdpd-deps'  => wp_json_encode(
					array(
						array(
							'id'    => '_user_rule_exclude',
							'value' => 'specific_membership',
						),
						array(
							'id'    => '_enable_user_rule_exclude',
							'value' => 'yes',
						),
						array(
							'id'      => '_user_rules',
							'value'   => 'customers_list',
							'compare' => '!=',
						),
					)
				),
			),
			'multiple' => true,
			'options'  => $plans,
			'desc'     => __( 'Choose memberships plans to exclude from this discount', 'yith-woocommerce-membership' ),
			'default'  => array(),
		);
	}

	/**
	 * Check if user has membership with Dynamic 2.0
	 *
	 * @param bool   $is_valid Whether the rule is valid.
	 * @param string $type     The rule type.
	 * @param array  $rule     The rule.
	 *
	 * @return  bool
	 */
	public function validate_price_rule_user( $is_valid, $type, $rule ) {
		$is_in_exclusion = false;

		$is_exclude_enabled = isset( $rule['enable_user_rule_exclude'] ) && yith_plugin_fw_is_true( $rule['enable_user_rule_exclude'] );

		if ( is_user_logged_in() ) {
			$member = YITH_WCMBS_Members()->get_member( get_current_user_id() );
			if ( $is_exclude_enabled ) {
				$ex_type = ! empty( $rule['user_rule_exclude'] ) ? $rule['user_rule_exclude'] : '';

				if ( 'specific_membership' === $ex_type ) {

					$membership_list = ! empty( $rule['user_rules_excluded_memberships_list'] ) ? $rule['user_rules_excluded_memberships_list'] : array();

					foreach ( $membership_list as $plan_id ) {

						if ( $member->has_active_plan( $plan_id, false ) ) {
							$is_in_exclusion = true;
							$is_valid        = false;
							break;
						}
					}
				}
			}

			if ( ! $is_in_exclusion ) {

				if ( 'specific_membership' === $type ) {
					$membership_list = ! empty( $rule['user_rules_memberships_list'] ) ? $rule['user_rules_memberships_list'] : array();
					foreach ( $membership_list as $plan_id ) {

						if ( $member->has_active_plan( $plan_id, false ) ) {
							$is_valid = true;
							break;
						}
					}
				}
			}
		}

		return $is_valid;
	}

	/**
	 * Check if rule is valid for membership
	 *
	 * @param bool             $is_valid Is valid or not.
	 * @param YWDPD_Price_Rule $rule     The rule.
	 *
	 * @return bool
	 */
	public function is_valid_for_member( $is_valid, $rule ) {
		if ( is_user_logged_in() ) {
			$member                 = YITH_WCMBS_Members()->get_member( get_current_user_id() );
			$is_exclude_user_active = yith_plugin_fw_is_true( $rule->get_enable_user_rule_exclude() );
			$is_in_exclusion        = false;
			if ( $is_exclude_user_active ) {
				$type = $rule->get_user_rule_exclude();
				if ( 'specific_membership' === $type ) {
					$membership_list = $rule->get_meta( 'user_rules_excluded_memberships_list' );
					foreach ( $membership_list as $plan_id ) {

						if ( $member->has_active_plan( $plan_id, false ) ) {
							$is_in_exclusion = true;
							$is_valid        = false;
							break;
						}
					}
				}
			}
			if ( ! $is_in_exclusion ) {

				if ( 'specific_membership' === $rule->get_user_rules() ) {
					$membership_list = $rule->get_meta( 'user_rules_memberships_list' );
					foreach ( $membership_list as $plan_id ) {

						if ( $member->has_active_plan( $plan_id, false ) ) {
							$is_valid = true;
							break;
						}
					}
				}
			}
		}

		return $is_valid;
	}

	/**
	 * Validate customer cart rule
	 *
	 * @param bool  $sub_rules_valid Whether the sub-rule is valid.
	 * @param array $condition       The condition.
	 * @param array $conditions      The conditions.
	 *
	 * @return bool
	 */
	public function validate_customer_condition_rule( $sub_rules_valid, $condition, $conditions ) {

		if ( is_user_logged_in() ) {
			$is_customers_excluded = yith_plugin_fw_is_true( $condition['enable_exclude_users'] );
			$member                = YITH_WCMBS_Members()->get_member( get_current_user_id() );

			if ( $is_customers_excluded ) {
				$membership_excluded = ! empty( $condition['rules_type_excluded_memberships_list'] ) ? $condition['rules_type_excluded_memberships_list'] : array();

				if ( ! empty( $membership_excluded ) ) {
					$sub_rules_valid = false;
					foreach ( $membership_excluded as $plan_id ) {
						if ( ! $member->has_active_plan( $plan_id, false ) ) {
							return true;
						}
					}
				}
			} else {

				$membership_included = ! empty( $condition['rules_type_memberships_list'] ) ? $condition['rules_type_memberships_list'] : array();
				if ( ! empty( $membership_included ) ) {
					$sub_rules_valid = false;
					foreach ( $membership_included as $plan_id ) {

						if ( $member->has_active_plan( $plan_id, false ) ) {
							return true;
						}
					}
				}
			}
		}

		return $sub_rules_valid;
	}

	/**
	 * Validate user rule
	 *
	 * @param bool   $sub_rules_valid Whether the sub-rule is valid.
	 * @param string $discount_type   The discount type.
	 * @param array  $r               The rule.
	 *
	 * @return bool
	 *
	 * @deprecated since 2.5.0
	 */
	public function validate_user_rule( $sub_rules_valid, $discount_type, $r ) {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5.0' );

		return $sub_rules_valid;
	}

	/**
	 * Add Membership fields in Pricing Rules Meta-box
	 *
	 * @param array $options The pricing rule meta-box options.
	 *
	 * @return array
	 * @since 1.3.5
	 * @deprecated Since 2.5.0
	 */
	public function add_membership_fields_in_pricing_rules_metabox( $options ) {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5.0' );

		return $options;
	}

	/**
	 * Validate User
	 *
	 * @param bool   $to_return  The return value.
	 * @param string $type       The type.
	 * @param array  $users_list The user list.
	 *
	 * @return bool
	 * @deprecated Since 2.5.0
	 */
	public function validate_user( $to_return = false, $type = '', $users_list = array() ) {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '2.5.0' );
		return $to_return;
	}
}

/**
 * Unique access to instance of YITH_WCMBS_Dynamic_Pricing_Compatibility class
 *
 * @return YITH_WCMBS_Dynamic_Pricing_Compatibility
 * @since 1.0.0
 */
function yith_wcmbs_dynamic_pricing_compatibility() {
	return YITH_WCMBS_Dynamic_Pricing_Compatibility::get_instance();
}
