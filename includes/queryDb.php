<?php

namespace TelinfyMessaging\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class telinfy_query_db {

	public $cart_record_tb;
	public $wpdb;
	public $user_phone_tb;
	public $user_queue_tb;
	protected static $instance = null;

	protected $format = array(
		'user_id'             => '%d',
		'abandoned_cart_info' => '%s',
		'abandoned_cart_time' => '%s',
		'abd_sent'			  => '%d',
		'current_lang'        => '%s',
		'user_type'           => '%s',
		'session_id'          => '%s',
		'whatsapp_sent'       => '%d',
		'whatsapp_complete'   => '%d',
		'sms_sent'     		  => '%d',
		'sms_complete'        => '%s',
		'message_complete'    => '%s',
		'customer_ip'         => '%s',
	);

	public function __construct() {

		global $wpdb;
		$this->wpdb             = $wpdb;
		$this->cart_record_tb   = $wpdb->prefix . "telinfy_abandoned_cart_record";
		$this->user_phone_tb	= $wpdb->prefix . "telinfy_user_phone";
		$this->user_queue_tb    = $wpdb->prefix . "telinfy_order_message_queue";

	}

	public static function telinfy_get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
	* Set the session value
	*
	*/

	public static function telinfy_set_session( $key, $value ) {
		WC()->session->set( $key, $value );
	}

	/**
	* Get the session value 
	*
	*/

	public static function telinfy_get_session( $key ) {
		return WC()->session ? WC()->session->get( $key ) : '';
	}

	/**
	* Update abandoned cart record
	*
	*/

	public function telinfy_update_abd_cart_record_data($data = array(), $where = array()){

		global $wpdb;

		$data_fm = $where_fm = array();

		foreach ( $data as $item ) {
			if ( isset( $this->format[ $item ] ) ) {
				$data_fm[] = $this->format[ $item ];
			}
		}

		foreach ( $where as $item ) {
			if ( isset( $this->format[ $item ] ) ) {
				$where_fm[] = $this->format[ $item ];
			}
		}

		return $wpdb->update( $this->cart_record_tb, $data, $where, $data_fm, $where_fm );

	}

	/**
	* Fetch abandoned cart record
	*
	*/

	public function telinfy_get_abdct_record( $abdc_id ) {
		global $wpdb;
		$query = "SELECT * FROM {$this->cart_record_tb} WHERE abd_id = %s";

		return $wpdb->get_row( $wpdb->prepare( $query, $abdc_id ) );
	}

	/**
	* Remove abandoned cart record
	*
	*/

	public function telinfy_remove_abd_cart_record( $id) {
		global $wpdb;
		$where = array( 'abd_id' => $id );

		return $wpdb->delete( $this->cart_record_tb, $where, array( '%d' ) );
	}

	/**
	* Remove abandoned cart record by user id
	*
	*/

	public function telinfy_remove_abd_cart_record_by_user_id( $id ) {
		global $wpdb;
		$wpdb->delete( $this->cart_record_tb, array( 'user_id' => $id ), array( '%d', '%d' ) );
	}

	/**
	* Insert abandoned cart record
	*
	*/

	public function telinfy_create_abd_cart_record( $data = array() ) {
		global $wpdb;

		$data_fm = $where_fm = array();

		foreach ( $data as $item ) {
			if ( isset( $format[ $item ] ) ) {
				$data_fm[] = $this->format[ $item ];
			}
		}	
		$wpdb->insert( $this->cart_record_tb, $data, $data_fm );

		return $wpdb->insert_id;
	}

	/**
	* Bulk remove abandoned cart record
	*
	*/

	public function telinfy_bulk_remove_abd_record( $ids ) {

		global $wpdb;
		$ids   = implode( ',', array_map( 'absint', $ids ) );
		$query = "delete from {$this->cart_record_tb} where abd_id in(%s) ";
		$sql = $wpdb->prepare( $query, $ids );
		$res = $wpdb->query( $sql );
	}

	/**
	* Remove abandoned cart phone number record
	*
	*/

	public function telinfy_remove_phone_record( $user_id ) {
		global $wpdb;
		$query = "delete from {$this->user_phone_tb} where user_id = %d ";
		$sql = $wpdb->prepare($query,$user_id);
        $res = $wpdb->query($sql);
	}


	/**
	* Remove abandoned cart record by time
	*
	*/

	public function telinfy_remove_abd_cart_record_by_time( $time,$abd_cart_time_hour ) {
		global $wpdb;
		$time_in_seconds  = $time * DAY_IN_SECONDS;
		$abd_cart_time_hour_in_seconds = $abd_cart_time_hour * HOUR_IN_SECONDS;
		$total_time_to_leave_abd_cart = $time_in_seconds + $abd_cart_time_hour_in_seconds;
		$target_time_to_remove =  TELINFY_CURRENT_TIME - $total_time_to_leave_abd_cart;

		$query = "delete from {$this->cart_record_tb} where abandoned_cart_time < %d limit 500";
		$wpdb->query( $wpdb->prepare( $query, $target_time_to_remove ) );
	}

