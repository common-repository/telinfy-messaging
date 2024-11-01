<?php

namespace TelinfyMessaging\Api;

// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class telinfy_sms_connector{

	protected static $instance = null;

	public static function telinfy_get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

     /**
     * Send sms to the customers
     *
     * @return void
     */

	public function telinfy_send_sms($message, $to, $template_id) {
		// Prepare request parameters
		$api_url = 'http://sapteleservices.com/SMS_API/sendsms.php';
		$username = get_option('wc_settings_telinfy_messaging_api_key_sms');
		$password = get_option('wc_settings_telinfy_messaging_api_secret_sms');
		$mobile = $to;
		$sendername = get_option('wc_settings_telinfy_messaging_sms_sender_name');
		$routetype = 1;
		$encoded_message = urlencode($message);
	
		// Build the request URL
		$request_url = add_query_arg(
			array(
				'username' => $username,
				'password' => $password,
				'mobile' => $mobile,
				'sendername' => $sendername,
				'message' => $encoded_message,
				'routetype' => $routetype,
				'tid' => $template_id
			),
			$api_url
		);
	
		// Send HTTP request using WordPress HTTP API
		$response = wp_remote_get($request_url);
		
		// Check for errors
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			return array(
				"status" => "error",
				"message" => "Failed: $error_message"
			);
		}
	
		// Parse response
		$response_data = wp_remote_retrieve_body($response);
		$response_parts = explode(";", $response_data);

		$status = '';

		foreach ($response_parts as $part) {
		    $pair = explode(":", $part);
		    $key = trim($pair[0]);
		    $value = trim($pair[1]);
		    
		    if ($key === "Status") {
		        $status = $value;
		        break;
		    }
		}
	
		if (isset($status) && $status == 1) {
			$result = array(
				"status" => "success",
				"status_code" => 200,
				"message" => "Message sent successfully",
				"response" => $response_data
			);
		} else {
			$result = array(
				"status" => "error",
				"message" => "Failed",
				"response" => $response_data
			);
		}

		return $result;
	}

}