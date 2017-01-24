<?php
namespace AffiliateLTP;

use \AffiliateWP_Multi_Level_Marketing;
use \Affiliate_WP_Referral_Meta_DB;
use AffiliateLTP\admin\Menu;
use AffiliateLTP\admin\Referrals;
use AffiliateLTP\admin\Settings;

/**
 * Main starting point for the plugin.  Registers all the classes.
 *
 * @author snielson
 */
class Plugin {
        
    const AFFILIATEWP_LTP_VERSION = "0.1.0";
    
    const LOCALHOST_RESTRICTED = true;
    
    private $settings;
    
    /**
     * The meta object for interacting with referrals.
     * @var Affiliate_WP_Referral_Meta_DB 
     */
    public $referralMeta;
    
    /**
     *
     * @var AffiliateLTP
     */
    private static $instance = null;
    
    public function __construct() {
        
        $includePath = plugin_dir_path( __FILE__ );
        
        require_once "class-sugarcrm-dal.php";
        require_once "class-commission-type.php";
        
        if (self::LOCALHOST_RESTRICTED) {
            require_once "class-sugarcrm-dal-localhost.php";
        }
        
        if( is_admin() ) {
            
            require_once $includePath . '/admin/class-referrals.php';
            require_once $includePath . '/admin/class-affiliates.php';
            require_once $includePath . '/admin/class-menu.php';
            require_once $includePath . "/admin/class-settings.php";
            require_once $includePath . "/admin/class-upgrades.php";
            
            // setup the settings.
            $this->settings = new Settings();
            
            // setup our admin scripts.
            add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts' ) );
        }
        
        require_once $includePath . "/class-shortcodes.php";
        new Shortcodes(); //setup the shortcodes.
        
        
        // come in last here.
        add_filter( 'load_textdomain_mofile', array($this, 'load_ltp_en_mofile'), 50, 2 );
        
        // do some cleanup on the plugins
        add_action ('plugins_loaded', array($this, 'remove_affiliate_wp_mlm_tab_hooks'));
        add_action ('plugins_loaded', array($this, 'setup_dependent_objects') );
        
        add_action( 'init', array($this, 'load_ltp_affiliate_ranks_translation' ) );
        
        add_filter( 'affwp_affiliate_area_show_tab', array($this, 'remove_unused_tabs'), 10, 2 );
        
        add_action( 'affwp_affiliate_dashboard_tabs', array( $this, 'add_organization_tab' ), 10, 2 );
			
        // Add template folder to hold the sub affiliates tab content
        add_filter( 'affwp_template_paths', array( $this, 'get_theme_template_paths' ) );
        
        // so we can check to see if its active
        add_filter( 'affwp_affiliate_area_tabs', function( $tabs ) {
                return array_merge( $tabs, array( 'organization' ) );
        } );
        
        // we need to clear the tracking cookie when an affiliate registers
        add_action( 'affwp_register_user', array( $this, 'clearTrackingCookie' ), 10, 3 );
        
        add_action( 'affwp_affiliate_dashboard_after_graphs', array( $this, 'addPointsToGraphTab' ), 10, 1);
    }
    
    public function addPointsToGraphTab( $affiliate_id ) {
        
        // TODO: stephen see if there's a way to get around this global function
        $points_retriever = new \AffiliateLTP\Points_Retriever( $this->referralMeta );
        
        $date_range = affwp_get_report_dates();
        $start = $date_range['year'] . '-' . $date_range['m_start'] . '-' . $date_range['day'] . ' 00:00:00';
        $end   = $date_range['year_end'] . '-' . $date_range['m_end'] . '-' . $date_range['day_end'] . ' 23:59:59';
        
        $points_date_range = array(
            "start_date" => $start
            ,"end_date" => $end
            ,"range" => $date_range['range']
        );
        
        $points_data = $points_retriever->get_points( $affiliate_id, $points_date_range );
        
        $graph = new \AffiliateLTP\Points_Graph($points_data, $this->referralMeta, $points_date_range);
//        $graph = new \AffiliateLTP\Points_Graph;
	$graph->set( 'x_mode', 'time' );
	$graph->set( 'affiliate_id', $affiliate_id );
        // hide the date filter since the graph above this one controls all the
        // date filters.
        $graph->set( 'show_controls', false );
        
//        $data = $graph->get_data();
//                        echo "<pre>";
//                var_dump($data);
//                echo "</pre>";
        
        $template_path = affiliate_wp()->templates->get_template_part('dashboard-tab', 'graphs-point', false);
        
        include_once $template_path;
    }
    
    /**
     * 
     * @return SugarCRMDAL
     */
    public function getSugarCRM() {
        if (self::LOCALHOST_RESTRICTED) {
            return SugarCRMDALLocalhost::instance();
        }
        else {
            return SugarCRMDAL::instance();
        }
    }
    
    public function getReferralMetaDb() {
        return $this->referralMeta;
    }
    
