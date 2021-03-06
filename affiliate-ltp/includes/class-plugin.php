<?php
namespace AffiliateLTP;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use AffiliateLTP\stripe\Subscriptions;
use AffiliateLTP\Sugar_CRM_DAL;
use AffiliateLTP\Sugar_CRM_DAL_Localhost;

use AffiliateLTP\admin\GravityForms\Gravity_Forms_Bootstrap;
use AffiliateLTP\Agent_Checklist_AJAX;
use AffiliateLTP\Agent_Partner_Search_AJAX;
use AffiliateLTP\admin\Affiliates;

use AffiliateLTP\dashboard\Agent_Events;

use AffiliateLTP\commands\Command_Registration;

/**
 * Main starting point for the plugin.  Registers all the classes.
 *
 * @author snielson
 */
class Plugin {
        
    const AFFILIATEWP_LTP_VERSION = "1.1.0";
    
    const LOCALHOST_RESTRICTED = true;
    
    /**
     * Whether the errors and ommissions stripe account handling
     * is enabled or not.
     */
    const STRIPE_EO_HANDLING_ENABLED = true;
    
    /**
     *
     * @var AffiliateLTP
     */
    private static $instance = null;
    
    /**
     * Service registry of all of the objects in the system.
     * @var ContainerBuilder
     */
    private $container = null;
    
