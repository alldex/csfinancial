<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
?>
<div class="events-accordion">
<?php foreach ($events as $event) : ?>
<h3><?= $event['title'] ?></h3>
<div>
    <?php if (!empty($event['registrants'])) : ?>
    <table class="table table-striped affwp-table">
        <thead>
            <tr>
                <th>Attendee</th>
                <th>Spouse</th>
                <th>Price Paid</th>
        </thead>
        <tbody>
            <?php foreach ($event['registrants'] as $attendee) : ?>
            <tr>
                <td><?= $attendee['name']; ?></td>
                <td><?= $attendee['spouse']; ?></td>
                <td>$<?= $attendee['price_paid']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
        <p>No one has registered yet.</p>
    <?php endif; ?>
</div>
<?php endforeach; ?>