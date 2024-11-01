<?php

namespace TelinfyMessaging\Includes;

// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( TELINFY_WOOCOMMERCE_PLUGIN_PATH . 'includes/queryDb.php' );

use TelinfyMessaging\Includes\telinfy_query_db;

class telinfy_abandoned_controller {

	protected static $instance = null;
	public $query;

	public function __construct() {

		$this->query = telinfy_query_db::telinfy_get_instance();

		add_action( 'woocommerce_cart_updated', array( $this, 'telinfy_save_abandoned_cart_record' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'telinfy_save_abandoned_cart_record' ), 999 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'telinfy_save_abandoned_cart_record' ), 999 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'telinfy_save_abandoned_cart_record' ), 999 );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'telinfy_save_abandoned_cart_record' ), 999 );
		add_action( 'woocommerce_calculate_totals', array( $this, 'telinfy_save_abandoned_cart_record' ), 999 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'telinfy_remove_abd_cart_after_success_order' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'telinfy_remove_abd_cart_at_thank_you_page' ) );
		add_action( 'wp_login', array( $this, 'telinfy_user_login' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_form', array( $this, 'telinfy_tm_cart_abandonment_tracking_script' ) );

		// update phone number from the checkout page.
		add_action( 'wp_ajax_telinfy_tm_update_cart_abandonment_data', array( $this, 'telinfy_tm_update_cart_abandonment_data' ) );
		add_action( 'wp_ajax_nopriv_telinfy_tm_update_cart_abandonment_data', array( $this, 'telinfy_tm_update_cart_abandonment_data' ) );

	}

	public static function telinfy_get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * update abandoned record inlogin
     *
     * @return void
     */

	public function telinfy_user_login( $user_name, $user_info ) {
		$user_id        = $user_info->ID;
		if ( ! WC()->session ) return;
		$session_user_id        = WC()->session->get( 'user_id' );
		$session_key = WC()->session->get_customer_id();
		if ( $session_user_id ) {
			$this->query->telinfy_update_abd_cart_record_data(
				array( 'user_id' => $user_id, 'user_type' => 'user' ),
				array( 'user_id' => $session_user_id )
			);
		} else {
			$this->query->telinfy_update_abd_cart_record_data(
				array( 'user_id' => $user_id, 'user_type' => 'user' ),
				array( 'session_id' => $session_key )
			);
		}
	}

	/**
     * Save abandoned cart data to the table
     *
     * @return void
     */
	public function telinfy_save_abandoned_cart_record() {


		if ( $this->telinfy_check_bot() || ( is_admin() && ! wp_doing_ajax() ) || current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->ip_add      = \WC_Geolocation::get_ip_address();

		if ( is_user_logged_in() ) {
			$whatsapp_abd_enabled = get_option('wc_settings_telinfy_messaging_checkbox_abandoned_cart_whatsapp');
			$sms_abd_enabled = get_option('wc_settings_telinfy_messaging_checkbox_abandoned_cart_sms');

			if($whatsapp_abd_enabled == "yes" || $sms_abd_enabled =="yes" ){
				$abd_cart_time =  get_option('wc_settings_telinfy_messaging_abd_cart_time');
				if($abd_cart_time != null ){
					$this->telinfy_track_abandoned_cart( 'user' );
				}
			}
		} 
	}

	/**
     * Track abandoned cart 
     *
     * @return void
     */

	public function telinfy_track_abandoned_cart($type) {

		$cart_count   = wc()->cart->get_cart_contents_count();
		$abdc_data    = $cart_count ? json_encode( array( 'cart' => wc()->session->cart, 'currency' => get_woocommerce_currency() ) ) : '';

		$abdc_id = wc()->session->get( 'telinfy_cart_record_id' );
		$user_id = $type === 'user' ? get_current_user_id() : wc()->session->get( 'user_id' );
		$user_id = $user_id ? $user_id : 0;
		
		$check_insert = false;

		if ( $abdc_id ) {
			$result = $this->query->telinfy_get_abdct_record( $abdc_id );
			if ( $result ) {
				if ( $abdc_data ) {
					$cut_time = 'user' == $type ? $this->telinfy_member_abd_time() : 0;
					if ( $cut_time > $result->abandoned_cart_time ) { //out time	
						$this->query->telinfy_remove_abd_cart_record( $abdc_id );

						$check_insert = true;
					} else {  //in time
						$this->query->telinfy_update_abd_cart_record_data(
							array(
								'abandoned_cart_info' => $abdc_data,
								'abandoned_cart_time' => current_time( 'U' ),
								'current_lang'        => "",
								'user_id'             => $user_id,
								'user_type'           => $type
							),
							array( 'abd_id' => $abdc_id ) );
					}
				} else {
					$this->query->telinfy_remove_abd_cart_record( $abdc_id );
					if($user_id){
						$this->query->telinfy_remove_phone_record($user_id);
					}
				}
			} else {
				$check_insert = true;
			}
		} else {

			$check_insert = true;
		}
		
		if ( $check_insert && $abdc_data ) {
			if ( $user_id ) {
				$this->query->telinfy_remove_abd_cart_record_by_user_id( $user_id );
				$this->query->telinfy_remove_phone_record($user_id);
			}
			$insert_id = $this->query->telinfy_create_abd_cart_record( array(
				'user_id'             => $user_id,
				'abandoned_cart_info' => $abdc_data,
				'abandoned_cart_time' => current_time( 'U' ),
				'user_type'           => $type,
				'customer_ip'         => $this->ip_add
			) );
			$billing_phone = get_user_meta( $user_id, 'billing_phone', true );

			$this->query->telinfy_insert_abd_phone_record($billing_phone,$user_id);
			wc()->session->set( 'telinfy_cart_record_id', $insert_id );
			$current_list   = (array) wc()->session->get( 'telinfy_cart_record_ids_list' );
			$current_list[] = $insert_id;
			wc()->session->set( 'telinfy_cart_record_ids_list', $current_list );

		}

	}

	/**
	* check for bot requests 
	*
	* @return boolean
	*/

	public function telinfy_check_bot() {

		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return true;
		}

		$user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);

		$bots = array(
			'rambler',
			'scoutjet',
			'similarpages',
			'oozbot',
			'shrinktheweb.com',
			'aboutusbot',
			'followsite.com',
			'googlebot',
			'aport',
			'yahoo',
			'msnbot',
			'turtle',
			'sape_context',
			'gigabot',
			'snapbot',
			'alexa.com',
			'megadownload.net',
			'askpeter.info',
			'igde.ru',
			'ask.com',
			'qwartabot',
			'yanga.co.uk',
			'yandex',
			'yandexSomething',
			'Copyscape.com',
			'AdsBot-Google',
			'domaintools.com',
			'dataparksearch',
			'google-sitemaps',
			'appEngine-google',
			'feedfetcher-google',
			'liveinternet.ru',
			'xml-sitemaps.com',
			'agama',
			'mail.ru',
			'omsktele',
			'yetibot',
			'Nigma.ru',
			'bing.com',
			'dotnetdotcom',
			'AspiegelBot',
			'curl',
			'picsearch',
			'sape.bot',
			'metadatalabs.com',
			'h1.hrn.ru',
			'googlealert.com',
			'seo-rus.com',
			'yaDirectBot',
			'yandeG',
			
		);
		foreach ( $bots as $bot ) {
			if (stripos($user_agent, $bot) !== false || preg_match('/bot|crawl|slurp|spider|mediapartners/i', $user_agent)) {
				return true;
			}
		}

		return false;
	}

	/**
	* Remove abandoned cart data on thankyou page event
	*
	* @return void
	*/

	public function telinfy_remove_abd_cart_at_thank_you_page( $order_id ) {
		$this->telinfy_remove_abd_cart_when_cart_is_purchase( $order_id );
	}

	/**
	* Remove abandoned cart on purchase
	*
	* @return void
	*/

	public function telinfy_remove_abd_cart_when_cart_is_purchase( $order_id ) {


		
		$id = $this->query->telinfy_get_session( 'telinfy_cart_record_id' );
		if ( ! wc()->session ) {
			return;
		}
		$user_id = get_current_user_id();
		$ids = wc()->session->get( 'telinfy_cart_record_ids_list' );
		if ( ! empty( $ids ) ) {

			$this->query->telinfy_bulk_remove_abd_record( $ids );
			wc()->session->__unset( 'telinfy_cart_record_ids_list' );

			$this->query->telinfy_remove_phone_record($user_id);
		}

	}

	/**
	* Remove abandoned cart data on order success
	*
	* @return void
	*/
	public function telinfy_remove_abd_cart_after_success_order( $order_id ) {

		//Remove record if order success
		$this->telinfy_remove_abd_cart_when_cart_is_purchase( $order_id );
	}

	public function telinfy_member_abd_time() {

		$abd_cart_time =  (int)get_option('wc_settings_telinfy_messaging_abd_cart_time');

		$abd_cart_time_seconds = $abd_cart_time * HOUR_IN_SECONDS;

		return TELINFY_CURRENT_TIME -  $abd_cart_time_seconds;
	}

	/**
	* Add script to update cart data on chekout form field events
	*
	* @return void
	*/
	public function telinfy_tm_cart_abandonment_tracking_script(){
		$telinfy_ca_ignore_users = get_option( 'telinfy_ca_ignore_users' );
		$current_user           = wp_get_current_user();
		$roles                  = $current_user->roles;
		$role                   = array_shift( $roles );
		if ( ! empty( $telinfy_ca_ignore_users ) ) {
			foreach ( $telinfy_ca_ignore_users as $user ) {
				$user = strtolower( $user );
				$role = preg_replace( '/_/', ' ', $role );
				if ( $role === $user ) {
					return;
				}
			}
		}

		global $post;
		wp_enqueue_script(
			'telinfy-abandon-tracking',
			plugins_url( 'includes/assets/js/telinfy-cart-abandonment-tracking.js', TELINFY_WOOCOMMERCE_INCLUDES_PATH ),
			array( 'jquery' ),
			TELINFY_ABANDON_VER,
			true
		);

		$vars = array(
			'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
			'_nonce'                    => wp_create_nonce( 'telinfy_tm_update_cart_abandonment_data' ),
			'_post_id'                  => get_the_ID(),
			'enable_ca_tracking'        => true,
		);

		wp_localize_script( 'telinfy-abandon-tracking', 'telinfy_ca_vars', $vars );
	}

	/**
	* Update phone number on filling address
	*
	* @return void
	*/

	public function telinfy_tm_update_cart_abandonment_data(){

		check_ajax_referer( 'telinfy_tm_update_cart_abandonment_data', 'security' );
		$post_data = $this->telinfy_tm_sanitize_post_data();
		if ( isset( $post_data['telinfy_phone'] ) ) {
			$user_id = get_current_user_id();
			if($user_id){
				$this->query->telinfy_insert_abd_phone_record($post_data['telinfy_phone'],$user_id);
			}
			
		}

	}

	/**
	* Sanitize post data
	*
	* @return array
	*/

	public function telinfy_tm_sanitize_post_data() {
	    $input_post_values = [
	        'telinfy_email' => [
	            'default' => '',
	            'sanitize' => 'sanitize_email',
	        ],
	        'telinfy_phone' => [
	            'default' => '',
	            'sanitize' => 'sanitize_text_field',
	        ],
	        'telinfy_post_id' => [
	            'default' => 0,
	            'sanitize' => 'absint',
	        ],
	    ];

	    $sanitized_post = [];

	    foreach ($input_post_values as $key => $input_post_value) {
	        if (isset($_POST[$key])) {
	            $sanitized_post[$key] = call_user_func($input_post_value['sanitize'], $_POST[$key]);
	        } else {
	            $sanitized_post[$key] = $input_post_value['default'];
	        }
	    }

	    return $sanitized_post;
	}


}