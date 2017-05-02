<?php
/**
 * WooCommerce Template Functions
 *
 * Functions used in the template files to output content - in most cases hooked in via the template actions. All functions are pluggable.
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}

if (! function_exists('woocommerce_auction_add_to_cart') ) {

    /**
     * Output the auction product add to cart area.
     *
     * @access     public
     * @subpackage Product
     * @return     void
     */
    function woocommerce_auction_add_to_cart() 
    {
        global $product;
        
        if(method_exists($product, 'get_type') && $product->get_type() == 'auction') {
            wc_get_template('single-product/add-to-cart/auction.php'); 
        }
    }
}
if (! function_exists('woocommerce_auction_bid') ) {

    /**
     * Output the to bid block.
     *
     * @access     public
     * @subpackage Product
     * @return     void
     */
    function woocommerce_auction_bid() 
    {
        global $product;
        
        if(method_exists($product, 'get_type') && $product->get_type() == 'auction') {
            wc_get_template('single-product/bid.php'); 
        }
    }
}
if (! function_exists('woocommerce_auction_pay') ) {

    /**
     * Output the to pay block.
     *
     * @access     public
     * @subpackage Product
     * @return     void
     */
    function woocommerce_auction_pay() 
    {
        global $product;
        
        if(method_exists($product, 'get_type') && $product->get_type() == 'auction') {
            wc_get_template('single-product/pay.php'); 
        }
    }
}