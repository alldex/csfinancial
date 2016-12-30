<div id="affwp-affiliate-dashboard-url-generator" class="affwp-tab-content">

	<h4><?php _e( 'Agent URLs', 'affiliate-ltp' ); ?></h4>

	<?php do_action( 'affwp_affiliate_dashboard_urls_top', affwp_get_affiliate_id() ); ?>

	<?php if ( 'id' == affwp_get_referral_format() ) : ?>
		<p><?php printf( __( 'Your affiliate ID is: <strong>%s</strong>', 'affiliate-wp' ), affwp_get_affiliate_id() ); ?></p>
	<?php elseif ( 'username' == affwp_get_referral_format() ) : ?>
		<p><?php printf( __( 'Your affiliate username is: <strong>%s</strong>', 'affiliate-wp' ), affwp_get_affiliate_username() ); ?></p>
	<?php endif; ?>
</div>