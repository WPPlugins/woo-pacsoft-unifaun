<?php

namespace Wetail\Pacsoft;

use Wetail\Pacsoft\Admin\Settings;
use Wetail\Pacsoft\Admin\Orders\Table as Orders_Table;
use Mustache_Autoloader;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;
use WC_Pacsoft_Interface;
use WC_Shipping_Zones;
use Wetail\Pacsoft\API\Request;
use WC_Shipping;

class Plugin
{
	const TEXTDOMAIN = "woo-pacsoft-unifaun";

	/**
	 * Activate
	 */
	public static function activate() {}

	/**
	 * Deactivate
	 */
	public static function deactivate() {}

	/**
	 * Get plugin path
	 *
	 * @param string $path
	 */
	public static function getPath( $path = '' )
	{
		return plugin_dir_path( dirname( dirname( dirname( __FILE__ ) ) ) ) . ltrim( $path, '/' );
	}

	/**
	 * Get plugin URL
	 *
	 * @param string $path
	 */
	public static function getUrl( $path = '' )
	{
		return plugins_url( $path, dirname( dirname( dirname( __FILE__ ) ) ) );
	}

	/**
	 * Load textdomain
	 *
	 * @hook 'plugins_loaded'
	 */
	public static function loadTextdomain()
	{
		load_plugin_textdomain( self::TEXTDOMAIN );
	}

	/**
	 * Check license
	 *
	 * @hook filter 'pacsoft_check_license'
	 */
	public static function checkLicense()
	{
		$license_key = get_option('pacsoft_license_key');
		if(!isset($license_key)){
		    return false;
		}

		// -----------------------------------
		//  -- Configuration Values --
		// -----------------------------------
		$whmcsurl = 'http://whmcs.onlineforce.net/';
		$licensing_secret_key = 'ak4763';
		$check_token = time() . md5(mt_rand(1000000000, 9999999999) . $license_key);
		$checkdate = date("Ymd");
		$domain = $_SERVER['SERVER_NAME'];
		$usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
		$dirpath = dirname(__FILE__);
		$verifyfilepath = 'modules/servers/licensing/verify.php';

	    $postfields = array(
	        'licensekey' => $license_key,
	        'domain' => $domain,
	        'ip' => $usersip,
	        'dir' => $dirpath,
	    );
	    if ($check_token) $postfields['check_token'] = $check_token;
	    $query_string = '';
	    foreach ($postfields AS $k=>$v) {
	        $query_string .= $k.'='.urlencode($v).'&';
	    }
	    if (function_exists('curl_exec')) {
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $whmcsurl . $verifyfilepath);
	        curl_setopt($ch, CURLOPT_POST, 1);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
	        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        $data = curl_exec($ch);
	        curl_close($ch);
	    } else {
	        $fp = fsockopen($whmcsurl, 80, $errno, $errstr, 5);
	        if ($fp) {
	            $newlinefeed = "\r\n";
	            $header = "POST ".$whmcsurl . $verifyfilepath . " HTTP/1.0" . $newlinefeed;
	            $header .= "Host: ".$whmcsurl . $newlinefeed;
	            $header .= "Content-type: application/x-www-form-urlencoded" . $newlinefeed;
	            $header .= "Content-length: ".@strlen($query_string) . $newlinefeed;
	            $header .= "Connection: close" . $newlinefeed . $newlinefeed;
	            $header .= $query_string;
	            $data = '';
	            @stream_set_timeout($fp, 20);
	            @fputs($fp, $header);
	            $status = @socket_get_status($fp);
	            while (!@feof($fp)&&$status) {
	                $data .= @fgets($fp, 1024);
	                $status = @socket_get_status($fp);
	            }
	            @fclose ($fp);
	        }
	    }
	    if (!$data) {
	        return false;
	    } else {
	        preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
	        $results = array();
	        foreach ($matches[1] AS $k=>$v) {
	            $results[$v] = $matches[2][$k];
	        }
	    }

	    logthis(print_r($results, true));
	    if (!is_array($results)) {
	        die("Invalid License Server Response");
	    }

	    if( empty( $results['md5hash'] ) ) {
	        return false;
	    }

	    if ( ! empty( $results['md5hash'] ) ) {
	        if ($results['md5hash'] != md5($licensing_secret_key . $check_token)) {
	            return false;
	        }
	    }