	/**
	* Get abandoned cart records to notify customers
	*
	*/
	public function telinfy_get_list_message_to_send( $sent_count, $time ) {

		global $wpdb;
		$sent_count = $sent_count;

		$que_member = $wpdb->prepare(" AND (abandoned_cart_time < %s AND abd_sent < %d AND user_type = 'user')", $time, $sent_count);

		$query = "SELECT acr.*, upt.*, wpu.user_login, wpu.user_email FROM {$this->cart_record_tb} AS acr ";
		$query .= "LEFT JOIN {$wpdb->users} AS wpu ON acr.user_id = wpu.id ";
		$query .= "INNER JOIN {$this->user_phone_tb} AS upt ON acr.user_id = upt.user_id ";
		$query .= "WHERE acr.abandoned_cart_info NOT LIKE '\"\"' ";
		$query .= "AND acr.abandoned_cart_info NOT LIKE '[]' ";
		$query .= "AND acr.abandoned_cart_info NOT LIKE '{\"cart\":[]}' ";
		$query .= "AND acr.message_complete IS NULL {$que_member} ORDER BY acr.abd_id DESC";

	
		return $wpdb->get_results($query);

	}

	public function telinfy_update_sent_status($count,$type,$last_one,$abd_record_id){
		global $wpdb;
		$data = array($type."_sent" => $count);
		if($last_one){
			$data[$type."_complete"] = 1;
		}

		$where = array("abd_id" => $abd_record_id);

		$wpdb->update( $this->cart_record_tb, $data, $where);

	}

	public function telinfy_update_abd_phone_record($phone,$user_id){
		global $wpdb;
		$data = array("phone" => $phone);

		$where = array("user_id" => $user_id);

		$wpdb->update( $this->user_phone_tb, $data, $where);
	}

	public function telinfy_update_notified($abd_record_id,$last_one,$abd_sent =""){
		global $wpdb;
		$data = array();
		if($last_one){
			$data["message_complete"] = 1;
		}
		if($abd_sent){
			$data["abd_sent"] = $abd_sent;
		}

		$where = array("abd_id" => $abd_record_id);

		$wpdb->update( $this->cart_record_tb, $data, $where);

	}

	/**
	* Insert abandoned cart phone number record
	*
	*/

	public function telinfy_insert_abd_phone_record($phone,$user_id){

		global $wpdb;

		$update_query = "INSERT INTO {$this->user_phone_tb} (`user_id`,`phone`) VALUES (%d,%d) ON DUPLICATE KEY UPDATE `phone` = %d";

		$sql = $wpdb->prepare($update_query,$user_id,$phone,$phone);
        $res = $wpdb->query($sql);


	}

	public function telinfy_insert_message_queue($user_id,$customer_phone,$order_id){

		global $wpdb;
		$update_query = "INSERT IGNORE INTO {$this->user_queue_tb} (`user_id`,`status`,`phone`,`order_id`) VALUES (%d,%d,%d,%d)";
		$status = 0;
		$sql = $wpdb->prepare($update_query,$user_id,$status,$customer_phone,$order_id);
        $res = $wpdb->query($sql);
	}
	public function telinfy_get_message_queue($limit){

		global $wpdb;
		$query = "SELECT * FROM {$this->user_queue_tb} WHERE status < 2 ORDER BY `queue_id` ASC LIMIT %d";
		$prepared_query = $wpdb->prepare($query, $limit);
		return ( $wpdb->get_results( $prepared_query ) );
	}
	public function telinfy_get_message_disabled_queue($limit){

		global $wpdb;
		$query = "SELECT queue_id FROM {$this->user_queue_tb} WHERE status = 2 ORDER BY `queue_id` ASC LIMIT %d";
		$prepared_query = $wpdb->prepare($query, $limit);
		return ( $wpdb->get_results( $prepared_query ) );
	}
	public function telinfy_remove_message_queue($queue_id){
		global $wpdb;
		// Create a comma-separated list of placeholders for the SQL query
		$placeholders = implode(',', array_fill(0, count($queue_id), '%d'));

		// Create a comma-separated list of IDs for the SQL query
		$ids_string = implode(',', array_map('absint', $queue_id));

		$queue_delete = "DELETE FROM {$this->user_queue_tb} WHERE queue_id IN ({$placeholders})";

		// Use the prepare function to replace placeholders with actual values
		$queue_sql = $wpdb->prepare($queue_delete, $queue_id);
		// Execute the query
		$res = $wpdb->query($queue_sql);

	}

	public function telinfy_update_message_queue($queue_id){
		global $wpdb;
		// Create a comma-separated list of placeholders for the SQL query
		$placeholders = implode(',', array_fill(0, count($queue_id), '%d'));

		// Create a comma-separated list of IDs for the SQL query
		$ids_string = implode(',', array_map('absint', $queue_id));

		$queue_update = "UPDATE {$this->user_queue_tb} SET status = status + 1 WHERE queue_id IN ({$placeholders})";

		// Use the prepare function to replace placeholders with actual values
		$queue_sql = $wpdb->prepare($queue_update, $queue_id);
		// Execute the query
		$res = $wpdb->query($queue_sql);
	}

}

