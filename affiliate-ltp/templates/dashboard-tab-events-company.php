<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
?>
<ul>
    <?php foreach ($events as $event) : ?>
            <li><?= $event['title'] ?>
                <?php if (!empty($event['partners'])) : ?>
                    <ul>
                    <?php foreach ($event['partners'] as $partner_id => $partner) : ?>
                        <li><?= $partner['name']; ?>
                            <?php 
                                $registrants = $partner['registrants'];
                                include 'dashboard-tab-events-registrants.php'; 
                            ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p>No one has registered yet.</p>
                <?php endif; ?>
            </li>
<?php endforeach; ?>
</ul>