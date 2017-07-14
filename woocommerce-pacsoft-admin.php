<?php

if(!defined('TESTING')){
    define('TESTING', true);
}

if ( ! function_exists( 'logthis' ) ) {
    function logthis($msg) {
        if(TESTING){
            if(!file_exists('/tmp/testlog.log')){
                $fileobject = fopen('/tmp/testlog.log', 'a');
                chmod('/tmp/testlog.log', 0666);
            }
            else{
                $fileobject = fopen('/tmp/testlog.log', 'a');
            }

            if(is_array($msg) || is_object($msg)){
                fwrite($fileobject,print_r($msg, true));
            }
            else{
                fwrite($fileobject,date("Y-m-d H:i:s"). "\n" . $msg . "\n");
            }
        }
        else{
            error_log($msg);
        }
    }
}

if ( ! class_exists( 'WC_Pacsoft' ) ) {

    include_once("woocommerce-pacsoft-interface.php");
    include_once("pacsoft-ajax-callbacks.php");
    /**
     * Shortcode function for order trace
     *
     * @access public
     */
    function pacsoft_trace_url_func( ) {
        if(!isset($_GET['order_id'])){ ?>
            <div class="error">
                <p>Ordernummer saknas</p>
            </div>
            <?php
            return;
        }

        $message = WC_Pacsoft_Interface::trace_order($_GET['order_id'], true);
        echo  '<iframe src="' . $message . '" style="width:100%;height:500px;"></iframe>' ;
    }
    add_shortcode( 'pacsoft_trace_url', 'pacsoft_trace_url_func' );

    // in javascript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
    function pacsoft_enqueue(){
        wp_enqueue_script('jquery');
        wp_register_script( 'pacsoft-script', plugins_url( '/woo-pacsoft-unifaun/assets/scripts/pacsoft.js' ) );
        wp_enqueue_script( 'pacsoft-script' );
    }

    add_action( 'admin_enqueue_scripts', 'pacsoft_enqueue' );
    add_action( 'admin_enqueue_scripts', 'load_pacsoft_admin_style' );

    function load_pacsoft_admin_style() {
        //wp_register_style( 'admin_css', plugins_url( '/woocommerce-pacsoft-unifaun/assets/styles/admin-style.css'), false, '1.0.0' );
        //wp_enqueue_style( 'admin_css', plugins_url( '/woocommerce-pacsoft-unifaun/assets/styles/admin-style.css'), false, '1.0.0' );
    }

    class WC_Pacsoft {

        const SERVICE_POSTNORD_MYPACK = 'P19';
        const SERVICE_POSTNORD_VB_1K = 'PUA';
        const SERVICE_POSTNORD_VB_EK = 'PUE';
        const SERVICE_POSTNORD_VB_KL_EK = 'PAG';
        const SERVICE_DB_SCHENKER_PRIVPAK_STANDARD = 'BHP';
        const SERVICE_DHL_SERVICE_POINT = 'ASPO';
        const SERVICE_POSTNORD_COMPANY_PACKAGE = 'P15';

        public static $services = array(

            'PostNord MyPack ' => WC_Pacsoft::SERVICE_POSTNORD_MYPACK,
            'PostNord - Varubrev 1:a klass' => WC_Pacsoft::SERVICE_POSTNORD_VB_1K,
            'PostNord - Varubrev Ekonomi' => WC_Pacsoft::SERVICE_POSTNORD_VB_EK,
            'PostNord - Varubrev Klimatekonomisk' => WC_Pacsoft::SERVICE_POSTNORD_VB_KL_EK,
            'DB SCHENKER privpak - Ombud Standard (1 kolli, <20 kg) ' => WC_Pacsoft::SERVICE_DB_SCHENKER_PRIVPAK_STANDARD,
            'DHL Service Point' => WC_Pacsoft::SERVICE_DHL_SERVICE_POINT,
            'PostNord DPD Företagspaket' => WC_Pacsoft::SERVICE_POSTNORD_COMPANY_PACKAGE,
        );

        public function __construct() {
	        // @see src/Plugin::addSettingsPage
            //add_action( 'admin_menu', array( &$this, 'woocommerce_pacsoft_menu' ));

	        // @see src/Plugin::addSettings
            //call register settings function
            //add_action( 'admin_init', array( &$this, 'register_woocommerce_pacsoft_settings' ));

/*
            $on_order_status = get_option('pacsoft_on_order_status');

            if(!empty($on_order_status)){
                add_action( 'woocommerce_order_status_' . $on_order_status, array(&$this, 'handle_order'), 10, 1 );
            }
            else{
                add_action( 'woocommerce_order_status_completed' , array(&$this, 'handle_order'), 10, 1 );
            }
*/            
            //add_action( 'admin_notices', array( &$this, 'display_admin_notice' ) );
            //add_action( 'admin_notices', array( &$this, 'display_print' ) );
            //add_action( 'admin_notices', array( &$this, 'display_options' ) );
            //add_filter( 'manage_edit-shop_order_columns',  array( &$this, 'pacsoft_order_columns_head'), 20, 1);
            //add_action( 'manage_shop_order_posts_custom_column',  array( &$this, 'pacsoft_order_columns_content'), 10, 2);
        }

        /**
         * WooCommerce Pacsoft Settings
         *
         * @access public
         * @param void
         * @return void
         */
        public function register_woocommerce_pacsoft_settings() {
            //register our settings
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_account_type' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_shipping_type' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_sender_quick_value' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_on_order_status' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_usern_unif' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_pass_unif' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'activate_pacsoft' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_weight_limit' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_lower_weight_service' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_upper_weight_service' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_license_key' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_addon_sms' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_sync_with_options' );
            register_setting( 'woocommerce-pacsoft-settings-group', 'pacsoft_sender_id' );
        }

        /**
         * Adds Pacsoft/Unifaun Column to listing
         *
         * @access public
         * @param $columns
         * @return mixed
         */
        public function pacsoft_order_columns_head($columns){
            $new_columns = (is_array($columns)) ? $columns : array();
            //all of your columns will be added before the actions column
            $new_columns['pacsoft_order_synchronized'] = '<span class="center">Pacsoft/Unifaun</span>';
            $new_columns['pacsoft_synchronize'] = '<span class="center">Synkronisera</span>';
            $new_columns['pacsoft_print'] = '<span class="center">Skriv ut</span>';
            //stop editing

            $new_columns['order_actions'] = $columns['order_actions'];
            return $new_columns;
        }

        /**
         * Renders image for Pacsoft status
         *
         * @access public
         * @param $column_name
         * @param $post_id
         * @return void
         */
        public function pacsoft_order_columns_content($column_name, $post_id) {
            if ($column_name == 'pacsoft_order_synchronized') {
                $synced = get_post_meta($post_id, '_pacsoft_order_synced', true);
                if($synced == 1){ ?>
                    <mark class="pacsoft-status completed" title="Order har synkroniserats"></mark>
                <?php }
                else { ?>
                    <mark class="pacsoft-status not-completed" title="Order har EJ synkroniserats"></mark>
                <?php }
            }
            elseif($column_name == 'pacsoft_synchronize'){
                $ajax_nonce = wp_create_nonce( "pacsoft_woocommerce" );
                if(get_option('pacsoft_sync_with_options') == 'on'){ ?>
                    <button type="button" class="button" title="Exportera" style="margin:5px" onclick="pacsoft_show_options(<?php echo $post_id;?>, '<?php echo $ajax_nonce;?>')">></button>
                <?php }
                else { ?>

                    <button type="button" class="button" title="Exportera" style="margin:5px" onclick="pacsoft_sync_order(<?php echo $post_id;?>, '<?php echo $ajax_nonce;?>')">></button>

                <?php }
            }
            elseif($column_name == 'pacsoft_print'){
                $ajax_nonce = wp_create_nonce( "pacsoft_woocommerce" );?>
                <button type="button" class="button" title="Print" style="margin:5px" onclick="pacsoft_print_order(<?php echo $post_id;?>, '<?php echo $ajax_nonce;?>')">></button>
            <?php
            }
        }

        public function display_admin_notice() {

            $html = '<div id="ajax-pacsoft-notification" class="updated" style="display: none">';
            $html .= '<p id="ajax-pacsoft-message">';
            $html .= '</p>';
            $html .= '</div><!-- /.updated -->';

            echo $html;
        }

        public function display_print() {

            $html = '<div id="ajax-pacsoft-print" style="display: none">';
            $html .= '</div><!-- /.updated -->';

            echo $html;
        }

        public function display_options() {

            $html = '<div id="ajax-pacsoft-options" style="display: none; background-color: #fff; padding: 8px;">';
            $html .= '</div><!-- /.updated -->';

            echo $html;
        }

        /**
         * Admin Menu
         *
         * @access public
         * @param void
         * @return void
         */
        public function woocommerce_pacsoft_menu() {
            add_options_page( 'WooCommerce Pacsoft', 'WooCommerce Pacsoft', 'manage_options', 'pacsoft', array( &$this, 'woocommerce_pacsoft_options' ));
        }

        /**
         * Admin options page
         *
         * @access public
         * @param void
         * @return void
         */
        public function woocommerce_pacsoft_options() {
            if ( !current_user_can( 'manage_options' ) )  {
                wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
            }
            echo '<div class="wrap">';
            echo '<h2>Pacsoft inställningar</h2>';

            echo '<form method="post" action="options.php">';
            settings_fields( 'woocommerce-pacsoft-settings-group' );
            do_settings_sections( 'woocommerce-pacsoft-settings-group' );

            echo '<h3>Pacsoft/Unifaun</h3>';
            echo '<table class="form-table">';
            echo '<tr valign="top">';
            echo '<th scope="row">Användarnamn</th>';
            echo '<td><input type="text" name="pacsoft_usern_unif" value="'. get_option('pacsoft_usern_unif').'" /></td>';
            echo '</tr>';
            echo '<tr valign="top">';
            echo '<th scope="row">Lösenord</th>';
            echo '<td><input type="text" name="pacsoft_pass_unif" value="'. get_option('pacsoft_pass_unif').'" /></td>';
            echo '</tr>';
            echo '</table>';

            echo '<table class="form-table">';
            echo '<tr valign="top">';
            echo '<th scope="row">Kontotyp</th>';
            echo '<td>';?>
            <select name="pacsoft_account_type" >
                <option value=""<?php if(get_option('pacsoft_account_type') == ''){echo 'selected="selected"';}?>>Välj nedan</option>
                <option value="po_SE"<?php if(get_option('pacsoft_account_type') == 'po_SE'){echo 'selected="selected"';}?>>Pacsoft</option>
                <option value="ufo_SE"<?php if(get_option('pacsoft_account_type') == 'ufo_SE'){echo 'selected="selected"';}?>>Unifaun</option>
            </select>
            <?php
            echo '</td></tr>';
            echo '<tr valign="top">';
            echo '<th scope="row">Avsändarens snabbsökvärde</th>';
            echo '<td><input type="text" name="pacsoft_sender_quick_value" value="'. get_option('pacsoft_sender_quick_value').'" /></td>';
            echo '</tr>';

            $pacsoft_sync_with_options = '';
            if(get_option('pacsoft_sync_with_options') == 'on'){
                $pacsoft_sync_with_options = 'checked="checked"';
            }
            echo '<tr valign="top">';
            echo '<th scope="row">Visa alternativ för varje synkning (detta avaktiverar automatisk synkning)</th>';
            echo '<td><input type="checkbox" name="pacsoft_sync_with_options" ' . $pacsoft_sync_with_options. ' /></td>';
            echo '</tr>';

            echo '<th scope="row">Frakttyp</th>';
            echo '<td>';?>
            <select id="pacsoft_shipping_type" name="pacsoft_shipping_type" >
                <option value=""<?php if(get_option('pacsoft_shipping_type') == ''){echo 'selected="selected"';}?>>Välj nedan</option>
                <?php foreach (WC_Pacsoft::$services as $key => $value) {?>
                    <option value="<?php echo $value;?>"<?php if(get_option('pacsoft_shipping_type') == $value){echo 'selected="selected"';}?>><?php echo $key;?></option>
                <?php } ?>
                <option value="custom"<?php if(get_option('pacsoft_shipping_type') == 'custom'){echo 'selected="selected"';}?>>Anpassad</option>
            </select>

            <?php
            echo '</td></tr>';
            echo '</table>';

            echo '<table class="form-table">';
            echo '<tr valign="top">';
            echo '<th scope="row">Skicka order vid status</th>';
            echo '<td>';?>
            <select name="pacsoft_on_order_status" >
                <option value=""<?php if(get_option('pacsoft_on_order_status') == ''){echo 'selected="selected"';}?>>Välj nedan</option>
                <option value="processing"<?php if(get_option('pacsoft_on_order_status') == 'processing'){echo 'selected="selected"';}?>>Bearbetar</option>
                <option value="completed"<?php if(get_option('pacsoft_on_order_status') == 'completed'){echo 'selected="selected"';}?>>Genomförd</option>
            </select>
            <?php
            echo '</td></tr>';
            echo '</table>';

            echo '<h3>Tilläggstjänster</h3>';
            echo '<table class="form-table">';
            echo '<tr valign="top">';
            echo '<th scope="row">SMS Notifikation</th>';

            $addon_sms = '';
            if(get_option('pacsoft_addon_sms') == 'on'){
                $addon_sms = 'checked="checked"';
            }

            echo '<td><input type="checkbox" name="pacsoft_addon_sms"' . $addon_sms .' /></td>';
            echo '</tr>';
            echo '</table>';


            echo '<h3 id="custom-freight-title" style="display: none;">Anpassad Freight Shipping</h3>';
            echo '<table class="form-table" id="custom-freight-table" style="display: none;">';
            echo '<tr valign="top">';
            echo '<th scope="row">Viktgräns(kg)</th>';
            echo '<td><input type="text" name="pacsoft_weight_limit" value="'. get_option('pacsoft_weight_limit').'" /></td>';
            echo '</tr>';
            echo '</td></tr>';

            echo '<th scope="row">Leverantör nedre viktgräns</th>';
            echo '<td>';?>
            <select name="pacsoft_lower_weight_service" >
                <option value=""<?php if(get_option('pacsoft_lower_weight_service') == ''){echo 'selected="selected"';}?>>Välj nedan</option>
                <?php foreach (WC_Pacsoft::$services as $key => $value) {?>
                    <option value="<?php echo $value;?>"<?php if(get_option('pacsoft_lower_weight_service') == $value){echo 'selected="selected"';}?>><?php echo $key;?></option>
                <?php } ?>
            </select>
            <?php
            echo '</td></tr>';
            echo '</td></tr>';

            echo '<th scope="row">Leverantör övre viktgräns</th>';
            echo '<td>';?>
            <select name="pacsoft_upper_weight_service" >
                <option value=""<?php if(get_option('pacsoft_upper_weight_service') == ''){echo 'selected="selected"';}?>>Välj nedan</option>
                <?php foreach (WC_Pacsoft::$services as $key => $value) {?>
                    <option value="<?php echo $value;?>"<?php if(get_option('pacsoft_upper_weight_service') == $value){echo 'selected="selected"';}?>><?php echo $key;?></option>
                <?php } ?>
            </select>
            <?php
            echo '</td></tr>';
            echo '</table>';


            submit_button();
            echo '</form>';
            echo '</div>';
        }

        public function handle_order($order_id){
            WC_Pacsoft_Interface::handle_order($order_id, true);
        }
    }
    $GLOBALS['wc_pacsoft'] = new WC_Pacsoft();
}
