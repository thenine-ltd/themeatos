<?php
/**
 * Do the necessary db upgrade
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes;

use WPTelegram\Pro\modules\p2tg\Main as P2TGMain;
use WPTelegram\Pro\modules\p2tg\Utils as P2TGUtils;
use WPTelegram\Pro\modules\p2tg\restApi\SettingsController as P2TGSettingsController;

/**
 * Do the necessary db upgrade.
 *
 * Do the nececessary the incremental upgrade.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 * @author     WP Socio
 */
class Upgrade extends BaseClass {

	/**
	 * Do the necessary db upgrade, if needed
	 *
	 * @since    1.0.0
	 */
	public function do_upgrade() {

		$current_version = get_option( 'wptelegram_pro_ver', '0.0.1' );

		if ( ! version_compare( $current_version, $this->plugin()->version(), '<' ) ) {
			return;
		}

		if ( ! defined( 'WPTELEGRAM_PRO_DOING_UPGRADE' ) ) {
			define( 'WPTELEGRAM_PRO_DOING_UPGRADE', true );
		}

		do_action( 'wptelegram_pro_before_do_upgrade', $current_version );

		$is_new_install = ! get_option( 'wptelegram_pro' );

		$version_upgrades = [];
		if ( ! $is_new_install ) {
			// the sequential upgrades
			// subsequent upgrade depends upon the previous one.
			$version_upgrades = [
				'1.1.0', // first upgrade.
				'1.3.0',
				'1.4.0',
				'1.4.3',
				'1.4.4',
				'1.5.2',
				'2.0.0',
			];
		}

		// always.
		if ( ! in_array( $this->plugin()->version(), $version_upgrades, true ) ) {
			$version_upgrades[] = $this->plugin()->version();
		}

		foreach ( $version_upgrades as $target_version ) {

			if ( version_compare( $current_version, $target_version, '<' ) ) {

				$this->upgrade_to( $target_version, $is_new_install );

				$current_version = $target_version;
			}
		}

		do_action( 'wptelegram_pro_after_do_upgrade', $current_version );
	}

	/**
	 * Upgrade to a specific version
	 *
	 * @since 1.0.0
	 *
	 * @param string  $version        The plugin version to upgrade to.
	 * @param boolean $is_new_install Whether it's a fresh install of the plugin.
	 */
	private function upgrade_to( $version, $is_new_install ) {

		// "x.y.z" becomes "x_y_z".
		$_version = str_replace( '.', '_', $version );

		$method = [ $this, "upgrade_to_{$_version}" ];

		// No upgrades for fresh installations.
		if ( ! $is_new_install && is_callable( $method ) ) {

			call_user_func( $method );
		}

		update_option( 'wptelegram_pro_ver', $version );
	}

	/**
	 * Update Parse Mode from Markdown to MarkdownV2
	 *
	 * @since 1.1.0
	 */
	private function upgrade_to_1_1_0() {

		$data = $this->plugin()->options()->get_data();

		if ( ! empty( $data['notify']['parse_mode'] ) && 'Markdown' === $data['notify']['parse_mode'] ) {
			$data['notify']['parse_mode'] = 'MarkdownV2';
			// update the main plugin data.
			$this->plugin()->options()->set_data( $data )->update_data();
		}

		$prefix = P2TGMain::PREFIX;

		// default args.
		$query_args = [
			'post_type'   => P2TGMain::CPT_NAME,
			'fields'      => 'ids', // we only need IDs.
			'numberposts' => -1,
		];

		// get instance posts.
		$instance_ids = get_posts( $query_args );

		foreach ( $instance_ids as $instance_id ) {
			$parse_mode = get_post_meta( $instance_id, $prefix . 'parse_mode', true );
			if ( 'Markdown' === $parse_mode ) {
				update_post_meta( $instance_id, $prefix . 'parse_mode', 'MarkdownV2' );
			}
		}
	}

