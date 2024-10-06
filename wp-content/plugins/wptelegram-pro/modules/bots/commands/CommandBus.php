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
 * Command Handling functionality of the plugin.
 *
 * Inspired from https://github.com/irazasyed/telegram-bot-sdk
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\commands
 * @author     WP Socio
 */
class CommandBus {

	/**
	 * The object handler that triggered the command
	 *
	 * @since   1.0.0
	 * @access  private
	 *
	 * @var     mixed   $trigger_origin The object handler that triggered the command
	 */
	private $trigger_origin;

	/**
	 * The Command list.
	 *
	 * @var array   $commands   Holds all commands.
	 */
	protected $commands = [];

	/**
	 * The command aliases.
	 *
	 * @var array   $command_aliases    Holds all commands' aliases.
	 */
	protected $command_aliases = [];

	/**
	 * Instantiate Command Bus.
	 *
	 * @param   mixed $trigger_origin The object handler that triggered the command.
	 */
	public function __construct( $trigger_origin ) {
		$this->trigger_origin = $trigger_origin;
	}

	/**
	 * Returns the list of commands.
	 *
	 * @return array
	 */
	public function get_commands() {
		return $this->commands;
	}

	/**
	 * Returns the trigger_origin
	 *
	 * @return CommandHandler
	 */
	public function get_trigger_origin() {
		return $this->trigger_origin;
	}

	/**
	 * Add a list of commands.
	 *
	 * @param array $commands The command list.
	 */
	public function add_commands( $commands ) {
		foreach ( (array) $commands as $command ) {
			$this->add_command( $command );
		}
	}

	/**
	 * Add a command to the commands list.
	 *
	 * @param string $command Either an object or full path to the command class.
	 */
	public function add_command( $command ) {

		if ( ! is_object( $command ) ) {

			if ( ! class_exists( $command ) ) {

				trigger_error( sprintf( __( '%s class not found! Please make sure the class exists.', 'wptelegram-pro' ), $command ) ); // phpcs:ignore
				return;

			} else {

				$command = new $command();
			}
		}

		$base_class = __NAMESPACE__ . '\\CommandHandler';

		if ( $command instanceof $base_class ) {

			$command->set_command_bus( $this );
			$command->set_trigger_origin( $this->get_trigger_origin() );

			$this->commands[ strtolower( $command->get_name() ) ] = $command;

			$aliases = $command->get_aliases();

			if ( ! empty( $aliases ) ) {

				foreach ( $aliases as $alias ) {

					if ( isset( $this->commands[ $alias ] ) ) {

						trigger_error( sprintf( '%1$s alias conflicts with command name of %2$s.' . ' ' . 'Try with another name or remove this alias from the list.', $alias, get_class( $command ) ) ); // phpcs:ignore
						continue;
					}

					if ( isset( $this->command_aliases[ $alias ] ) ) {

						trigger_error( sprintf( '%1$s alias conflicts with alias list of %2$s.' . ' ' . 'Try with another name or remove this alias from the list.', $alias, get_class( $command ) ) ); // phpcs:ignore
						continue;
					}

					$this->command_aliases[ strtolower( $alias ) ] = $command;
				}
			}
		} else {

			trigger_error( sprintf( '%1$s class should extend %2$s class', get_class( $command ), $base_class ) ); // phpcs:ignore
		}
	}

	/**
	 * Remove a command from the list.
	 *
	 * @param string $name The command to remove.
	 */
	public function remove_command( $name ) {
		unset( $this->commands[ $name ] );
	}

	/**
	 * Removes a list of commands.
	 *
	 * @param array $names The commands to remove.
	 */
	public function remove_commands( $names ) {
		foreach ( (array) $names as $name ) {
			$this->remove_command( $name );
		}
	}

	/**
	 * Get the handler of a command
	 *
	 * @since   1.0.0
	 *
	 * @param   string $command    Command name.
	 * @param   string $arguments  Command arguments.
	 *
	 * @return  mixed
	 */
	public function get_handler( $command, $arguments ) {

		$handler = false;
		if ( array_key_exists( $command, $this->commands ) ) {

			$handler = $this->commands[ $command ];

		} elseif ( array_key_exists( $command, $this->command_aliases ) ) {

			$handler = $this->command_aliases[ $command ];
		}

		if ( is_object( $handler ) ) {

			$handler->set_arguments( $arguments );
		}

		$handler = apply_filters( 'wptelegram_pro_bots_command_handler', $handler, $command, $this );

		$handler = apply_filters( "wptelegram_pro_bots_{$command}_command_handler", $handler, $this );

		return $handler;
	}
}
