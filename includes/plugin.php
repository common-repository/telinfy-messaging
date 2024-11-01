<?php

namespace TelinfyMessaging\Includes;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class telinfy_plugin{

    public $cron;
    protected static $instance = null;

    public static function telinfy_get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
    * Tasks that need to run when activating the plugin
    *
    * @return void
    */

    public function telinfy_activate (){

        global $wpdb;
        
        $telinfy_collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $telinfy_collate = $wpdb->get_charset_collate();
        }

        $telinfy_abd_record_tb = $wpdb->prefix . "telinfy_abandoned_cart_record";

        $query = "CREATE TABLE IF NOT EXISTS {$telinfy_abd_record_tb} (
                             `abd_id` int(11) NOT NULL AUTO_INCREMENT,
                             `user_id` int(11) NOT NULL,
                             `abandoned_cart_info` text COLLATE utf8_unicode_ci NOT NULL,
                             `abandoned_cart_time` int(11) NOT NULL,
                             `abd_sent` int(11) NOT NULL,
                             `current_lang` text,
                             `user_type` text,
                             `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
                             `whatsapp_sent` int(3) NOT NULL,
                             `whatsapp_complete` enum('0','1'),
                             `sms_sent` int(3) NOT NULL,
                             `sms_complete` enum('0','1'),
                             `message_complete` enum('0','1'),
                             `customer_ip` tinytext COLLATE utf8_unicode_ci,
                             PRIMARY KEY  (`abd_id`)
                             ) $telinfy_collate";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $query );


        $user_phone_tb = $wpdb->prefix . "telinfy_user_phone";
        $query       = "CREATE TABLE IF NOT EXISTS {$user_phone_tb} (
                        `abd_ph_id` int(11) NOT NULL auto_increment,
                        `user_id` varchar(50) collate utf8_unicode_ci,
                        `phone` varchar(50) collate utf8_unicode_ci,
                        PRIMARY KEY  (`abd_ph_id`),
                        UNIQUE KEY `unique_user_id` (`user_id`)
                        ) $telinfy_collate AUTO_INCREMENT=1 ";
        dbDelta( $query );
        $user_queue_tb = $wpdb->prefix . "telinfy_order_message_queue";
        $query       = "CREATE TABLE IF NOT EXISTS {$user_queue_tb} (
                        `queue_id` int(11) NOT NULL auto_increment,
                        `status` int(3) NOT NULL,
                        `user_id` varchar(50) collate utf8_unicode_ci,
                        `phone` varchar(50) collate utf8_unicode_ci,
                        `order_id` varchar(50) collate utf8_unicode_ci,
                        PRIMARY KEY  (`queue_id`),
                        UNIQUE KEY `unique_order_id` (`order_id`)
                        ) $telinfy_collate AUTO_INCREMENT=1 ";
        dbDelta( $query );

    }

}