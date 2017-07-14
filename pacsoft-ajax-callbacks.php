<?php
/**
 * Created by PhpStorm.
 * User: tomas
 * Date: 6/5/15
 * Time: 9:21 AM
 */

/**
 * Called with AJAX. Syncs order to Pacsoft
 *
 * @access public
 * @param void
 * @return void
 */
function pacsoft_sync_order_callback() {
    global $wpdb; // this is how you get access to the database

    logthis("pacsoft_sync_order_callback");
    ob_start();
    if(isset($_POST['service_id'])){
        //$message = WC_Pacsoft_Interface::handle_order($_POST['order_id'], $_POST['service_id'], true);
        $message = Wetail\Pacsoft\Request::syncOrder( $_POST['order_id'], $_POST['service_id'], true );
    }
    else{
        check_ajax_referer( 'pacsoft_woocommerce', 'security' );
        //$message = WC_Pacsoft_Interface::handle_order($_POST['order_id'], false, true);
        $message = Wetail\Pacsoft\Request::syncOrder( $_POST['order_id'], null, true );
    }
    
    ob_end_clean();
    echo json_encode($message);
    die(); // this is required to return a proper result
}

//add_action( 'wp_ajax_pacsoft_sync_order', 'pacsoft_sync_order_callback' );

/**
 * Called with AJAX. Returns  print iframe from Pacsoft
 *
 * @access public
 * @param void
 * @return void
 */
function pacsoft_print_order_callback() {
    global $wpdb; // this is how you get access to the database
    check_ajax_referer( 'pacsoft_woocommerce', 'security' );
    ob_start();
    include_once("woocommerce-pacsoft-interface.php");
    $message = WC_Pacsoft_Interface::print_order($_POST['order_id'], true);
    ob_end_clean();
    echo json_encode($message);;
    die(); // this is required to return a proper result
}

//add_action( 'wp_ajax_pacsoft_print_order', 'pacsoft_print_order_callback' );

/**
 * Called with AJAX. Returns available services
 *
 * @access public
 * @param void
 * @return void
 */
function pacsoft_get_services_callback() {
    echo json_encode(WC_Pacsoft::$services);
    die(); // this is required to return a proper result
}

//add_action( 'wp_ajax_pacsoft_get_services', 'pacsoft_get_services_callback' );