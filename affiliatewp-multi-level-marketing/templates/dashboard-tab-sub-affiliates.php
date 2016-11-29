<?php 

	$sub_affiliates = affwp_mlm_get_sub_affiliates( affwp_get_affiliate_id() );

	$sub_affiliate_ids = wp_list_pluck( $sub_affiliates, 'affiliate_id' );

	$sub_affiliate_count = count( $sub_affiliates );
	
	// DEBUG - var_dump( $sub_affiliate_ids );
	
?>

<div id="affwp-affiliate-dashboard-sub-affiliates" class="affwp-tab-content">

	<?php 	if ( $sub_affiliates ) : ?>
            <h4><?php printf( __( 'Sub Affiliates %s', 'affiliatewp-multi-level-marketing' ), $sub_affiliate_count ); ?></h4>
            
            <table id="sub_affiliates" class="affwp-table table">
                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'affiliatewp-multi-level-marketing' ); ?></th>
                        <th><?php _e( 'Name', 'affiliatewp-multi-level-marketing' ); ?></th>
                        <th><?php _e( 'Sub Affiliates', 'affiliatewp-multi-level-marketing' ); ?></th>
                        <th><?php _e( 'Status', 'affiliatewp-multi-level-marketing' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                
                    <?php
    
                    if ( $sub_affiliate_ids ) {
                        foreach ( $sub_affiliate_ids as $sub_id ) {
                            $sub_name = affiliate_wp()->affiliates->get_affiliate_name( $sub_id );
                            $sub_count = count( affwp_mlm_get_sub_affiliates( $sub_id ) );
							$sub_status = affwp_get_affiliate_status( $sub_id );
                            
                    ?>
                    
                    <tr>
                        <td><?php echo $sub_id; ?></td>
                        <td><?php echo $sub_name; ?></td>
                        <td><?php echo $sub_count; ?></td>
                        <td><?php echo $sub_status; ?></td>
                    </tr>  
                    
                    <?php
                        }
                    }
                    ?>
                </tbody>
                            
            </table>
            <?php else : ?>
            
            <h4><?php _e( 'No Sub Affiliates yet.', 'affiliatewp-multi-level-marketing' ); ?></h4>
            
			<?php if ( affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) { ?>
				<p><?php _e( 'When a user registers as an affiliate using your referral URL they will become your sub-affiliate.', 'affiliatewp-multi-level-marketing' ); ?></p>
			<?php } ?>
            
        <?php endif; ?>

	<h4><?php _e( 'Indirect Referrals', 'affiliatewp-multi-level-marketing' ); ?></h4>
	
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
	<p><?php _e( 'These referrals were awarded to you due to the efforts of your Sub Affiliates. These are also shown on the referrals tab.', 'affiliatewp-multi-level-marketing' ); ?></p>
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
					<td colspan="4"><?php _e( 'You have not made any referrals from sub affiliates yet.', 'affiliatewp-multi-level-marketing' ); ?></td>
				</tr>

			<?php endif; ?>
		</tbody>
	</table>

	<div class="affwp-pagination">
		<?php
			echo paginate_links( array(
				'current'      => $page,
				'total'        => ceil( affiliate_wp_mlm()->count_sub_affiliate_referrals() / $per_page ),
				'add_fragment' => '#affwp-affiliate-dashboard-sub-affiliates',
				'add_args'     => array(
				'tab'          => 'sub-affiliates'
				)
			) );
		?>
	</div>

</div>	