<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
?>
<ul>
        <?php foreach ($registrants as $attendee) : ?>
        <li><?= $attendee['name'] ?>
            <?php if (!empty($attendee['spouse'])) : ?> & <?= $attendee['spouse']; ?><?php endif; ?>
            
            Paid: $<?= $attendee['price_paid']; ?>
        </li>
        <?php endforeach; ?>
</ul>