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
    <?php if (!empty($event['partners'])) : ?>
    <table class="table table-striped affwp-table">
        <thead>
            <tr>
                <th>Partner</th>
                <th>Attendee</th>
                <th>Spouse</th>
                <th>Price Paid</th>
        </thead>
        <tbody>
        <?php foreach ($event['partners'] as $partner_id => $partner) : ?>
            <?php foreach ($partner['registrants'] as $attendee) : ?>
            <tr>
                <td><?= $partner['name']; ?></td>
                <td><?= $attendee['name']; ?></td>
                <td><?= $attendee['spouse']; ?></td>
                <td>$<?= $attendee['price_paid']; ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
        <p>No one has registered yet.</p>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>