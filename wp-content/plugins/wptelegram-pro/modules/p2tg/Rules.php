<?php
/**
 * Handle the rules for P2TG Instance
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\p2tg
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\includes\Options;
use WPTelegram\Pro\includes\Utils as MainUtils;
use WP_Post;

/**
 * Class responsible for handling the rules for P2TG Instance
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\p2tg
 * @author     WP Socio
 */
class Rules {

	/**
	 * The post being processed.
	 *
	 * @var @param WP_Post $post The post being processed.
	 */
	public $post;

	/**
	 * The options of the instance being used.
	 *
	 * @var Options $instance_options The options object.
	 */
	public $instance_options;

	/**
	 * The template parser instance
	 *
	 * @var TemplateParser $template_parser The template parser.
	 */
	public $template_parser;

	/**
	 * The data from post edit page.
	 *
	 * @var Options $form_data The options object.
	 */
	public $form_data;

	/**
	 * The post data for rule params.
	 *
	 * @var array $post_data The data.
	 */
	public $post_data;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post      The post being processed.
	 * @param Options $form_data The data from post edit page.
	 */
	public function __construct( $post, $form_data ) {
		$this->post      = $post;
		$this->form_data = $form_data;
		// Set the parser.
		$this->template_parser = new TemplateParser( $this->post );
	}

	/**
	 * Get the P2TG rule types.
	 *
	 * @since    1.4.0
	 */
	public static function get_rule_types() {

		$rule_types = [
			[
				'label'   => __( 'Post', 'wptelegram-pro' ),
				'options' => [
					[
						'value' => 'post',
						'label' => __( 'Post', 'wptelegram-pro' ),
					],
					[
						'value' => 'category',
						'label' => __( 'Post Category', 'wptelegram-pro' ),
					],
					[
						'value' => 'post_tag',
						'label' => __( 'Post Tag', 'wptelegram-pro' ),
					],
					[
						'value' => 'post_format',
						'label' => __( 'Post Format', 'wptelegram-pro' ),
					],
					[
						'value' => 'post_author',
						'label' => __( 'Post Author', 'wptelegram-pro' ),
					],
				],
			],
			[
				'label'   => __( 'Custom Taxonomy', 'wptelegram-pro' ),
				'options' => self::get_taxonomy_rule_types(),
			],
			[
				'label'   => __( 'Custom', 'wptelegram-pro' ),
				'options' => [
					[
						'value' => 'custom',
						'label' => __( 'Custom Rule', 'wptelegram-pro' ),
					],
				],
			],
		];

		// Allow custom rule_types.
		return (array) apply_filters( 'wptelegram_pro_p2tg_rule_types', $rule_types );
	}

	/**
	 * Get the taxonomy for rule types
	 *
	 * @since    1.0.0
	 */
	public static function get_taxonomy_rule_types() {

		$to_skip = [
			'product_shipping_class',
		];

		$rule_types = [];

		$args = [
			'public'   => true,
			'_builtin' => false,
		];

		$taxonomies = get_taxonomies( $args, 'objects' );

		foreach ( $taxonomies as $taxonomy ) {

			if ( in_array( $taxonomy->name, $to_skip, true ) ) {
				continue;
			}

			$rule_types[] = [
				// Use a prefix for identification.
				'value' => 'tax:' . $taxonomy->name,
				'label' => "{$taxonomy->labels->singular_name} ({$taxonomy->name})",
			];
		}

		return apply_filters( 'wptelegram_pro_p2tg_taxonomy_rule_types', $rule_types );
	}

	/**
	 * Check if the instance rules apply to the post
	 *
	 * @since   1.0.0
	 *
	 * @param Options $instance_options The options object.
	 * @return  bool
	 */
	public function instance_rules_apply( $instance_options ) {
		// Reset the options on every call.
		$this->template_parser->set_options( $this->instance_options );

		$this->instance_options = $instance_options;

		// Check if the instance rules apply on the post.
		$rules_apply = $this->all_instance_rules_apply();

		return (bool) apply_filters( 'wptelegram_pro_p2tg_instance_rules_apply', $rules_apply, $this->instance_options, $this->post );
	}

