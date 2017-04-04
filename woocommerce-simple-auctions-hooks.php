<?php
/**
 * WooCommerce Hooks
 *
 * Action / filter hooks used for WooCommerce functions/templates
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Templates
 * @version  1.6.4
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}

if (! is_admin() || defined('DOING_AJAX') ) {
        
    // Product Add to cart
    add_action('woocommerce_auction_add_to_cart', 'woocommerce_auction_add_to_cart', 30);

    add_action('woocommerce_single_product_summary', 'woocommerce_auction_bid', 25);

    if (is_user_logged_in()) { 
            add_action('woocommerce_single_product_summary', 'woocommerce_auction_pay', 26); 
    }
}