    public function __construct() {
        
        $logger = new Logger('affiliate-ltp');
        $logger->pushHandler(new StreamHandler(AFFILIATE_LTP_PLUGIN_DIR . "/debug.log", Logger::DEBUG));
        
        $this->container = new ContainerBuilder();
        $this->container->set("logger", $logger);
        $this->container->register('progress_items', 'AffiliateLTP\Progress_Item_DB');
        $this->container->register('settings_dal', 'AffiliateLTP\admin\Settings_DAL_Affiliate_WP_Adapter');
        
        // we override this when we setup the dependent objects, but we need an inital setting
        // as part of the registration
        $this->container->setParameter("company_agent_id", null);
        $this->container->setParameter("partner_rank_id", null);
        
        $this->container->register('agent_dal', 'AffiliateLTP\admin\Agent_DAL_Affiliate_WP_Adapter')
                ->addArgument(new Reference("logger"))
                ->addArgument(new Reference("progress_items"))
                ->addArgument(new Reference("settings_dal"));
        
         if (self::LOCALHOST_RESTRICTED) {
             $this->container->register("sugarcrm", "AffiliateLTP\Sugar_CRM_DAL_Localhost");
        }
        else {
            $this->container->register("sugarcrm", "AffiliateLTP\Sugar_CRM_DAL");
        }
        
        $this->container->register('settings', 'AffiliateLTP\admin\Settings');
          $this->container->register("shortcodes", "AffiliateLTP\Shortcodes");
        $this->container->register("gravityforms_bootstrap", "AffiliateLTP\admin\GravityForms\Gravity_Forms_Bootstrap");
        $this->container->register("ajax_agent_checklist", "AffiliateLTP\Agent_Checklist_AJAX")
                ->addArgument(new Reference("logger"))
                ->addArgument(new Reference("agent_dal"))
                ->addArgument(new Reference("settings_dal"));
        $this->container->register("ajax_agent_partner_search", "AffiliateLTP\Agent_Partner_Search_AJAX")
                ->addArgument(new Reference('agent_dal'))
                ->addArgument(new Reference('settings_dal'));
        $this->container->register("ajax_agent_search", "AffiliateLTP\Agent_Search_AJAX")
                ->addArgument(new Reference('logger'))
                ->addArgument(new Reference('agent_dal'))
                ->addArgument(new Reference('settings_dal'));
        $this->container->register("affiliates", "AffiliateLTP\admin\Affiliates");
        $this->container->register('referral_meta', 'AffiliateLTP\AffiliateWP\Affiliate_WP_Referral_Meta_DB');
        $this->container->register('commission_request_db', 'AffiliateLTP\Commission_Request_DB');
        $this->container->register('template_loader', 'AffiliateLTP\Template_Loader');
        $this->container->register('agent_org_chart_handler', 'AffiliateLTP\charts\Agent_Organization_Chart')
                ->addArgument(new Reference("agent_dal"));
        $this->container->register('agent_emails', 'AffiliateLTP\Agent_Emails')
                ->addArgument(new Reference("agent_dal"))
                ->addArgument(new Reference("settings_dal"));
        $this->container->register("commission_dal", "AffiliateLTP\admin\Commission_DAL_Affiliate_WP_Adapter")
                ->addArgument(new Reference("referral_meta"));
        $this->container->register("commission_chargeback_processor", "AffiliateLTP\admin\Commission_Chargeback_Processor")
                ->addArgument(new Reference("commission_dal"))
                ->addArgument(new Reference("agent_dal"))
                ->addArgument(new Reference("logger"))
                ->addArgument("%company_agent_id%");
        $this->container->register("commission_processor", "AffiliateLTP\admin\Commission_Processor")
                ->addArgument(new Reference("commission_dal"))
                ->addArgument(new Reference("agent_dal"))
                ->addArgument(new Reference("settings_dal"))
                ->addArgument(new Reference("sugarcrm"))
                ->addArgument(new Reference("logger"));
        $this->container->register("commission_payout_exporter", "AffiliateLTP\admin\Commission_Payout_Export")
                ->addArgument(new Reference('referral_meta'))
                ->addArgument(new Reference("settings_dal"))
                ->addArgument(new Reference("commission_dal"));
        $this->container->register("state_dal", "AffiliateLTP\admin\State_DAL");
        $this->container->register('commissions', 'AffiliateLTP\admin\commissions\Commissions')
                ->addArgument(new Reference("commission_dal"))
                ->addArgument(new Reference('agent_dal'))
                ->addArgument(new Reference('settings_dal'))
                ->addArgument(new Reference("commission_processor"))
                ->addArgument(new Reference("commission_payout_exporter"))
                ->addArgument(new Reference("state_dal"))
                ->addArgument(new Reference("sugarcrm"))
                ->addArgument(new Reference("commission_chargeback_processor"))
                ->addArgument(new Reference("logger"));
        $this->container->register('admin_menu', 'AffiliateLTP\admin\Menu')
                ->addArgument(new Reference('commissions'))
                ->addArgument(new Reference('subscriptions'))
                ->addArgument(new Reference('policies'));
        $this->container->register("cli_commands", "AffiliateLTP\commands\Command_Registration")
                ->addArgument(new Reference('settings_dal'))
                ->addArgument(new Reference('agent_dal'))
                ->addArgument(new Reference('sugarcrm'))
                ->addArgument(new Reference('commission_dal'));
        $this->container->register("leaderboards", "AffiliateLTP\leaderboards\Leaderboards")
                ->addArgument(new Reference("settings_dal"))
                ->addArgument(new Reference("agent_dal"))
                ->addArgument(new Reference("template_loader"));
        
        
        $this->container->register('upgrades', 'AffiliateLTP\admin\Upgrades')
                ->addArgument(new Reference('referral_meta'))
                ->addArgument(new Reference('progress_items'))
                ->addArgument(new Reference('commission_request_db'));
        $this->container->register("commissions_table_extensions", "AffiliateLTP\admin\commissions\Commissions_Table_Extensions")
                ->addArgument(new Reference("commission_dal"))
                ->addArgument("%company_agent_id%");
        $this->container->register("commissions_importer", "AffiliateLTP\admin\\tools\Commissions_Importer")
                ->addArgument(new Reference('logger'))
                ->addArgument(new Reference("agent_dal"))
                ->addArgument(new Reference("sugarcrm"))
                ->addArgument(new Reference("commission_processor"));
        $this->container->register('tools', 'AffiliateLTP\admin\\tools\Tools')
                ->addArgument(new Reference('logger'))
                ->addArgument(new Reference('commissions_importer'));
        $this->container->register('points_retriever', 'AffiliateLTP\Points_Retriever')
                ->addArgument(new Reference("referral_meta"));
        $this->container->register("current_user", "AffiliateLTP\Current_User")
                ->addArgument(new Reference("settings_dal"))
                ->addArgument(new Reference("agent_dal"));
        $this->register_dashboard();
        
       
        
        $this->container->register("translations", "AffiliateLTP\Translations");
        $this->container->register("asset_loader", "AffiliateLTP\Asset_Loader");
       
        
        $this->container->register("subscriptions",  "AffiliateLTP\admin\subscriptions\Subscriptions")
                ->addArgument(new Reference("settings_dal"))
                ->addArgument(new Reference("template_loader"));
        $this->container->register("subscriptions_listener",  "AffiliateLTP\stripe\Subscription_Event_Listener")
                ->addArgument(new Reference("logger"));
        
        $this->container->register("notices", "AffiliateLTP\admin\Notices");
        
        $this->container->register("gfmailchimp", "\GFMailChimp")
                    ->setFactory(array('AffiliateLTP\mailchimp\GFMailChimp_Factory', 'createInstance'));
        $this->container->register("mailchimp-service", "AffiliateLTP\mailchimp\MailChimp_Service")
                ->addArgument(new Reference("logger"))
                ->addArgument(new Reference("gfmailchimp"));
        $this->container->register("profile-builder-extensions", "AffiliateLTP\ProfileBuilder\Profile_Builder_Extensions")
                ->addArgument(new Reference("logger"))
                ->addArgument(new Reference("mailchimp-service"));
        
        if( is_admin() ) {
            $this->container->register("actions_processor", "AffiliateLTP\admin\Actions_Processor");
            
            $this->container->register('policies', "AffiliateLTP\admin\policies\Policies")
                    ->addArgument(new Reference("commission_dal"))
                    ->addArgument(new Reference("template_loader"))
                    ->addArgument(new Reference("agent_dal"))
                    ->addArgument(new Reference("state_dal"))
                    ->addArgument(new Reference("notices"));
            $this->container->register('ajax_policy_add', "AffiliateLTP\admin\policies\Policy_Add_AJAX")
                    ->addArgument(new Reference("commission_processor"))
                    ->addArgument(new Reference("logger"));
            
            $this->container->register('policy_delete', "AffiliateLTP\admin\policies\Policy_Delete")
                    ->addArgument(new Reference("commission_dal"))
                    ->addArgument(new Reference("notices"))
                    ->addArgument(new Reference("logger"));
            
            $this->container->register('policy_chargeback', "AffiliateLTP\admin\policies\Policy_ChargeBack")
                    ->addArgument(new Reference("settings_dal"))
                    ->addArgument(new Reference("commission_chargeback_processor"))
                    ->addArgument(new Reference("notices"))
                    ->addArgument(new Reference("logger"));
            
            $this->register_hooks_and_actions(['settings', 'notices','actions_processor']);
        }
        
        // these have actions in their constructors so we need to initialize them.
        $this->register_hooks_and_actions([
            'shortcodes', 'gravityforms_bootstrap', 'translations', 'asset_loader'
            ,'subscriptions_listener'
        ]);
       
        // do some cleanup on the plugins
        add_action ('plugins_loaded', array($this, 'setup_dependent_objects') );

        // we need to clear the tracking cookie when an affiliate registers
        // TODO: stephen move this out into a more appropriate place instead of in Plugin.
        add_action( 'affwp_register_user', array( $this, 'clearTrackingCookie' ), 10, 3 );
    }
    
