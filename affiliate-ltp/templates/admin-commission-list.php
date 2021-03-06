<?php ?>
<div class="wrap">
    <h1>
        <?php _e('Referrals', 'affiliate-wp'); ?>
        <a href="<?php echo esc_url(add_query_arg(array('page' => 'affiliate-ltp-policies', 'action' => 'add_policy'))); ?>" class="page-title-action"><?php _e('Add New', 'affiliate-wp'); ?></a>
        <a href="<?php echo esc_url(add_query_arg(array('page' => 'affiliate-wp-reports', 'tab' => 'referrals'))); ?>" class="page-title-action"><?php _ex('Reports', 'referrals', 'affiliate-wp'); ?></a>
        <button class="page-title-action affwp-referrals-export-toggle"><?php _e('Generate Payout File', 'affiliate-wp'); ?></button>
        <button class="page-title-action affwp-referrals-export-toggle" style="display:none"><?php _e('Close', 'affiliate-wp'); ?></button>
    </h1>

    <?php do_action('affwp_referrals_page_top'); ?>

    <div id="affwp-referrals-export-wrap">

        <?php do_action('affwp_referrals_page_buttons'); ?>

        <form id="affwp-referrals-export-form" style="display:none;" action="<?php echo admin_url('admin.php?page=affiliate-wp-referrals'); ?>" method="post">
            <p>
                <input type="text" class="affwp-datepicker" autocomplete="off" name="from" placeholder="<?php _e('From - mm/dd/yyyy', 'affiliate-wp'); ?>"/>
                <input type="text" class="affwp-datepicker" autocomplete="off" name="to" placeholder="<?php _e('To - mm/dd/yyyy', 'affiliate-wp'); ?>"/>
                <label>
                    <?= _e('Minimum amount', 'affiliate-wp'); ?><input type="text" class="affwp-text" name="minimum" 
                       title=""
                       alt="<?= _e('Minimum amount', 'affiliate-wp'); ?>" 
                       placeholder="<?php esc_attr_e('Minimum amount', 'affiliate-wp'); ?>" value="<?= $minimum_payout_amount; ?>"/>
                
                </label>
                <input type="checkbox" class="affwp-checkbox" name="is_life_commission" checked /> Life Insurance Only
                <input type="hidden" name="affwp_action" value="generate_commission_payout"/>
                <?php do_action('affwp_referrals_page_csv_export_form'); ?>
                <input type="submit" value="<?php _e('Generate CSV File', 'affiliate-wp'); ?>" class="button-secondary"/>
            <p><?php printf(__('This will mark all unpaid referrals in this timeframe as paid. To export referrals with a status other than <em>unpaid</em>, go to the <a href="%s">Tools &rarr; Export</a> page.', 'affiliate-wp'), admin_url('admin.php?page=affiliate-wp-tools&tab=export_import')); ?></p>
            </p>
        </form>

    </div>
    <form id="affwp-referrals-filter-form" method="get" action="<?php echo admin_url('admin.php?page=affiliate-wp-referrals'); ?>">

        <?php $referrals_table->search_box(__('Search', 'affiliate-wp'), 'affwp-referrals'); ?>

        <input type="hidden" name="page" value="affiliate-wp-referrals" />

        <?php $referrals_table->views() ?>
        <?php $referrals_table->display() ?>
    </form>
    <?php do_action('affwp_referrals_page_bottom'); ?>
</div>