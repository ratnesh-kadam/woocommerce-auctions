<?php
/**
 * Customer remind to pay email (plain)
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
global $woocommerce;

$product_data = wc_get_product($product_id);

echo $email_heading . "\n\n";

printf(__("Auction for %s has been relisted. Reason: auction not paid in %s hours", 'wc_auction_software'),  $product_data -> get_title(), $product_data->get_auction_relist_not_paid_time()); 
echo "\n\n";



echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));