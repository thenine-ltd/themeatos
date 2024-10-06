<?php
/**
 * Singleton class trait.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\Traits
 */

/**
 * Singleton trait.
 */
trait YITH_WCMBS_Singleton_Trait {
	/**
	 * The single instance of the class.
	 *
	 * @var self
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {
	}

	/**
	 * Get class instance.
	 *
	 * @return self
	 */
	final public static function get_instance() {
		return ! is_null( static::$instance ) ? static::$instance : static::$instance = new static();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {
	}

	// TODO: prevent un-serializing.

}
