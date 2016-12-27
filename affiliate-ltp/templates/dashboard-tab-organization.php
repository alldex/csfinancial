<?php 
        $affiliate_id = affwp_get_affiliate_id();
	$show = affiliate_wp()->settings->get( 'affwp_mlm_view_subs' );
	if ( empty( $show ) ) $show = 'tree';
 ?>

<div id="affwp-affiliate-dashboard-organization" class="affwp-tab-content">
	<?php show_sub_affiliates( $affiliate_id, $show ); ?>

</div>	