<?php

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
?>
<ul>
    <?php foreach ($events as $event) : ?>
            <li><?= $event['title'] ?>
                <?php if (!empty($event['registrants'])) : ?>
                <?php 
                    $registrants = $event['registrants']; 
                    include 'dashboard-tab-events-registrants.php'; 
                ?>
                <?php else : ?>
                <p>No one has registered yet.</p>
                <?php endif; ?>
            </li>
<?php endforeach; ?>
</ul>