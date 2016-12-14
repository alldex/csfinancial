<?php 

// TODO: stephen clean up mixed if elseif syntax with wordpress conventions.
        $affiliate_id = affwp_get_affiliate_id();
	$sub_affiliates = affwp_mlm_get_sub_affiliates( $affiliate_id );

	$sub_affiliate_ids = wp_list_pluck( $sub_affiliates, 'affiliate_id' );

	$sub_affiliate_count = count( $sub_affiliates );
        
        $direct_agent_id = affwp_mlm_get_direct_affiliate($affiliate_id);
        $parent_agent_id = affwp_mlm_get_parent_affiliate($affiliate_id);
        $parent_agent = $parent_agent_id ? affwp_get_affiliate_name($parent_agent_id) : null;
        $direct_agent = $direct_agent_id ? affwp_get_affiliate_name($direct_agent_id) : null;
        
        $agent_position = 1;
        $upline = affwp_mlm_get_upline($affiliate_id);
        if (!empty($upline)) {
            $agent_position = count($upline) + 1;
        }
	
	// DEBUG - var_dump( $sub_affiliate_ids );
        
        function display_sub_affiliates( $sub_affiliates, $sub_affiliate_count, $sub_affiliate_ids ) {
            
            $traverseArray = array();
            
            ?>
            <?php 	if ( $sub_affiliates ) : ?>
            <h4><?php printf( __( 'Sub Agents %s', 'affilate-ltp' ), $sub_affiliate_count ); ?></h4>
            
            <table id="sub_affiliates" class="affwp-table table">
                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'affilate-ltp' ); ?></th>
                        <th><?php _e( 'Name', 'affilate-ltp' ); ?></th>
                        <th><?php _e( 'Sub Agents', 'affilate-ltp' ); ?></th>
                        <th><?php _e( 'Status', 'affilate-ltp' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                
                    <?php
    
                    if ( $sub_affiliate_ids ) {
                        foreach ( $sub_affiliate_ids as $sub_id ) {
                            $sub_name = affiliate_wp()->affiliates->get_affiliate_name( $sub_id );
                            $sub_sub_affiliates = affwp_mlm_get_sub_affiliates( $sub_id );
                            $sub_count = count( $sub_sub_affiliates );
							$sub_status = affwp_get_affiliate_status( $sub_id );
                            if ($sub_count > 0) {
                                $traverseArray[] = array(
                                    "name" => $sub_name
                                        ,"sub-id" => $sub_id
                                        ,"affiliates" => $sub_sub_affiliates
                                        , "count" => $sub_count
                                        , "ids" => wp_list_pluck( $sub_sub_affiliates, 'affiliate_id' ));
                            }
                    ?>
                    
                    <tr>
                        <td><?php echo $sub_id; ?></td>
                        <?php if ($sub_count > 0) { ?>
                        <td><a href="#sub-<?php echo $sub_id; ?>"><?php echo $sub_name; ?></a></td>
                        <?php } else { ?>
                        <td><?php echo $sub_name; ?></td>
                        <?php } ?>
                        <td><?php echo $sub_count; ?></td>
                        <td><?php echo $sub_status; ?></td>
                    </tr>  
                    
                    <?php
                        }
                    }
                    ?>
                </tbody>
                            
            </table>
            <?php if (!empty($traverseArray)) { 
                foreach ($traverseArray as $record) { 
                    
                    echo "<a name='sub-",$record['sub-id'],"'></a>";
                    echo "<h5>",$record['name'],"</h5>";
                    display_sub_affiliates($record['affiliates'], $record['count'], $record['ids']);
                }
            }
            ?>
            <?php else : ?>
            
            <h4><?php _e( 'No Sub Agents yet.', 'affilate-ltp' ); ?></h4>
            
			<?php if ( affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) { ?>
				<p><?php _e( 'When a user registers as an agent using your commission URL they will become your sub-agent.', 'affilate-ltp' ); ?></p>
			<?php } ?>
            
        <?php endif; ?>
                                <?php
        }
	
?>

<div id="affwp-affiliate-dashboard-organization" class="affwp-tab-content">
    
        
        <?php if (!empty($direct_agent_id)) : ?>
        <p><?php printf( __('Agent that recruited you: %s', 'affilate-ltp' ), $direct_agent); ?></p>    
        <?php endif; ?>
        
        <?php if (!empty($parent_agent_id)) : ?>
        <p><?php printf( __('Parent Agent: %s', 'affilate-ltp' ), $parent_agent); ?></p>
        <?php endif; ?>
        
        <p><?php printf( __('Agent Organization Position: %s', 'affilate-ltp' ), $agent_position); ?></p>
        

	<?php display_sub_affiliates($sub_affiliates, $sub_affiliate_count, $sub_affiliate_ids); ?>

	<h4><?php _e( 'Indirect Commissions', 'affilate-ltp' ); ?></h4>
	
	<?php
		// get referrals for sub affiliates				
		$per_page  = 30;
		$page      = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

		// get the affiliate's referrals
		$referrals = affiliate_wp_mlm()->get_sub_affiliate_referrals(
			array(
				'number' => $per_page,
				'offset' => $per_page * ( $page - 1 ),
			)
		);
	?>

	<?php if ( $referrals ) : ?>
	<p><?php _e( 'These commissions were awarded to you due to the efforts of your Sub Agents. These are also shown on the commissions tab.', 'affilate-ltp' ); ?></p>
	<?php endif; ?>
	
	<table id="affwp-affiliate-dashboard-referrals" class="affwp-table">
		<thead>
			<tr>
				<th class="referral-amount"><?php _e( 'Amount', 'affiliate-wp' ); ?></th>
				<th class="referral-description"><?php _e( 'Description', 'affiliate-wp' ); ?></th>
				<th class="referral-status"><?php _e( 'Status', 'affiliate-wp' ); ?></th>
				<th class="referral-date"><?php _e( 'Date', 'affiliate-wp' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php if ( $referrals ) : ?>

				<?php foreach ( $referrals as $referral ) : ?>
					<tr>
						<td class="referral-amount"><?php echo affwp_currency_filter( affwp_format_amount( $referral->amount ) ); ?></td>
						<td class="referral-description"><?php echo $referral->description; ?></td>
						<td class="referral-status <?php echo $referral->status; ?>"><?php echo $referral->status; ?></td>
						<td class="referral-date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $referral->date ) ); ?></td>
					</tr>
				<?php endforeach; ?>

			<?php else : ?>

				<tr>
					<td colspan="4"><?php _e( 'You have not made any commissions from sub agents yet.', 'affilate-ltp' ); ?></td>
				</tr>

			<?php endif; ?>
		</tbody>
	</table>

	<div class="affwp-pagination">
		<?php
			echo paginate_links( array(
				'current'      => $page,
				'total'        => ceil( affiliate_wp_mlm()->count_sub_affiliate_referrals() / $per_page ),
				'add_fragment' => '#affwp-affiliate-dashboard-organization',
				'add_args'     => array(
				'tab'          => 'organization'
				)
			) );
		?>
	</div>

</div>	