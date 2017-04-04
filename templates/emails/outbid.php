<?php
/**
 * Customer outbid email
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly 
}$product_data = wc_get_product($product_id);

?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php printf(__("Hi there. Your bid for <a href='%s'>%s</a> has been outbid. Current bid is: %s", 'wc_simple_auctions'), get_permalink($product_id), $product_data->get_title(), wc_price($product_data->get_curent_bid())); ?></p>

<?php do_action('woocommerce_email_footer'); ?>