	/**
	 * Update module and instance bot from token to username.
	 *
	 * @since 1.3.0
	 */
	private function upgrade_to_1_3_0() {

		$data = $this->plugin()->options()->get_data();

		// bail early if no bots.
		if ( empty( $data['bots']['collection'] ) ) {
			return;
		}

		$bots_collection = $data['bots']['collection'];

		$bots = [
			// token => username.
		];

		// create a flat associative array of bots.
		foreach ( $bots_collection as $bot ) {
			$bots[ $bot['bot_token'] ] = $bot['bot_username'];
		}

		// update selected for for modules.
		foreach ( [ 'p2tg', 'notify' ] as $module ) {
			if ( ! empty( $data[ $module ]['bot'] ) ) {
				$bot_token              = $data[ $module ]['bot'];
				$bot_username           = $bots[ $bot_token ];
				$data[ $module ]['bot'] = $bot_username;
			}
		}

		// update the main plugin data.
		$this->plugin()->options()->set_data( $data )->update_data();

		$prefix = P2TGMain::PREFIX;

		// default args.
		$query_args = [
			'post_type'   => P2TGMain::CPT_NAME,
			'fields'      => 'ids', // we only need IDs.
			'numberposts' => -1,
		];

		// get instance posts.
		$instance_ids = get_posts( $query_args );

		foreach ( $instance_ids as $instance_id ) {
			$bot_token = get_post_meta( $instance_id, $prefix . 'bot', true );
			if ( ! empty( $bot_token ) ) {
				$bot_username = $bots[ $bot_token ];
				update_post_meta( $instance_id, $prefix . 'bot', $bot_username );
			}
		}
	}

	/**
	 * Update field arrays and string rule values for p2tg.
	 *
	 * @since 1.4.0
	 */
	private function upgrade_to_1_4_0() {

		$data = $this->plugin()->options()->get_data();

		if ( ! empty( $data['notify']['catch_emails'] ) ) {
			$catch_emails = $data['notify']['catch_emails'];
			// Convert chat_ids from comma separated string to array.
			foreach ( $catch_emails as $key => &$catch_email ) {
				if ( ! empty( $catch_email['chat_ids'] ) ) {
					$catch_email['chat_ids'] = array_filter(
						array_map( 'trim', explode( ',', $catch_email['chat_ids'] ) )
					);
				} else {
					unset( $catch_emails[ $key ] );
				}
			}
			// Reset keys.
			$data['notify']['catch_emails'] = array_values( $catch_emails );
			// Destroy reference.
			unset( $catch_email );
		}

		if ( isset( $data['proxy']['proxy_port'] ) ) {
			// Convert port to string.
			$data['proxy']['proxy_port'] = strval( $data['proxy']['proxy_port'] );
		}

		if ( empty( $data['proxy']['proxy_method'] ) ) {
			// Set default proxy method.
			$data['proxy']['proxy_method'] = 'cf_worker';
		}

		if ( empty( $data['proxy']['proxy_method'] ) ) {
			// Set default proxy type.
			$data['proxy']['proxy_type'] = 'CURLPROXY_HTTP';
		}

		// update the main plugin data.
		$this->plugin()->options()->set_data( $data )->update_data();

		// Now lets upgrade the rules.
		$prefix = P2TGMain::PREFIX;

		// default args.
		$query_args = [
			'post_type'   => P2TGMain::CPT_NAME,
			'fields'      => 'ids', // we only need IDs.
			'numberposts' => -1,
		];

		// get instance posts.
		$instance_ids = get_posts( $query_args );

		foreach ( $instance_ids as $instance_id ) {
			// Lets upgrade the rules.
			$rules   = get_post_meta( $instance_id, $prefix . 'rules', true );
			$updated = false;
			if ( ! empty( $rules ) && is_array( $rules ) ) {
				// iterate over each group.
				foreach ( $rules as &$rule_group ) {
					if ( ! empty( $rule_group ) && is_array( $rule_group ) ) {
						// iterate over each rule.
						foreach ( $rule_group as &$rule ) {
							if ( ! empty( $rule['values'] ) && is_array( $rule['values'] ) ) {
								// iterate over each rule value.
								foreach ( $rule['values'] as &$rule_value ) {
									if ( ! empty( $rule_value['value'] ) ) {
										// Convert rule value to string.
										$rule_value['value'] = strval( $rule_value['value'] );

										$updated = true;
									}
								}
							}
						}
					}
				}
			}
			if ( $updated ) {
				update_post_meta( $instance_id, $prefix . 'rules', $rules );
			}

			// Lets upgrade the Miscellaneous options.
			$misc = (array) get_post_meta( $instance_id, $prefix . 'misc', true );

			foreach ( [ 'disable_web_page_preview', 'disable_notification' ] as $misc_opt ) {
				$value = in_array( $misc_opt, $misc, true );
				update_post_meta( $instance_id, $prefix . $misc_opt, $value );
			}
			delete_post_meta( $instance_id, $prefix . 'misc' );
		}
	}

