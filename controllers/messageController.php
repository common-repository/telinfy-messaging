<?php

namespace TelinfyMessaging\Controllers;

// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TelinfyMessaging\Api\telinfy_whatsapp_connector;
use TelinfyMessaging\Api\telinfy_sms_connector;
use TelinfyMessaging\Includes\telinfy_query_db;

class telinfy_message_controller {

	protected static $instance = null;
	public $connector;
	public $sms_connector;

	public static function telinfy_get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

	/**
     * Obseverve hooks and send mails to the customer
     *
     * @return void
     */

	public function init(){

		add_action('woocommerce_checkout_order_created', array($this,'telinfy_wpts_new_order_queue'), 10,1);
        add_action( 'woocommerce_order_note_added', array($this,'telinfy_wpts_order_note'), 10, 2 );
        add_action( 'woocommerce_order_status_changed', array( $this,'telinfy_wpts_on_order_update'), 10, 3 );
		add_action( 'woocommerce_order_refunded', array( $this,'telinfy_wpts_order_refunded'), 10, 2 ); 

	}

	/**
     * get header images
     *
     * @return string
     */


	public function telinfy_get_telinfy_header_image($order){

		$order_items = $order->get_items();
		$number_of_items = count($order_items);
		if($number_of_items == 1){
			foreach ( $order_items as $item_id => $item ) {
			    $product_id = $item->get_product_id();
			    $product = wc_get_product( $product_id );

			    if ( is_a( $product, 'WC_Product' ) ) {
			        // $header_image_link = $product->get_image();   
			        $image_id = get_post_thumbnail_id($product->get_id());
    				$header_image_link = wp_get_attachment_image_url($image_id, 'full');
			    }
			}
		}else{
			$header_image_link = get_option('wc_settings_telinfy_messaging_whatsapp_file_upload');
		}
		return $header_image_link;
	}

	public function telinfy_wpts_new_order_queue($order){

		$this->query = telinfy_query_db::telinfy_get_instance();
		$order_confirmation_status_whatsapp = get_option('wc_settings_telinfy_messaging_checkbox_order_confirmation_whatsapp');
		$order_confirmation_status_sms = get_option('wc_settings_telinfy_messaging_checkbox_order_confirmation_sms');

		if($order_confirmation_status_whatsapp  == "yes" || $order_confirmation_status_sms == "yes"){

			$customer_phone = $order->get_billing_phone();
			$order_id = $order->get_id();
			$user_id = is_user_logged_in() ? get_current_user_id() : wc()->session->get( 'user_id' );
			$user_id = $user_id ? $user_id : 0;
			$this->query->telinfy_insert_message_queue($user_id,$customer_phone,$order_id);
		}
	}

