<?php
namespace Wetail\Pacsoft;

use Wetail\Pacsoft\Plugin;
use Wetail\Pacsoft\API\Request;
use WC_Pacsoft_Interface;
use Exception;

class AJAX {
	/**
	 * Send AJAX response
	 *
	 * @param array $data
	 */
	public static function respond( $data = [] )
	{
		$defaults = [
			'error' => false
		];
		$data = array_merge( $defaults, $data );
		
		die( json_encode( $data ) );
	}
	
	/**
	 * Send AJAX error
	 *
	 * @param string $message
	 */
	public static function error( $message )
	{
		self::respond( [ 'error' => true, 'message' => $message ] );
	}
	
	/**
	 * Sync order
	 */
	public static function syncOrder()
	{
		if( empty( $_REQUEST['order_id'] ) )
			self::error( __( "Missing order ID.", Plugin::TEXTDOMAIN ) );
		
		$orderId = $_REQUEST['order_id'];
		$serviceId = null;
		
		if( ! empty( $_REQUEST['service_id'] ) )
			$serviceId = $_REQUEST['service_id'];
		
		// Force sync if it has been synced before and shift key was pressed during manual
		// sync
		if( ! empty( $_REQUEST['force'] ) )
			$force = $_REQUEST['force'];

		try {
			Request::syncOrder( $orderId, $serviceId, $force );
		}
		catch( Exception $error ) {
			self::error( $error->getMessage() );
		}
		
		self::respond( [ 'message' => __( "Order successfully synchronised.", Plugin::TEXTDOMAIN ) ] );
	}
	
	/**
	 * Print order
	 */
	public static function printOrder()
	{
		if( empty( $_REQUEST['order_id'] ) )
			self::error( __( "Missing order ID.", Plugin::TEXTDOMAIN ) );
		
		$message = WC_Pacsoft_Interface::print_order( $_REQUEST['order_id'] );
		
		die( json_encode( $message ) );
	}
}