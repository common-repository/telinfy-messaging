<?php
/**
 * Telinfy Messaging
 *
 * Uninstalling Telinfy Sms Rcs WhatsApp Alert plugin. Remove plugin tables, options.
 *
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// remove options 

$remove_options_array = array(
    "wc_settings_telinfy_messaging_api_key_whatsapp",
    "wc_settings_telinfy_messaging_api_secret_whatsapp",
    "wc_settings_telinfy_messaging_whatsapp_cred_check",
    "wc_settings_telinfy_messaging_whatsapp_template_name_order_confirmation",
    "wc_settings_telinfy_messaging_whatsapp_template_name_order_cancellation",
    "wc_settings_telinfy_messaging_whatsapp_template_name_order_refund",
    "wc_settings_telinfy_messaging_whatsapp_template_name_order_notes",
    "wc_settings_telinfy_messaging_whatsapp_template_name_order_shipment",
    "wc_settings_telinfy_messaging_whatsapp_template_name_other_order_status",
    "wc_settings_telinfy_messaging_whatsapp_template_name_abandoned_cart",
    "wc_settings_telinfy_messaging_whatsapp_language",
    "wc_settings_telinfy_messaging_whatsapp_file_upload",
    "wc_settings_telinfy_messaging_checkbox_order_confirmation_whatsapp",
    "wc_settings_telinfy_messaging_checkbox_order_cancellation_whatsapp",
    "wc_settings_telinfy_messaging_checkbox_order_refund_whatsapp",
    "wc_settings_telinfy_messaging_checkbox_order_notes_whatsapp",
    "wc_settings_telinfy_messaging_checkbox_order_shipment_whatsapp",
    "wc_settings_telinfy_messaging_checkbox_other_order_status_whatsapp",
    "wc_settings_telinfy_messaging_checkbox_abandoned_cart_whatsapp",
    "wc_settings_telinfy_messaging_api_key_sms",
    "wc_settings_telinfy_messaging_api_secret_sms",
    "wc_settings_telinfy_messaging_sms_sender_name",
    "wc_settings_telinfy_messaging_sms_tid_order_confirmation",
    "wc_settings_telinfy_messaging_sms_tid_order_cancellation",
    "wc_settings_telinfy_messaging_sms_tid_order_refund",
    "wc_settings_telinfy_messaging_sms_tid_order_notes",
    "wc_settings_telinfy_messaging_sms_tid_order_shipment",
    "wc_settings_telinfy_messaging_sms_tid_other_order_status",
    "wc_settings_telinfy_messaging_sms_tid_abandoned_cart",
    "wc_settings_telinfy_messaging_checkbox_order_confirmation_sms",
    "wc_settings_telinfy_messaging_checkbox_order_cancellation_sms",
    "wc_settings_telinfy_messaging_checkbox_order_refund_sms",
    "wc_settings_telinfy_messaging_checkbox_order_notes_sms",
    "wc_settings_telinfy_messaging_checkbox_order_shipment_sms",
    "wc_settings_telinfy_messaging_checkbox_other_order_status_sms",
    "wc_settings_telinfy_messaging_checkbox_abandoned_cart_sms",
    "wc_settings_telinfy_messaging_abd_cart_cron_interval",
    "wc_settings_telinfy_messaging_abd_cart_time",
    "wc_settings_telinfy_messaging_abd_cart_send_time",
    "wc_settings_telinfy_messaging_abd_cart_remove_interval",
    "wc_settings_telinfy_messaging_abd_cart_remove_time",
    "wc_settings_telinfy_messaging_sms_tdata_abandoned_cart",
    "wc_settings_telinfy_messaging_sms_tdata_order_confirmation",
    "wc_settings_telinfy_messaging_sms_tdata_order_cancellation",
    "wc_settings_telinfy_messaging_sms_tdata_order_refund",
    "wc_settings_telinfy_messaging_sms_tdata_order_notes",
    "wc_settings_telinfy_messaging_sms_tdata_order_shipment",
    "wc_settings_telinfy_messaging_sms_tdata_other_order_status",
    "wc_settings_telinfy_messaging_api_base_url_whatsapp",
    "wc_settings_telinfy_messaging_message_queue_cron_time",
    "wc_settings_telinfy_messaging_message_queue_cron_item"
);

foreach ($remove_options_array as $option_name) {
    delete_option($option_name);
}


// removing tables

global $wpdb;

$abd_cart_record_tb   = $wpdb->prefix . "telinfy_abandoned_cart_record";
$abd_user_phone_tb   = $wpdb->prefix . "telinfy_user_phone";
$user_queue_tb    = $wpdb->prefix . "telinfy_order_message_queue";

$sql = "DROP TABLE IF EXISTS  {$abd_cart_record_tb}, {$abd_user_phone_tb}, {$user_queue_tb}";
$wpdb->query( $sql );

// delete cron event
$timestamp = wp_next_scheduled('telinfy_tm_execute_cron');
wp_unschedule_event($timestamp, 'telinfy_tm_execute_cron');

$timestamp = wp_next_scheduled('telinfy_tm_remove_abandoned_cart');
wp_unschedule_event($timestamp, 'telinfy_tm_remove_abandoned_cart');

$timestamp = wp_next_scheduled('telinfy_tm_send_queue');
wp_unschedule_event($timestamp, 'telinfy_tm_send_queue');

// Output a message to indicate the uninstall process
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Your plugin has been uninstalled.');
}
