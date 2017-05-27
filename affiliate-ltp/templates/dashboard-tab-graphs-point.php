<?php 
// used as summative vars in the table iteration of the points record.
$life_sum = 0;
$non_life_sum = 0;
$graph_title = __("Points Graph", 'affiliate-ltp'); 
$table_title = __("Points Table", 'affiliate-ltp'); 
if ($is_partner) {
    if ($include_super_shop) {
        $graph_title = __("Super Shop Points Graph", 'affiliate-ltp'); 
        $table_title = __("Super Shop Points Table", 'affiliate-ltp'); 
    }
    else {
        $graph_title = __("Base Shop Points Graph", 'affiliate-ltp'); 
        $table_title = __("Base Shop Points Table", 'affiliate-ltp'); 
    }
}
?>
<h4><?= $graph_title ?></h4>
<?php if ($is_partner) : ?>
    <label><input type="checkbox" id="affwp_ltp_show_super_base_shop"
            <?php if ($include_super_shop) : ?>
                  CHECKED="CHECKED"
            <?php endif; ?>
                  /> Include Super Shop</label>
<?php endif; ?>
<?php $graph->display(); ?>

<h4><?= $table_title ?></h4>
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