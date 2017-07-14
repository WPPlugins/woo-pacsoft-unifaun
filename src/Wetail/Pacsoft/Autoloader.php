<?php

namespace Wetail\Pacsoft;

class Autoloader {
	/**
	 * Setup class autoloader
	 */
	static public function register() {
		/**
		 * Autoload classes
		 *
		 * @param $path
		 */
		error_log('The funktion Autoloader::register was run.');
		spl_autoload_register( function( $path ) {
			error_log('The funktion spl_autoload_register in /src/Wetail/Pacsoft/Autoloader.php was run.');
/*
			if ( PACSOFT_CURRENT_WOOCOMMERCE_VERSION < 3 ) {
				$hasLegacy = [
					'API/Request'
				];
				if (in_array($class, $hasLegacy)) {
					$class .= 'Legacy';
				}
				
			}
*/
			if( 0 !== strpos( $path, __NAMESPACE__ ) )
				return;

			$path = str_replace( "\\", "/", $path );
			$path = preg_replace( '/^' . str_replace( '\\', '\/', __NAMESPACE__ ) . '\//', '', $path );
			$file = plugin_dir_path( dirname( __FILE__ ) ) . "src/{$path}.php";
			
			if( ! file_exists( $file ) )
				return;
			
			require_once $file;
		} );
	}
}