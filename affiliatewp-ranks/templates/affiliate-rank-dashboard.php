<?php
/**
 * Template file for displaying affiliate rank table
 *
 * @since 1.0
 */
 
 
// Get affiliate's rank data
$rank_id = affwp_ranks_get_affiliate_rank( $affiliate_id );
$rank_data = get_rank_by_id( $rank_id );
$rank_order = $rank_data[0]['order'];
$rank_name = $rank_data[0]['name'];
$rank_mode = $rank_data[0]['mode'];
$rank_type = $rank_data[0]['type'];
$rank_requirement = $rank_data[0]['requirement'];
$rank_rate = $rank_data[0]['rate'];
$rank_rate_type = $rank_data[0]['rate_type'];

// Get last rank
$last_rank = affwp_ranks_get_affiliate_last_rank( $affiliate_id );
$last_rank_data = get_rank_by_id( $last_rank_id );
$last_rank_order = $last_rank_data[0]['order'];
$last_rank_name = $last_rank_data[0]['name'];
$last_rank_mode = $last_rank_data[0]['mode'];
$last_rank_type = $last_rank_data[0]['type'];
$last_rank_requirement = $last_rank_data[0]['requirement'];
$last_rank_rate = $last_rank_data[0]['rate'];
$last_rank_rate_type = $last_rank_data[0]['rate_type'];

// Get next rank
$next_rank_id = affwp_ranks_get_affiliate_next_rank( $affiliate_id );
$next_rank_data = get_rank_by_id( $next_rank_id );
$next_rank_order = $next_rank_data[0]['order'];
$next_rank_name = $next_rank_data[0]['name'];
$next_rank_mode = $next_rank_data[0]['mode'];
$next_rank_type = $next_rank_data[0]['type'];
$next_rank_requirement = $next_rank_data[0]['requirement'];
$next_rank_rate = $next_rank_data[0]['rate'];
$next_rank_rate_type = $next_rank_data[0]['rate_type'];
 
 
?>

<div id="affwp-affiliate-dashboard-affiliate_rank" class="affwp-tab-content" style="padding-top: 20px;">

	<h4><?php _e( 'Affiliate Rank', 'affiliatewp-ranks' ); ?></h4>

	<table class="affwp-table table">
		<thead>
			<tr>
				<th><?php _e( 'Last Rank', 'affiliatewp-ranks' ); ?></th>
				<th><?php _e( 'Current Rank', 'affiliatewp-ranks' ); ?></th>
				<th><?php _e( 'Next Rank', 'affiliatewp-ranks' ); ?></th>
			</tr>
		</thead>

		<tbody>
			
            <?php 
			
				if ( empty( $last_rank_name ) ) $last_rank_name = 'None'; 
				if ( empty( $rank_name ) ) $last_rank_name = 'None'; 
				if ( empty( $next_rank_name ) ) $next_rank_name = 'None'; 
			
			?>
            
			<tr>
				<td><?php echo $last_rank_name; ?></td>
				<td><?php echo $rank_name; ?></td>
                <td><?php echo $next_rank_name; ?></td>
			</tr>

		</tbody>
	</table>

	<?php do_action( 'affwp_affiliate_dashboard_after_affiliate_rank', affwp_get_affiliate_id() ); ?>

</div>