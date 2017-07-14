<?php

namespace Wetail\Pacsoft\Admin\Orders;

use Wetail\Pacsoft\Plugin;
use WC_Order;
use Exception;

class Table {
	/**
	 * Modify table
	 */
	static public function modify() 
	{
		// Add ThickBox
		add_thickbox();
		
		// Add templates
		add_action( 'admin_head', [ __CLASS__, 'addTemplates' ] );
		
		// Modify columns
		add_filter( 'manage_shop_order_posts_columns', [ __CLASS__, 'addColumns' ] );
		
		// Get column content
		add_filter( 'manage_posts_custom_column', [ __CLASS__, 'getColumnContent' ], 10, 2 );
	}
	
	/**
	 * Add templates to <head>
	 */
	static public function addTemplates() 
	{
		$data = [
			'i18n' => [
				'selectPacsoftService' => __( "Select Pacsoft/Unifaun service", Plugin::TEXTDOMAIN ),
				'syncOrder' => __( "Send order", Plugin::TEXTDOMAIN )
			],
			'services' => Plugin::getServices()
		];
		$i18n = [
			'Sync order %d to Pacsoft/Unifaun' => __( "Sync order %d to Pacsoft/Unifaun", Plugin::TEXTDOMAIN ),
			'Print Pacsoft/Unifaun order' => __( "Print Pacsoft/Unifaun order", Plugin::TEXTDOMAIN )
		];
		$mustache = Plugin::getMustache();
		
		print '<script>window.pacsoftSyncOptionsDialog=\'' . str_replace( [ "\n", "\t" ], '', $mustache->render( 'admin/table/pacsoft-sync-options', $data ) ) . '\';pacsoftI18n=' . json_encode( $i18n ) . '</script>';
	}
	
	/**
	 * Add columns
	 *
	 * @param array $columns
	 */
	static public function addColumns( $columns = [] ) 
	{
		$columns['pacsoft_order'] = __( "Pacsoft/Unifaun", Plugin::TEXTDOMAIN );
		
		return $columns;
	}
	
	/**
	 * Get column content
	 *
	 * @param $column
	 * @param $orderId
	 */
	static public function getColumnContent( $column, $orderId ) 
	{
		switch( $column ) {
			case "pacsoft_order":
				$mustache = Plugin::getMustache();
				$data = [
					'syncButton' => [
						'href' => "#",
						'title' => __( "Sync order to Pacsoft/Unifaun", Plugin::TEXTDOMAIN )
					],
					'printButton' => [
						'href' => "#",
						'title' => __( "Print Pacsoft/Unifaun order", Plugin::TEXTDOMAIN )
					],
					'orderId' => $orderId,
					'serviceId' => ( get_option( 'pacsoft_sync_with_options' ) ? '' : self::getOrderService( $orderId ) ),
					'isSynced' => get_post_meta( $orderId, '_pacsoft_order_synced', true )
				];
				
				print $mustache->render( 'admin/table/pacsoft-column', $data );
				break;
		}
	}
	
	/**
	 * Get order service ID
	 *
	 * @param int $orderId
	 */
	public static function getOrderService( $orderId )
	{
		$order = new WC_Order( $orderId );
		$services = get_option( 'pacsoft_services' );
		$shipping = $order->get_items( 'shipping' );
		$shipping = reset( $shipping );
		
		if( empty( $services ) )
			//throw new Exception( __( "No services defined! Check your settings.", Plugin::TEXTDOMAIN ) );
			return false;
		
		foreach( $services as $s )
			if( isset( $s['shipping_method_id'] ) && $s['shipping_method_id'] == $shipping['method_id'] )
				return $s['service'];
	}
}
