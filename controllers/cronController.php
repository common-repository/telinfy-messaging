<?php

namespace TelinfyMessaging\Controllers;

// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
   exit;
}

require_once( TELINFY_WOOCOMMERCE_PLUGIN_PATH . 'includes/queryDb.php' );

define( 'TELINFY_CRON_CURRENT_TIME', current_time( 'U' ) );

use TelinfyMessaging\Api\telinfy_sms_connector;
use TelinfyMessaging\Includes\telinfy_query_db;
use TelinfyMessaging\Api\telinfy_whatsapp_connector;
use TelinfyMessaging\controllers\telinfy_message_controller;


class telinfy_cron_controller {

   protected static $instance = null;
   public $query;
   public $sms_connector;
   public $whatsapp_connector;

    /**
     * Initiate admin actions for updating settings in WooCommerce
     *
     * @return void
     */

   public function __construct(){

      $this->query = telinfy_query_db::telinfy_get_instance();

      add_action( 'telinfy_tm_cron_send_message_abd_cart', array( $this, 'telinfy_send_abandoned_message' ) );
      add_action( 'telinfy_tm_cron_abd_cart_remove', array( $this, 'telinfy_abandoned_cart_remove' ) );
      add_action( 'telinfy_tm_cron_send_message', array( $this, 'telinfy_send_queue_message' ) );

   }

    public static function telinfy_get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

   /**
     * Send messages to notify the abandoned cart 
     *
     * @return void
     */

   public function telinfy_send_abandoned_message() {

      $abd_cart_send_time = get_option('wc_settings_telinfy_messaging_abd_cart_send_time');
      $hours_array = explode(",",$abd_cart_send_time);

      $hours_array = array_filter($hours_array, 'is_numeric');
      $hours_array = array_map('intval', $hours_array);
      $hours_array = array_unique($hours_array);
      $hours_array = array_values($hours_array);
      
      $abd_cart_send_time_count = count($hours_array);

      $abd_cart_time = (int)get_option('wc_settings_telinfy_messaging_abd_cart_time');
      $previous_time = 0;
      $i = 0;
      if($abd_cart_send_time != null && $abd_cart_time){

         for ($j = $abd_cart_send_time_count - 1; $j >= 0; $j--) {

            $abd_send_time = $abd_cart_time + $hours_array[$j];
            $time_to_send  = current_time( 'timestamp' ) - intval($abd_send_time) * HOUR_IN_SECONDS;

            $lists = $this->query->telinfy_get_list_message_to_send( $j+1, $time_to_send );
            $last_one = 0;
            if($abd_cart_send_time_count-1 == $j){
               $last_one = 1;
            }

            if ( is_array( $lists ) && count( $lists ) > 0 ) {
               foreach ( $lists as $id => $item ) {
                  $this->telinfy_message_content( $item, $j+1,$last_one);
               }
            }
                
         }
      }

   }

      /**
     * Prepare and send abandoned cart messages via whatsApp and sms
     *
     * @return void
     */

