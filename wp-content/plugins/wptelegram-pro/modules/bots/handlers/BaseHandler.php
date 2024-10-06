<?php
/**
 * The base class for all the handler classes
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 */

namespace WPTelegram\Pro\modules\bots\handlers;

use WPTelegram\Pro\modules\bots\commands\CommandBus;
use WPTelegram\Pro\modules\bots\commands\StartCommandHandler;
use WPTelegram\Pro\modules\bots\commands\HelpCommandHandler;
use WPTelegram\BotAPI\API;
use ReflectionClass;

/**
 * The base class for all the handler classes
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 * @author     WP Socio
 */
abstract class BaseHandler {

	/**
	 * The current object
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @var     array   $current_object
	 */
	protected $current_object;

	/**
	 * The current object type
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @var     array   $current_object_type
	 */
	protected $current_object_type;

	/**
	 * The handler for the parent of current object
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @var     object   $parent_handler
	 */
	protected $parent_handler;

	/**
	 * The update object
	 *
	 * @since   1.0.0
	 * @access  public
	 * @var     array       $update
	 */
	public static $update;

	/**
	 * The Telegram API
	 *
	 * @since   1.0.0
	 * @access  public
	 * @var     API  $bot_api    Telegram API Object
	 */
	public static $bot_api;

	/**
	 * A bus that has commands as it passengers :)
	 *
	 * @since   1.0.0
	 * @access  public
	 * @var     CommandBus  $command_bus
	 */
	public $command_bus;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 *
	 * @param   array  $current_object The current object.
	 * @param   string $type           The current object type.
	 * @param   string $parent_handler The handler for the parent object.
	 */
	public function __construct( $current_object = null, $type = null, $parent_handler = null ) {
		$this->set_current_object( $current_object );
		$this->set_current_object_type( $type );
		$this->set_parent_handler( $parent_handler );
	}

	/**
	 * Magic method to get or set properties dynamically.
	 *
	 * @since   1.0.0
	 *
	 * @param string $method The method name.
	 * @param array  $arguments The arguments.
	 *
	 * @return mixed
	 */
	public function __call( $method, $arguments ) {

		$action   = substr( $method, 0, 4 );
		$property = strtolower( substr( $method, 4 ) );

		$class = get_class( $this );
		$ref   = new ReflectionClass( $class );

		if ( $ref->hasProperty( $property ) && in_array( $action, [ 'get_', 'set_' ], true ) ) {

			$static = $ref->getProperty( $property )->isStatic() ? true : false;

			switch ( $action ) {
				case 'get_':
					if ( $static ) {
						return $ref->getStaticPropertyValue( $property, null );
					}
					return $this->{$property};
				case 'set_':
					if ( $static ) {
						$ref->setStaticPropertyValue( $property, $arguments[0] );
					} else {
						$this->{$property} = $arguments[0];
					}
					break;
			}
		} else {

			$trace = debug_backtrace(); // phpcs:ignore
			$file  = $trace[0]['file'];
			$line  = $trace[0]['line'];
			trigger_error( "Call to undefined method $class::$method() in $file on line $line", E_USER_ERROR ); // phpcs:ignore
		}
	}