	/**
	 * Check if the instance rules apply to the post.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool
	 */
	private function all_instance_rules_apply() {

		$bypass_date_rules = ( 'yes' === $this->form_data['force_send'] );

		$date_rules_apply = $this->date_rules_apply( $bypass_date_rules );

		$date_rules_apply = (bool) apply_filters( 'wptelegram_pro_p2tg_post_date_rules_apply', $date_rules_apply, $this->instance_options, $this->post );

		if ( ! $date_rules_apply ) {
			return false;
		}

		$post_type_rules_apply = $this->post_type_rules_apply();

		$post_type_rules_apply = (bool) apply_filters( 'wptelegram_pro_p2tg_post_type_rules_apply', $post_type_rules_apply, $this->instance_options, $this->post );

		if ( ! $post_type_rules_apply ) {
			return false;
		}

		$dynamic_rules_apply = $this->dynamic_rules_apply();

		return (bool) apply_filters( 'wptelegram_pro_p2tg_dynamic_rules_apply', $dynamic_rules_apply, $this->instance_options, $this->post );
	}

	/**
	 * Check if the instance date rules apply to the post.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $bypass_date_rules Whether to bypass date rules.
	 *
	 * @return bool
	 */
	public function date_rules_apply( $bypass_date_rules = false ) {

		$bypass_date_rules = (bool) apply_filters( 'wptelegram_pro_p2tg_bypass_post_date_rules', $bypass_date_rules, $this->instance_options, $this->post );

		if ( $bypass_date_rules ) {
			return true;
		}

		$use_when = $this->instance_options->get( 'use_when', [] );

		$is_new = Utils::is_post_new( $this->post, $this->instance_options->get( 'id' ) );

		$send_new = in_array( 'new', $use_when, true );
		$send_new = (bool) apply_filters( 'wptelegram_pro_p2tg_rules_send_new_post', $send_new, $this->instance_options, $this->post );

		// If sending new posts is disabled and is new post.
		if ( $is_new && ! $send_new ) {
			return false;
		}

		$send_existing = in_array( 'existing', $use_when, true );
		$send_existing = (bool) apply_filters( 'wptelegram_pro_p2tg_rules_send_existing_post', $send_existing, $this->instance_options, $this->post );

		// If sending existing posts is disabled and is existing post.
		if ( ! $is_new && ! $send_existing ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if the instance date rules apply to the post.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function post_type_rules_apply() {
		// Check for Post type.
		// Although all instances satisfy this rule by default.
		$post_types = $this->instance_options->get( 'post_types', [] );

		$send_post_type = in_array( $this->post->post_type, $post_types, true );

		$send_post_type = (bool) apply_filters( 'wptelegram_pro_p2tg_rules_send_post_type', $send_post_type, $this->instance_options, $this->post );

		return (bool) apply_filters( 'wptelegram_pro_p2tg_rules_send_' . $this->post->post_type, $send_post_type, $this->instance_options, $this->post );
	}

	/**
	 * Check if the dynamic instance rules apply to the post.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function dynamic_rules_apply() {

		$bypass_custom_rules = (bool) apply_filters( 'wptelegram_pro_p2tg_bypass_dynamic_rules', false, $this->instance_options, $this->post );

		if ( $bypass_custom_rules ) {
			return true;
		}

		$rules = $this->instance_options->get( 'rules', [] );

		// if no rules are set.
		if ( empty( $rules ) ) {
			return true;
		}

		// default false, until we find a condition that makes it true.
		$rules_apply = false;

		foreach ( (array) $rules as $rule_group ) {

			$group_matches = true;

			foreach ( (array) $rule_group as $rule ) {

				if ( ! $this->dynamic_rule_matches( $rule ) ) {

					$group_matches = false;

					// no need to check other rules in the same group.
					break;
				}
			}

			if ( $group_matches ) {

				$rules_apply = true;
			}
		}
		return $rules_apply;
	}

	/**
	 * Check if a particular rule applies to the post
	 *
	 * @since   1.0.0
	 *
	 * @param array $rule A single rule.
	 *
	 * @return bool
	 */
	public function dynamic_rule_matches( $rule ) {
		$is_custom_param = 'custom' === $rule['param'];

		/**
		 * Extract values from array of ['value'=> '', 'label'=>''].
		 */
		$values = ! empty( $rule['values'] ) ? wp_list_pluck( $rule['values'], 'value' ) : [];

		$param = $is_custom_param ? $rule['custom_param'] : $rule['param'];

		$post_param_value = implode( ',', $this->get_post_data_for_param( $param, $values, $is_custom_param ) );

		$rule_param_value = implode( ',', $values );

		if ( $is_custom_param ) {
			// Lets support dynamic templates for custom rules.
			$rule_param_value = $this->template_parser->parse( $rule_param_value );
		}

		$rule_matches = MainUtils::compare_values( $post_param_value, $rule_param_value, $rule['operator'] );

		return (bool) apply_filters( 'wptelegram_pro_p2tg_dynamic_rule_matches', $rule_matches, $rule, $this->instance_options, $this->post );
	}

	/**
	 * Check if a particular rule applies to the post.
	 *
	 * @since   1.0.0
	 *
	 * @param string $param           The param to get the data for.
	 * @param array  $rule_values     The rule values.
	 * @param bool   $is_custom_param Whether the param is a custom param.
	 *
	 * @return array
	 */
	public function get_post_data_for_param( $param, $rule_values, $is_custom_param ) {
		// If we already have a value for the param.
		if ( isset( $this->post_data[ $param ] ) ) {
			return $this->post_data[ $param ];
		}

		$post = $this->post;

		$data = [];

		switch ( $param ) {

			case 'post':
				$data = $post->ID;
				break;

			case 'post_format':
				$post_format = get_post_format( $post->ID );

				$data = $post_format ? $post_format : 'standard';
				break;

			case 'post_author':
				$data = $post->post_author;
				break;

			default:
				// If it's a taxonomy.
				if ( preg_match( '/^(?:tax:|category$|post_tag$)/i', $param ) ) {

					$taxonomy = preg_replace( '/^tax:/i', '', $param );

					$terms = get_the_terms( $post->ID, $taxonomy );

					// make sure that it's not a non-existent taxonomy.
					if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {

						$data = wp_list_pluck( array_filter( $terms ), 'term_id' );

						// Add children of each taxonomy to values.
						$include_child = (bool) apply_filters( 'wptelegram_pro_p2tg_rules_include_child_terms', true, $param, $data, $this->instance_options, $this->post );

						if ( ! empty( $data ) && $include_child && is_taxonomy_hierarchical( $taxonomy ) ) {
							// It's possible that a parent category is selected in rules.
							// We want to make sure that the rules apply if its child term is set for the post.
							foreach ( $rule_values as $rule_term_id ) {
								foreach ( $data as $post_term_id ) {
									// If the term selected in rules is a parent of any of the post terms.
									if ( term_is_ancestor_of( $rule_term_id, $post_term_id, $taxonomy ) ) {
										// Add parent term to the post data.
										$data[] = $rule_term_id;
									}
								}
							}
							$data = array_unique( $data );
						}
					}
				} elseif ( $is_custom_param ) {

					$text = $this->template_parser->parse( $param );

					$data = [ $text ];
				}
				break;
		}

		$data = (array) apply_filters( 'wptelegram_pro_p2tg_rules_post_data_for_param', $data, $param, $this->instance_options, $this->post );

		// Ensure that we have string values for strict comparison.
		$this->post_data[ $param ] = array_map( 'strval', $data );

		return $this->post_data[ $param ];
	}
}
