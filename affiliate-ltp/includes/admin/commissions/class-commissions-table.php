<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\admin\commissions;

use AffWP_Referrals_Table;
use AffiliateLTP\admin\Commission_DAL;
use AffiliateLTP\admin\Agent_DAL;
use AffiliateLTP\Commission_Type;


/**
 * Description of class-commissions-table
 *
 * @author snielson
 */
class Commissions_Table extends AffWP_Referrals_Table {
    
    /**
     * The database service to retrieve commissions from.
     * @var Commission_DAL
     */
    private $commission_dal;
    
    /**
     * The agent service.
     * @var Agent_DAL
     */
    private $agent_dal;
    
    public function __construct(Commission_Dal $commission_dal, 
            Agent_DAL $agent_dal,
            $args = array()) {
        $this->commission_dal = $commission_dal;
        $this->agent_dal = $agent_dal;
        parent::__construct( $args );
    }
    
        public function column_type($commission) {
		$value = $commission->context;
		if ($value == Commission_Type::TYPE_LIFE) {
			return __('Life Insurance', 'affiliate-ltp');
		}
		else {
			return __('Non-Life Insurance', 'affiliate-ltp');
		}
	}

	public function column_points($commission) {
                $value = $this->commission_dal->get_commission_agent_points($commission->referral_id);
                if (empty($value)) {
			return '';
		}
		return $value;
	}
        
        /**
	 * Render the affiliate column
	 *
	 * @access public
	 * @since 1.0
	 * @param array $referral Contains all the data for the checkbox column
	 * @return string The affiliate
	 */
	public function column_affiliate( $commission ) {
            $agent_id = $commission->affiliate_id;
            $agent_name = $this->agent_dal->get_agent_name($agent_id);
            $agent_url = admin_url( 'admin.php?page=affiliate-wp-referrals&affiliate_id=' . $agent_id );
            $agent_code = $this->agent_dal->get_agent_code($agent_id);
            $value = apply_filters( 'affwp_referral_affiliate_column', 
                '<a href="' . $agent_url . '">' . $agent_name 
                . " (" . $agent_code . ")"  . '</a>', $commission );
                
            return apply_filters( 'affwp_referral_table_affiliate', $value, $commission );
	}
        
        public function column_client( $commission ) {
            $id = $commission->referral_id;
            $name = $this->commission_dal->get_commission_client_name($id);
            return $name;
        }
    
        /**
         * Override the referral data to handle our own search criteria
         * @return array the list of commissions(aka referrals)
         */
    public function referrals_data() {
        $page      = isset( $_GET['paged'] )        ? absint( $_GET['paged'] ) : 1;
        $status    = isset( $_GET['status'] )       ? $_GET['status']          : '';
        $affiliate = isset( $_GET['affiliate_id'] ) ? $_GET['affiliate_id']    : '';
        $reference = isset( $_GET['reference'] )    ? $_GET['reference']       : '';
        $context   = isset( $_GET['context'] )      ? $_GET['context']         : '';
        $campaign  = isset( $_GET['campaign'] )     ? $_GET['campaign']        : '';
        $from      = isset( $_GET['filter_from'] )  ? $_GET['filter_from']     : '';
        $to        = isset( $_GET['filter_to'] )    ? $_GET['filter_to']       : '';
        $order     = isset( $_GET['order'] )        ? $_GET['order']           : 'DESC';
        $orderby   = isset( $_GET['orderby'] )      ? $_GET['orderby']         : 'referral_id';
        $referral  = '';
        $is_search = false;

        $date = array();
        if( ! empty( $from ) ) {
                $date['start'] = $from;
        }
        if( ! empty( $to ) ) {
                $date['end']   = $to . ' 23:59:59';;
        }

        if( ! empty( $_GET['s'] ) ) {

                $is_search = true;

                $search = sanitize_text_field( $_GET['s'] );

                if ( strpos( $search, 'agent:' ) !== false ) {
                    $affiliate = trim( str_replace( 'agent:', '', $search ) );
                } else if ( strpos( $search, 'code:') !== false ) {
                    $code = trim( str_replace( 'code:', '', $search ) );
                    $affiliate = $this->agent_dal->get_agent_by_code($code);
                } elseif ( strpos( $search, 'context:' ) !== false ) {
                        $context = trim( str_replace( 'context:', '', $search ) );
                }
        }

        $per_page = $this->get_items_per_page( 'affwp_edit_referrals_per_page', $this->per_page );

        $args = wp_parse_args( $this->query_args, array(
                'number'       => $per_page,
                'offset'       => $per_page * ( $page - 1 ),
                'status'       => $status,
                'referral_id'  => $referral,
                'affiliate_id' => $affiliate,
                'reference'    => $reference,
                'context'      => $context,
                'campaign'     => $campaign,
                'date'         => $date,
                'search'       => $is_search,
                'orderby'      => sanitize_text_field( $orderby ),
                'order'        => sanitize_text_field( $order )
        ) );

        $referrals = affiliate_wp()->referrals->get_referrals( $args );
        return $referrals;
    }
}