	/**
	 * Get a value from the deeply nested current object using "dot" notation
	 * e.g. ->get( 'message.chat.id', $default = null );
	 *
	 * @since   1.0.0
	 *
	 * @param   mixed $key            The array key.
	 * @param   mixed $default        The default value.
	 * @param   bool  $from_current   Whether to fetch the value from the current object or the update object.
	 *
	 * @return mixed
	 */
	public function get( $key = null, $default = null, $from_current = true ) {

		if ( $from_current ) {
			$value = $this->get_current_object();
		} else {
			$value = $this->get_update();
		}

		if ( is_null( $key ) ) {
			return $value;
		}
		if ( array_key_exists( $key, $value ) ) {
			return $value[ $key ];
		}
		if ( false === strpos( $key, '.' ) ) {
			return $default;
		}

		foreach ( explode( '.', $key ) as $segment ) {

			if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
				return $default;
			}
			$value = $value[ $segment ];
		}
		return $value;
	}

	/**
	 * Get the type, from current object
	 * e.g. if current_object is "message"
	 * then the type may be "text", "photo" etc.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $known_types    known object types.
	 * @param   bool  $from_current   Whether to get the type from the current object or the update object.
	 *
	 * @return mixed
	 */
	protected function get_type( $known_types, $from_current = true ) {

		$_type = false;
		if ( $from_current ) {
			$current_object_type = $this->get_current_object_type();
		} else {
			$current_object_type = 'update';
		}

		foreach ( $known_types as $type ) {
			if ( ! is_null( $this->get( $type, null, $from_current ) ) ) {
				$_type = $type;
			}
		}
		return apply_filters( "wptelegram_pro_bots_{$current_object_type}_type", $_type, $this );
	}

	/**
	 * Get chat or a chat value
	 *
	 * @since   1.0.0
	 *
	 * @param   string $key    The array key.
	 *
	 * @return  null|array
	 */
	public function get_chat( $key = '' ) {
		$update_type = $this->get_type( UpdateHandler::known_types(), false );

		switch ( $update_type ) {
			case 'message':
			case 'channel_post':
				$_key = $update_type . '.chat';
				break;
			case 'callback_query':
				$_key = $update_type . '.message.chat';
				break;
			default:
				$_key = '';
				break;
		}
		if ( ! empty( $_key ) ) {
			if ( ! empty( $key ) ) {
				$_key .= '.' . $key;
			}
			return $this->get( $_key, null, false );
		}
		return null;
	}

	/**
	 * Get user or a user value
	 *
	 * @since   1.0.0
	 *
	 * @param   string $key    The array key.
	 *
	 * @return  null|array
	 */
	public function get_user( $key = '' ) {
		$update_type = $this->get_type( UpdateHandler::known_types(), false );

		// update types which contain the user object.
		$types_with_user = [
			'message',
			'edited_message',
			'inline_query',
			'chosen_inline_result',
			'callback_query',
			'shipping_query',
			'pre_checkout_query',
		];

		// if not an update with user object.
		if ( ! in_array( $update_type, $types_with_user, true ) ) {
			return null;
		}
		$_key = $update_type . '.from';

		if ( ! empty( $key ) ) {
			$_key .= '.' . $key;
		}
		return $this->get( $_key, null, false );
	}

	/**
	 * Check whether the text is a command.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text   The text.
	 *
	 * @return  bool
	 */
	public function is_command( $text ) {
		/**
		 * Command pattern for private and group chats
		 * For example /help, /help@BotUsername
		 */
		$pattern = '/^\/[^\s@]+?(?:@[a-z]\w+)?/i';

		if ( preg_match( $pattern, $text ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Parse a command for a Match.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text The command text.
	 *
	 * @return  array
	 */
	public function parse_command( $text ) {

		// save to variable to avoid error in PHP < 5.5
		// Can't use method return value in write context.
		$text = trim( $text );

		if ( empty( $text ) ) {
			trigger_error( __( 'Text is empty, cannot parse for command', 'wptelegram-pro' ) ); // phpcs:ignore
		}

		/**
		 * Back references:
		 * [1] Command name
		 * [2] Bot Username (optional)
		 * [1] Command Arguments (optional)
		 */
		preg_match( '/^\/([^\s@]+)@?(\S+)?\s?(.*)$/s', $text, $match );

		return $match;
	}

	/**
	 * Trigger a command.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $command        Command name.
	 * @param   string $arguments      The command arguments.
	 * @param   string $bot_username   Username of the bot.
	 *
	 * @return  array
	 */
	public function trigger_command( $command, $arguments = '', $bot_username = '' ) {

		$res = false;

		$command = strtolower( $command ); // Commands must be lowercase.

		/**
		 * Fires just after a command is triggered
		 * Can used to include the classes to handle the command
		 *
		 * @param   string  $command    Command name
		 * @param   string  $arguments  The command arguments
		 * @param   object  $this       Current class object
		 */
		do_action( 'wptelegram_pro_bots_command_init', $command, $arguments, $bot_username, $this );

		$this->setup_command_bus();

		// give preference and try to get a command handler object.
		$handler = $this->command_bus->get_handler( $command, $arguments );

		/**
		 * Fires after getting the command handler
		 *
		 * @since 1.0.0
		 *
		 * @param mixed     $handler    The command handler object (passed via identifier).
		 * @param string    $command    The Command name
		 * @param string    $this       The current object
		 */
		do_action( 'wptelegram_pro_bots_command_handler', $handler, $command, $arguments, $this );

		// if the command has a handler.
		if ( is_callable( [ $handler, 'process' ] ) ) {

			do_action( 'wptelegram_pro_bots_before_process_command', $command, $arguments, $this );
			// process the command.
			$res = $handler->process();

			do_action( 'wptelegram_pro_bots_after_process_command', $command, $arguments, $res, $this );

		} elseif ( $command_post = $this->get_command_post( $command ) ) { // phpcs:ignore

			do_action( 'wptelegram_pro_bots_before_process_command_post', $command, $arguments, $command_post, $this );

			$handler = new CommandPostHandler( $command_post, 'command_post', $this );
			// process the post.
			$res = $handler->process();

			do_action( 'wptelegram_pro_bots_after_process_command_post', $command, $arguments, $command_post, $res, $this );

		} else {

			$text = apply_filters( 'wptelegram_pro_bots_unknown_command_text', __( 'Unknown command', 'wptelegram-pro' ), $this );

			if ( ! empty( $text ) ) {

				$chat_id = $this->get_chat( 'id' );
				$res     = $this->get_bot_api()->sendMessage( compact( 'chat_id', 'text' ) );
			}

			do_action( 'wptelegram_pro_bots_unknown_command', $command, $arguments, $this );
		}

		do_action( 'wptelegram_pro_bots_trigger_command', $command, $arguments, $this );

		return $res;
	}

	/**
	 * Set up the basics
	 *
	 * @since  1.0.0
	 */
	private function setup_command_bus() {

		$command_bus = new CommandBus( $this );
		/**
		 * Fires after getting the command handler
		 *
		 * @since 1.0.0
		 *
		 * @param mixed     $command_bus    The command bus (passed via identifier)
		 * @param string    $this           The current object (passed via identifier)
		 */
		do_action( 'wptelegram_pro_bots_before_setup_command_bus', $command_bus, $this );

		$this->set_command_bus( $command_bus );
		$bot_token = $this->get_bot_api()->get_bot_token();

		// generic default commands for all bots.
		$command_handlers = [
			StartCommandHandler::class,
			HelpCommandHandler::class,
		];

		/**
		 * Filter command classes/objects for a bot
		 *
		 * Every command class should extend the
		 * CommandHandler class
		 *
		 * $name and $description properties of every class
		 * should be added with the appropriate values,
		 * which will be used when processing the command.
		 *
		 * Every command class should implement the process() method
		 * which would be called when a user sends the command
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $command_handlers    An array of all the command classes
		 */
		$command_handlers = (array) apply_filters( 'wptelegram_pro_bots_command_handlers', $command_handlers, $bot_token, $this );

		$command_handlers = (array) apply_filters( "wptelegram_pro_bots_command_handlers_{$bot_token}", $command_handlers, $this );

		$this->command_bus->add_commands( $command_handlers );
	}

	/**
	 * Get the post associated with the command
	 *
	 * @since   1.0.0
	 *
	 * @param   string $command    Command name.
	 *
	 * @return  WP_Post|bool
	 */
	public function get_command_post( $command ) {

		// for getting the bot specific command.
		$bot_token = $this->get_bot_api()->get_bot_token();

		$posts = [];// phpcs:ignore - WPTG_Pro()->helpers->get_command_posts( $bot_token, $command );

		$command_post = reset( $posts );

		return apply_filters( 'wptelegram_pro_bots_command_post', $command_post, $command, $this );
	}

	/**
	 * Process the current object
	 *
	 * @since  1.0.0
	 */
	abstract protected function process();
}