	/**
     * Send messages when creating an order
     *
     * @return void
     */
	public function telinfy_wpts_new_order($order){


		$order_confirmation_status_whatsapp = get_option('wc_settings_telinfy_messaging_checkbox_order_confirmation_whatsapp');
		$order_confirmation_status_sms = get_option('wc_settings_telinfy_messaging_checkbox_order_confirmation_sms');

		$order_id = $order->get_id();
		$order_total = $order->get_total();
		$currency_symbol = get_woocommerce_currency();
		$customer_name = $order->get_billing_first_name();
		$customer_phone = $order->get_billing_phone();

		// Send WhatsApp messages when placing an order

		if ($order->get_status() === 'processing') {

			$response = 0;

			if($order_confirmation_status_whatsapp =="yes"){
				
				$whatsapp_order_confirmation_template_name =get_option("wc_settings_telinfy_messaging_whatsapp_template_name_order_confirmation");

				if($whatsapp_order_confirmation_template_name){

					$header_image_link = $this->telinfy_get_telinfy_header_image($order);
					$body_params =  array(
	                        array(
	                            'type' => 'text',
	                            'text' => $customer_name
	                        ), array(
	                            'type' => 'text',
	                            'text' => '#'.$order_id
	                        ), array(
	                        	'type' => 'text',
	                            'text' => $currency_symbol." ".$order_total
	                        )
	                    );
					$this->connector = telinfy_whatsapp_connector::telinfy_get_instance();
		            $body = $this->connector->telinfy_render_whatsapp_body($body_params,$customer_phone,$whatsapp_order_confirmation_template_name,$header_image_link);          
			    	$whats_result = $this->connector->telinfy_send_message($body,$customer_phone);

			    	if($whats_result['status_code'] == 200){
			    		$response = $response+1;
			    	}


			    }
			}

			// Send sms messages when placing an order

			if($order_confirmation_status_sms =="yes"){

				$sms_order_confirmation_template_id = get_option("wc_settings_telinfy_messaging_sms_tid_order_confirmation");
				$button_redirect_url =  get_permalink(wc_get_page_id('myaccount'));

				if($sms_order_confirmation_template_id){
					$formatted_order_id = "#".$order_id;
					$currency_symbol = get_woocommerce_currency();
					$formatted_order_total = $currency_symbol." ".$order_total;

		           	// $message_content ="Dear {$customer_name} \nThank you for Shopping with us. We have received your order {$formatted_order_id} worth {$formatted_order_total}\nView Order: {$button_redirect_url}.\nGreenAds Global Pvt Ltd";


					$replacements_order_confirmation = array(
					    '{$customer_name}' => $customer_name, 
					    '{$order_id}' => $formatted_order_id,
					    '{$redirect_url}' => $button_redirect_url,
					    '{$order_total}' => $formatted_order_total 
					);

					
		           	$message_template_order_confirmation = get_option("wc_settings_telinfy_messaging_sms_tdata_order_confirmation");

		           	$modified_message_order_confirmation = str_replace(array_keys($replacements_order_confirmation), $replacements_order_confirmation, $message_template_order_confirmation);


		           	$this->sms_connector = telinfy_sms_connector::telinfy_get_instance ();
		           	$sms_result = $this->sms_connector->telinfy_send_sms($modified_message_order_confirmation,$customer_phone,$sms_order_confirmation_template_id);

			    	if($sms_result['status_code'] == 200){
			    		$response = $response+1;
			    	}
		       }
	           
	        }

	        if($response > 0){
	        	return 200;
	        }else{
	        	return 401;
	        }
	    }else if($order->get_status() === 'pending'){
	    	return 201;
	    }
	    else{
	    	return 401;
	    }

	}

	/**
     * Send messages when adding order notes for customer
     *
     * @return void
     */
	public function telinfy_wpts_order_note( $comment_id, $order ) {


		$comment_obj   = get_comment( $comment_id );
		$comment_meta = get_comment_meta( $comment_id);

        if(isset($comment_meta['is_customer_note']) && $comment_meta['is_customer_note']){
        	$order_comment_status_whatsapp = get_option('wc_settings_telinfy_messaging_checkbox_order_notes_whatsapp');
			$order_comment_status_sms = get_option('wc_settings_telinfy_messaging_checkbox_order_notes_sms');

			$customer_note = $comment_obj->comment_content;
			$customer_name = $order->get_billing_first_name();
			$customer_phone = $order->get_billing_phone();
			$order_total = $order->get_total();
			$currency_symbol = get_woocommerce_currency();
			$order_id = $order->get_id();

			// Send WhatsApp messages when adding customer notes by admin

			if($order_comment_status_whatsapp =="yes"){
				$whatsapp_order_notes_template_name =get_option("wc_settings_telinfy_messaging_whatsapp_template_name_order_notes"); 
				if($whatsapp_order_notes_template_name){
					$header_image_link = $this->telinfy_get_telinfy_header_image($order);

					$body_params =  array(
                        array(
                            'type' => 'text',
                            'text' => $customer_name
                        ), array(
                            'type' => 'text',
                            'text' => '#'.$order_id
                        ), array(
                        	'type' => 'text',
                            'text' => $customer_note
                        )
                    );
					$this->connector = telinfy_whatsapp_connector::telinfy_get_instance();
		        	$body = $this->connector->telinfy_render_whatsapp_body($body_params,$customer_phone,$whatsapp_order_notes_template_name,$header_image_link);
		        	$this->connector->telinfy_send_message($body,$customer_phone);
		        }

			}

			// Send sms messages when adding customer notes by admin

			if($order_comment_status_sms =="yes"){
				$sms_order_notes_template_id = get_option("wc_settings_telinfy_messaging_sms_tid_order_notes");
				$button_redirect_url =  get_permalink(wc_get_page_id('myaccount'));

				if($sms_order_notes_template_id){
					$formatted_order_id = "#".$order_id;

		           	// $message_content = "Hi {$customer_name} ,\nAn order note has been added for order {$formatted_order_id}\nPlease see the order note here {$button_redirect_url}\nGreenAds Global Pvt Ltd";

		        $replacements_order_note = array(
				    '{$customer_name}' => $customer_name, 
				    '{$order_id}' => $formatted_order_id,
				    '{$redirect_url}' => $button_redirect_url
				    
				);

	           	$message_template_order_note = get_option("wc_settings_telinfy_messaging_sms_tdata_order_notes");

	           	$modified_message_order_note = str_replace(array_keys($replacements_order_note), $replacements_order_note, $message_template_order_note);

		           	$this->sms_connector = telinfy_sms_connector::telinfy_get_instance ();
		          	$this->sms_connector->telinfy_send_sms($modified_message_order_note,$customer_phone,$sms_order_notes_template_id);
		       }
               
            }

        }

	}

