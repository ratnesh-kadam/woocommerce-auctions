<?php
/**
 * Customer buy now email (plain)
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
global $woocommerce;

$product_data = wc_get_product($product_id);

echo $email_heading . "\n\n";

printf(__("Sorry the item that you were bidding on (%s) was sold for the buy now price. Better luck next time!", 'wc_auction_software'),  $product_data -> get_title()); 
echo "\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));