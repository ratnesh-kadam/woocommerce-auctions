<?php
/**
 * Auction history tab
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
global $woocommerce, $post, $product;

$heading = esc_html(apply_filters('woocommerce_auction_history_heading', __('Auction History', 'wc_simple_auctions')));

?>

<h2><?php echo $heading; ?></h2>

<?php if(($product->is_closed() === true ) and ($product->is_started() === true )) : ?>
    
	<p><?php _e('Auction has finished', 'wc_simple_auctions') ?></p>
    <?php if ($product->get_auction_fail_reason() == '1') {
        _e('Auction failed because there were no bids', 'wc_simple_auctions');
} elseif($product->get_auction_fail_reason() == '2') {
    _e('Auction failed because item did not make it to reserve price', 'wc_simple_auctions');
}
    
if($product->get_auction_closed() == '3') {?>
		<p><?php _e('Product sold for buy now price', 'wc_simple_auctions') ?>: <span><?php echo wc_price($product->get_regular_price()) ?></span></p>
    <?php 
}elseif($product->get_auction_current_bider()) { ?>
		<p><?php _e('Highest bidder was', 'wc_simple_auctions') ?>: <span><?php echo get_userdata($product->get_auction_current_bider())->display_name ?></span></p>
    <?php 
} ?>
						
<?php endif; ?>	
        
<table id="auction-history-table-<?php echo $product->get_id(); ?>" class="auction-history-table">
    <?php 
        
        $auction_history = apply_filters('woocommerce__auction_history_data', $product->auction_history());
                
    if (!empty($auction_history) ) : ?>

        <thead>
            <tr>
                <th><?php _e('Date', 'wc_simple_auctions') ?></th>
                <th><?php _e('Bid', 'wc_simple_auctions') ?></th>
                <th><?php _e('User', 'wc_simple_auctions') ?></th>
                <th><?php _e('Auto', 'wc_simple_auctions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($product->is_sealed()) {
            
                echo "<tr>";
                   echo "<td colspan='4'  class='sealed'>".__('This auction is sealed. Upon auction finish auction history and winner will be available to the public.', 'wc_simple_auctions')."</td>";
                echo "</tr>"; 

} else {
    foreach ($auction_history as $history_value) {
        echo "<tr>";
        echo "<td class='date'>$history_value->date</td>";
        echo "<td class='bid'>".wc_price($history_value->bid)."</td>";
        echo "<td class='username'>".get_userdata($history_value->userid)->display_name."</td>";
        if ($history_value->proxy == 1) {
            echo " <td class='proxy'>".__('Auto', 'wc_simple_auctions')."</td>"; 
        }
        else { 
            echo " <td class='proxy'></td>"; 
        }
        echo "</tr>";
    }
}?> 
        </tbody>

    <?php endif;?>
        
	<tr class="start">
        <?php 
        if ($product->is_started() === true ) {
            echo '<td class="date">'.$product->get_auction_start_time().'</td>'; 
            echo '<td colspan="3" class="started">';
            echo apply_filters('auction_history_started_text', __('Auction started', 'wc_simple_auctions'), $product);
            echo '</td>';

        } else {
                echo '<td  class="date">'.$product->get_auction_start_time().'</td>'; 
                echo '<td colspan="3"  class="starting">';
                echo apply_filters('auction_history_starting_text', __('Auction starting', 'wc_simple_auctions'), $product);
                echo '</td>' ;
        }
        ?>
	</tr>
</table>