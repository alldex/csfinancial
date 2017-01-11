<?php 
// used as summative vars in the table iteration of the points record.
$life_sum = 0;
$non_life_sum = 0;
?>

<h4><?php _e( 'Point Graphs', 'affiliate-ltp' ); ?></h4>
<?php $graph->display(); ?>

<h4><?php _e('Points Table', 'affiliate-ltp'); ?></h4>
<?php if ( !empty( $points_data ) ) : ?>
<table>
    <thead>
        <tr>
            <th><?php _e( 'Date', 'affiliate-ltp' ); ?></th>
            <th><?php _e( 'Life Points', 'affiliate-ltp' ); ?></th>
            <th><?php _e( 'Non-Life Points', 'affiliate-ltp' ); ?></th>
            <th><?php _e( 'Total Points', 'affiliate-ltp' ); ?></th>
            <th><?php _e( 'Life Sum', 'affiliate-ltp' ); ?></th>
            <th><?php _e( 'Non-Life Sum', 'affiliate-ltp' ); ?></th>
            <th><?php _e( 'Total Sum', 'affiliate-ltp' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($points_data as $records) : ?>
        <?php foreach ($records as $record) { 
            $life_sum += $record->get_life();
            $non_life_sum += $record->get_non_life();
        ?>
        <tr>
            <td><?= $record->get_date(); ?></td>
            <td><?= $record->get_life(); ?></td>
            <td><?= $record->get_non_life(); ?></td>
            <td><?= $record->get_total(); ?></td>
            <td><?= $life_sum; ?></td>
            <td><?= $non_life_sum; ?></td>
            <td><?= $life_sum + $non_life_sum; ?></td>
        </tr>
        <?php } ?>
        <?php endforeach; ?>
        <tr>
        </tr>
    </tbody>
</table>
<?php else : ?>
<?php _e('No points earned for this time period.', 'affiliate-ltp'); ?>
<?php endif; ?>