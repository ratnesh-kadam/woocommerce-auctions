<?php
/**
 * Email auction won (plain)
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
global $woocommerce;

$product_data = wc_get_product($product_id);

echo $email_heading . "\n\n";

printf(__("Congratulations! You have the won auction for %s. Your bid was: %s. Please click on this link to pay for your auction", 'wc_simple_auctions'),  $product_data -> get_title(), wc_price($current_bid));  
echo "\n\n";
echo esc_attr(add_query_arg("pay-auction", $product_id, $checkout_url));
echo "\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));