	/**
	 * Fix additional media and inline keyboard bug.
	 *
	 * @since 1.4.3
	 */
	private function upgrade_to_1_4_3() {

		// Now lets upgrade the rules.
		$prefix = P2TGMain::PREFIX;

		// default args.
		$query_args = [
			'post_type'   => P2TGMain::CPT_NAME,
			'fields'      => 'ids', // we only need IDs.
			'numberposts' => -1,
		];

		// get instance posts.
		$instance_ids = get_posts( $query_args );

		foreach ( $instance_ids as $instance_id ) {
			foreach ( [ 'additional_media', 'inline_keyboard' ] as $json_array_field ) {
				$value = get_post_meta( $instance_id, $prefix . $json_array_field, true );
				if ( empty( $value ) ) {
					$value = [];
				}
				if ( is_array( $value ) ) {
					$value = wp_json_encode( $value );
				}
				update_post_meta( $instance_id, $prefix . $json_array_field, addslashes( $value ) );
			}

			// Lets clean up the rules.
			$rules = get_post_meta( $instance_id, $prefix . 'rules', true );
			if ( ! empty( $rules ) && is_array( $rules ) ) {
				$rules = P2TGSettingsController::clean_up_rules( $rules );
			} else {
				$rules = [];
			}
			update_post_meta( $instance_id, $prefix . 'rules', $rules );
		}
	}

	/**
	 * Fix share button URL
	 *
	 * @since 1.4.4
	 */
	private function upgrade_to_1_4_4() {
		$data = $this->plugin()->options()->get_data();

		$pattern = '/(?:\{encode:)?(\{post_(?:title|url)\})\}?/';

		if ( ! empty( $data['p2tg']['buttons']['url'] ) ) {
			foreach ( $data['p2tg']['buttons']['url'] as &$url_button ) {
				// if it's the share button.
				if ( false !== strpos( $url_button['url'], 'https://t.me/share' ) ) {
					$url_button['url'] = preg_replace( $pattern, '{encode:${1}}', $url_button['url'] );
				}
			}
		}

		// update the main plugin data.
		$this->plugin()->options()->set_data( $data )->update_data();

		// Now lets upgrade the instances.
		$prefix = P2TGMain::PREFIX;

		// default args.
		$query_args = [
			'post_type'   => P2TGMain::CPT_NAME,
			'fields'      => 'ids', // we only need IDs.
			'numberposts' => -1,
		];

		// get instance posts.
		$instance_ids = get_posts( $query_args );

		foreach ( $instance_ids as $instance_id ) {
			$inline_keyboard = get_post_meta( $instance_id, $prefix . 'inline_keyboard', true );
			$inline_keyboard = ! empty( $inline_keyboard ) ? json_decode( $inline_keyboard, true ) : [];
			if ( ! empty( $inline_keyboard ) ) {
				$updated = false;
				foreach ( $inline_keyboard as &$button_row ) {
					foreach ( $button_row as &$button ) {
						if ( ! empty( $button['url'] ) && false !== strpos( $button['url'], 'https://t.me/share' ) ) {
							$button['url'] = preg_replace( $pattern, '{encode:${1}}', $button['url'] );
							$updated       = true;
						}
					}
				}

				if ( $updated ) {
					$inline_keyboard = addslashes( wp_json_encode( $inline_keyboard ) );
					update_post_meta( $instance_id, $prefix . 'inline_keyboard', $inline_keyboard );
				}
			}
		}
	}