	/**
     * Send confirmation messages on shipping the order,cancelling the order and changing the status of the order
     *
     * @return void
     */

	public function telinfy_wpts_on_order_update( $order_id, $old_status, $new_status ) {

	    // Get the value of the custom field from the updated order data
	    $notify_customer = isset( $_POST['customer_notify_checkbox_field'] ) ? sanitize_text_field( $_POST['customer_notify_checkbox_field'] ) : '';

    	// configurations to check whether the shipment messaging is enabled or not

   		$order_shipment_status_whatsapp = get_option('wc_settings_telinfy_messaging_checkbox_order_shipment_whatsapp');
		$order_shipment_status_sms = get_option('wc_settings_telinfy_messaging_checkbox_order_shipment_sms');

		// configurations to check whether the order cancellation messaging is enabled or not

		$order_cancellation_status_whatsapp = get_option('wc_settings_telinfy_messaging_checkbox_order_cancellation_whatsapp');
		$order_cancellation_status_sms = get_option('wc_settings_telinfy_messaging_checkbox_order_cancellation_sms');

		// configurations to check whether the messaging for order status change is enabled or not

		$order_other_status_whatsapp = get_option("wc_settings_telinfy_messaging_checkbox_other_order_status_whatsapp");
        $order_other_status_sms = get_option("wc_settings_telinfy_messaging_checkbox_other_order_status_sms");

		$order = wc_get_order($order_id);
		$header_image_link = $this->telinfy_get_telinfy_header_image($order);
		$customer_name = $order->get_billing_first_name();
		$customer_phone = $order->get_billing_phone();
		$currency_symbol = get_woocommerce_currency();
		$order_total = $order->get_total();

		$reserved_statuses  = array("completed","cancelled","refunded");
		

		// Send WhatsApp messages when shipping the order

		if($order_shipment_status_whatsapp =="yes" && $new_status =="completed"){
			$body_params =  array(
                    array(
                        'type' => 'text',
                        'text' => $customer_name
                    ), array(
                        'type' => 'text',
                        'text' => '#'.$order_id
                    ), array(
                    	'type' => 'text',
                        'text' => $currency_symbol." ".$order_total
                    )
                );

        	$whatsapp_order_shipment_template_name =get_option("wc_settings_telinfy_messaging_whatsapp_template_name_order_shipment"); 
        	if($whatsapp_order_shipment_template_name){
        		$this->connector = telinfy_whatsapp_connector::telinfy_get_instance();
	        	$body = $this->connector->telinfy_render_whatsapp_body($body_params,$customer_phone,$whatsapp_order_shipment_template_name,$header_image_link);
	        	$this->connector->telinfy_send_message($body,$customer_phone);
	        }
        }


        // Send WhatsApp messages when cancelling the order


        if($order_cancellation_status_whatsapp =="yes" && $new_status =="cancelled"){
        	
			$body_params =  array(
                    array(
                        'type' => 'text',
                        'text' => $customer_name
                    ), array(
                        'type' => 'text',
                        'text' => '#'.$order_id
                    ), array(
                    	'type' => 'text',
                        'text' => $currency_symbol." ".$order_total
                    )
                );

        	$whatsapp_order_cancellation_template_name =get_option("wc_settings_telinfy_messaging_whatsapp_template_name_order_cancellation"); 
        	if($whatsapp_order_cancellation_template_name){
        		$this->connector = telinfy_whatsapp_connector::telinfy_get_instance();
	        	$body = $this->connector->telinfy_render_whatsapp_body($body_params,$customer_phone,$whatsapp_order_cancellation_template_name,$header_image_link);
	        	$this->connector->telinfy_send_message($body,$customer_phone);
        	}

        }


        // Send WhatsApp messages for all other order status change


        if($order_other_status_whatsapp =="yes" && $notify_customer && !in_array($new_status,$reserved_statuses)){
			$body_params =  array(
                    array(
                        'type' => 'text',
                        'text' => $customer_name
                    ), array(
                        'type' => 'text',
                        'text' => '#'.$order_id
                    ),array(
                    	'type' => 'text',
                        'text' => $new_status
                    )
                );

        	$whatsapp_order_status_change_template_name =get_option("wc_settings_telinfy_messaging_whatsapp_template_name_other_order_status"); 

        	if($whatsapp_order_status_change_template_name){
        		$this->connector = telinfy_whatsapp_connector::telinfy_get_instance();
	        	$body = $this->connector->telinfy_render_whatsapp_body($body_params,$customer_phone,$whatsapp_order_status_change_template_name,$header_image_link);
	        	$this->connector->telinfy_send_message($body,$customer_phone);
        	}

        }

        // Send sms when shipping an order

        if($order_shipment_status_sms =="yes" && $new_status =="completed"){

        	$sms_order_shipment_template_id = get_option("wc_settings_telinfy_messaging_sms_tid_order_shipment");
			$button_redirect_url =  get_permalink(wc_get_page_id('myaccount'));

			if($sms_order_shipment_template_id){
				$formatted_order_id = "#".$order_id;
				$formatted_order_total = $currency_symbol." ".$order_total;

	           	// $message_content = "Hi {$customer_name}\nThe order {$formatted_order_id} worth {$formatted_order_total} has been shipped\nView Order: {$button_redirect_url}.\nGreenAds Global Pvt Ltd";

				$replacements_order_ship = array(
				    '{$customer_name}' => $customer_name, 
				    '{$order_id}' => $formatted_order_id,
				    '{$order_total}' => $formatted_order_total,
				    '{$redirect_url}' => $button_redirect_url
				    
				);

	           	$message_template_order_ship = get_option("wc_settings_telinfy_messaging_sms_tdata_order_shipment");

	           	$modified_message_order_ship = str_replace(array_keys($replacements_order_ship), $replacements_order_ship, $message_template_order_ship);

	           	$this->sms_connector = telinfy_sms_connector::telinfy_get_instance ();
	           	$this->sms_connector->telinfy_send_sms($modified_message_order_ship,$customer_phone,$sms_order_shipment_template_id);
	       }

        }

        // Send sms when cancelling an order

        if($order_cancellation_status_sms =="yes" && $new_status =="cancelled"){

        	$sms_order_cancellation_template_id = get_option("wc_settings_telinfy_messaging_sms_tid_order_cancellation");
			$button_redirect_url =  get_permalink(wc_get_page_id('myaccount'));

			if($sms_order_cancellation_template_id){
				$formatted_order_id = "#".$order_id;
				$formatted_order_total = $currency_symbol." ".$order_total;

	           // $message_content = "Dear {$customer_name}\nYour order {$formatted_order_id} worth {$formatted_order_total} is now cancelled.\nLet us know in case any support is required\nView Order: {$button_redirect_url}.\nGreenAds Global Pvt Ltd";

				$replacements_order_cancel = array(
				    '{$customer_name}' => $customer_name, 
				    '{$order_id}' => $formatted_order_id,
				    '{$order_total}' => $formatted_order_total,
				    '{$redirect_url}' => $button_redirect_url
				    
				);

	           	$message_template_order_cancel = get_option("wc_settings_telinfy_messaging_sms_tdata_order_cancellation");

	           	$modified_message_order_cancel = str_replace(array_keys($replacements_order_cancel), $replacements_order_cancel, $message_template_order_cancel);

	           $this->sms_connector = telinfy_sms_connector::telinfy_get_instance ();
	           $this->sms_connector->telinfy_send_sms($modified_message_order_cancel,$customer_phone,$sms_order_cancellation_template_id);
	       }
        }

        // Send sms when changing status of an order

       	if($order_other_status_sms =="yes" && $notify_customer && !in_array($new_status,$reserved_statuses)){

        	$sms_order_status_change_template_id = get_option("wc_settings_telinfy_messaging_sms_tid_other_order_status");
			$button_redirect_url =  get_permalink(wc_get_page_id('myaccount'));

			if($sms_order_status_change_template_id){
			   $formatted_order_id = "#".$order_id;

	           // $message_content = "Dear {$customer_name},\nThe status of the order {$formatted_order_id} changed to {$new_status}\nHappy Shopping\nView Order: {$button_redirect_url}.\nGreenAds Global Pvt Ltd";

	           $replacements_order_status = array(
				    '{$customer_name}' => $customer_name, 
				    '{$order_id}' => $formatted_order_id,
				    '{$new_status}' => $new_status,
				    '{$redirect_url}' => $button_redirect_url
				    
				);

	           	$message_template_order_status = get_option("wc_settings_telinfy_messaging_sms_tdata_other_order_status");

	           	$modified_message_order_status = str_replace(array_keys($replacements_order_status), $replacements_order_status, $message_template_order_status);

	           $this->sms_connector = telinfy_sms_connector::telinfy_get_instance ();
	           $this->sms_connector->telinfy_send_sms($modified_message_order_status,$customer_phone,$sms_order_status_change_template_id);
	       }
        }


	}

