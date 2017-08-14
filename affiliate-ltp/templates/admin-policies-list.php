<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
// is there anything else we want the template to do??
?>
<div class="wrap">
<h1><?= _e('Policies', 'affiliate-ltp'); ?></h1>
<?php $notices->display_notices(); ?>
<a href="<?php echo esc_url(add_query_arg('action', 'add_policy')); ?>" class="page-title-action"><?php _e('Add New', 'affiliate-ltp'); ?></a>
<?php
$table->display();
?>
</div>