    private function register_dashboard() {
         $this->container->register("dashboard", "AffiliateLTP\dashboard\Dashboard")
                ->addArgument(new Reference("current_user"));
         $this->container->register("dashboard.agent_events", "AffiliateLTP\dashboard\Agent_Events")
                ->addArgument(new Reference("settings_dal"))
                ->addArgument(new Reference("template_loader"));
         $this->container->register("dashboard.agent_promotions", "AffiliateLTP\dashboard\Agent_Promotions")
                ->addArgument(new Reference("logger"))
                ->addArgument(new Reference("settings_dal"))
                ->addArgument(new Reference("template_loader"))
                ->addArgument(new Reference("agent_dal"))
                ->addArgument(new Reference("current_user"));
         $this->container->register("dashboard.agent_commissions", "AffiliateLTP\dashboard\Agent_Commissions")
                ->addArgument(new Reference("logger"))
                ->addArgument(new Reference("commission_dal"))
                ->addArgument(new Reference("template_loader"))
                ->addArgument(new Reference("agent_dal"))
                ->addArgument(new Reference("current_user"));
         $this->container->register('dashboard.agent_graphs', 'AffiliateLTP\dashboard\Agent_Graphs')
                ->addArgument(new Reference("agent_dal"))
                ->addArgument(new Reference("referral_meta"))
                ->addArgument(new Reference("points_retriever"))
                ->addArgument("%partner_rank_id%");
    }
    
    /**
     * Given a service or array of services go through and initiate all the
     * wordpress hooks, filters, shortcodes, etc for the service.
     * @param array $services Array of service string names used by the container
     * @return void
     */
    private function register_hooks_and_actions($services) {
        if (empty($services)) {
            return;
        }
        if (!is_array($services)) {
            $services = [$services];
        }
        foreach ($services as $service) {
            $obj = $this->container->get($service);
            if ($obj instanceof I_Register_Hooks_And_Actions) {
                $obj->register_hooks_and_actions();
            }
            else {
                error_log("attempted to initialize class '$service' hooks and actions but interface is not implemented");
            }
        }
    }
    
    private function register_cli_commands() {
        if (class_exists('WP_CLI')) {
            $command_registration = $this->container->get("cli_commands");
            $command_registration->register();
        }
    }
    
    public function get_container() {
        return $this->container;
    }
    
    public function setup_dependent_objects() {
        $settings_dal = $this->container->get("settings_dal");
        $this->container->setParameter("company_agent_id", $settings_dal->get_company_agent_id());
        $this->container->setParameter("partner_rank_id", $settings_dal->get_partner_rank_id());
        // TODO: stephen need to search and replace this referralMeta garbage inconsistency
        $this->register_hooks_and_actions([ 
            'upgrades', 'affiliates', 'dashboard'
            , 'ajax_agent_partner_search', 'ajax_agent_checklist', 'ajax_agent_search'
            ,'referral_meta', 'progress_items', 'commission_request_db'
            , 'template_loader', 'agent_org_chart_handler', 'agent_emails'
            ,'leaderboards', 'dashboard.agent_events', 'dashboard.agent_promotions'
            ,'dashboard.agent_graphs', 'dashboard.agent_commissions'
            , 'profile-builder-extensions'
        ]);
        
        // need to register commands after the other plugins have executed.
        $this->register_cli_commands();
        
        if (is_admin()) {
            $this->register_hooks_and_actions([ 
                'commissions'
                ,'admin_menu'
                ,'tools'
                , 'commissions_table_extensions'
                ,'ajax_policy_add'
                ,'policy_delete'
                ,'policy_chargeback'
            ]);
        }
    }

   /**
    * 
    * @return Plugin
    */
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