	/**
     * Send refund messages when refunding order
     *
     * @return void
     */

	public function telinfy_wpts_order_refunded( $order_id, $refund_id ) 
	{ 

		// configurations to check whether the other status change messaging is enabled or not

		$order_refund_whatsapp = get_option('wc_settings_telinfy_messaging_checkbox_order_refund_whatsapp');
		$order_refund_sms = get_option('wc_settings_telinfy_messaging_checkbox_order_refund_sms');

		$order = wc_get_order($order_id);
    	$refund = wc_get_order($refund_id);
    	$refund_amount = $refund->get_amount();
    	$currency_symbol = get_woocommerce_currency();
		$header_image_link = $this->telinfy_get_telinfy_header_image($order);
		$customer_name = $order->get_billing_first_name();
		$customer_phone = $order->get_billing_phone();
	
        // Send WhatsApp messages when refunding the order

        if($order_refund_whatsapp =="yes"){
			$body_params =  array(
                    array(
                        'type' => 'text',
                        'text' => $customer_name
                    ), array(
                        'type' => 'text',
                        'text' => $currency_symbol." ".$refund_amount
                    ), array(
                    	'type' => 'text',
                        'text' => '#'.$order_id
                    )
                );

        	$whatsapp_order_refund_template_name =get_option("wc_settings_telinfy_messaging_whatsapp_template_name_order_refund"); 

        	if($whatsapp_order_refund_template_name){
        		$this->connector = telinfy_whatsapp_connector::telinfy_get_instance();
	        	$body = $this->connector->telinfy_render_whatsapp_body($body_params,$customer_phone,$whatsapp_order_refund_template_name,$header_image_link);
	        	$this->connector->telinfy_send_message($body,$customer_phone);
        	}

        }

        // Send SMS when refunding the order

        if($order_refund_sms =="yes"){
			$sms_order_refund_template_id = get_option("wc_settings_telinfy_messaging_sms_tid_order_refund");
			$button_redirect_url =  get_permalink(wc_get_page_id('myaccount'));

			if($sms_order_refund_template_id){

				$formatted_order_id = "#".$order_id;
				$formatted_refund_amount = $currency_symbol." ".$refund_amount;

	           	// $message_content = "Hi {$customer_name} ,\nWe've processed a refund of {$formatted_refund_amount} for the order {$formatted_order_id}, and you should expect to see the amount appear in your bank account in the next couple of business days.\nView Order: {$button_redirect_url}.\nGreenAds Global Pvt Ltd";

				$replacements_order_refund = array(
				    '{$customer_name}' => $customer_name, 
				    '{$order_id}' => $formatted_order_id,
				    '{$refund_amount}' => $formatted_refund_amount,
				    '{$redirect_url}' => $button_redirect_url
				    
				);

	           	$message_template_order_refund = get_option("wc_settings_telinfy_messaging_sms_tdata_order_refund");

	           	$modified_message_order_refund = str_replace(array_keys($replacements_order_refund), $replacements_order_refund, $message_template_order_refund);

	           	$this->sms_connector = telinfy_sms_connector::telinfy_get_instance ();
	           	$this->sms_connector->telinfy_send_sms($modified_message_order_refund,$customer_phone,$sms_order_refund_template_id);
	       }
        }

	}

}