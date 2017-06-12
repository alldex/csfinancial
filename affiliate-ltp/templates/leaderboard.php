<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */

?>
<form method="POST">
    <label>Time Period:
    <select name="leaderboard_filter">
        <option value="year" <?= ($currentFilter === "year") ? "SELECTED " : "" ?>>Last Year</option>
        <?php foreach ($months as $index => $name) : ?>
        <option value="<?= $index; ?>" <?php if ($currentFilter === $index) echo "SELECTED "; ?>><?= $name; ?></option>
        <?php endforeach; ?>
    </select>
    <label>
    <input type="submit" name="submit" value="Display" />
</form>
<?php if (empty($scores)) : ?>
<p>
    <?= __("No data exists for your selection criteria", "affiliate-ltp"); ?>
</p>
<?php endif; ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th></th>
            <th></th>
            <th><?= _e("Agent", "affiliate-ltp"); ?></th>
            <th><?= $type ?></th>
        </tr>
    </thead>
    <tbody>
        <?php for ($i = 0; $i < count($scores); $i++) : ?>
        <tr>
            <td><?= ($i+ 1); ?></td>
            <td><img class="thumbnail" class="avatar avatar-26 photo" width="26" height="26" src="<?= $scores[$i]['image']; ?>" /></td>
            <td><?= $scores[$i]['agent']; ?></td>
            <td><?= $scores[$i]['value']; ?></td>
        </tr>
        <?php endfor; ?>
    </tbody>
</table>
