		<?php
  /**
 * Loop Add to Cart
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 1.6.4
 */

  if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
    }
    global $product;

?>

<?php if (method_exists($product, 'get_type') && $product->get_type() == 'auction' ) : ?>
    <?php echo apply_filters('woocommerce_simple_auction_auction_bage', '<span class="auction-bage"  ></span>',  $product); ?>
<?php 
endif; ?>
