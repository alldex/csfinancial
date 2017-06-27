<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
$affiliate_id = affwp_get_affiliate_id();

?>
<div id="affwp-affiliate-dashboard-events" class="affwp-tab-content">
        <?php 
        /* We do this since we can't overwrite the dashboard panel too easily
         * See Agents_Tree_Display
         */
        ?>
        <?php do_action("affwp_affiliate_dashboard_events_show", $affiliate_id ); ?>
</div>	