<?php

namespace Wetail\Pacsoft\API;

use Wetail\Pacsoft\Plugin;
use SimpleXMLElement;
use DOMDocument;
use Exception;
use WC_Order;
use WC_Shipping_Zones;

class Request {
	/**
	 * Sync order
	 *
	 * @param $orderId
	 * @param $service
	 * @param $force
	 */
	public static function syncOrder( $orderId, $service = null, $force = false )
	{
		if( ! Plugin::checkLicense() )
			throw new Exception( "Invalid license." );
		
		if( get_post_meta( $orderId, '_pacsoft_order_synced' ) && ! $force )
			throw new Exception( __( "Order already synced to Pacsoft/Unifaun.", Plugin::TEXTDOMAIN ) );
		
		try {
			$xml = self::generateXML( $orderId, $service );
		}
		catch( Exception $error ) {
			throw new Exception( $error->getMessage() );
		}
		
		$url = "https://www.unifaunonline.se/ufoweb/order?"
			. "session=" . get_option( 'pacsoft_account_type' )
			. "&user=" . get_option( 'pacsoft_usern_unif' )
			. "&pin=" . get_option( 'pacsoft_pass_unif' )
			. "&type=XML&developerid=0020012792";
		
		$ch = curl_init();
		
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [ "Content-Type: text/xml" ] );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 40 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $xml );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		
		$response = curl_exec( $ch );
		
		curl_close( $ch );
		
		$response = json_decode( json_encode( simplexml_load_string( $response ) ), true );
		
		if( 201 == $response['val'][ 1 ] ) {
			update_post_meta( $orderId, '_pacsoft_order_synced', 1 );
			
			return true;
		}
		else
			throw new Exception( __( "An error occurred while syncing order to Pacsoft/Unifaun.", Plugin::TEXTDOMAIN ) );
	}
	
	/**
	 * Generate request XML
	 *
	 * @param int $orderId
	 * @param string $serviceId
	 */
	public static function generateXML( $orderId, $serviceId = '' )
	{
		$order = new WC_Order( $orderId );
		$xml = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><data></data>' );
		$defaultServicePackageCode = '';
		$name = $order->get_shipping_first_name() ? "{$order->get_shipping_first_name()} {$order->get_shipping_last_name()}" : "{$order->get_billing_first_name()} {$order->get_billing_last_name()}";
		$address = $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1();
		$address2 = $order->get_shipping_address_2() ? $order->get_shipping_address_2() : $order->get_billing_address_2();
		$zipcode = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->billing_postcode;
		$city = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
		$country = $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country();
		
		$shipping = $order->get_items( 'shipping' );
		$shipping = reset( $shipping );
		
		if( empty( $shipping ) && empty( $serviceId ) )
			throw new Exception( __( "No shipping and service ID specified for the selected order.", Plugin::TEXTDOMAIN ) );

		// Attempt to find a service ID
		if( empty( $serviceId ) ) {
			$services = get_option( 'pacsoft_services', [] );
			
			if( empty( $services ) )
				throw new Exception( __( "No services defined! Check your settings.", Plugin::TEXTDOMAIN ) );

			$theMostExpensiveShippingClass = null;
			$foundClasseNames = [];

			$shippingZones = WC_Shipping_Zones::get_zones();
			$instance_settings;
			foreach ($shippingZones as $zone) {
				foreach ($zone['shipping_methods'] as $shippingMethod) {
					$ov = get_object_vars($shippingMethod);
					if ($ov['id'] === 'flat_rate') {
						$instance_settings = $ov['instance_settings'];
						break 2;
					}
				}
			}
			
			$items = $order->get_items();
			foreach ($items as $item) {
				/* an item:
				39 --> {"id":39,"order_id":258,"name":"Nixons turtle","product_id":195,"variation_id":0,"quantity":1,"tax_class":"","subtotal":"269","subtotal_tax":"67.25","total":"269","total_tax":"67.25","taxes":{"total":{"1":"67.25"},"subtotal":{"1":"67.25"}},"meta_data":[]}*/

				$pID = $item['product_id'];
				$prd = wc_get_product($pID);
				$shippingClassID = $prd->get_shipping_class_id();

				if (array_key_exists("class_cost_$shippingClassID",$instance_settings) && 
					intval($instance_settings["class_cost_$shippingClassID"]) > 0)
				{
					if (!$theMostExpensiveShippingClass) {
						$theMostExpensiveShippingClass = "class_cost_$shippingClassID";
					} else {
						$champion = intval($instance_settings[$theMostExpensiveShippingClass]);
						$new = intval($instance_settings["class_cost_$shippingClassID"]);
						if ($champion < $new) {
							$theMostExpensiveShippingClass = "class_cost_$shippingClassID";
						}
					}
				}
			}
			
			if ($theMostExpensiveShippingClass) {
				$theMostExpensiveShippingClass = 'product_shipping_class:'.explode("_", $theMostExpensiveShippingClass)[2];
				foreach( $services as $s ) {
					// Explode shipping_method_id and get method and instance
					$x = explode(":", $s["shipping_method_id"]);
					
					$s["shipping_method_id"] = $x[0] . ":" . $x[2];

					if( $s['shipping_method_id'] == $theMostExpensiveShippingClass ) {
						$serviceId = $s['service'];
						$senderQuickValue = $s['sender_quick_value'];
					}
				}

			} else {

				foreach( $services as $s ) {
					// Explode shipping_method_id and get method and instance
					$x = explode(":", $s["shipping_method_id"]);
					
					$s["shipping_method_id"] = $x[0] . ":" . $x[2];

					if( $s['shipping_method_id'] == $shipping['method_id'] ) {
						$serviceId = $s['service'];
						$senderQuickValue = $s['sender_quick_value'];
					}
				}
			}
			
		}
		
		if( empty( $serviceId ) )
			throw new Exception( __( "No service ID specified for the selected order.", Plugin::TEXTDOMAIN ) );
        
        // Meta
        $meta = $xml->addChild( 'meta' );
        
        // Test mode
        if( get_option( 'pacsoft_test_mode' ) ) {
	        $val = $meta->addChild( 'val', "YES" );
	        $val->addAttribute( 'n', "TEST" );
        }
        
		// Receiver
		$receiver = $xml->addChild( 'receiver' );
		$receiver->addAttribute( 'rcvid', "6565" );
		
		// Name
		$val = $receiver->addChild( 'val', $name );
		$val->addAttribute( 'n', "name" );
		
		$val = $receiver->addChild( 'val', $address );
		$val->addAttribute( 'n', "address1" );
		
		$val = $receiver->addChild( 'val', $address2 );
		$val->addAttribute( 'n', "address2" );

		$val = $receiver->addChild( 'val', $zipcode );
		$val->addAttribute( 'n', "zipcode" );
		
		$val = $receiver->addChild( 'val', $city );
		$val->addAttribute( 'n', "city" );
		
		$val = $receiver->addChild( 'val', $country );
		$val->addAttribute( 'n', "country" );
		
		$val = $receiver->addChild( 'val', $order->get_billing_email() );
		$val->addAttribute( 'n', "email" );
		
		$val = $receiver->addChild( 'val', $order->get_billing_phone() );
		$val->addAttribute( 'n', "phone" );
		
		$val = $receiver->addChild( 'val', $order->get_billing_phone() );
		$val->addAttribute( 'n', "sms" );
		
		$val = $receiver->addChild( 'val', $name );
		$val->addAttribute( 'n', "contact" );
		
		// Shipment
		$shipment = $xml->addChild( 'shipment' );
		$shipment->addAttribute( 'orderno', apply_filters( 'woocommerce_order_number', $orderId, $order ) );
		
		if( empty( $senderQuickValue ) )
			$senderQuickValue = 1;
		
		$val = $shipment->addChild( 'val', $senderQuickValue );
		$val->addAttribute( 'n', "from" );
		
		$val = $shipment->addChild( 'val', "6565" );
		$val->addAttribute( 'n', "to" );
		
		$val = $shipment->addChild( 'val', "OrderID: " . apply_filters( 'woocommerce_order_number', $orderId, $order ) );
		$val->addAttribute( 'n', "reference" );
		
		//$val = $shipment->addChild( 'val', "" );
		//$val->addAttribute( 'n', "freetext1" );
		
		// Pre-notification by e-mail
		if( get_option( 'pacsoft_addon_enot' ) ) {
			$ufonline = $shipment->addChild( 'ufonline' );
			
			$option = $ufonline->addChild( 'option' );
			$option->addAttribute( 'optid', "enot" );
			
			$val = $option->addChild( 'val', get_option( 'admin_email' ) );
			$val->addAttribute( 'n', "from" );
			
			$val = $option->addChild( 'val', $order->get_billing_email() );
			$val->addAttribute( 'n', "to" );
			
			$val = $option->addChild( 'val', get_option( 'admin_email' ) );
			$val->addAttribute( 'n', "errorto" );
		}
		
		// Service
		$service = $shipment->addChild( 'service' );
		$service->addAttribute( 'srvid', $serviceId );
		
		// Addons
		if( get_option( 'pacsoft_addon_sms' ) ) {
			$addon = $service->addChild( 'addon' );
			$addon->addAttribute( 'adnid', "NOTSMS" );
			
			$val = $addon->addChild( 'val', $order->get_billing_phone() );
			$val->addAttribute( 'n', "misc" );
			
			// Electronic notification
			if( "IT16" == $serviceId ) {
				$addon = $service->addChild( 'addon' );
				$addon->addAttribute( 'adnid', "NOT" );
				
				$val = $addon->addChild( 'val', $order->get_billing_email() );
				$val->addAttribute( 'n', "misc" );
			}
		}

		// Return labels
		if( get_option( 'pacsoft_print_return_labels' ) ) {
			$returnlabel = $service->addChild('val', 'both');
			$returnlabel->addAttribute('n', 'returnlabel');
		}
		
		$totalWeight = 0;
		
		// Container
		foreach( $order->get_items() as $item ) {
			// FIX: Skip bundled items (Mix & Match)
			if( ! isset( $item['mnm_config'] ) && isset( $item['mnm_container'] ) )
				continue;
			
			$product = $order->get_product_from_item( $item );
			$weight = $product->get_weight();
			$weight = floatval($weight);
			if( 'g' == get_option( 'woocommerce_weight_unit' ) )
				$weight = $weight / 1000;
			
			$weight *= $item['qty'];
			
			$totalWeight += $weight;
			
			if( get_option( 'pacsoft_single_package_per_order' ) )
				continue;
			
			// Send order items in separate parcels
			$container = $shipment->addChild( 'container' );
			$container->addAttribute( 'type', "parcel" );
			
			$val = $container->addChild( 'val', get_option( 'pacsoft_print_freight_label_per_item' ) ? $item['qty'] : 1 );
			$val->addAttribute( 'n', "copies" );
			
			$val = $container->addChild( 'val', $weight );
			$val->addAttribute( 'n', "weight" );
			
			$val = $container->addChild( 'val', "PC" );
			$val->addAttribute( 'n', "packagecode" );
			
			$val = $container->addChild( 'val', get_option( 'pacsoft_default_product_type', "Varor" ) );
			$val->addAttribute( 'n', "contents" );
		}
		
		// Send order in one parcel
		if( get_option( 'pacsoft_single_package_per_order' ) ) {
			$container = $shipment->addChild( 'container' );
			$container->addAttribute( 'type', "parcel" );
			
			$val = $container->addChild( 'val', 1 );
			$val->addAttribute( 'n', "copies" );
			
			$val = $container->addChild( 'val', $totalWeight );
			$val->addAttribute( 'n', "weight" );
			
			$val = $container->addChild( 'val', "PC" );
			$val->addAttribute( 'n', "packagecode" );
			
			$val = $container->addChild( 'val', get_option( 'pacsoft_default_product_type', "Varor" ) );
			$val->addAttribute( 'n', "contents" );
		}
		
		$doc = new DOMDocument( '1.0' );
		$doc->formatOutput = true;
		$docXML = $doc->importNode( dom_import_simplexml( $xml ), true );
		$docXML = $doc->appendChild( $docXML );
		
		return $doc->saveXML( $doc, LIBXML_NOEMPTYTAG );
	}
}