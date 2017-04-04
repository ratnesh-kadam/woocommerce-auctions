<?php
/**
 * Customer remind to pay email
 */

if (!defined('ABSPATH')) { exit; // Exit if accessed directly
}
$product_data = wc_get_product($product_id);
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php printf(__("The auction for <a href='%s'>%s</a>.  has been relisted. Reason: auction not paid for %s hours", 'wc_simple_auctions'), get_permalink($product_id), $product_data -> get_title(), $product_data->get_auction_relist_not_paid_time()); ?></p>



<?php do_action('woocommerce_email_footer'); ?>