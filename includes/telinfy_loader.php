<?php
/**
 * Telinfy Plugin Loader.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

if ( ! class_exists( 'telinfy_loader' ) ) {

	/**
	 * Class telinfy_loader.
	 */
	final class telinfy_loader {


		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance = null;

		/**
		 *  Initiator
		 */
		public static function telinfy_get_instance() {

			if ( is_null( self::$instance ) ) {

				self::$instance = new self();

				/**
				 * Telinfy loaded.
				 *
				 * Fires when Telinfy was fully loaded and instantiated.
				 *
				 */
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			if ( ! defined( 'TELINFY_WOOCOMMERCE_INCLUDES_PATH' ) ) {
				define('TELINFY_WOOCOMMERCE_INCLUDES_PATH', plugin_dir_path( __FILE__ ));
			}

			$this->telinfy_define_constants();

			add_action( 'plugins_loaded', array( $this, 'telinfy_load_plugin' ), 99 );

			// Remove the cron task when the plugin has been deactivated
			register_deactivation_hook(__FILE__, 'telinfy_messaging_deactivation');

		}

		/**
		 * Defines all constants
		 *
		 * @since 1.0.0
		 */
		public function telinfy_define_constants() {

			define( 'TELINFY_CURRENT_PLUGIN_NAME', 'Telnify messaging' );

		}

		/**
		 * Loads plugin files.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function telinfy_load_plugin() {


			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', array( $this, 'telinfy_missing_wc_notice' ) );
				return;
			}
			$this->telinfy_load_core_files();

			if(is_admin()) {
			    $adminController = \TelinfyMessaging\Controllers\telinfy_admin_controller::telinfy_get_instance();
			    $adminController->init();
			}

			$messageController = \TelinfyMessaging\Controllers\telinfy_message_controller::telinfy_get_instance();
			$messageController->init();

			\TelinfyMessaging\Includes\telinfy_abandoned_controller::telinfy_get_instance();

			\TelinfyMessaging\Includes\telinfy_cron::telinfy_get_instance();

			\TelinfyMessaging\Controllers\telinfy_cron_controller::telinfy_get_instance();

			\TelinfyMessaging\Api\telinfy_sms_connector::telinfy_get_instance();

			// add_action( 'admin_notices', array( $this, '<<function name>>' ) );

			// Hook to run when the cron schedule is triggered
			add_action('woocommerce_telinfy_messaging_cron', function() {
			    // Only proceed if WooCommerce is available
			    if(function_exists('wc')) {
			        $cronController = \TelinfyMessaging\Controllers\telinfy_cron_controller::telinfy_get_instance();
			        $cronController->cron();
			    }
			});
		}


		/**
		 * Load Core Components.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function telinfy_load_core_files() {

			include_once( TELINFY_WOOCOMMERCE_PLUGIN_PATH . 'controllers/adminController.php' );
			include_once( TELINFY_WOOCOMMERCE_PLUGIN_PATH . 'controllers/cronController.php' );
			include_once( TELINFY_WOOCOMMERCE_PLUGIN_PATH . 'controllers/messageController.php' );
			include_once( TELINFY_WOOCOMMERCE_PLUGIN_PATH . 'controllers/abandonedController.php' );
			include_once( TELINFY_WOOCOMMERCE_PLUGIN_PATH . 'includes/Cron.php' );
			include_once( TELINFY_WOOCOMMERCE_PLUGIN_PATH . 'api/smsConnector.php');
			include_once( TELINFY_WOOCOMMERCE_PLUGIN_PATH . 'api/telinfyConnector.php');
		}

		/**
		 * Deactivate plugin
		 */

		function telinfy_messaging_deactivation() {

		    wp_clear_scheduled_hook( 'woocommerce_telinfy_messaging_cron' );

		}

		/**
		 * WooCommerce fallback notice.
		 *
		 * @return void
		 */
		public function telinfy_missing_wc_notice() {
			/* translators: %s WC download URL link. */
			echo '<div class="error"><p><strong>' . sprintf( esc_html__( '%1$s requires WooCommerce to be installed and active. You can download %2$s here.', 'telinfy-messaging' ), esc_html( TELINFY_CURRENT_PLUGIN_NAME ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
		}
	}


	/**
	 *  Prepare if class 'telinfyLoader' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	telinfy_loader::telinfy_get_instance();
}


if ( ! function_exists( 'telinfy_load' ) ) {
	/**
	 * Get global class.
	 *
	 * @return object
	 */
	function telinfy_load() {
		return telinfy_loader::telinfy_get_instance();
	}
}

