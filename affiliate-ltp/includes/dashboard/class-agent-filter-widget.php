<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

namespace AffiliateLTP\dashboard;

/**
 * Description of class-agent-filter-widget
 *
 * @author snielson
 */
class Agent_Filter_Widget {
    
    private $tab;
    
    private $agent_id;
    
    private $agent_name;
    
    private $dates;
    
    private $filter_from;
    
    private $filter_to;
    
    public function __construct($tab, $agent, $dates, $filter_from = '', $filter_to='') {
        $this->tab = $tab;
        $this->agent_id = $agent['id'];
        $this->agent_name = $agent['name'];
        $this->dates = $dates;
        $this->filter_from = $filter_from;
        $this->filter_to = $filter_to;
    }
    
    private function get_date_options() {
        $date_options = apply_filters( 'affwp_report_date_options', array(
			'today' 	    => __( 'Today', 'affiliate-wp' ),
			'yesterday'     => __( 'Yesterday', 'affiliate-wp' ),
			'this_week' 	=> __( 'This Week', 'affiliate-wp' ),
			'last_week' 	=> __( 'Last Week', 'affiliate-wp' ),
			'this_month' 	=> __( 'This Month', 'affiliate-wp' ),
			'last_month' 	=> __( 'Last Month', 'affiliate-wp' ),
			'this_quarter'	=> __( 'This Quarter', 'affiliate-wp' ),
			'last_quarter'	=> __( 'Last Quarter', 'affiliate-wp' ),
			'this_year'		=> __( 'This Year', 'affiliate-wp' ),
			'last_year'		=> __( 'Last Year', 'affiliate-wp' ),
			'other'			=> __( 'Custom', 'affiliate-wp' )
		) );
        return $date_options;
    }
    
    public function get_date_search() {
        $date_range = affwp_get_report_dates(); // TODO: stephen need to fix this function for myself
        $start = $date_range['year'] . '-' . $date_range['m_start'] . '-' . $date_range['day'] . ' 00:00:00';
        $end   = $date_range['year_end'] . '-' . $date_range['m_end'] . '-' . $date_range['day_end'] . ' 23:59:59';
        return ['start' => $start, 'end' => $end];
    }
    
    
    /**
	 * Show report graph date filters
	 *
	 * @since 1.0
	 * @return void
	*/
	function display() {
		$date_options = $this->get_date_options();

		$display = $this->dates['range'] == 'other' ? 'style="display:inline-block;"' : 'style="display:none;"';
		?>
		<form id="affwp-graphs-filter search-controls" method="get">
			<div class="tablenav top">
                                <span class="affwp-ajax-search-wrap">
                                   <input class='agent-name affwp-agent-search' type='text' data-affwp-status="active"
                                   <?php if (!empty($this->agent_name)) : ?>
                                    value="<?= $this->agent_name; ?>"
                                    <?php endif; ?>
                                   name='input_search_agent' />
                                   <input name='input_agent_id'
                                       type='hidden' class='agent-id' 
                                           <?php if (!empty($this->agent_id)) : ?>
                                           value="<?= $this->agent_id; ?>"
                                           <?php endif; ?> />
                               </span>
				<input type="hidden" name="tab" value="<?php echo esc_attr( $this->tab ); ?>"/>
				
				<?php if( isset( $this->agent_id ) ) : ?>
				<input type="hidden" name="agent_id" value="<?php echo absint( $this->agent_id ); ?>"/>
				<?php endif; ?>

				<select id="affwp-graphs-date-options" name="range">
				<?php
					foreach ( $date_options as $key => $option ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $this->dates['range'] ) . '>' . esc_html( $option ) . '</option>';
					}
				?>
				</select>

				<div id="affwp-date-range-options" <?php echo $display; ?>>

					<span class="affwp-search-date">
						<span><?php _ex( 'From', 'date filter', 'affiliate-wp' ); ?></span>
						<input type="text" class="affwp-datepicker" autocomplete="off" name="filter_from" placeholder="<?php esc_attr_e( 'From - mm/dd/yyyy', 'affiliate-wp' ); ?>" aria-label="<?php esc_attr_e( 'From - mm/dd/yyyy', 'affiliate-wp' ); ?>" value="<?php echo esc_attr( $this->filter_from ); ?>" />
						<span><?php _ex( 'To', 'date filter', 'affiliate-wp' ); ?></span>
						<input type="text" class="affwp-datepicker" autocomplete="off" name="filter_to" placeholder="<?php esc_attr_e( 'To - mm/dd/yyyy', 'affiliate-wp' ); ?>" aria-label="<?php esc_attr_e( 'To - mm/dd/yyyy', 'affiliate-wp' ); ?>" value="<?php echo esc_attr( $this->filter_to ); ?>" />
					</span>

				</div>

				<input type="submit" class="button" value="<?php _e( 'Filter', 'affiliate-wp' ); ?>"/>
			</div>
		</form>
		<?php
	}

}