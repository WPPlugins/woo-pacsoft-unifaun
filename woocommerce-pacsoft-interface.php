<?php
/**
 * Created by PhpStorm.
 * User: tomas
 * Date: 4/9/15
 * Time: 5:31 PM
 */

class WC_Pacsoft_Interface {
    /** @private String $sync_url */
    private static $sync_url = 'https://www.unifaunonline.se/ufoweb/order?session=';

    /** @private Array $trace_urls */
    private static $trace_urls = array(
        'ufo_SE' => 'https://www.unifaunonline.com/ext.po.se.se.track?key=',
        'po_SE' => 'https://www.pacsoftonline.com/ext.po.se.se.track?key='
    );

    /** @private Array $print_urls */
    private static $print_urls = array(
        'ufo_SE' => 'https://www.unifaunonline.com/ext.uo.se.se.StartEmbeddedShipmentJob?Login=',
        'po_SE' => 'https://www.pacsoftonline.com/ext.uo.se.se.StartEmbeddedShipmentJob?Login='
    );

    /**
     * Sends order to Unifaun/Pacsoft in XML - format with curl
     *
     * @access public
     * @param $order_id
     * @param $service_type
     * @param $forced
     * @return mixed
     */
    public static function handle_order($order_id, $service_type = false, $forced = false){
        if(!WC_Pacsoft_Interface::create_license_validation_request()){
            return array(
                'success'=> false,
                'message'=> 'Licens är ej giltig'
            );
        }
        
        if( ! $forced && get_post_meta( $order_id, '_pacsoft_order_synced', true ) )
            return;

        include_once("class-woocommerce-pacsoft-xml-document.php");
        $url = WC_Pacsoft_Interface::$sync_url 
        	. get_option('pacsoft_account_type') 
        	. "&user=" . get_option('pacsoft_usern_unif') 
        	. '&pin=' . get_option('pacsoft_pass_unif') 
        	. '&type=XML&developerid=0020012792';
        $ch = curl_init();

        $doc = new WCPacsoftXMLDocument ();
        $xml = $doc->generate('data', $order_id, $service_type);

die( var_dump( $xml ) );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_POST, 1);

        logthis(print_r($xml, true));
        $result = curl_exec($ch);
        curl_close( $ch );

        $arrayData = json_decode(json_encode(simplexml_load_string($result)), true);

        logthis(print_r($arrayData, true));

        if($arrayData['val'][1] == 201){
            update_post_meta($order_id, '_pacsoft_order_synced', 1);
            return array(
                'success'=> true,
                'message'=> 'Order skickad till Pacsoft'
            );
        }
        
        return array(
            'success'=> false,
            'message'=> 'Något gick fel'
        );
    }

    /**
     * Sends order to Unifaun/Pacsoft in XML - format with curl
     *
     * @access public
     * @param $order_id
     * @return mixed
     */
    public static function trace_order($order_id){

        $url = WC_Pacsoft_Interface::$trace_urls[get_option('pacsoft_account_type')] . get_option('pacsoft_usern_unif') . '&order=' . $order_id;
        return $url;
    }


    public static function print_order($order_id){
        if(!WC_Pacsoft_Interface::create_license_validation_request()){
//                return array(
//                    'success'=> false,
//                    'message'=> 'Licens är ej giltig'
//                );
        }

        $url = WC_Pacsoft_Interface::$print_urls[get_option('pacsoft_account_type')] . get_option('pacsoft_usern_unif') . '&Pass=' . get_option('pacsoft_pass_unif') . '&Stage=PRINT&OrderNo=' . $order_id . '&ReturnUrl=';
        return array('url'=> $url);
    }

    /**
     * Creates a HttpRequest and appends the given XML to the request and sends it For license key
     *
     * @access public
     * @param string $localkey
     * @return bool
     */
    public static function create_license_validation_request($localkey=''){
        $license_key = get_option('pacsoft_license_key');

        if(!isset($license_key)){
            return false;
        }

        // -----------------------------------
        //  -- Configuration Values --
        // -----------------------------------
        // Enter the url to your WHMCS installation here
        //$whmcsurl = 'http://176.10.250.47/whmcs/';
        $whmcsurl = 'http://whmcs.onlineforce.net/';
        // Must match what is specified in the MD5 Hash Verification field
        // of the licensing product that will be used with this check.
        $licensing_secret_key = 'ak4763';
        //$licensing_secret_key = 'itservice';
        // The number of days to wait between performing remote license checks
        $localkeydays = 15;
        // The number of days to allow failover for after local key expiry
        $allowcheckfaildays = 5;

        // -----------------------------------
        //  -- Do not edit below this line --
        // -----------------------------------

        $check_token = time() . md5(mt_rand(1000000000, 9999999999) . $license_key);
        $checkdate = date("Ymd");
        $domain = $_SERVER['SERVER_NAME'];
        $usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];

        $dirpath = dirname(__FILE__);
        $verifyfilepath = 'modules/servers/licensing/verify.php';
        $localkeyvalid = false;
        if ($localkey) {
            $localkey = str_replace("\n", '', $localkey); # Remove the line breaks
            $localdata = substr($localkey, 0, strlen($localkey) - 32); # Extract License Data
            $md5hash = substr($localkey, strlen($localkey) - 32); # Extract MD5 Hash
            if ($md5hash == md5($localdata . $licensing_secret_key)) {
                $localdata = strrev($localdata); # Reverse the string
                $md5hash = substr($localdata, 0, 32); # Extract MD5 Hash
                $localdata = substr($localdata, 32); # Extract License Data
                $localdata = base64_decode($localdata);
                $localkeyresults = unserialize($localdata);
                $originalcheckdate = $localkeyresults['checkdate'];
                if ($md5hash == md5($originalcheckdate . $licensing_secret_key)) {
                    $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $localkeydays, date("Y")));
                    if ($originalcheckdate > $localexpiry) {
                        $localkeyvalid = true;
                        $results = $localkeyresults;
                        $validdomains = explode(',', $results['validdomain']);
                        if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                        $validips = explode(',', $results['validip']);
                        if (!in_array($usersip, $validips)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                        $validdirs = explode(',', $results['validdirectory']);
                        if (!in_array($dirpath, $validdirs)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = "Invalid";
                            $results = array();
                        }
                    }
                }
            }
        }
        if (!$localkeyvalid) {
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
                $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($localkeydays + $allowcheckfaildays), date("Y")));
                if ($originalcheckdate > $localexpiry) {
                    $results = $localkeyresults;
                } else {
                    $results = array();
                    $results['status'] = "Invalid";
                    $results['description'] = "Remote Check Failed";
                    return $results;
                }
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
	            $results['status'] = "Invalid";
                $results['description'] = "MD5 Checksum Verification Failed. Invalid API license key?";
                
                return $results;
            }
            
            if ( ! empty( $results['md5hash'] ) ) {
                if ($results['md5hash'] != md5($licensing_secret_key . $check_token)) {
                    $results['status'] = "Invalid";
                    $results['description'] = "MD5 Checksum Verification Failed";
                    return $results;
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
        }

        unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$localkeydays,$allowcheckfaildays,$md5hash);
        return $results['status'] == 'Active' ? true : false;
    }
}