<?php

namespace TelinfyMessaging\Controllers;

// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use TelinfyMessaging\Api\telinfy_whatsapp_connector;

require_once( ABSPATH . 'wp-admin/includes/file.php' );

class telinfy_admin_controller {

    protected static $instance = null;
    public $connector;
    
    public static function telinfy_get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }


    /**
     * Initiate admin actions for updating settings in WooCommerce
     *
     * @return void
     */
    public function init() {

        
        add_filter('woocommerce_settings_tabs_array',array($this,'telinfy_add_settings_tab'),50);
        add_action('woocommerce_settings_tabs_settings_telinfy_messaging',  array($this,'telinfy_settings_tab'),50);
        add_action('woocommerce_update_options_settings_telinfy_messaging', array($this,'telinfy_update_settings'),50);
        add_action('woocommerce_admin_order_data_after_order_details', array($this,'telinfy_add_custom_checkbox_to_order_admin'), 10, 1);
        //Custom feild hooks
        add_action('woocommerce_admin_field_file',array($this,'telinfy_add_file_upload'),10,1);
        add_action('woocommerce_admin_field_button',array($this,'telinfy_add_cred_check_button'),10,1);
        // Ajax hooks
        add_action('wp_ajax_telinfy_tm_check_cred', array($this,'telinfy_tm_check_cred'));
        add_action('wp_ajax_nopriv_telinfy_tm_check_cred', array($this,'telinfy_tm_check_cred'));

        add_action('wp_ajax_telinfy_tm_list_templates', array($this,'telinfy_tm_list_templates'));
        add_action('wp_ajax_nopriv_telinfy_tm_list_templates', array($this,'telinfy_tm_list_templates'));

        add_action('admin_head', array($this,'telinfy_custom_css'));
    }
    
    /**
     * Adds new settings tab for the telinfy messaging plugin on the WooCommerce settings page
     *
     * @return array
     */
    public static function telinfy_add_settings_tab( $settings_tabs ) {
        $settings_tabs['settings_telinfy_messaging'] = __( 'Telinfy Messaging', 'woocommerce-settings-telinfy-integration' );
        return $settings_tabs;
    }

    /**
     * Ajax function to validate the creditials
     *
     * @return void
     */

    public function telinfy_tm_check_cred(){


        check_ajax_referer( 'telinfy_check_cred', 'security' );
        $post_data = $this->telinfy_tm_sanitize_post_data();

        $api_key = $post_data['username'];
        $apiEndpoint = $post_data['apiEndpoint'];
        $type = $post_data['type'];
        if($api_key && $apiEndpoint){

            if($type == "whatsapp-config"){
                $this->connector = telinfy_whatsapp_connector::telinfy_get_instance();
                $result = $this->connector->telinfy_get_api_token($apiEndpoint,$api_key);
                error_log(json_encode($result));

                if(isset($result['status']) && $result['status'] == "success"){

                    update_option("wc_settings_telinfy_messaging_api_key_whatsapp",$api_key,true);
                    update_option("wc_settings_telinfy_messaging_api_base_url_whatsapp",$apiEndpoint,true);

                    echo wp_json_encode(array("status" =>"success"));
                }else{
                    echo wp_json_encode(array("status" =>"failed"));
                }
            }else{
                echo wp_json_encode(array("status" =>"failed"));
            }
        }

        // Always remember to exit after handling AJAX
        wp_die();

    }

     /**
     * Ajax function to render templates
     *
     * @return void
     */

    public function telinfy_tm_list_templates(){

        check_ajax_referer( 'telinfy_check_cred', 'security' );
        $post_data = $this->telinfy_tm_sanitize_post_data();

        $type = $post_data['type'];

        if($type == "whatsapp-config"){
            $this->connector = telinfy_whatsapp_connector::telinfy_get_instance();
            sleep(2);
            $whatsapp_template_list = $this->connector->telinfy_get_whatsapp_templates();
            if(is_array($whatsapp_template_list)){
                array_unshift($whatsapp_template_list,"select a template");
            }
            
            $whatsapp_selected = array();

            $whatsapp_selected["wc_settings_telinfy_messaging_whatsapp_template_name_order_confirmation"] = get_option("wc_settings_telinfy_messaging_whatsapp_template_name_order_confirmation");
            $whatsapp_selected["wc_settings_telinfy_messaging_whatsapp_template_name_order_cancellation"] = get_option("wc_settings_telinfy_messaging_whatsapp_template_name_order_cancellation");
            $whatsapp_selected["wc_settings_telinfy_messaging_whatsapp_template_name_order_refund"] = get_option("wc_settings_telinfy_messaging_whatsapp_template_name_order_refund");
            $whatsapp_selected["wc_settings_telinfy_messaging_whatsapp_template_name_order_notes"] = get_option("wc_settings_telinfy_messaging_whatsapp_template_name_order_notes"); 
            $whatsapp_selected["wc_settings_telinfy_messaging_whatsapp_template_name_order_shipment"] = get_option("wc_settings_telinfy_messaging_whatsapp_template_name_order_shipment"); 
            $whatsapp_selected["wc_settings_telinfy_messaging_whatsapp_template_name_other_order_status"] = get_option("wc_settings_telinfy_messaging_whatsapp_template_name_other_order_status"); 
            $whatsapp_selected["wc_settings_telinfy_messaging_whatsapp_template_name_abandoned_cart"] = get_option("wc_settings_telinfy_messaging_whatsapp_template_name_abandoned_cart"); 


            if(isset($whatsapp_template_list)){

                echo wp_json_encode(array("status" =>"success","data" => $whatsapp_template_list,"selected" => $whatsapp_selected));
            }else{
                echo wp_json_encode(array("status" =>"failed"));
            }
        }else{
            echo wp_json_encode(array("status" =>"failed"));
        }

        // Always remember to exit after handling AJAX
        wp_die();

    }


    /**
     * Adds new plugin settings to the `Telinfy messaging` tab on the WooCommerce settings page
     *
     * @return void
     */
    public function telinfy_settings_tab() {

        $settings = self::telinfy_get_settings();
        woocommerce_admin_fields( $settings );

        // Add this line to display the file upload field
        wp_enqueue_script(
            'telinfy-cred-check',
            plugins_url( 'includes/assets/js/telinfy-check-cred.js', TELINFY_WOOCOMMERCE_INCLUDES_PATH ) ,
            array( 'jquery' ),
            TELINFY_ABANDON_VER,
            true
        );

        $vars = array(
            'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
            '_nonce'                    => wp_create_nonce( 'telinfy_check_cred' ),
            '_post_id'                  => get_the_ID(),
            'enable_ca_tracking'        => true,
        );

        wp_localize_script( 'telinfy-cred-check', 'telinfy_cred_vars', $vars );

    }

    /**
     * Add the file upload in the settings for WhatsApp
     *
     * @return void
     */

    public function telinfy_add_file_upload($value){

        $default_img = get_option('wc_settings_telinfy_messaging_whatsapp_file_upload');
    ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="wc_settings_telinfy_messaging_whatsapp_file_upload"><?php esc_html_e( 'Default header image', 'woocommerce-settings-telinfy-integration' ); ?></label>
            </th>
            <td class="forminp">
                <?php if($default_img){ ?>
                    <a href="<?php echo esc_url($default_img)?>" class="whatsapp-config"><img src="<?php echo esc_url($default_img)?>" alt="noimages" width="50px" height="50px" style="display:block" /></a>
                <?php }else{echo "<p style='display:block'>Image is not available</p>";}?>
                <input type="file" class="whatsapp-config" name="wc_settings_telinfy_messaging_whatsapp_file_upload" id="wc_settings_telinfy_messaging_whatsapp_file_upload">
                <p class="description"><?php esc_html_e( 'Upload your file here.', 'woocommerce-settings-telinfy-integration' ); ?></p>
            </td>
        </tr>
    <?php
    }

    /**
     * Add custom button for validating the credentials
     *
     * @return void
     */

    public function telinfy_add_cred_check_button($value){

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                
            </th>
            <td class="forminp">
                <span id="<?php echo esc_attr($value["custom_attributes"]["custom_id"])?>-error" style="display: block;margin-bottom: 5px;"></span>
                <button id=<?php echo esc_attr($value['id'])?> class=<?php echo esc_attr($value['class'])?>><?php echo esc_html($value['name'])?></button>
            </td>
        </tr>
    <?php

    }

    /**
     * Updates settings in the database
     *
     * @return void
     */
    public function telinfy_update_settings() {

        $settings_data = self::telinfy_get_settings();

        $uploaded_file = $_FILES['wc_settings_telinfy_messaging_whatsapp_file_upload'];
        if ($uploaded_file["name"] !="") {

            error_log(json_encode($uploaded_file));
            $allowed_mime_types = array('image/jpeg', 'image/png', 'image/gif');
            $file_type = wp_check_filetype($uploaded_file['name'], array('jpg|jpeg|jpe' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png'));
            error_log($file_type['type']);
            if (in_array($file_type['type'], $allowed_mime_types)) {

                $upload_overrides = array('test_form' => false);
                $movefile = wp_handle_upload($uploaded_file, $upload_overrides);
            
                if ($movefile && !isset($movefile['error'])) {
                    $file_url = $movefile['url'];
                    $file_url = esc_url($file_url);
                    update_option('wc_settings_telinfy_messaging_whatsapp_file_upload', $file_url);
                } else {
                    $error_message = isset($movefile['error']) ? $movefile['error'] : 'Unknown error during file upload.';
                    error_log($error_message);
                }
            } else {
                $error_message = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_mime_types);
                error_log($error_message);
            }
        }
        woocommerce_update_options($settings_data);

    }


    /**
     * Get an array of available settings for the plugin
     *
     * @return array
    */
    public function telinfy_get_settings() {

        $this->connector = telinfy_whatsapp_connector::telinfy_get_instance();
        $whatsapp_template_list=[];
        $whatsapp_template_list = $this->connector->telinfy_get_whatsapp_templates();
        if(is_array($whatsapp_template_list)){
            array_unshift($whatsapp_template_list,"select a template");
        }else{
            $whatsapp_template_list = array("select a template");
        }

        $country_code_list = $this->get_country_code_array();
        
        // Configurations for whatsapp integration

        $settings_whatsapp = array(
            'telinfy_section_title_telinfy_whatsapp' => array(
                'name'     => __( 'WhatsApp integration settings', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'title',
                'desc'     => 'Please add configuration to enable WhatsApp messaging services. <a href="https://www.greenadsglobal.com/whatsapp-business-api-pricing/" target="_blank">Click here</a> for purchase plans',
                'id'       => 'wc_settings_telinfy_messaging_section_title_whatsapp'
            ),
            'telinfy_api_base_url' => array(
                'name'     => __( 'API Base URL', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'text',
                'desc'     => __( '' ),
                'id'       => 'wc_settings_telinfy_messaging_api_base_url_whatsapp'
            ),
            'telinfy_api_key_whatsapp' => array(
                'name'     => __( 'Api Key', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'text',
                'desc'     => __( '' ),
                'id'       => 'wc_settings_telinfy_messaging_api_key_whatsapp'
            ),
            'telinfy_default_country_code' => array(
                'name'     => __( 'Default Country Code', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'select',
                'options'  => $country_code_list,
                'desc'     => __( ''),
                'class'    => 'whatsapp-config',
                'id'       => 'wc_settings_telinfy_messaging_default_country_code'
            ),
            'telinfy_whatsapp_check_cred' => array(
                'name'     => __( 'Validate credentials', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'button',
                'desc'     => __( ''),
                'custom_attributes'=> array("custom_id" =>'whatsapp-config'),
                'class'    => 'button-primary',
                'id'       => 'wc_settings_telinfy_messaging_whatsapp_cred_check'
            ),
            'telinfy_whatsapp_template_name_order_confirmation' => array(
                'name'     => __( 'Template for order confirmation', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'select',
                'options'  => $whatsapp_template_list,
                'desc'     => __( ''),
                'class'    => 'whatsapp-config',
                'id'       => 'wc_settings_telinfy_messaging_whatsapp_template_name_order_confirmation'
            ),
            'telinfy_whatsapp_template_name_order_cancellation' => array(
                'name'     => __( 'Template for order cancellation', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'select',
                'options'  => $whatsapp_template_list,
                'desc'     => __( ''),
                'class'    => 'whatsapp-config',
                'id'       => 'wc_settings_telinfy_messaging_whatsapp_template_name_order_cancellation'
            ),
            'telinfy_whatsapp_template_name_order_refund' => array(
                'name'     => __( 'Template for order refund', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'select',
                'options'  => $whatsapp_template_list,
                'desc'     => __( ''),
                'class'    => 'whatsapp-config',
                'id'       => 'wc_settings_telinfy_messaging_whatsapp_template_name_order_refund'
            ),
            'telinfy_whatsapp_template_name_order_notes' => array(
                'name'     => __( 'Template for order notes', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'select',
                'options'  => $whatsapp_template_list,
                'desc'     => __( ''),
                'class'    => 'whatsapp-config',
                'id'       => 'wc_settings_telinfy_messaging_whatsapp_template_name_order_notes'
            ),
            'telinfy_whatsapp_template_name_order_shipment' => array(
                'name'     => __( 'Template for order shipment', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'select',
                'options'  => $whatsapp_template_list,
                'desc'     => __( ''),
                'class'    => 'whatsapp-config',
                'id'       => 'wc_settings_telinfy_messaging_whatsapp_template_name_order_shipment'
            ),
            'telinfy_whatsapp_template_name_other_order_status' => array(
                'name'     => __( 'Template for other order status', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'select',
                'options'  => $whatsapp_template_list,
                'desc'     => __( ''),
                'class'    => 'whatsapp-config',
                'id'       => 'wc_settings_telinfy_messaging_whatsapp_template_name_other_order_status'
            ),
            'telinfy_whatsapp_template_name_abandoned_cart' => array(
                'name'     => __( 'Template for abandoned cart', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'select',
                'options'  => $whatsapp_template_list,
                'desc'     => __( ''),
                'class'    => 'whatsapp-config',
                'id'       => 'wc_settings_telinfy_messaging_whatsapp_template_name_abandoned_cart'
            ),
            'telinfy_whatsapp_language' => array(
                'name'     => __( 'Language code', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'text',
                'desc'     => __( ''),
                'class'    => 'whatsapp-config',
                'id'       => 'wc_settings_telinfy_messaging_whatsapp_language'
            ),
            'telinfy_whatsapp_file_upload' => array(
                'name'     => __( 'File Upload', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'file',
                'desc'     => __( 'Upload your file here.', 'woocommerce-settings-telinfy-integration' ),
                'class'    => 'whatsapp-config',
                'id'       => 'wc_settings_telinfy_messaging_whatsapp_file_upload'
            ),
            'telinfy_checkbox_order_confirmation_whatsapp' => array(
                'title'         => __( 'Order confirmation', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_order_confirmation_whatsapp',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'default'       => 'no',
                'class'         => 'whatsapp-config',
                'tooltip'       => 'Check this box to enable the WhatsApp messaging for order confirmation',
                'autoload'      => false,
            ),
            'telinfy_checkbox_order_cancellation_whatsapp' => array(
                'title'         => __( 'Order cancellation', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_order_cancellation_whatsapp',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'default'       => 'no',
                'class'         => 'whatsapp-config',
                'tooltip'       => 'Check this box to enable the WhatsApp messaging for order cancellation',
                'autoload'      => false,
            ),
            'telinfy_checkbox_order_refund_whatsapp' => array(
                'title'         => __( 'Order refund', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_order_refund_whatsapp',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'default'       => 'no',
                'class'         => 'whatsapp-config',
                'tooltip'       => 'Check this box to enable the WhatsApp messaging for order refund',
                'autoload'      => false,
            ),
            'telinfy_checkbox_order_notes_whatsapp' => array(
                'title'         => __( 'Order notes', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_order_notes_whatsapp',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'default'       => 'no',
                'class'         => 'whatsapp-config',
                'tooltip'       => 'Check this box to enable the WhatsApp messaging for order notes',
                'autoload'      => false,
            ),
            'telinfy_checkbox_order_shipment_whatsapp' => array(
                'title'         => __( 'Order shipment', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_order_shipment_whatsapp',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'default'       => 'no',
                'class'         => 'whatsapp-config',
                'tooltip'       => 'Check this box to enable the WhatsApp messaging for order shipment',
                'autoload'      => false,
            ),
            'telinfy_checkbox_other_order_status_whatsapp' => array(
                'title'         => __( 'Other order status', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_other_order_status_whatsapp',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'default'       => 'no',
                'class'         => 'whatsapp-config',
                'tooltip'       => 'Check this box to enable the WhatsApp messaging for all other order status change',
                'autoload'      => false,
            ),
            'telinfy_checkbox_order_abandoned_cart_whatsapp' => array(
                'title'         => __( 'Abandoned cart', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_abandoned_cart_whatsapp',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'default'       => 'no',
                'class'         => 'whatsapp-config',
                'tooltip'       => 'Check this box to enable the WhatsApp messaging for abandoned cart',
                'autoload'      => false,
            ),
            'telinfy_section_end_whatsapp' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_settings_telinfy_section_end_whatsapp'
            )
        );

        // Configurations for sms integration

        $settings_sms = array(
            'telinfy_section_title_sms' => array(
                'name'     => __( 'SMS integration settings', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'title',
                'desc'     => 'Please add configuration to enable the SMS services. <a href="https://www.greenadsglobal.com/sms-pricing/" target="_blank">Click here</a> for purchase plans',
                'id'       => 'wc_settings_telinfy_messaging_section_title_sms'
            ),
            'telinfy_api_key_sms' => array(
                'name' => __( 'Username', 'woocommerce-settings-telinfy-integration' ),
                'type' => 'text',
                'desc' => __( '' ),
                'id'   => 'wc_settings_telinfy_messaging_api_key_sms'
            ),
            'telinfy_api_secret_sms' => array(
                'name' => __( 'Password', 'woocommerce-settings-telinfy-integration' ),
                'type' => 'password',
                'desc' => __( '',),
                'id'   => 'wc_settings_telinfy_messaging_api_secret_sms'
            ),
            'telinfy_messaging_sms_sender_name' => array(
                'title'         => __( 'Sender name', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_sender_name',
                'class'         => 'sms-readonly',
                'type'          => 'text'
            ),
            'telinfy_messaging_sms_tid_order_confirmation' => array(
                'title'         => __( 'Template id for order confirmation', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tid_order_confirmation',
                'class'         => 'sms-readonly',
                'type'          => 'text'
            ),
            'telinfy_messaging_sms_tdata_order_confirmation' => array(
                'title'         => __( 'Template for order confirmation', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tdata_order_confirmation',
                'class'         => 'sms-readonly',
                'type'          => 'textarea'
            ),
            'telinfy_messaging_sms_tid_order_cancellation' => array(
                'title'         => __( 'Template id for cancellation', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tid_order_cancellation',
                'class'         => 'sms-readonly',
                'type'          => 'text'
            ),
            'telinfy_messaging_sms_tdata_order_cancellation' => array(
                'title'         => __( 'Template for cancellation', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tdata_order_cancellation',
                'class'         => 'sms-readonly',
                'type'          => 'textarea'
            ),
            'telinfy_messaging_sms_tid_order_refund' => array(
                'title'         => __( 'Template id for order refund', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tid_order_refund',
                'class'         => 'sms-readonly',
                'type'          => 'text'
            ),
            'telinfy_messaging_sms_tdata_order_refund' => array(
                'title'         => __( 'Template for order refund', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tdata_order_refund',
                'class'         => 'sms-readonly',
                'type'          => 'textarea'
            ),
            'telinfy_messaging_sms_tid_order_notes' => array(
                'title'         => __( 'Template id for order notes', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tid_order_notes',
                'class'         => 'sms-readonly',
                'type'          => 'text'
            ),
            'telinfy_messaging_sms_tdata_order_notes' => array(
                'title'         => __( 'Template for order notes', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tdata_order_notes',
                'class'         => 'sms-readonly',
                'type'          => 'textarea'
            ),
            'telinfy_messaging_sms_tid_order_shipment' => array(
                'title'         => __( 'Template id for order shipment', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tid_order_shipment',
                'class'         => 'sms-readonly',
                'type'          => 'text'
            ),
            'telinfy_messaging_sms_tdata_order_shipment' => array(
                'title'         => __( 'Template for order shipment', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tdata_order_shipment',
                'class'         => 'sms-readonly',
                'type'          => 'textarea'
            ),
            'telinfy_messaging_sms_tid_other_order_status' => array(
                'title'         => __( 'Template id for other order status', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tid_other_order_status',
                'class'         => 'sms-readonly',
                'type'          => 'text'
            ),
            'telinfy_messaging_sms_tdata_other_order_status' => array(
                'title'         => __( 'Template for other order status', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tdata_other_order_status',
                'class'         => 'sms-readonly',
                'type'          => 'textarea'
            ),
            'telinfy_messaging_sms_tid_abandoned_cart' => array(
                'title'         => __( 'Template id for abandoned cart', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tid_abandoned_cart',
                'class'         => 'sms-readonly',
                'type'          => 'text'
            ),
            'telinfy_messaging_sms_tdata_abandoned_cart' => array(
                'title'         => __( 'Template for abandoned cart', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_sms_tdata_abandoned_cart',
                'class'         => 'sms-readonly',
                'type'          => 'textarea'
            ),
            'telinfy_checkbox_order_confirmation_sms' => array(
                'title'         => __( 'Order confirmation', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_order_confirmation_sms',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'tooltip'       => 'Check this box to enable the SMS services for order confirmation',
                'default'       => 'no',
                'class'         => 'sms-config',
                'autoload'      => false,
            ),
            'telinfy_checkbox_order_cancellation_sms' => array(
                'title'         => __( 'Order cancellation', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_order_cancellation_sms',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'tooltip'       => 'Check this box to enable the SMS services for order cancellation',
                'default'       => 'no',
                'class'         => 'sms-config',
                'autoload'      => false,
            ),
            'telinfy_checkbox_order_refund_sms' => array(
                'title'         => __( 'Order refund', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_order_refund_sms',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'tooltip'       => 'Check this box to enable the SMS services for order refund',
                'default'       => 'no',
                'class'         => 'sms-config',
                'autoload'      => false,
            ),
            'telinfy_checkbox_order_notes_sms' => array(
                'title'         => __( 'Order notes', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_order_notes_sms',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'tooltip'       => 'Check this box to enable the SMS services for order notes',
                'default'       => 'no',
                'class'         => 'sms-config',
                'autoload'      => false,
            ),
            'telinfy_checkbox_order_shipment_sms' => array(
                'title'         => __( 'Order shipment', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_order_shipment_sms',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'tooltip'       => 'Check this box to enable the SMS services for order shipment',
                'default'       => 'no',
                'class'         => 'sms-config',
                'autoload'      => false,
            ),
            'telinfy_checkbox_other_order_status_sms' => array(
                'title'         => __( 'Other order status', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_other_order_status_sms',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'tooltip'       => 'Check this box to enable the SMS services for all the other order status change',
                'default'       => 'no',
                'class'         => 'sms-config',
                'autoload'      => false,
            ),
            'telinfy_checkbox_order_abandoned_cart_sms' => array(
                'title'         => __( 'Abandoned cart', 'woocommerce-settings-telinfy-integration' ),
                'desc'          => __( '' ),
                'id'            => 'wc_settings_telinfy_messaging_checkbox_abandoned_cart_sms',
                'type'          => 'checkbox',
                'checkboxgroup' => 'start',
                'tooltip'       => 'Check this box to enable the SMS services for abandoned cart',
                'default'       => 'no',
                'class'         => 'sms-config',
                'autoload'      => false,
            ),
            'telinfy_section_end_sms' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_settings_telinfy_messaging_section_end_sms'
            )
        );
        
        // Configuration for cron and abandoned cart

        $settings_cron = array(
             'telinfy_section_title_cron' => array(
                'name'     => __( 'Abandoned cart & cron schedule', 'woocommerce-settings-telinfy-integration' ),
                'type'     => 'title',
                'desc'     => 'Please setup time for cron schedules and abandoned cart timings',
                'id'       => 'wc_settings_telinfy_messaging_section_title_cron'
            ),
             'telinfy_cron_schedule_abd_cart' => array(
                'name'      => __( 'Abandoned cart cron interval', 'woocommerce-settings-telinfy-integration' ),
                'type'      => 'number',
                'desc'      => __( 'Time interval to run the cron to check abandoned cart. Please add time in <b>minutes</b>'),
                'id'        => 'wc_settings_telinfy_messaging_abd_cart_cron_interval'
            ),
             'telinfy_abd_cart_time' => array(
                'name'      => __( 'Abandoned cart time', 'woocommerce-settings-telinfy-integration' ),
                'type'      => 'number',
                'desc'      => __( 'Select the time to mark a cart as abandoned for logged in users. Please add time in <b>hours</b>'),
                'id'        => 'wc_settings_telinfy_messaging_abd_cart_time'
            ),
             'telinfy_abd_cart_send_time' => array(
                'name'      => __( 'Abandoned cart send message time', 'woocommerce-settings-telinfy-integration' ),
                'type'      => 'text',
                'desc'      => __( 'The time to send the the messages to customer after the cart is marked as abandoned. Enter the time in <b>hours</b> separated by comma '),
                'id'        => 'wc_settings_telinfy_messaging_abd_cart_send_time'
            ),
             'telinfy_cron_schedule' => array(
                'name'      => __( 'Abandoned cart remove cron interval', 'woocommerce-settings-telinfy-integration' ),
                'type'      => 'number',
                'desc'      => __( 'Time interval to run the cron to remove the abandoned cart record. Please add time in <b> days</b>',),
                'id'        => 'wc_settings_telinfy_messaging_abd_cart_remove_interval'
            ),
             'telinfy_abd_cart_remove_time' => array(
                'name'      => __( 'Delete abandoned records', 'woocommerce-settings-telinfy-integration' ),
                'type'      => 'number',
                'desc'      => __( 'Abandoned cart records will be automatically deleted after the above mentioned time. Please add time in <b>days</b>',),
                'id'        => 'wc_settings_telinfy_messaging_abd_cart_remove_time'
            ),
            'telinfy_message_queue_cron_time' => array(
                'name'      => __( 'Message queue cron interval', 'woocommerce-settings-telinfy-integration' ),
                'type'      => 'number',
                'desc'      => __( 'A message queue will be added when an order is placed.This is the time interval to execute the queue. Please add time in <b>minutes</b>',),
                'id'        => 'wc_settings_telinfy_messaging_message_queue_cron_time'
            ),
            'telinfy_message_queue_cron_item' => array(
                'name'      => __( 'Message cron item count', 'woocommerce-settings-telinfy-integration' ),
                'type'      => 'number',
                'desc'      => __( 'Number of items processed in an execution of queue. Please add <b>item count</b>',),
                'id'        => 'wc_settings_telinfy_messaging_message_queue_cron_item'
            ),
             'telinfy_section_end_cron' => array(
                 'type'     => 'sectionend',
                 'id'       => 'wc_settings_telinfy_messaging_section_end_cron'
            )
        );
    

        // Merge all the configuration arrays

        $settings = array_merge($settings_whatsapp,$settings_sms,$settings_cron);

        return apply_filters( 'wc_settings_telinfy_messaging_settings', $settings );
    }

    /**
     * Add a new checkbox in the admin order detail page to control customer notify when changing status
     *
     * @return void
     */

    public function telinfy_add_custom_checkbox_to_order_admin( $order ) {
        // Get the value of the customer notify checkbox field for the order
        $customer_notify_checkbox_value = get_post_meta( $order->get_id(), 'customer_notify_checkbox_field', true );

        // Output the custom checkbox field HTML
        echo '<p class="form-field form-field-wide"><strong>' . esc_html__( 'Notify Customer','woocommerce-settings-telinfy-integration') . '</strong> ';
        echo '<input type="checkbox" name="customer_notify_checkbox_field" value="1" ' . checked( esc_attr($customer_notify_checkbox_value), '1', false ) . ' style="width:unset"/>';
        echo 'Notify using Telinfy messaging services';
        echo '</p>';
    }

    /**
    * Sanitize post data
    *
    * @return array
    */

    public function telinfy_tm_sanitize_post_data() {
        $input_post_values = [
            'username' => [
                'default' => '',
                'sanitize' => 'sanitize_text_field', // Optional: You can remove this line if not needed.
            ],
            'password' => [
                'default' => '',
                'sanitize' => 'skip_sanitization', // Custom marker for skipping sanitization
            ],
            'type' => [
                'default' => '',
                'sanitize' => 'sanitize_text_field', // Optional: You can remove this line if not needed.
            ],
            'apiEndpoint' => [
                'default' => '',
                'sanitize' => 'sanitize_url', // Optional: You can remove this line if not needed.
            ],
        ];

        $sanitized_post = [];

        foreach ($input_post_values as $key => $input_post_value) {
            if (isset($_POST[$key])) {
                if($input_post_value['sanitize'] === 'skip_sanitization'){
                    $sanitized_post[$key] = $_POST[$key];
                }else if($input_post_value['sanitize'] === 'sanitize_url'){
                    $sanitized_post[$key] = sanitize_url($_POST[$key]);
                }else{
                    $sanitized_post[$key] = sanitize_text_field($_POST[$key]);
                }
            } else {
                $sanitized_post[$key] = $input_post_value['default'];
            }
        }

        return $sanitized_post;
    }

    public function telinfy_custom_css(){
        echo "<style>
        .sms-readonly[readonly] {
            background: rgba(255,255,255,.5);
            border-color: rgba(220,220,222,.75);
            box-shadow: inset 0 1px 2px rgb(0 0 0 / 4%);
            color: rgba(44,51,56,.5);
            border-radius: 4px;
            cursor: default;
        }
        </style>";
    }

    public function get_country_code_array(){

        $countryCodes = [
            91 => "India", 355 => "Albania", 213 => "Algeria", 1684 => "American Samoa", 376 => "Andorra", 
            244 => "Angola", 1264 => "Anguilla", 1268 => "Antigua and Barbuda", 54 => "Argentina", 374 => "Armenia", 
            297 => "Aruba", 61 => "Australia", 43 => "Austria", 994 => "Azerbaijan", 1242 => "Bahamas", 973 => "Bahrain", 
            880 => "Bangladesh", 1246 => "Barbados", 375 => "Belarus", 32 => "Belgium", 501 => "Belize", 229 => "Benin", 
            1441 => "Bermuda", 975 => "Bhutan", 591 => "Bolivia", 387 => "Bosnia and Herzegovina", 267 => "Botswana", 
            55 => "Brazil", 246 => "British Indian Ocean Territory", 1284 => "British Virgin Islands", 673 => "Brunei", 
            359 => "Bulgaria", 226 => "Burkina Faso", 257 => "Burundi", 855 => "Cambodia", 237 => "Cameroon", 1 => "Canada", 
            238 => "Cape Verde", 599 => "Caribbean Netherlands", 1345 => "Cayman Islands", 236 => "Central African Republic", 
            235 => "Chad", 56 => "Chile", 86 => "China", 57 => "Colombia", 269 => "Comoros", 242 => "Congo", 682 => "Cook Islands", 
            506 => "Costa Rica", 385 => "Croatia", 53 => "Cuba", 357 => "Cyprus", 420 => "Czech Republic", 243 => "DR Congo", 
            45 => "Denmark", 253 => "Djibouti", 1767 => "Dominica", 1809 => "Dominican Republic", 593 => "Ecuador", 20 => "Egypt", 
            503 => "El Salvador", 240 => "Equatorial Guinea", 291 => "Eritrea", 372 => "Estonia", 268 => "Eswatini", 251 => "Ethiopia", 
            500 => "Falkland Islands", 298 => "Faroe Islands", 679 => "Fiji", 358 => "Finland", 33 => "France", 594 => "French Guiana", 
            689 => "French Polynesia", 241 => "Gabon", 220 => "Gambia", 995 => "Georgia", 49 => "Germany", 233 => "Ghana", 
            350 => "Gibraltar", 30 => "Greece", 299 => "Greenland", 1473 => "Grenada", 590 => "Guadeloupe", 1671 => "Guam", 
            502 => "Guatemala", 44 => "Guernsey", 224 => "Guinea", 245 => "Guinea-Bissau", 592 => "Guyana", 509 => "Haiti", 
            504 => "Honduras", 852 => "Hong Kong", 36 => "Hungary", 354 => "Iceland", 93 => "Afghanistan", 62 => "Indonesia", 
            98 => "Iran", 964 => "Iraq", 353 => "Ireland", 44 => "Isle of Man", 972 => "Israel", 39 => "Italy", 225 => "Ivory Coast", 
            1876 => "Jamaica", 81 => "Japan", 44 => "Jersey", 962 => "Jordan", 7 => "Kazakhstan", 254 => "Kenya", 686 => "Kiribati", 
            383 => "Kosovo", 965 => "Kuwait", 996 => "Kyrgyzstan", 856 => "Laos", 371 => "Latvia", 961 => "Lebanon", 266 => "Lesotho", 
            231 => "Liberia", 218 => "Libya", 423 => "Liechtenstein", 370 => "Lithuania", 352 => "Luxembourg", 853 => "Macao", 
            261 => "Madagascar", 265 => "Malawi", 60 => "Malaysia", 960 => "Maldives", 223 => "Mali", 356 => "Malta", 692 => "Marshall Islands", 
            596 => "Martinique", 222 => "Mauritania", 230 => "Mauritius", 262 => "Mayotte", 52 => "Mexico", 691 => "Micronesia", 
            373 => "Moldova", 377 => "Monaco", 976 => "Mongolia", 382 => "Montenegro", 1664 => "Montserrat", 212 => "Morocco", 
            258 => "Mozambique", 95 => "Myanmar", 264 => "Namibia", 674 => "Nauru", 977 => "Nepal", 31 => "Netherlands", 687 => "New Caledonia", 
            64 => "New Zealand", 505 => "Nicaragua", 227 => "Niger", 234 => "Nigeria", 683 => "Niue", 672 => "Norfolk Island", 850 => "North Korea", 
            389 => "North Macedonia", 1670 => "Northern Mariana Islands", 47 => "Norway", 968 => "Oman", 92 => "Pakistan", 680 => "Palau", 
            970 => "Palestine", 507 => "Panama", 675 => "Papua New Guinea", 595 => "Paraguay", 51 => "Peru", 63 => "Philippines", 
            64 => "Pitcairn Islands", 48 => "Poland", 351 => "Portugal", 1787 => "Puerto Rico", 974 => "Qatar", 40 => "Romania", 
            7 => "Russia", 250 => "Rwanda", 262 => "Réunion", 590 => "Saint Barthélemy", 290 => "Saint Helena", 1869 => "Saint Kitts and Nevis", 
            1758 => "Saint Lucia", 590 => "Saint Martin", 508 => "Saint Pierre and Miquelon", 1784 => "Saint Vincent and the Grenadines", 
            685 => "Samoa", 378 => "San Marino", 966 => "Saudi Arabia", 221 => "Senegal", 381 => "Serbia", 248 => "Seychelles", 232 => "Sierra Leone", 
            65 => "Singapore", 1721 => "Sint Maarten", 421 => "Slovakia", 386 => "Slovenia", 677 => "Solomon Islands", 252 => "Somalia", 
            27 => "South Africa", 500 => "South Georgia", 82 => "South Korea", 211 => "South Sudan", 34 => "Spain", 94 => "Sri Lanka", 249 => "Sudan", 
            597 => "Suriname", 47 => "Svalbard and Jan Mayen", 46 => "Sweden", 41 => "Switzerland", 963 => "Syria", 886 => "Taiwan", 
            992 => "Tajikistan", 255 => "Tanzania", 66 => "Thailand", 670 => "Timor-Leste", 228 => "Togo", 690 => "Tokelau", 676 => "Tonga", 
            1868 => "Trinidad and Tobago", 216 => "Tunisia", 90 => "Turkey", 993 => "Turkmenistan", 1649 => "Turks and Caicos Islands", 
            688 => "Tuvalu", 256 => "Uganda", 380 => "Ukraine", 971 => "United Arab Emirates", 44 => "United Kingdom", 1 => "United States", 
            598 => "Uruguay", 998 => "Uzbekistan", 678 => "Vanuatu", 379 => "Vatican City", 58 => "Venezuela", 84 => "Vietnam", 
            681 => "Wallis and Futuna", 212 => "Western Sahara", 967 => "Yemen", 260 => "Zambia", 263 => "Zimbabwe"
        ];  
        return $countryCodes;

    }

}