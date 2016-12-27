<?php
	$show = affiliate_wp()->settings->get( 'affwp_mlm_view_subs' );
	if ( empty( $show ) ) $show = 'tree';
 ?>

<div id="affwp-affiliate-dashboard-sub-affiliates" class="affwp-tab-content">

	<?php show_sub_affiliates( $affiliate_id, $show ); ?>

</div>	