<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
$affiliate_id = affwp_get_affiliate_id();

?>
<div id="affwp-affiliate-dashboard-commissions" class="affwp-tab-content">
        <?php do_action("affwp_affiliate_dashboard_commissions_show", $affiliate_id ); ?>
</div>	