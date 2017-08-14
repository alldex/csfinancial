<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\policies;

use WP_List_Table;
use AffiliateLTP\admin\Agent_DAL;

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
    
    function get_sortable_columns() {
        return [
            'contract_number' => ['contract_number', true]
            ,'amount' => ['amount', true]
            ,'date' => ['date', true]
            ,'date_created' => ['date_created', true]
            ,'agent' => ['writing_agent_id', true]
        ];
    }
    
    function get_columns(){
        $columns = array(
            'contract_number' => __('Contract Number', 'affiliate-ltp'),
            'client' => __('Client', 'affiliate-ltp'),
            'agent'    => __('Writing Agent', 'affiliate-ltp'),
            'amount' => __('Amount', 'affiliate-ltp'),
            'points' => __('Points', 'affiliate-ltp'),
            'request_type' => __('Type', 'affiliate-ltp'),
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
    $sortable = $this->get_sortable_columns();
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
  
    $chargeback_url = esc_url( add_query_arg( ['commission_request_id' => $item->commission_request_id
            , 'action' => 'chargeback'] ) );
    $chargeback = '<span class="chargeback"><a href="' 
            . $chargeback_url . '">Chargeback</a></span>';
    
    $edit_url = esc_url( add_query_arg( ['commission_request_id' => $item->commission_request_id
            , 'action' => 'edit'] ) );
    $edit = '<span class="edit"><a href="' 
            . $edit_url . '">Edit</a></span>';
    
    $delete_url = wp_nonce_url( add_query_arg( ['commission_request_id' => $item->commission_request_id
            , 'action' => 'delete_policy'] ) , 'affwp_ltp_delete_commission_nonce');
    $delete = '<span class="delete"><a href="' 
            . $delete_url . '">Delete</a></span>';
    
    $out = '<div class="row-actions visible">' . $chargeback 
            . ' | ' . $edit 
            . ' | ' . $delete 
            . '</div>';
    
    
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
  
  function column_date_created( $item ) {
      return date("F d, Y", strtotime($item->date_created));
  }
  
  function column_request_type( $item ) {
      $type = "Non-Life";
      if ($item->request_type == \AffiliateLTP\Commission_Type::TYPE_LIFE) {
          $type = "Life";
      }
      
      if ($item->new_business !== 'Y') {
          $type = $type . " Repeat";
          
          $request = json_decode($item->request, $true);
          if ($request->renewal == true) {
              $type = $type . " Renewal";
          }
      }
      return __($type, 'affiliate-ltp');
  }
  
  function column_agent( $item ) {
    $agent_id = $item->writing_agent_id;
    $agent_name = $this->agent_dal->get_agent_displayname( $agent_id );
    $agent_url = admin_url( 'admin.php?page=affiliate-wp-referrals&affiliate_id=' . $agent_id );
    $agent_code = $this->agent_dal->get_agent_code($agent_id);
    $value = apply_filters( 'affwp_referral_affiliate_column', 
        '<a href="' . $agent_url . '">' . $agent_name 
        . " (" . $agent_code . ")"  . '</a>', $item );
    return $value;
  }
  
  function column_default( $item, $column_name ) {
      return $item->$column_name;
  }
    
}
