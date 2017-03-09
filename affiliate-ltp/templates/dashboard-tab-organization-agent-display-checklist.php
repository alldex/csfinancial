<li class="checklist statistics-row-category">
    <i class="fa fa-clipboard"></i>
    <span class='title'>Checklist</span>
    <i class="fa fa-chevron-down"></i>
    <div class='statistics-row-category-items hidden'>
        <ol>
            <li>
                <?php foreach ($checklist as $id => $item) : ?>
                <label><input type="checkbox" <?php
                if (!empty($item['date_completed'])) {
                    echo 'checked="checked"';
                }
                ?> />
                <?= $item['name']; ?>
                <?php if (!empty($item['date_completed'])) { ?>
                    - <?= $item['date_completed'] ?>
                <?php } else { ?>
                    <?php _e("Not started", 'affiliate-ltp'); ?>
                <?php } ?>
                </label>
                <?php endforeach; ?>
            </li>
        </ol>
        <input type='button' value='Update' />
    </div>
</li>