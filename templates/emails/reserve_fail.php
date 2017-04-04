<?php
/**
 * Admin auction failed email
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly 
}
$product_data = wc_get_product($product_id);

?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php printf(__("Sorry. The auction for <a href='%s'>%s</a> has failed because it did not make the reserve price.", 'wc_simple_auctions'), get_permalink($product_id), $product_data->get_title()); ?></p>

<?php do_action('woocommerce_email_footer'); ?>