	    if ($results['status'] == "Active") {
	        $results['checkdate'] = $checkdate;
	        $data_encoded = serialize($results);
	        $data_encoded = base64_encode($data_encoded);
	        $data_encoded = md5($checkdate . $licensing_secret_key) . $data_encoded;
	        $data_encoded = strrev($data_encoded);
	        $data_encoded = $data_encoded . md5($data_encoded . $licensing_secret_key);
	        $data_encoded = wordwrap($data_encoded, 80, "\n", true);
	        $results['localkey'] = $data_encoded;
	    }
	    $results['remotecheck'] = true;

	    unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$md5hash);

		// Return true on valid license
		if ($results["status"] == "Active") {
			return true;
		}

		return false;
	}

	/**
	 * Admin post table view
	 */
	public static function onPostEditView()
	{
		if( "shop_order" == $_REQUEST['post_type'] )
			Orders_Table::modify();
	}

	/**
	 * Add settings
	 *
	 * @hook action 'admin_init'
	 */
	public static function addSettings()
	{
		global $wpdb;

		$page = "woocommerce-pacsoft";

		// General section
		Settings::addSection( [
			'page' => $page,
			'name' => "pacsoft-general",
			'title' => __( "General", self::TEXTDOMAIN )
		] );

		// Account type field
		Settings::addField( [
			'page' => $page,
			'section' => "pacsoft-general",
			'name' => "pacsoft_account_type",
			'title' => __( "Account type", self::TEXTDOMAIN ),
			'type' => "dropdown",
			'options' => [
				[
					'value' => "",
					'label' => __( "Please select...", self::TEXTDOMAIN )
				],
				[
					'value' => "po_SE",
					'label' => "Pacsoft"
				],
				[
					'value' => "ufo_SE",
					'label' => "Unifaun"
				]
			]
		] );

		// User field
		Settings::addField( [
			'page' => $page,
			'section' => "pacsoft-general",
			'name' => "pacsoft_usern_unif",
			'title' => __( "User", self::TEXTDOMAIN )
		] );

		// Password field
		Settings::addField( [
			'page' => $page,
			'section' => "pacsoft-general",
			'name' => "pacsoft_pass_unif",
			'title' => __( "Password", self::TEXTDOMAIN )
		] );

		// License field
		Settings::addField( [
			'page' => $page,
			'section' => "pacsoft-general",
			'name' => "pacsoft_license_key",
			'title' => __( "API license key", self::TEXTDOMAIN )
		] );

		// Services table
		Settings::addField( [
			'page' => $page,
			'section' => "pacsoft-general",
			'title' => __( "Map services", self::TEXTDOMAIN ),
			'type' => "table",
			'name' => "pacsoft_services",
			'table' => [
				'table' => [
					'id' => "pacsoft-services",
					'columns' => [
						[
							'column' => [
								'name' => "shipping-method",
								'title' => __( "Shipping Method", self::TEXTDOMAIN )
							]
						],
						[
							'column' => [
								'name' => "service",
								'title' => __( "Service", self::TEXTDOMAIN )
							]
						],
						[
							'column' => [
								'name' => "sender-quick-value",
								'title' => __( "Sender Quick Value", self::TEXTDOMAIN )
							]
						]
					],
					'rows' => [ __CLASS__, 'getServicesSettings' ],
					'addRowButton' => true,
					'addRowButtonClass' => "addPacsoftServiceRow"
				]
			],
			'description' => __( "NOTE: Remember to set your customer number for each service added in the list above in Pacsoft/Unifaun Admin &#8594; Maintenance &#8594; Senders &#8594; Search (sender quick value) &#8594; Edit<br>", self::TEXTDOMAIN )
		] );

		// FIX: Backward compat
		$old = get_option( 'pacsoft_shipping_type' ); # V. Shevchenko - Move data acquisition variable procedure out of verification of the variable on empty value
		if( !empty( $old  ) ) {
			$services = self::getServices();

			foreach( $services as $service ) {
				if( $old == $service['code'] )
					break;

				unset( $service );
			}

			if( ! empty( $service ) ) {
				$updatedServiceSettings = [
					[
						'default' => '1',
						'description' => "Default",
						'service' => $service['code'],
						'sender_quick_value' => get_option( 'pacsoft_sender_quick_value', 1 ),
						'from' => $service['from'],
						'to' => $service['to']
					]
				];

				update_option( 'pacsoft_services', $updatedServiceSettings );

				$wpdb->query( "DELETE FROM {$wpdb->options} WHERE 1 AND option_name IN ( 'pacsoft_shipping_type', 'pacsoft_sender_quick_value' );" );
			}
		}

		// Default product type
		Settings::addField( [
			'page' => $page,
			'section' => "pacsoft-general",
			'name' => "pacsoft_default_product_type",
			'title' => __( "Default product type", self::TEXTDOMAIN ),
			'default' => "Varor"
		] );

		// Send on order status
		Settings::addField( [
			'page' => $page,
			'section' => "pacsoft-general",
			'name' => "pacsoft_on_order_status",
			'title' => __( "Send on order status", self::TEXTDOMAIN ),
			'type' => "dropdown",
			'options' => [
				[
					'value' => "",
					'label' => __( "Please select...", self::TEXTDOMAIN )
				],
				[
					'value' => "processing",
					'label' => __( "Processing", self::TEXTDOMAIN )
				],
				[
					'value' => "completed",
					'label' => __( "Completed", self::TEXTDOMAIN )
				]
			]
		] );

		// More options field
		Settings::addField( [
			'page' => $page,
			'section' => "pacsoft-general",
			'type' => "checkboxes",
			'title' => __( "More options", self::TEXTDOMAIN ),
			'options' => [
				[
					'name' => "pacsoft_sync_with_options",
					'label' => __( "Show options when syncing (disables auto-sync)", self::TEXTDOMAIN )
				],
				[
					'name' => "pacsoft_addon_sms",
					'label' => __( "Send SMS notification (Addon)", self::TEXTDOMAIN )
				],
				[
					'name' => "pacsoft_addon_enot",
					'label' => __( "Send pre-notification by e-mail (Addon)", self::TEXTDOMAIN )
				],
				[
					'name' => "pacsoft_print_freight_label_per_item",
					'label' =>  __( "Print freight label per item in a box", self::TEXTDOMAIN )
				],
				[
					'name' => "pacsoft_single_package_per_order",
					'label' =>  __( "Send single package per order", self::TEXTDOMAIN )
				],
				[
					'name' => "pacsoft_print_return_labels",
					'label' =>  __( "Add return labels to orders", self::TEXTDOMAIN )
				]
			]
		] );

		// Test mode field
		Settings::addField( [
			'page' => $page,
			'section' => "pacsoft-general",
			'type' => "radio",
			'name' => "pacsoft_test_mode",
			'title' => __( "Test mode", self::TEXTDOMAIN ),
			'options' => [
				[
					'value' => "1",
					'label' => __( "On", self::TEXTDOMAIN )
				],
				[
					'value' => "0",
					'label' => __( "Off", self::TEXTDOMAIN )
				]
			],
			'default' => "0"
		] );
	}

	/**
	 * Get services settings
	 */
	public static function getServicesSettings()
	{
		$settings = get_option( 'pacsoft_services', [] );
		$rows = [];

		if( ! empty( $settings ) ) {
			foreach( $settings as $x => $setting ) {
				if( empty( $setting['shipping_method_id'] ) )
					continue;
				$rows[] = [
					'columns' => [
						[
							'column' => [
								'name' => "id",
								'content' => self::getShippingMethodsDropdown( $setting['shipping_method_id'] )
							]
						],
						[
							'column' => [
								'name' => "service",
								'content' => self::getServicesDropdown( $setting['service'] )
							]
						],
						[
							'column' => [
								'name' => "sender-quick-value",
								'content' => '<input type="text" name="pacsoft_services[sender_quick_value][]" value="' . $setting['sender_quick_value'] . '" placeholder="1"> <a href="#" class="dashicons dashicons-dismiss removeRow"></a>'
							]
						]
					]
				];
			}
		}

		return $rows;
	}

	/**
	 * Get services list
	 */
	public static function getServices()
	{
		static $services = [];

		if( ! empty( $services ) )
			return $services;

		$csv = file_get_contents( self::getPath( 'data/services.csv' ) );
		$rows = explode( "\n", $csv );

		foreach( $rows as $row ) {
			$columns = explode( "\t", $row );
			$title = $columns[ 3 ] . ' (' . $columns[ 2 ] . ')';
			$code = $columns[ 2 ];
			$from = explode( ", ", $columns[ 4 ] );
			$to = explode( ", ", $columns[ 5 ] );
			$packagecode = self::getPackageType( $columns[ 0 ] );

			$services[] = compact( 'code', 'title', 'from', 'to', 'packagecode' );
		}

		return $services;
	}

	/**
	 * Get package type
	 *
	 * @param $service
	 */
	public static function getPackageType( $service )
	{
		switch( $service ) {
			default:
				$packagecode = "";
				break;

			case "BOX":
				$packagecode = "PK";
				break;

			case "CG":
			case "SBTL":
			case "DGF":
			case "DHLAIR":
			case "DHLROAD":
			case "DSVD":
			case "DSVI":
			case "DACHSER":
			case "KK":
			case "FREE":
			case "DTPG":
			case "TNT":
				$packagecode = "PC";
				break;

			case "HIT":
				$packagecode = "PARCEL";
				break;

			case "PP":
			case "PPDK":
			case "PPFI":
				$packagecode = "KXX";
				break;

			case "TP":
				$packagecode = "KLI";
				break;
		}

		return $packagecode;
	}

	/**
	 * Get services dropdown
	 *
	 * @param $selected
	 */
	public static function getServicesDropdown( $selected = '' )
	{
		$select = '<select name="pacsoft_services[service][]"><select>';
		$services = self::getServices();

		$options = array_map( function( $service ) use( $selected ) {
			$selected = ( $service['code'] == $selected ? ' selected="selected"' : '' );

			return '<option value="' . $service['code'] . '"' . $selected . '>' . $service['title'] . '</option>';
		}, $services );

		return '<select name="pacsoft_services[service][]"><option value=""></option>' . join( '', $options ) . '</select>';
	}

	/**
	 * Get WooCommerce Shipping Methods dropdown
	 *
	 * @param $selected
	 */
	public static function getShippingMethodsDropdown( $selected = '' )
	{

		$shippingZones = WC_Shipping_Zones::get_zones();
		
		$shippingMethods = [];

		foreach ($shippingZones as $zone) {
			foreach ($zone['shipping_methods'] as $shippingMethod) {
				$shippingMethods[] = [
					// Be aware that zone_id was incorrectly used after WC 2.6
					// and that we can't remove it since users have these set
					// in their database. In Request.php we explode "id"
					// and take only instance_id manually since this is
					// what WC sets the order shipping to.
					
					// DO NOT change the order of the ":" strings here, it will
					// invalidate the explode() in Request.php
					'id' => $shippingMethod->id . ":" . $zone['zone_id'].':'.$shippingMethod->instance_id,
					'name' => $shippingMethod->title . " - " . $zone['zone_name']
				];
			}
		}
		$classes = WC_Shipping::instance()->get_shipping_classes();
		
		$classesAsOptions = [];
		foreach ($classes as $class) {
			$shippingMethods[] = [
				'id' => $class->taxonomy . ":" . $class->slug . ":" . $class->term_taxonomy_id . ":" . $class->term_id,
				'name' => $class->name
			];
		}

		$options = array_map( function( $shippingMethod ) use( $selected ) {
			$selected = ( $shippingMethod['id'] == $selected ) ? ' selected="selected"' : '';

			return '<option value="' . $shippingMethod['id'] . '"' . $selected . '>' . $shippingMethod['name'] . '</option>';
		}, array_values( $shippingMethods ) );

		return '<select name="pacsoft_services[shipping_method_id][]"><option value=""></option>' . join( '', $options ) . '</select>';
	}

	/**
	 * Filter services settings before we save it
	 *
	 * @param $new
	 * @param $old
	 */
	public static function filterServicesSettings( $new, $old = [] )
	{
		$settings = [];
		$services = self::getServices();

		// FIX: Check format before reformatting
		if( ! isset( $new['shipping_method_id'] ) )
			return $new;

		foreach( $new as $setting => $values ) {
			foreach( $values as $x => $value ) {
				if( ! isset( $setting[ $x ] ) )
					$settings[ $x ] = [];

				$settings[ $x ][ $setting ] = $value;

				if( 'service' == $setting ) {
					foreach( $services as $service ) {
						if( $service['code'] == $value ) {
							$settings[ $x ]['from'] = $service['from'];
							$settings[ $x ]['to'] = $service['to'];
						}
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * Add settings page
	 *
	 * @hook 'admin_menu'
	 */
	public static function addSettingsPage()
	{
		$page = Settings::addPage( [
			'slug' => "woocommerce-pacsoft",
			'title' => __( "WooCommerce Pacsoft/Unifaun integration", self::TEXTDOMAIN ),
			'menu' => __( "Pacsoft/Unifaun", self::TEXTDOMAIN )
		] );
	}

	/**
	 * Add scripts
	 */
	public static function addAdminScripts()
	{
		wp_register_script( 'pacsoft', self::getUrl( 'assets/scripts/admin.js' ), [ 'jquery', 'mustache' ], 1 );

		$mustache = self::getMustache();

		wp_localize_script( 'pacsoft', 'pacsoft', [
			'row' => $mustache->render( 'admin/settings/table-row', [
				'columns' => [
					[
						'column' => [
							'name' => "shipping_method_id",
							'content' => self::getShippingMethodsDropdown()
						]
					],
					[
						'column' => [
							'name' => "service",
							'content' => self::getServicesDropdown()
						]
					],
					[
						'column' => [
							'name' => "sender-quick-value",
							'content' => '<input type="text" name="pacsoft_services[sender_quick_value][]" value="" placeholder="1">  <a href="#" class="dashicons dashicons-dismiss removeRow"></a>'
						]
					]
				]
			] ),
			'notice' => $mustache->getLoader()->load( 'admin/notice' )
		] );

		wp_enqueue_script( 'mustache', self::getUrl( 'assets/scripts/mustache.js' ) );
		wp_enqueue_script( 'pacsoft' );
		wp_enqueue_style( 'pacsoft', self::getUrl( 'assets/styles/admin.css' ) );
	}

	/**
	 * Get Mustache instance (singleton)
	 */
	public static function getMustache()
	{
		static $mustache = null;

		if( empty( $mustache ) ) {
			if( ! class_exists( '\Mustache_Autoloader' ) ) {
				require_once self::getPath( '/vendor/mustache-php/src/Mustache/Autoloader.php' );

				Mustache_Autoloader::register();
			}

			$mustache = new Mustache_Engine( [
				'loader' => new Mustache_Loader_FilesystemLoader( self::getPath( '/assets/templates' ), [
					'extension' => "ms"
				] ),
				'partials_loader' => new Mustache_Loader_FilesystemLoader( self::getPath( '/assets/templates/partials' ), [
					'extension' => "ms"
				] ),
				'cache' => WP_CONTENT_DIR . '/mustache',
				'helpers' => self::getMustacheHelpers()
			] );
		}

		return $mustache;
	}

	/**
	 * Get Mustache helpers
	 */
	public static function getMustacheHelpers()
	{
		$helpers = [];

		/**
		 * i18n
		 *
		 * @param string $text
		 */
		$helpers['i18n'] = function( $text ) {
			$strings = Plugin::getTranslatedStrings();

			if( isset( $strings[ $text ] ) )
				return $strings[ $text ];

			return $text;
		};

		return $helpers;
	}

	/**
	 * Get translated strings (i18n)
	 */
	public static function getTranslatedStrings()
	{
		$strings = [
			'Save changes' => __( "Save changes", self::TEXTDOMAIN ),
		];

		return $strings;
	}

	/**
	 * AJAX sync order
	 */
	public static function ajaxSyncOrder()
	{
		if( empty( $_REQUEST['order_id'] ) )
			die( json_encode( [
				'success' => false,
				'message' => __( "Missing order ID.", self::TEXTDOMAIN )
			] ) );

		if( ! empty( $_REQUEST['service_id'] ) )
			$message = Request::syncOrder( $_REQUEST['order_id'], $_REQUEST['service_id'], true );

		else
			$message = Request::syncOrder( $_REQUEST['order_id'], null, true );

		die( json_encode( $message ) );
	}

	/**
	 * Print order AJAX
	 */
	public static function ajaxPrintOrder()
	{
		if( empty( $_POST['order_id'] ) )
			die( json_encode( [
				'success' => false,
				'message' => __( "Missing order ID.", self::TEXTDOMAIN )
			] ) );

		$message = WC_Pacsoft_Interface::print_order( $_POST['order_id'] );

		die( json_encode( $message ) );
	}
}