	/**
	 * Upgrade inline keyboard buttons.
	 *
	 * @since 1.5.2
	 */
	private function upgrade_to_1_5_2() {

		$data = $this->plugin()->options()->get_data();

		$url_buttons = ! empty( $data['p2tg']['buttons']['url'] ) ? $data['p2tg']['buttons']['url'] : [];
		$rxn_buttons = ! empty( $data['p2tg']['buttons']['reaction'] ) ? $data['p2tg']['buttons']['reaction'] : [];
		$buttons     = array_values( array_merge( $rxn_buttons, $url_buttons ) );

		$get_button_hash = function( $button ) {
			// use 'label', 'value' and 'url' to generate md5 hash.
			$md5_str = sprintf(
				'%1$s %2$s %3$s',
				! empty( $button['label'] ) ? $button['label'] : '',
				! empty( $button['value'] ) ? $button['value'] : '',
				! empty( $button['url'] ) ? $button['url'] : ''
			);

			return md5( $md5_str );
		};

		$buttons_hash_ids = [];

		// add an 'id' to each button and create a map with key as button hash.
		foreach ( $buttons as &$button ) {

			$button['id'] = Utils::uuid();

			$hash = $get_button_hash( $button );

			$buttons_hash_ids[ $hash ] = $button['id'];
		}
		// remove reference.
		unset( $button );

		// Now lets upgrade the instances.
		$prefix = P2TGMain::PREFIX;

		// default args.
		$query_args = [
			'post_type'   => P2TGMain::CPT_NAME,
			'fields'      => 'ids', // we only need IDs.
			'numberposts' => -1,
		];

		// get instance posts.
		$instance_ids = get_posts( $query_args );

		foreach ( $instance_ids as $instance_id ) {
			$inline_keyboard = get_post_meta( $instance_id, $prefix . 'inline_keyboard', true );
			$inline_keyboard = ! empty( $inline_keyboard ) ? json_decode( $inline_keyboard, true ) : [];
			if ( ! empty( $inline_keyboard ) ) {
				foreach ( $inline_keyboard as &$button_row ) {
					foreach ( $button_row as &$button ) {
						$hash = $get_button_hash( $button );
						// if the same button is not present in the list.
						if ( ! isset( $buttons_hash_ids[ $hash ] ) ) {
							$button['id'] = Utils::uuid();

							// add the button to hash map.
							$buttons_hash_ids[ $hash ] = $button['id'];
							// add the button to the list.
							$buttons[] = $button;
						}
						$button = $buttons_hash_ids[ $hash ];
					}
				}

				$inline_keyboard = addslashes( wp_json_encode( $inline_keyboard ) );
				update_post_meta( $instance_id, $prefix . 'inline_keyboard', $inline_keyboard );
			}
		}

		unset( $button, $button_row );

		$data['p2tg']['buttons'] = $buttons;

		// update the main plugin data.
		$this->plugin()->options()->set_data( $data )->update_data();

		/**
		 * Now let us update the posts affected by new inline keyboard.
		 */
		// The posts that are scheduled, i.e. they have instances saved.
		$query_args = [
			'post_type'   => 'any',
			'post_status' => 'any',
			'fields'      => 'ids', // we only need IDs.
			'numberposts' => -1,
			'meta_query'  => [ //phpcs:ignore
				'relation' => 'AND',
				[
					'key'     => P2TGMain::PREFIX . 'instances',
					'compare' => 'EXISTS',
				],
				[
					'key'     => P2TGMain::PREFIX . 'instances',
					'value'   => '',
					'compare' => '!=',
				],
			],
		];

		// Get all the posts.
		$post_ids = get_posts( $query_args );

		foreach ( $post_ids as $post_id ) {
			$saved_instances = get_post_meta( $post_id, P2TGMain::PREFIX . 'instances', true );

			if ( ! empty( $saved_instances ) ) {
				// flag to avoid unnecessary updates.
				$updated = false;
				// decode the values.
				$saved_instances = array_map( [ P2TGUtils::class, 'decode_instance_values' ], $saved_instances );
				// loop though all the instances.
				foreach ( $saved_instances as $instance_id => &$instance ) {
					if ( ! empty( $instance['inline_keyboard'] ) ) {
						// Get the updated keyboard from actual instance.
						$inline_keyboard = get_post_meta( $instance_id, P2TGMain::PREFIX . 'inline_keyboard', true );
						$inline_keyboard = $inline_keyboard ? json_decode( $inline_keyboard, true ) : [];

						// update the saved instance.
						$instance['inline_keyboard'] = $inline_keyboard;
						// switch the flag.
						$updated = true;
					}
				}

				unset( $instance );

				if ( $updated ) {
					$saved_instances = array_map( [ P2TGUtils::class, 'encode_instance_values' ], $saved_instances );
					update_post_meta( $post_id, P2TGMain::PREFIX . 'instances', $saved_instances );
				}
			}
		}
	}

