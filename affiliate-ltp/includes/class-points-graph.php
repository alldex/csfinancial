<?php

namespace AffiliateLTP;

use \Affiliate_WP_Graph;
use AffiliateLTP\AffiliateWP\Affiliate_WP_Referral_Meta_DB;


/**
 * Displays all of the points for a given range as a graph.
 *
 * @author snielson
 */
class Points_Graph extends Affiliate_WP_Graph {
    
    /**
     * Affiliate_WP_Referral_Meta_DB
     * @var type 
     */
    private $referralMetaDb;
    
    /**
     * The points data we need to graph
     * @var type 
     */
    private $points_data;
    
    /**
     * The date the graph should start its display on the x-axis for.
     * @var date
     */
    private $start_date;
    
    /**
     * The date string the graph should end its display on the x-axis for.
     * @var date 
     */
    private $end_date;
    
    /**
	 * Get things started
	 *
     *  @points_data array of dates to points data.  Assumes the dates are in sorted order.
	 * @since 1.0
	 */
	public function __construct( array $points_data, Affiliate_WP_Referral_Meta_DB $metaDb = null, $graph_options = array()) {

                $this->points_data = $points_data;
                $this->referralMetaDb = $metaDb;
                
                reset($points_data);
                
                if ( isset( $graph_options['start_date'] ) ) {
                    $this->start_date = $graph_options['start_date'];
                }
                else {
                    // try to pull it from the data
                    if (!empty($points_data)) {
                        $this->start_date = key($points_data);
                    }
                }
                
                
                
                if ( isset( $graph_options['start_date'] ) ) {
                    $this->end_date = $graph_options['end_date'];
                }
                else {
                    // grab the date from the last array date key
                    if (!empty($points_data)) {
                        end($points_data);
                        $this->end_date = key($points_data);
                        reset($points_data);
                    }
                }
                
		// Generate unique ID
		$this->id = md5( rand() );

		// Setup default options;
		$this->options = array(
			'y_mode'          => null,
			'y_decimals'      => 0,
			'x_decimals'      => 0,
			'y_position'      => 'right ',
			'time_format'     => '%d/%b',
			'ticksize_unit'   => 'day',
			'ticksize_num'    => 1,
			'multiple_y_axes' => false,
			'bgcolor'         => '#f9f9f9',
			'bordercolor'     => '#ccc',
			'color'           => '#bbb',
			'borderwidth'     => 2,
			'bars'            => false,
			'lines'           => true,
			'points'          => true,
			'affiliate_id'    => false,
			'show_controls'   => true,
		);

	}
        
        public function get_data() {
            $points_data = $this->points_data;
            $nonLifePoints = array();
            $lifePoints = array();
            $totalPoints = array(
                // we have to have the start and end times so we can 
                // have the graph X axis have the start/end points
                array( strtotime( $this->start_date ) * 1000 ) 
                ,array( strtotime( $this->end_date ) * 1000 )
            );

            $lifePointsSum = 0;
            $nonLifePointsSum = 0;
            foreach ( $points_data as $date => $records ) {
                $time = current($records)->get_date_in_milliseconds();
                foreach ($records as $record) {
                    $lifePointsSum += $record->get_life();
                    $nonLifePointsSum += $record->get_non_life();
                }
                
                $lifePoints[] = array($time, $lifePointsSum);
                $nonLifePoints[] = array($time, $nonLifePointsSum);
                $totalPoints[] = array($time, $lifePointsSum + $nonLifePointsSum);
            }
            
            $data = array(
                    __( 'Non-Life Points', 'affiliate-ltp' )   => $nonLifePoints,
                    __( 'Life Points', 'affiliate-ltp' )  => $lifePoints,
                    __( 'Total Points', 'affiliate-ltp' ) => $totalPoints,
            );

            return $data;            
            
        }
}
