<?php

namespace AffiliateLTP\dashboard;
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
use AffiliateLTP\admin\Commission_DAL;
use AffiliateLTP\Template_Loader;
use AffiliateLTP\admin\Agent_DAL;
use Psr\Log\LoggerInterface;
use AffiliateLTP\Current_User;

/**
 * Displays the current agent's commissions they've earned in the agent dashboard.
 */
class Agent_Commissions implements \AffiliateLTP\I_Register_Hooks_And_Actions {
     /**
     *
     * @var Template_Loader
     */
    private $template_loader;
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    /**
     * Logger
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * The database service for retrieving commissions
     * @var Commission_DAL
     */
    private $commission_DAL;
    
    public function __construct(LoggerInterface $logger, 
            Commission_DAL $commission_dal, Template_Loader $template_loader
            ,Agent_DAL $agent_dal) {
        $this->logger = $logger;
        // this hook gets triggered in the templates/dashboard-tab-events.php which is the way
        // the affiliate plugin works.
        $this->template_loader = $template_loader;
        $this->agent_dal = $agent_dal;
        $this->commission_DAL = $commission_dal;
    }
    
     public function register_hooks_and_actions() {
        add_action("affwp_affiliate_dashboard_commissions_show", array($this, "show_commissions"));
    }
    
     public function show_commissions( $current_agent_id ) {
        $this->logger->info("show_commissions(" . $current_agent_id . ")");
        
        $per_page = 30;
        $page = get_query_var('page', 1);
        $total_commissions = $this->commission_DAL->get_total_commissions_for_agent($current_agent_id);
//        affwp_count_referrals($current_agent_id);
        $pages = absint(ceil($total_commissions / $per_page));
        $commissions = $this->commission_DAL->get_commissions_for_agent($current_agent_id, $per_page, $per_page * ( $page - 1 ) );
        
        // TODO: stephen look at optimizing this by combining it in the single query result set.
        foreach ($commissions as $record) {
            $record->client_name = $this->commission_DAL->get_commission_client_name($record->commission_id);
            $record->status = ucfirst($record->status);
        }
        
        $has_pagination = false;
        $pagination = "";
        if ($pages > 1) {
            $has_pagination = true;
            $pagination = paginate_links(
                    array(
                        'format' => '?paged=%#%',
                        'current' => $page,
                        'total' => $pages,
                        'add_fragment' => '#affwp-affiliate-dashboard-referrals',
                        'add_args' => array(
                            'tab' => 'commissions',
                        ),
                    )
            );
        }
        
        $include = $this->template_loader->get_template_part('dashboard-tab', 'commissions-list', false);
        include_once $include;
    }

//put your code here
}
