<?php

namespace Wetail\Pacsoft\Prototypes;

class Singleton {
	/**
	 * Protect constructor
	 */
	protected function __construct() {}
	
	/**
	 * Prevent cloning of the instance
	 */
	private function __clone() {}
	
	/**
	 * Prevent unserializing the instance
	 */
	private function __wakeup() {}
	
	/**
	 * Returns the *singleton* instance
	 *
	 * Supports extends
	 */
	public static function getInstance() {
		static $instances;
		
		// Get called class name
		$class = get_called_class();
		
		// Create a new instance if does't exist yet
		if( ! isset( $instances[ $class ] ) )
			$instances[ $class ] = new $class();
		
		return $instances[ $class ];
	}
	
}