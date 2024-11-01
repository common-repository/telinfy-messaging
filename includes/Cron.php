<?php

namespace TelinfyMessaging\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class telinfy_cron {

	protected static $instance = null;

	public function __construct() {
		
		add_filter( 'cron_schedules', array( $this, 'telinfy_add_cron_schedule' ) );

		$whatsapp_abd_enabled = get_option('wc_settings_telinfy_messaging_checkbox_abandoned_cart_whatsapp');
		$sms_abd_enabled = get_option('wc_settings_telinfy_messaging_checkbox_abandoned_cart_sms');

		$whatsapp_orderc_enabled = get_option('wc_settings_telinfy_messaging_checkbox_order_confirmation_whatsapp');
		$sms_orderc_enabled = get_option('wc_settings_telinfy_messaging_checkbox_order_confirmation_sms');

		if($whatsapp_abd_enabled == "yes" || $sms_abd_enabled =="yes" ){

			if ( ! wp_next_scheduled( 'telinfy_tm_execute_cron' ) ) {
				wp_schedule_event( time(), 'telinfy_tm_abd_cart', 'telinfy_tm_execute_cron' );
			}

			add_action( 'telinfy_tm_execute_cron', array( $this, 'telinfy_tm_execute_cron' ) );

			if ( ! wp_next_scheduled( 'telinfy_tm_remove_abandoned_cart' ) ) {
				wp_schedule_event( time(), 'telinfy_tm_abd_cart_remove', 'telinfy_tm_remove_abandoned_cart' );
			}

			add_action( 'telinfy_tm_remove_abandoned_cart', array( $this, 'telinfy_tm_remove_abandoned_cart' ) );

			
		}

		if($whatsapp_orderc_enabled == "yes" || $sms_orderc_enabled =="yes"){
			if ( ! wp_next_scheduled( 'telinfy_tm_send_queue' ) ) {
				wp_schedule_event( time(), 'telinfy_tm_send_queue_message', 'telinfy_tm_send_queue' );
			}

			add_action( 'telinfy_tm_send_queue', array( $this, 'telinfy_tm_send_queue' ) );
		}

	}

	public static function telinfy_get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }


	public function telinfy_add_cron_schedule( $schedules ) {

		$whatsapp_abd_enabled = get_option('wc_settings_telinfy_messaging_checkbox_abandoned_cart_whatsapp');
		$sms_abd_enabled = get_option('wc_settings_telinfy_messaging_checkbox_abandoned_cart_sms');

		if($whatsapp_abd_enabled == "yes" || $sms_abd_enabled =="yes" ){

			$interval = (int)get_option('wc_settings_telinfy_messaging_abd_cart_cron_interval');
			$abd_cart_send_time = get_option('wc_settings_telinfy_messaging_abd_cart_send_time');
			$hours_array = explode(",",$abd_cart_send_time);

			$hours_array = array_filter($hours_array, 'is_numeric');
			$hours_array = array_map('intval', $hours_array);
			$hours_array = array_unique($hours_array);
			$hours_array = array_values($hours_array);

      		$abd_cart_send_time_count = count($hours_array);

			if($interval && $abd_cart_send_time_count){
				$interval_seconds = $interval* MINUTE_IN_SECONDS;
				$schedules['telinfy_tm_abd_cart'] = array(
					'interval' => $interval_seconds,
					'display'  => __( 'Telinfy Abandoned Cart' ),
				);
			}

			$time_in_day = (int)get_option('wc_settings_telinfy_messaging_abd_cart_remove_interval');
			$abd_cart_remove_time = (int)get_option('wc_settings_telinfy_messaging_abd_cart_remove_time');

			if($time_in_day && $abd_cart_remove_time){
				$time_in_seconds = $time_in_day * DAY_IN_SECONDS;


				$schedules['telinfy_tm_abd_cart_remove'] = array(
					'interval' => $time_in_seconds,
					'display'  => __( 'Telinfy Abandoned Cart Remove' ),
				);
			}
			
		}
		$whatsapp_orderc_enabled = get_option('wc_settings_telinfy_messaging_checkbox_order_confirmation_whatsapp');
		$sms_orderc_enabled = get_option('wc_settings_telinfy_messaging_checkbox_order_confirmation_sms');

		if($whatsapp_orderc_enabled == "yes" || $sms_orderc_enabled =="yes"){
			$message_queue_interval = (int)get_option('wc_settings_telinfy_messaging_message_queue_cron_time');
			$message_queue_interval_seconds = $message_queue_interval* MINUTE_IN_SECONDS;
			$schedules['telinfy_tm_send_queue_message'] = array(
				'interval' => $message_queue_interval_seconds,
				'display'  => __( 'Telinfy Send Queue Message' ),
			);
		}

		return $schedules;

	}

	public function telinfy_tm_execute_cron() {
		
		do_action( 'telinfy_tm_cron_send_message_abd_cart' );
	}

	public function telinfy_tm_remove_abandoned_cart() {

		do_action( 'telinfy_tm_cron_abd_cart_remove' );
		
	}
	public function telinfy_tm_send_queue() {

		do_action( 'telinfy_tm_cron_send_message' );
		
	}
}