    public function setup_dependent_objects() {
        require_once "class-referral-meta-db.php";
        
        $this->referralMeta = new Affiliate_WP_Referral_Meta_DB();
        
        if (is_admin()) {
            $this->adminReferrals = new Referrals($this->referralMeta);
            // TODO: stephen look at renaming the AdminMenu to keep with our naming convention
            $adminMenu = new Menu($this->adminReferrals);
        }
        
        // require the points graph since it's dependent on other plugins.
        require_once "class-points-record.php";
        require_once "class-points-retriever.php";
        require_once "class-points-graph.php";
    }

    /**
     * Since the AffiliateWP_Multi_Level_Marketing class does not use the affiliatewp
     * affwp_affiliate_area_show_tab() function we have to just remove the hook
     * alltogether.
     */
    public function remove_affiliate_wp_mlm_tab_hooks() {
        if (class_exists("AffiliateWP_Multi_Level_Marketing")) {
            $instance = AffiliateWP_Multi_Level_Marketing::instance();
            remove_action( 'affwp_affiliate_dashboard_tabs', array( $instance, 'add_sub_affiliates_tab' ));
        }
    }
    
    /** Add any admin javascript files we need to load **/
    public function admin_scripts() {
        if( ! affwp_is_admin_page() ) {
		return;
	}

        $suffix = "";
//	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        $plugin_url = plugin_dir_url( __FILE__ );
        $url = $plugin_url . 'assets/js/admin/ltp-admin' . $suffix . '.js';
	wp_enqueue_script( 'affiliate-ltp-admin', $url, array( 'jquery', 'jquery-ui-autocomplete'  ));
        
        wp_enqueue_style( 'affwp-admin', $plugin_url . 'assets/css/admin' . $suffix . '.css', array());
        
    }

    public function load_ltp_en_mofile( $mofile, $domain )
    {
        if ( 'affiliate-wp' == $domain )
        {
            $includePath = plugin_dir_path( __FILE__ );
            return $includePath . "/languages/affiliate-wp-en.mo";
        }
        else if ( 'affiliatewp-multi-level-marketing' == $domain )
        {
            $includePath = plugin_dir_path( __FILE__ );
            return $includePath . "/languages/affiliatewp-multi-level-marketing-en.mo";
        }
        else if ( 'affiliatewp-ranks' == $domain )
        {
            $includePath = plugin_dir_path( __FILE__ );
            return $includePath . "/languages/affiliatewp-ranks-en.mo";
        }
        else if ( 'affiliate-ltp' == $domain ) {
            $includePath = plugin_dir_path( __FILE__ );
            return $includePath . "/languages/affiliate-ltp-en.mo";
        }
        return $mofile;
    }
    
    public function load_ltp_affiliate_ranks_translation() {
        load_plugin_textdomain( 'affiliatewp-ranks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
    
    /**
     * Remove the tabs that are not used for the agent system.
     * @param boolean $currentValue the current value of displaying the tab or not.
     * @param string $tab the tab to check if we need to remove
     */
    public function remove_unused_tabs( $currentValue, $tab ) {
        $shouldDisplay = true;
        
        // if the current value is not true just escape.
        if (!$currentValue) {
            return $currentValue;
        }
        
        switch ($tab) {
            // leaving in sub-affiliates in case the MLM plugin ever fixes
            // itself to use the right functions and can be filtered out...
            case 'sub-affiliates':
//            case 'urls':
            case 'visits':
            case 'creatives': {
                $shouldDisplay = false;
            }
            break;
            default: {
                $shouldDisplay = true;
            }
        }
        
        return $shouldDisplay;
    }
    
    /**
     * Add the organization tab.
     * @param type $affiliate_id
     * @param type $active_tab
     */
    public function add_organization_tab( $affiliate_id, $active_tab ) {
        
        // make sure we only show the tab if it hasn't been filtered out.
        if (affwp_affiliate_area_show_tab( 'organization' )) {
            ?>
            <li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == 'organization' ? ' active' : ''; ?>">
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'organization' ) ); ?>"><?php _e( 'Organization', 'affiliate-ltp' ); ?></a>
            </li>
                <?php	
        }
    }
    
    /**
    * Add template folder to hold the organization tab content
    *
    *
    * @return void
    */
   public function get_theme_template_paths( $file_paths ) {
           $file_paths[80] = plugin_dir_path( __FILE__ ) . '/templates';

           return $file_paths;
   }
   
   public static function instance() {
       if (self::$instance == null) {
           self::$instance = new Plugin();
       }
       return self::$instance;
   }
   
   public function clearTrackingCookie( $affiliateId, $status, $userData ) {
       $trackingAffiliateId = affiliate_wp()->tracking->get_affiliate_id();
       if (!empty($trackingAffiliateId)) {

        // TODO: stephen currently there's no way to clear a cookie or set the cookie to expire anything shorter than 1 day
        // this has to be kept in sync with the plugin code unfortunately so if they change the name or anything else... we have to deal with it.
        // setting the time to 0 leaves the cookie in the session
        // setting it to expire before the current time will clear the cookie on the next
        // page refresh
        $expirationTime = time() - 3600;
        setcookie( 'affwp_ref', null, $expirationTime, 
                COOKIEPATH, COOKIE_DOMAIN );
        
        // unfortunately scripts may still use the $_COOKIE array when they shouldn't
        // be touching it directly so we manually clear it
        unset($_COOKIE['affwp_ref']);
       }
   }
}