	/**
	 * Upgrade the markdown v2 to HTML.
	 *
	 * @since 2.0.0
	 *
	 * @param string $template The template to upgrade.
	 *
	 * @return string
	 */
	public static function md_v2_to_html( $template ) {

		$markdown_v2_to_html_map = [
			'*'   => 'b',
			'__'  => 'u',
			'_'   => 'i',
			'```' => 'pre',
			'`'   => 'code',
			'~'   => 's',
			'||'  => 'tg-spoiler',
		];

		// Escape the HTML chars.
		$template = htmlspecialchars( $template, ENT_NOQUOTES, 'UTF-8' );

		$macro_map = [];

		if ( preg_match_all( '/\{[^\}]+?\}/iu', $template, $matches ) ) {

			$total = count( $matches[0] );
			// Replace the macros with temporary placeholders.
			// This is to prevent the markdown chars in macros from being replaced.
			// For example, if the macro is {post_title}, "_" will get replaced with "<i>". This is not desired.
			for ( $i = 0; $i < $total; $i++ ) {
				$macro_map[ "{:MACRO{$i}:}" ] = $matches[0][ $i ];
			}
		}

		// Replace the macros with temporary placeholders.
		$template = str_replace( array_values( $macro_map ), array_keys( $macro_map ), $template );

		// Convert links to html.
		$template = preg_replace( '/\[([^\]]+?)\]\(([^\)]+?)\)/ui', '<a href="${2}">${1}</a>', $template );

		foreach ( $markdown_v2_to_html_map as $char => $tag ) {
			if ( false === strpos( $template, $char ) ) {
				continue;
			}
			$placeholder = '{:' . $tag . ':}';
			// Replace the escaped chars  with temporary placeholders.
			$template = str_replace( '\\' . $char, $placeholder, $template );

			$regex_char = preg_quote( $char, '/' );

			// Create a regex pattern to match the chars.
			// The pattern is like: /_([^_]+?)_/ius and replaces it with <i>${1}</i>.
			$pattern = sprintf( '/%1$s([^%1$s]+?)%1$s/ius', $regex_char );
			// Replace the markdown v2 chars with html.
			$replace = sprintf( '<%1$s>${1}</%1$s>', $tag );

			$template = preg_replace( $pattern, $replace, $template );
			// Replace the temporary placeholders with the chars.
			$template = str_replace( $placeholder, $char, $template );
		}

		// Replace the macros with the original values.
		$template = str_replace( array_keys( $macro_map ), array_values( $macro_map ), $template );

		$template = stripslashes( $template );

		return $template;
	}

	/**
	 * Upgrade to 2.0.0
	 *
	 * - Change parse_mode from Markdown to HTML.
	 *
	 * @since    2.0.0
	 */
	protected function upgrade_to_2_0_0() {

		$notify_options = WPTG_Pro()->options()->get( 'notify' );

		if ( isset( $notify_options['parse_mode'] ) && 'MarkdownV2' === $notify_options['parse_mode'] ) {

				// Update the message template.
				$notify_options['message_template'] = self::md_v2_to_html( $notify_options['message_template'] );
				// Set the parse mode to HTML.
				$notify_options['parse_mode'] = 'HTML';

				// Update the options.
				WPTG_Pro()->options()->set( 'notify', $notify_options );
		}

		$prefix = P2TGMain::PREFIX;

		// default args.
		$query_args = [
			'post_type'   => P2TGMain::CPT_NAME,
			'fields'      => 'ids', // we only need IDs.
			'numberposts' => -1,
		];

		// get instance posts.
		$instance_ids = get_posts( $query_args );

		foreach ( $instance_ids as $instance_id ) {
			$parse_mode = get_post_meta( $instance_id, $prefix . 'parse_mode', true );

			if ( 'MarkdownV2' === $parse_mode ) {
				// Update the message template fields.
				foreach ( [ 'message_template', 'wc_gallery_caption', 'content_images_caption' ] as $template_field ) {
					$value = get_post_meta( $instance_id, $prefix . $template_field, true );

					if ( ! empty( $value ) ) {
						$value = self::md_v2_to_html( json_decode( $value ) );

						update_post_meta( $instance_id, $prefix . $template_field, addslashes( wp_json_encode( $value ) ) );
					}
				}

				$additional_media = get_post_meta( $instance_id, $prefix . 'additional_media', true );

				// Media captions need special handling.
				if ( ! empty( $additional_media ) ) {
					$additional_media = json_decode( $additional_media, true );

					foreach ( $additional_media as &$item ) {
						// if it's a single media.
						if ( ! empty( $item['source'] ) ) {
							if ( ! empty( $item['caption'] ) ) {
								$item['caption'] = self::md_v2_to_html( $item['caption'] );
							}
						} elseif ( ! empty( $item['media'] ) ) {
							// loop through each group item.
							foreach ( $item['media'] as &$media ) {
								// Source should be present.
								if ( empty( $media['source'] ) ) {
									continue;
								}

								if ( ! empty( $media['caption'] ) ) {
									$media['caption'] = self::md_v2_to_html( $media['caption'] );
								}
							}
						}
					}

					update_post_meta( $instance_id, $prefix . 'additional_media', addslashes( wp_json_encode( $additional_media ) ) );
				}

				// Set the parse mode to HTML.
				update_post_meta( $instance_id, $prefix . 'parse_mode', 'HTML' );
			}
		}
	}
}
