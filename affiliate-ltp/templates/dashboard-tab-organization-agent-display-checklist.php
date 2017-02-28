<li class="info">
    <i class="fa info"></i>
    <span>Checklist</span>
    <ol>
        <?php foreach ($checklist as $label => $item) : ?>
        <label><input type="checkbox" <?php
        if (!empty($item['date_completed'])) {
            echo 'checked="checked"';
        }
        ?> />
        <?= $label; ?>
        <?php if (!empty($item['date_completed'])) { ?>
            - <?= $item['date_completed'] ?>
        <?php } else { ?>
            <?php _e("Not started", 'affiliate-ltp'); ?>
        <?php } ?>
        </label>
        <?php endforeach; ?>
    </ol>
</li>