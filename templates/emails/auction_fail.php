<?php
/**
 * Admin auction fail email
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly 
}$product_data = wc_get_product($product_id);
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php printf(__("Sorry. The auction for <a href='%s'>%s</a> has failed. %s ", 'wc_simple_auctions'), get_permalink($product_id), $product_data->get_title(), $reason); ?></p>


<?php do_action('woocommerce_email_footer'); ?>