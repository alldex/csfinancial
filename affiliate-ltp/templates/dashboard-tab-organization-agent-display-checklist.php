<?php $agent_id = $node['id']; ?>
<li class="checklist statistics-row-category" data-action='affwp_ltp_save_progress_item'>
    <i class="fa fa-clipboard"></i>
    <span class='title'>Checklist</span>
    <i class="fa fa-chevron-down"></i>
    <div class='statistics-row-category-items hidden'>
        <ol>
            <li class="checklist-item">
                <?php foreach ($checklist as $id => $item) : ?>
                <label><input class='progress-item' data-id='<?= $id; ?>' data-agent-id='<?= $agent_id; ?>' type="checkbox" <?php
                if (!empty($item['date_completed'])) {
                    echo 'checked="checked" ';
                    echo 'data-completed="1" ';
                }
                else {
                    echo 'data-completed="0" ';
                }
                ?> />
                <?= $item['name']; ?>
                    <span class='status'>
                <?php if (!empty($item['date_completed'])) { ?>
                    - <?= $item['date_completed'] ?>
                <?php } else { ?>
                    <?php _e("Not started", 'affiliate-ltp'); ?>
                <?php } ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </li>
        </ol>
    </div>
</li>