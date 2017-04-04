<?php
/**
 * User placed a bid email notification (plain)
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
global $woocommerce;

$product_data = wc_get_product($product_id);

echo $email_heading . "\n\n";

printf(__("Hi there. A bid was placed for %s . Bid: %s", 'wc_simple_auctions'), get_permalink($product_id), $product_data->get_title(), wc_price($product_data->get_curent_bid())); 
echo "\n\n";
echo get_permalink($product_id);
echo "\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));