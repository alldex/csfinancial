<?php
/**
 * Plugin Name: AffiliateWP Ranks
 * Plugin URI: http://propluginmarketplace.com/plugins/affiliatewp-ranks
 * Description: Create affiliate ranks to classify your affiliates into categories, or different levels with thier own special commission rates.
 * Author: Christian Freeman
 * Author URI: http://propluginmarketplace.com
 * Version: 1.0.1
 * Text Domain: affiliatewp-ranks
 * Domain Path: languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AffiliateWP_Ranks' ) ) {

	final class AffiliateWP_Ranks {

		/**
		 * Plugin instance.
		 *
		 * @see instance()
		 * @type object
		 */
		private static $instance;

		/**
		 * URL to this plugin's directory.
		 *
		 * @type string
		 */
		public static  $plugin_dir;
		public static  $plugin_url;
		private static $version;

		/**
		 * The settings instance variable
		 *
		 * @var AffiliateWP_Ranks_Settings
		 * @since 1.0
		 */
		public $settings;
		
		/**
		 * Class Properties
		 *
		 * @var AffiliateWP_Ranks_Emails
		 * @since 1.0.x
		 */
		// public $emails;
	
		/**
		 * The integrations handler instance variable
		 *
		 * @var AffiliateWP_Ranks_Base
		 * @since 1.0
		 */
		// public $integrations;

		/**
		 * Main AffiliateWP_Ranks Instance
		 *
		 * Insures that only one instance of AffiliateWP_Ranks exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0
		 * @static
		 * @staticvar array $instance
		 * @return The one true AffiliateWP_Ranks
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof AffiliateWP_Ranks ) ) {
				
				self::$instance = new AffiliateWP_Ranks;
				self::$version  = '1.0.1';

				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->setup_objects();
				self::$instance->hooks();
				// self::$instance->load_textdomain();

			}

			return self::$instance;
		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 1.0
		 * @access protected
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'affiliatewp-ranks' ), '1.0' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @since 1.0
		 * @access protected
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'affiliatewp-ranks' ), '1.0' );
		}

		/**
		 * Constructor Function
		 *
		 * @since 1.0
		 * @access private
		 */
		private function __construct() {
			self::$instance = $this;
		}

		/**
		 * Reset the instance of the class
		 *
		 * @since 1.0
		 * @access public
		 * @static
		 */
		public static function reset() {
			self::$instance = null;
		}
		
		/**
		 * Setup plugin constants
		 *
		 * @access private
		 * @since 1.0
		 * @return void
		 */
		private function setup_constants() {
			// Plugin version
			if ( ! defined( 'AFFWP_RANKS_VERSION' ) ) {
				define( 'AFFWP_RANKS_VERSION', self::$version );
			}

			// Plugin Folder Path
			if ( ! defined( 'AFFWP_RANKS_PLUGIN_DIR' ) ) {
				define( 'AFFWP_RANKS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Folder URL
			if ( ! defined( 'AFFWP_RANKS_PLUGIN_URL' ) ) {
				define( 'AFFWP_RANKS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Root File
			if ( ! defined( 'AFFWP_RANKS_PLUGIN_FILE' ) ) {
				define( 'AFFWP_RANKS_PLUGIN_FILE', __FILE__ );
			}
			
			// API URL
			if ( ! defined( 'AFFWP_RANKS_API_URL' ) ) {
				define( 'AFFWP_RANKS_API_URL', 'http://propluginmarketplace.com' );
			}
			
			// Software Title
			if ( ! defined( 'AFFWP_RANKS_SOFTWARE_TITLE' ) ) {
				define( 'AFFWP_RANKS_SOFTWARE_TITLE', 'AffiliateWP Ranks' );
			}
		}

		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function includes() {

				require_once AFFWP_RANKS_PLUGIN_DIR . 'includes/admin/class-settings.php';

				require_once AFFWP_RANKS_PLUGIN_DIR . 'includes/functions.php';
			// require_once AFFWP_RANKS_PLUGIN_DIR . 'includes/scripts.php';
				require_once AFFWP_RANKS_PLUGIN_DIR . 'includes/actions.php';
				require_once AFFWP_RANKS_PLUGIN_DIR . 'includes/filters.php';
				require_once AFFWP_RANKS_PLUGIN_DIR . 'includes/compatibility.php';
				require_once AFFWP_RANKS_PLUGIN_DIR . 'includes/shortcodes.php';

			// require_once AFFWP_RANKS_PLUGIN_DIR . 'includes/class-emails.php';
			// require_once AFFWP_RANKS_PLUGIN_DIR . 'integrations/class-base.php';


			/* Load the class for each integration enabled
			foreach ( affiliate_wp()->integrations->get_enabled_integrations() as $filename => $integration ) {

				if ( file_exists( AFFWP_RANKS_PLUGIN_DIR . 'integrations/class-' . $filename . '.php' ) ) {
					require_once AFFWP_RANKS_PLUGIN_DIR . 'integrations/class-' . $filename . '.php';
				}

			}
			*/
			
		}

		/**
		 * Setup all objects
		 *
		 * @access public
		 * @since 1.0
		 * @return void
		 */
		public function setup_objects() {
			self::$instance->settings = new AffiliateWP_Ranks_Settings;
			// self::$instance->emails = new AffiliateWP_Ranks_Emails;
			// self::$instance->integrations = new AffiliateWP_Ranks_Base;
		}

		/**
		 * Setup the default hooks and actions
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		private function hooks() {
		
			// Plugin meta
			add_filter( 'plugin_row_meta', array( $this, 'plugin_meta' ), null, 2 );
			
			
			
			// Add template path to affiliate rank template content
			add_action( 'affwp_affiliate_dashboard_bottom', array( $this, 'display_affiliate_rank_dashboard' ) );
		
		}
		
		/**
		 * Modify plugin metalinks
		 *
		 * @access      public
		 * @since       1.0
		 * @param       array $links The current links array
		 * @param       string $file A specific plugin table entry
		 * @return      array $links The modified links array
		 */
		public function plugin_meta( $links, $file ) {
		    if ( $file == plugin_basename( __FILE__ ) ) {
		        $plugins_link = array(
		            '<a title="' . __( 'Get more add-ons for AffiliateWP', 'affiliatewp-ranks' ) . '" href="http://propluginmarketplace.com/product-category/add-ons/affiliatewp/" target="_blank">' . __( 'Get add-ons', 'affiliatewp-ranks' ) . '</a>'
		        );

		        $links = array_merge( $links, $plugins_link );
		    }

		    return $links;
		}
		
		
		/**
		 * Display affiliate's rank in affiliate dashboard
		 * 
		 * @since  1.0
		 * @param  integer $affiliate_id ID of the affiliate from the filter
		 */
		public function display_affiliate_rank_dashboard( $affiliate_id ) {
			if ( isset( $_GET['tab'] ) && 'urls' != $_GET['tab'] ) {
				return;
			}
			ob_start();
			
			include AFFWP_RANKS_PLUGIN_DIR . '/templates/affiliate-rank-dashboard.php';
			echo ob_get_clean();
		}
		
		
	}
	
	/**
	 * The main function responsible for returning the one true AffiliateWP_Ranks
	 * Instance to functions everywhere.
	 *
	 * Use this function like you would a global variable, except without needing
	 * to declare the global.
	 *
	 * Example: <?php $affiliatewp_ranks = affiliatewp_ranks(); ?>
	 *
	 * @since 1.0
	 * @return object The one true AffiliateWP_Ranks Instance
	 */
	function affiliatewp_ranks() {

	    if ( ! class_exists( 'Affiliate_WP' ) ) {
	    	
	        if ( ! class_exists( 'AffiliateWP_Activation' ) ) {
	            require_once 'includes/class-activation.php';
	        }

	        $activation = new AffiliateWP_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
	        $activation = $activation->run();
	    } else {
	        return AffiliateWP_Ranks::instance();
	    }
	}
	add_action( 'plugins_loaded', 'affiliatewp_ranks', 100 );
	
}

class AFFWP_RANKS_License {

	static function init() {

		if ( ! class_exists( 'AFFWP_RANKS_License_Menu' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'am-license-menu.php' );
			
			AFFWP_RANKS_License_Menu::instance( __FILE__, 'AffiliateWP Ranks', '1.0.1', 'plugin', 'http://propluginmarketplace.com' );
		}
	}

}
AFFWP_RANKS_License::init();