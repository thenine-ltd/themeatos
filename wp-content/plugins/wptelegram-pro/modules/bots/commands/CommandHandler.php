<?php
/**
 * Command Handling functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\commands
 */

namespace WPTelegram\Pro\modules\bots\commands;

/**
 * Command Building functionality of the plugin.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\commands
 * @author     WP Socio
 */
abstract class CommandHandler {

	/**
	 * The name of the Telegram command.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @var string  $name   Command name
	 */
	protected $name;

	/**
	 * Command Aliases
	 * Helpful when you want to use same command with more than one name.
	 *
	 * @since   1.0.0
	 * @access  protected
	 *
	 * @var array   $aliases    Command Aliases
	 */
	protected $aliases;

	/**
	 * The command description.
	 *
	 * @since   1.0.0
	 * @access  protected
	 *
	 * @var string  $description    The command description.
	 */
	protected $description;

	/**
	 * The command arguments.
	 *
	 * @since   1.0.0
	 * @access  protected
	 *
	 * @var string  $arguments  Arguments passed to the command.
	 */
	protected $arguments;

	/**
	 * The object handler that triggered the command
	 *
	 * @since   1.0.0
	 * @access  protected
	 *
	 * @var     mixed   $trigger_origin The object handler that triggered the command
	 */
	protected $trigger_origin;

	/**
	 * Command bus, with commands and aliases as its passengers :)
	 *
	 * @since   1.0.0
	 * @access  protected
	 *
	 * @var CommandBus  $command_bus The Command Bus.
	 */
	protected $command_bus;

	/**
	 * Magic method to get or set properties dynamically.
	 *
	 * @since   1.0.0
	 *
	 * @param string $method Method name.
	 * @param array  $arguments The method args.
	 *
	 * @return mixed
	 */
	public function __call( $method, $arguments ) {

		$action   = substr( $method, 0, 4 );
		$property = strtolower( substr( $method, 4 ) );

		$class = get_class( $this );

		if ( property_exists( $this, $property ) && in_array( $action, [ 'get_', 'set_' ], true ) ) {
			switch ( $action ) {
				case 'get_':
					return $this->{$property};
				case 'set_':
					$this->{$property} = $arguments[0];
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
	 * Process the command
	 */
	abstract public function process();
}
