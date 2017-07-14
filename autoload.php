<?php

/**
 * PSR-4 compliant autoloader
 *
 * @param $path
 */
if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
define("PACSOFT_CURRENT_WOOCOMMERCE_VERSION", intval(explode('.', get_plugins()['woocommerce/woocommerce.php']['Version'])[0]) );
spl_autoload_register( function( $class ) {

	if ( PACSOFT_CURRENT_WOOCOMMERCE_VERSION < 3 ) {
		$hasLegacy = [
			'Wetail\Pacsoft\API\Request'
		];
		//error_log($class);
		if (in_array($class, $hasLegacy)) {
			$class .= 'Legacy';
			error_log($class);
		}
		
	}

	$file = plugin_dir_path( __FILE__ ) . "src/" . str_replace( "\\", "/", $class ) . ".php";
	
	if( ! file_exists( $file ) )
		return;
	
	require_once $file;
} );
