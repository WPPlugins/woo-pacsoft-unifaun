<?php
/**
 * Created by PhpStorm.
 * User: tomas
 * Date: 2/6/14
 * Time: 9:02 AM
 */

class WCPacsoftXMLDocument {

    /** @public String XML */
    public $xml;

    /** @public String version */
    var $ver;

    /** @public String Charset */
    var $charset;

    /**
     *
     */
    function __construct() {
        $this->ver = '1.0';
        $this->charset = 'UTF-8';
    }

    /**
     *
     * generates XML Document
     *
     * @access public
     * @param $root
     * @param $order_id
     * @param $service_type
     * @return String
     */
    function generate($root, $order_id, $service_type = false) {
        $this->xml = new XmlWriter();
        $this->xml->openMemory();
        $this->xml->startDocument($this->ver,$this->charset);
        $this->xml->startElement($root);
        $this->write_order($order_id, $service_type);
        $this->xml->endDocument();
        $xml = $this->xml->outputMemory(true);
        $this->xml->flush();
        return $xml;
    }

    /**
     *
     * generates an XML Node with an attribute
     *
     * @access public
     * @param $node_name
     * @param $node_value
     * @param $attribute_name
     * @param $attribute_value
     * @return void
     */
    function write_node($node_name, $node_value, $attribute_name, $attribute_value ){
        $this->xml->startElement($node_name);
        $this->xml->writeAttribute($attribute_name, $attribute_value);
        $this->xml->writeElement($node_name, $node_value);
        $this->xml->endElement();
    }

    /**
     *
     * generates an default XML Node with an attribute
     *
     * @access public
     * @param $node_value
     * @param $attribute_value
     * @return void
     */
    function write_val_node($node_value, $attribute_value ){
        $this->xml->startElement('val');
        $this->xml->writeAttribute('n', $attribute_value);
        $this->xml->text($node_value);
        $this->xml->endElement();
    }

    /**
     *
     * generates order XML Document
     *
     * @access public
     * @param $order_id
     * @return void
     */
    function write_order($order_id, $service_type = false){
        $order = new WC_Order($order_id);
        $services = get_option( 'pacsoft_services', [] );
        $default = '';
        
        if( empty( $services ) )
        	die( "No services are set. Please check your settings." );
        
        foreach( $services as $service ) {
	        if( $service['default'] )
	        	$default = $service;
	        
	        if( in_array( '*', $service['to'] ) || in_array( $order->shipping_country, $service['to'] ) )
	        	break;
	        
	        unset( $service );
        }
        
        if( empty( $default ) )
        	$default = $service[ 0 ];
        
        if( empty( $service ) )
        	$service = $default;
        
        if( get_option( 'pacsoft_test_mode' ) ) {
	        $this->xml->startElement( 'meta' );
	        $this->write_val_node( "YES", "test" );
	        $this->xml->endElement();
        }
        
        //receiver
        $this->xml->startElement('receiver');
        $this->xml->writeAttribute( 'rcvid', "6565" );
        $this->write_val_node($order->billing_first_name . " " . $order->billing_last_name, 'name');
        $this->write_val_node($order->shipping_address_1, 'address1');
        $this->write_val_node($order->shipping_postcode, 'zipcode');
        $this->write_val_node($order->shipping_city, 'city');
        $this->write_val_node($order->shipping_country, 'country');
        $this->write_val_node($order->billing_email, 'email');
        $this->write_val_node($order->billing_phone, 'phone');
        $this->write_val_node($order->billing_phone, 'sms');
        $this->write_val_node('Support', 'contact');
        $this->xml->endElement();

        //shipment
        $this->xml->startElement('shipment');
        $this->xml->writeAttribute('orderno', $order->id);
        
        if( ! empty( $service ) && ! empty( $service['sender_quick_value'] ) )
            $this->write_val_node( $service['sender_quick_value'], 'from' );
        else
            $this->write_val_node( '1', 'from' );

        $this->write_val_node( "6565", 'to' );
        $this->write_val_node( get_option( 'pacsoft_reference' ), 'reference' );
        $this->write_val_node( get_option( 'pacsoft_reference_text' ), 'freetext1' );

        //service
        $this->xml->startElement('service');
		
		if( $service_type )
			$this->xml->writeAttribute( 'srvid', $service_type );
		else
			$this->xml->writeAttribute( 'srvid', $service['service'] );
		
        //SMS Notification
        if(get_option('pacsoft_addon_sms') == '1'){
            $this->xml->startElement('addon');
            $this->xml->writeAttribute('adnid', 'NOTSMS');
            $this->write_val_node($order->billing_phone, 'misc');
            $this->xml->endElement();
        }

        $this->xml->fullEndElement();

        $this->xml->startElement('container');

        $weigth = $this->get_weight($order);
        if($weigth < 0.151){
            $this->write_val_node(0.151, 'weight');
        }
        else{
            $this->write_val_node($weigth, 'weight');
        }
        
        if( get_option( 'pacsoft_multiple_parcels' ) ) {
	        $copies = 0;
	        
	        foreach( $order->get_items( "line_item" ) as $item )
	        	$copies += $item['item_meta']['_qty'][ 0 ];
	        
	        $this->write_val_node( $copies, 'copies' );
        }
        else
	        $this->write_val_node( $order->get_item_count(), 'copies' );
        
        $this->write_val_node($this->get_package_type($service_type), 'packagecode');
        $this->write_val_node( get_option( 'pacsoft_default_product_type', "Varor" ), 'contents');

        $this->xml->endElement();
        $this->xml->endElement();
    }

    function write_addons($order){
        //SMS Notification

        $this->xml->startElement('addon');
        $this->xml->writeAttribute('adnid', 'NOTSMS');
        $this->write_val_node($order->billing_phone, 'misc');
        $this->xml->endElement();
        $this->xml->endElement();
    }

    /**
     *
     * calculates weight of order
     *
     * @access public
     * @param $order
     * @return float
     */
    function get_weight($order){
        $weight = 0;
        if ( sizeof( $order->get_items() ) > 0 ) {
            foreach( $order->get_items() as $item ) {
                if ( $item['product_id'] > 0 ) {
                    $_product = $order->get_product_from_item( $item );
                    if ( ! $_product->is_virtual() ) {
                        $weight += $_product->get_weight() * $item['qty'];
                    }
                }
            }
        }
        logthis( wc_get_weight($weight, 'kg'));
        return wc_get_weight($weight, 'kg');
    }

    /**
     *
     * returns package type
     *
     * @access public
     * @param $order
     * @return float
     */
    function get_package_type($service_type){

        if(!$service_type){
            $service_type = get_option('pacsoft_shipping_type');
        }


        switch($service_type){
            case WC_Pacsoft::SERVICE_POSTNORD_MYPACK:
            case WC_Pacsoft::SERVICE_POSTNORD_VB_1K:
            case WC_Pacsoft::SERVICE_POSTNORD_VB_EK:
            case WC_Pacsoft::SERVICE_POSTNORD_VB_KL_EK:
                return '';
                break;
            case WC_Pacsoft::SERVICE_DB_SCHENKER_PRIVPAK_STANDARD:
                return 'CH';
                break;
        }
        return '';
    }
}