   public function telinfy_message_content($item,$count,$last_one){
      
      $abandoned_cart_whatsapp = get_option('wc_settings_telinfy_messaging_checkbox_abandoned_cart_whatsapp');
      $abandoned_cart_sms = get_option('wc_settings_telinfy_messaging_checkbox_abandoned_cart_sms');
      $whatsapp_abandoned_cart_template_name =get_option("wc_settings_telinfy_messaging_whatsapp_template_name_abandoned_cart"); 
      $customer_name = $item->user_login;
      $customer_phone = $item->phone;
      $abd_record_id  = $item->abd_id;
      $abd_cart_data = $item->abandoned_cart_info;

      // check whether the customer phone number is available

      if(isset($customer_phone)){
         $customer_notified = 0;

         // send messages for abandoned cart via WhatsApp
         if($abandoned_cart_whatsapp == "yes" && $whatsapp_abandoned_cart_template_name){

            $this->whatsapp_connector = telinfy_whatsapp_connector::telinfy_get_instance();
            
            $body_params =  array(
                        array(
                            'type' => 'text',
                            'text' => $customer_name
                        )
                    );

            // fetch product image if there is only one product else fetch default image
            $abd_cart_data_array = json_decode($abd_cart_data, true);

            if (isset($abd_cart_data_array['cart']) && is_array($abd_cart_data_array['cart'])) {
                $number_of_products = count($abd_cart_data_array['cart']);
                if($number_of_products > 1){
                  $header_image_link = get_option('wc_settings_telinfy_messaging_whatsapp_file_upload');
               }else{
                  foreach ($abd_cart_data_array['cart'] as $cart_item) {
                       $product_id = $cart_item['product_id'];
                       $product = wc_get_product( $product_id );

                      if ( is_a( $product, 'WC_Product' ) ) {  
                        $image_id = get_post_thumbnail_id($product->get_id());
                        $header_image_link = wp_get_attachment_image_url($image_id, 'full');
                      }
                  }
               }
            } else {
                $header_image_link = get_option('wc_settings_telinfy_messaging_whatsapp_file_upload');
            }
            

            $body = $this->whatsapp_connector->telinfy_render_whatsapp_body($body_params,$customer_phone,$whatsapp_abandoned_cart_template_name,$header_image_link);

            $result_whatsapp = $this->whatsapp_connector->telinfy_send_message($body,$customer_phone);

            if(isset($result_whatsapp["status"]) && $result_whatsapp["status"] == "success"){
               $customer_notified = 1;
               $this->query->telinfy_update_sent_status($count,"whatsapp",$last_one,$abd_record_id);
            }
         }

          // send messages for abandoned cart via SMS

         if($abandoned_cart_sms == "yes"){

            $sms_abandoned_template_id = get_option("wc_settings_telinfy_messaging_sms_tid_abandoned_cart");
            $button_redirect_url =  get_permalink(wc_get_page_id('myaccount'));

            if($sms_abandoned_template_id){
               
               // $message_content = "Hi {$customer_name}\nWe see you were trying to make a purchase but did not complete your payment. You can continue to click the below button.\nView cart: {$button_redirect_url}.\nGreenAds Global Pvt Ltd";

               $replacements_abd_cart = array(
                '{$customer_name}' => $customer_name, 
                '{$redirect_url}' => $button_redirect_url
                
               );

               $message_template_abd_cart = get_option("wc_settings_telinfy_messaging_sms_tdata_abandoned_cart");

               $modified_message_abd_cart = str_replace(array_keys($replacements_abd_cart), $replacements_abd_cart, $message_template_abd_cart);

               $this->sms_connector = telinfy_sms_connector::telinfy_get_instance ();
               $result_sms = $this->sms_connector->telinfy_send_sms($modified_message_abd_cart,$customer_phone,$sms_abandoned_template_id);
               if(isset($result_sms["status"]) && $result_sms["status"] == "success"){
                  $customer_notified = 1;
                  $this->query->telinfy_update_sent_status($count,"sms",$last_one,$abd_record_id);
               }
            }
            
         }

         if($customer_notified){

            $this->query->telinfy_update_notified($abd_record_id,$last_one,$count);

         }

      }
   
   }

      /**
     * Remove abandoned cart records 
     *
     * @return void
     */

   public function telinfy_abandoned_cart_remove(){

      $abd_cart_remove_time = (int)get_option('wc_settings_telinfy_messaging_abd_cart_remove_time');

      $abd_cart_time_hour = (int)get_option('wc_settings_telinfy_messaging_abd_cart_time');

      if($abd_cart_remove_time && $abd_cart_time_hour){

         $this->query->telinfy_remove_abd_cart_record_by_time($abd_cart_remove_time,$abd_cart_time_hour);

      }
   }
   public function telinfy_send_queue_message(){

      $limit = (int)get_option('wc_settings_telinfy_messaging_message_queue_cron_item');
      $queue_data = $this->query->telinfy_get_message_queue($limit);
      $this->message = telinfy_message_controller::telinfy_get_instance();
      if ( is_array( $queue_data ) && count( $queue_data ) > 0 ) {
         $queue_del_id = array();
         $queue_status_id = array();
         foreach ( $queue_data as $id => $item ) {
            $order = wc_get_order($item->order_id);
            $response = $this->message->telinfy_wpts_new_order($order);
            if($response == 200){
               $queue_del_id[] = $item->queue_id;
            }else if($response != 201){
               $queue_status_id[]=$item->queue_id;
            }
         }
         if(!empty($queue_del_id)){
            $queue_delete_data = $this->query->telinfy_get_message_disabled_queue($limit);
            if ( is_array( $queue_delete_data ) && count( $queue_delete_data ) > 0 ) {
               foreach ( $queue_delete_data as $del_id => $del_item ) {
                  $queue_del_id[] = $del_item->queue_id;
               }
            }
            $this->query->telinfy_remove_message_queue($queue_del_id);
         }
         if(!empty($queue_status_id)){
            $this->query->telinfy_update_message_queue($queue_status_id);
         }
      }
   }

}