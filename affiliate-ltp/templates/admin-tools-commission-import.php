<?php
/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
?>
<div class="postbox">
    <h3><span><?php _e('Import Commissions', 'affiliate-ltp'); ?></span></h3>
    <div class="inside">
        <p><?php _e('Process commissions from a csv file.', 'affiliate-ltp'); ?></p>
        <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin.php?page=affiliate-wp-tools&tab=export_import'); ?>">
            <p>
                <input type="file" name="import_file"/>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="skip_life_licensed_check" value="1" CHECKED="CHECKED" />
                    Still process commissions with base shops that include non-licensed life agents?
                </label>
            </p>
            <p>
                <input type="hidden" name="affwp_action" value="import_commissions" />
                <?php wp_nonce_field('affwp_import_nonce', 'affwp_import_nonce'); ?>
                <?php submit_button(__('Import', 'affiliate-ltp'), 'secondary', 'submit', false); ?>
            </p>
        </form>
    </div><!-- .inside -->
    <p>An example CSV can be found <a href="/wp-content/plugins/affiliate-ltp/examples/commission-example.csv" download>here</a>.</p>
</div><!-- .postbox -->