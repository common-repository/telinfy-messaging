<?php

namespace TelinfyMessaging\Api;

use TelinfyMessaging\Includes\telinfy_query_db;

// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class telinfy_whatsapp_connector{

    private $client_id;
    private $client_secret;
    private $refresh_token;
    private $access_token;
    private $instance_url;
    public $querydb;
    private $retry = 0;
    protected static $instance = null;


    public function __construct(){

        $this->api_base_url = get_option( 'wc_settings_telinfy_messaging_api_base_url_whatsapp' );
        $this->api_key = get_option( 'wc_settings_telinfy_messaging_api_key_whatsapp' );
    }

    public static function telinfy_get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }


    /**
    * validate the credentials
    *
    * @return array
    */
    public function telinfy_get_api_token($apiEndpoint="",$api_key="")
    {

        $cred_check = 0;

        $baseURL = rtrim($apiEndpoint, '/') . '/';
        $path = $baseURL."whatsapp-business/accounts";
        $header = array('content-type' => 'application/json');


        $header['api-key'] = $api_key;
        $response = wp_remote_get(
            $path,
            array(
                'method' => "GET",
                'timeout' => 240,
                'headers' => $header
            )
        );


        $res = !is_wp_error($response) && isset($response['body']) ? $response['body'] : "";
        $response = json_decode($res, true);
        if (isset($response['data'][0]['whatsAppBusinessId']) && $response['data'][0]['whatsAppBusinessId'] != "") {

            // $this->telinfy_set_api_info($response['data']['accessToken']);
            return array(
                "status"=>"success"
            );
        }
        else{
            return array(
                "status"=>"error",
                "message"=>"Please check the credentials"
            );
        }
    }

    /**
    * Build Api request body
    *
    * @return array
    */

    public function telinfy_render_whatsapp_body($body_params,$to,$templateName,$header_image_link){
        
        $language=get_option("wc_settings_telinfy_messaging_whatsapp_language"); 
        // $button_redirect_url =  get_permalink(wc_get_page_id('myaccount'));

        $default_country_code = get_option("wc_settings_telinfy_messaging_default_country_code");
        $to = $this->ensureCountryCode($to,$default_country_code);

        $page_slug = 'myaccount';
        $page_id = wc_get_page_id($page_slug);

        if (isset($page_id)) {
            $page_permalink = get_permalink($page_id);

            // Get the base URL
            $base_url = site_url();

            // Remove the base URL from the permalink
            $relative_permalink = str_replace($base_url, '', $page_permalink);
        }


        $params = array(
                    array(
                        'sub_type' => 'url',
                        'index' => '0',
                        'parameters' => array(
                            array(
                                'type' => 'text',
                                'text' => $relative_permalink
                            )
                        )
                    )
                );

        $body = array(
                "to"=>$to,
                "type"=>"template",
                "templateName"=>$templateName,
                "language"=>$language,
                "header"=>array(
                    'parameters' => array(
                        array(
                            'type' => 'image',
                            'image' => array(
                                'link' => $header_image_link
                            )
                        )
                    )
                ),
                "body"=>array(
                    'parameters' => $body_params
                ),
                "button"=> $params
            );
        return $body;
    }


    /**
    * Call to Api end point
    *
    * @return void
    */

    public function telinfy_send_message($body,$to){

        $method = "POST";
        $api_base_url_whatsapp = get_option('wc_settings_telinfy_messaging_api_base_url_whatsapp');
        $trimmed_api_base_url = rtrim($api_base_url_whatsapp, '/');
        $endpoint = $trimmed_api_base_url."/whatsapp/templates/message";
        $response = $this->telinfy_send_api_request($endpoint,$method,json_encode($body));
        return $response;

    }

    /**
    * Function to fetch bussiness id of the account
    *
    * @return string
    */

    private function telinfy_get_whatsapp_bussiness_id(){

        $api_base_url_whatsapp = get_option('wc_settings_telinfy_messaging_api_base_url_whatsapp');
        $trimmed_api_base_url = rtrim($api_base_url_whatsapp, '/');
        $endpoint = $trimmed_api_base_url ."/whatsapp-business/accounts";
        $method = "GET";
        $get_whatsapp_bussiness_id_data = $this->telinfy_send_api_request($endpoint,$method);
        
        if(isset($get_whatsapp_bussiness_id_data["business_id"]) && $get_whatsapp_bussiness_id_data["business_id"]){
            return $get_whatsapp_bussiness_id_data["business_id"];
        }else{
            return 0;
        }
    }


    /**
    * Function to fetch WhatsApp templates
    *
    * @return array
    */

    public function telinfy_get_whatsapp_templates(){

        $business_id = $this->telinfy_get_whatsapp_bussiness_id();

        if(isset($business_id)){

            $api_base_url_whatsapp = get_option('wc_settings_telinfy_messaging_api_base_url_whatsapp');
            $baseURL = rtrim($api_base_url_whatsapp, '/') . '/';

            $endpoint = $baseURL."whatsapp/templates?whatsAppBusinessId=$business_id";
            $method = "GET";
            $whatsapp_templates = $this->telinfy_send_api_request($endpoint,$method);

            if(isset($whatsapp_templates['data']['status']) && $whatsapp_templates['data']['status'] == "404"){
                $new_business_id = $this->telinfy_get_whatsapp_bussiness_id();
                
                $endpoint = $trimmed_api_base_url."/whatsapp/templates?whatsAppBusinessId=$new_business_id";
                $whatsapp_templates = $this->telinfy_send_api_request($endpoint,$method);
            }

            if(isset($whatsapp_templates["business_id"])){

                $whatsapp_templates_list = $whatsapp_templates["business_id"];

                if(isset($whatsapp_templates_list)){

                    $whatsapp_template_names = array_column($whatsapp_templates_list, "name", "name");
                    
                    return $whatsapp_template_names;

                }else{

                    return 0;
                }
            }
        }else{
            return 0;
        }
    }

    /**
    * Connection to the endpoint
    *
    * @return void
    */

    private function telinfy_send_api_request($endpoint,$method,$body =""){
        
        // if(!isset($endpoint) || (!isset($this->access_token))){
        //     $response = $this->telinfy_get_api_token();// creating new token
        //     if(isset($response["status"]) && $response["status"] == "error"){
        //         return $response;
        //     }
        // }
        $path = $endpoint;
        if($method =="POST"){
            $header = array(
                "api-key" => $this->api_key,
                "content-type" => "application/json"
            );
            if (is_array($body) && count($body) > 0) {
                $body = http_build_query($body);
            }
            if ($method != "get") {
                $header['content-length'] = !empty($body) ? strlen($body) : 0;
            }
            $request = wp_remote_post(
                $path,
                array(
                    'method' => strtoupper($method),
                    'timeout' => 240,
                    'headers' => $header,
                    'body' => $body
                )
            );
        }else{
            $header = array(
                "api-key" => $this->api_key
            );

            $request = wp_remote_get(
                $path,
                array(
                    'method' => strtoupper($method),
                    'timeout' => 240,
                    'headers' => $header
                )
            );
        }
        if(is_wp_error($request)){
            return array("status"=>"error","message"=>"Telinfy API: Request failed");
        }

        $response = isset($request['body']) ? json_decode($request['body'],true) : [];


        if(wp_remote_retrieve_response_code( $request ) == 201 && $response["success"]){

            $response = array(
                "status"=>"created",
                "message"=>"Successfull",
                "data"=>$response
            );
        }else if(is_array($response) && wp_remote_retrieve_response_code( $request ) == 401 && ($response["message"] == "Wrong Api Key"|| $response["message"] == "Invalid session")){

             $response = array(
                "status"=>"error",
                "message"=>$response["message"],
            );

        }else if(wp_remote_retrieve_response_code( $request ) == 404){

            $response = array(
                "status"=>"error",
                "message"=>$response["message"]
            );

        }else if(isset($response["data"][0]["whatsAppBusinessId"])){

            $business_id = $response["data"][0]["whatsAppBusinessId"];

            $response = array(
                "status"=>"success",
                "message"=>"business id fetched",
                "business_id"=>$business_id
            );

        }else if(isset($response["data"]["waba_templates"])){

            $weba_templates = $response["data"]["waba_templates"];
            $response = array(
                "status"=>"success",
                "message"=>"business templates fetched",
                "business_id"=>$weba_templates
            );

        }else if(isset($response["data"]["status"]) && $response["data"]["status"] == "ACCEPTED"){
            $response = array(
                "status"=>"success",
                "status_code" => 200,
                "message"=>"message send Successfully",
                "response"=>$response["data"]
            );
        }
        else{

            $response = array(
                "status"=>"error",
                "message"=>isset($response[0]["message"])?$response[0]["message"]:"telinfy api error",
                "data"=>$response
            );
        }

        return $response;

    }


    public function ensureCountryCode($phoneNumber, $defaultCountryCode) {
        // Remove non-digit characters from the phone number
        $cleanedNumber = preg_replace('/\D/', '', $phoneNumber);

        // Remove leading zeros
        $cleanedNumber = ltrim($cleanedNumber, '0');

        // Check if the cleaned number is not empty
        if ($cleanedNumber) {
            // Check if the cleaned number starts with the plus sign
            if (strpos($cleanedNumber, '+') === 0) {
                return $cleanedNumber;
            }

            // Check if the cleaned number starts with the default country code
            if (strpos($cleanedNumber, $defaultCountryCode) === 0) {
                return '+' . $cleanedNumber;
            }

            // Append the default country code if none is present
            return '+' . $defaultCountryCode . $cleanedNumber;
        }

        return null; // Or any default value you prefer when the number is empty
    }

}