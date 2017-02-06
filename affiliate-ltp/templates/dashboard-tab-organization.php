<?php 
        $affiliate_id = affwp_get_affiliate_id();
	$show = affiliate_wp()->settings->get( 'affwp_mlm_view_subs' );
	if ( empty( $show ) ) $show = 'tree';
 ?>

<div id="affwp-affiliate-dashboard-organization" class="affwp-tab-content">
        <?php 
        /* We do this since we can't overwrite the dashboard panel too easily
         * See Agents_Tree_Display
         */
        ?>
        <?php do_action("affwp_affiliate_dashboard_organization_show", $affiliate_id, $show); ?>
</div>	