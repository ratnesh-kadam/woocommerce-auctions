<?php
/**
 * User placed a bid email notification
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly 
}$product_data = wc_get_product($product_id);
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php printf(__("Hi there. A bid was placed for <a href='%s'>%s</a>. Bid: %s", 'wc_simple_auctions'), get_permalink($product_id), $product_data->get_title(), wc_price($product_data->get_curent_bid())); ?></p>

<?php do_action('woocommerce_email_footer'); ?>