<?php
/**
 * Loop add to cart
 *
 * @author  WPEka
 * @package WooCommerce/Templates
 * @version 1.0.0
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
global $product;

if (method_exists($product, 'get_type') && $product->get_type() == 'auction' ) :
    
    $user_id  = get_current_user_id();

    if ($user_id == $product->get_auction_current_bider() && !$product->get_auction_closed() && !$product->is_sealed()) :
        
        echo apply_filters('woocommerce_simple_auction_winning_bage', '<span class="winning" data-auction_id="'.$product->get_id().'" data-user_id="'.get_current_user_id().'">'.__('Winning!', 'wc_auction_software').'</span>', $product);

    endif; 
endif; 

?>
