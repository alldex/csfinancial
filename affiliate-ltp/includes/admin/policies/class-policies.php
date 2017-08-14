<?php
namespace AffiliateLTP\admin\policies;

use AffiliateLTP\admin\Commission_DAL;
use AffiliateLTP\Template_Loader;
use WP_List_Table;
use AffiliateLTP\admin\Agent_DAL;
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
/**
 * Description of class-policies
 *
 * @author snielson
 */
class Policies {
    /**
     *
     * @var Commission_DAL
     */
    private $commission_dal;
    
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
    
    public function __construct(Commission_DAL $commission_dal, Template_Loader $template_loader
            ,Agent_DAL $agent_dal) {
        $this->commission_dal = $commission_dal;
        $this->template_loader = $template_loader;
        $this->agent_dal = $agent_dal;
    }

    public function handle_admin_sub_menu_page() {
        $requests = $this->commission_dal->get_commission_requests(1000, 1);
        $table = new Policies_List_Table([], $this->agent_dal, $requests);
        $table->prepare_items();
        $file = $this->template_loader->get_template_part("admin", "policies-list", false);
        include $file;
    }
}

class Policies_List_Table extends WP_List_Table {
    private $data;
    
    /**
     *
     * @var Agent_DAL
     */
    private $agent_dal;
    
    public function __construct($args, Agent_DAL $agent_dal, $data) {
        parent::__construct($args);
        $this->data = $data;
        $this->agent_dal = $agent_dal;
    }
    
    function get_columns(){
        $columns = array(
            'contract_number' => __('Contract Number', 'affiliate-ltp'),
            'client' => __('Client', 'affiliate-ltp'),
            'agent'    => __('Writing Agent', 'affiliate-ltp'),
            'amount' => __('Amount', 'affiliate-ltp'),
            'points' => __('Points', 'affiliate-ltp'),
            'request_type' => __('Type', 'affiliate-ltp'),
            'new_business' => __('New Business', 'affiliate-ltp'),
            'date_created' => __('Date', 'affiliate-ltp'),
            'actions' => __('Actions', 'affiliate-ltp')
        );
    return $columns;
  }

  function prepare_items() {
    $columns = $this->get_columns();
    /**
     * 'total_items' => 0,
			'total_pages' => 0,
			'per_page' => 0,
     */
    $this->set_pagination_args(["total_items" => count($this->data), 'total_pages' => count($this->data) % 20, 'per_page' => 20]);
    $hidden = array();
    $sortable = array();
    $this->_column_headers = array($columns, $hidden, $sortable);
    $this->items = array_slice($this->data, 0, 20);
  }
  
  function column_actions($item) {
      /*
       * $out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			$out .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';
       */
    $query_args = ['commission_request_id' => $item->commission_request_id, 'action' => 'chargeback'];
  
    $url = esc_url( add_query_arg( $query_args ) );
    $href= '<a href="' . $url . '">Chargeback</a>';
    $chargeback = '<span class="chargeback"><a href="' 
            . $url . '">Chargeback</a></span>';
    $out = '<div class="row-actions visible">' . $chargeback . ' | ' . $delete . '</div>';
    
    
    return $out;
  }
  
  function column_contract_number( $item ) {
      $url = esc_url(add_query_arg(['page' => 'affiliate-wp-referrals'
        , 'reference' => $item->contract_number]));
    return "<a href='" . $url . "'>" . $item->contract_number . "</a>";
  }
  
  function column_client( $item ) {
    $request = json_decode($item->request, true);
    $client = $request['client']['name'];
      return $client;
  }
  
  function column_amount( $item ) {
      return "$" . money_format('%i', $item->amount);
  }
  
  function column_default( $item, $column_name ) {
      
      
      if ($column_name === 'agent') {
          return $this->agent_dal->get_agent_displayname( $item->writing_agent_id );
      }
      
//      if ($column_name === 'status') {
//          if ($item->status !== 'active') {
//              return "<span class='error'>" . $item->status . "</span>";
//          }
//      }
      
      return $item->$column_name;
  }
    
}
