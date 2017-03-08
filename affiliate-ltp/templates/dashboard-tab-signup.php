<?php 
        $form_id = affwp_afgf_get_registration_form_id();
        
        
?>
<div id="affwp-affiliate-dashboard-signup" class="affwp-tab-content">
    <?php if (!empty($form_id)) : ?>
    <h3><?= _e("Signup Agents", 'affiliate-ltp'); ?></h3>
    <?= do_shortcode('[gravityform id="' . $form_id .'" title="false" description="false"]'); ?>
    <?php endif; ?>
</div>