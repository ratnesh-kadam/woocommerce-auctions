<?php
/**
 * Auction bid
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
global $woocommerce, $product, $post;
if(!(method_exists($product, 'get_type') && $product->get_type() == 'auction')) {
    return;
}
$current_user = wp_get_current_user();
$product_id =  $product->get_id();
$user_max_bid = $product->get_user_max_bid($product_id, $current_user->ID);


?>
	
<p class="auction-condition"><?php echo apply_filters('conditiond_text', __('Item condition:', 'wc_simple_auctions'), $product); ?><span class="curent-bid"> <?php  _e($product->get_condition(), 'wc_simple_auctions')  ?></span></p>

<?php if(($product->is_closed() === false ) and ($product->is_started() === true )) : ?>			
		
	<div class="auction-time" id="countdown"><?php echo apply_filters('time_text', __('Time left:', 'wc_simple_auctions'), $product_id); ?> 
		<div class="main-auction auction-time-countdown" data-time="<?php echo $product->get_seconds_remaining() ?>" data-auctionid="<?php echo $product_id ?>" data-format="<?php echo get_option('simple_auctions_countdown_format') ?>"></div>
	</div>

	<div class='auction-ajax-change' >
	    
		<p class="auction-end"><?php echo apply_filters('time_left_text', __('Auction ends:', 'wc_simple_auctions'), $product); ?> <?php echo  date_i18n(get_option('date_format'),  strtotime($product->get_auction_end_time()));  ?>  <?php echo  date_i18n(get_option('time_format'),  strtotime($product->get_auction_end_time()));  ?> <br />
    <?php printf(__('Timezone: %s', 'wc_simple_auctions'), get_option('timezone_string') ? get_option('timezone_string') : __('UTC+', 'wc_simple_auctions').get_option('gmt_offset')) ?>
		</p>

    <?php if ($product->get_auction_sealed() != 'yes') { ?>
		    <p class="auction-bid"><?php echo $product->get_price_html() ?> </p>
			
    <?php if(($product->is_reserved() === true) &&( $product->is_reserve_met() === false )  ) : ?>
				<p class="reserve hold"  data-auction-id="<?php echo esc_attr($product_id); ?>" ><?php echo apply_filters('reserve_bid_text', __("Reserve price has not been met", 'wc_simple_auctions')); ?></p>
    <?php 
endif; ?>	
			
    <?php if(($product->is_reserved() === true) &&( $product->is_reserve_met() === true )  ) : ?>
				<p class="reserve free"  data-auction-id="<?php echo esc_attr($product_id); ?>"><?php echo apply_filters('reserve_met_bid_text', __("Reserve price has been met", 'wc_simple_auctions')); ?></p>
    <?php 
endif; ?>
    <?php 
} elseif($product->get_auction_sealed() == 'yes') {?>
				<p class="sealed-text"><?php echo apply_filters('sealed_bid_text', __("This auction is <a href='#'>sealed</a>.", 'wc_simple_auctions')); ?>
					<span class='sealed-bid-desc' style="display:none;"><?php _e("In this type of auction all bidders simultaneously submit sealed bids so that no bidder knows the bid of any other participant. The highest bidder pays the price they submitted. If two bids with same value are placed for auction the one which was placed first wins the auction.", 'wc_simple_auctions') ?></span>
				</p>
				<?php 
    if (!empty($product->get_auction_start_price())) {?>
        <?php if($product->get_auction_type() == 'reverse' ) : ?>
							<p class="sealed-min-text"><?php echo apply_filters('sealed_min_text', sprintf(__("Maximum bid for this auction is %s.", 'wc_simple_auctions'), wc_price($product ->get_auction_start_price()))); ?></p>
        <?php else : ?>
							<p class="sealed-min-text"><?php echo apply_filters('sealed_min_text', sprintf(__("Minimum bid for this auction is %s.", 'wc_simple_auctions'), wc_price($product ->get_auction_start_price()))); ?></p>			
        <?php 
endif; ?>			
				<?php 
    } ?>	
    <?php 
} ?>	

    <?php if($product->get_auction_type() == 'reverse' ) : ?>
			<p class="reverse"><?php echo apply_filters('reverse_auction_text', __("This is reverse auction.", 'wc_simple_auctions')); ?></p>
    <?php 
endif; ?>	
    <?php if ($product->get_auction_sealed() != 'yes') { ?>
    <?php if ($product->get_auction_proxy() &&  $product->get_auction_max_current_bider() && get_current_user_id() == $product->get_auction_max_current_bider()) {?>
				<p class="max-bid"><?php  _e("Your max bid is", 'wc_simple_auctions') ?> <?php echo wc_price($product->get_auction_max_bid()) ?>
    <?php 
} ?>
    <?php 
} elseif($user_max_bid > 0) { ?>
			<p class="max-bid"><?php  _e("Your max bid is", 'wc_simple_auctions') ?> <?php echo wc_price($user_max_bid) ?>
    <?php 
} ?>	
    <?php do_action('woocommerce_before_bid_form'); ?>
		<form class="auction_form cart" method="post" enctype='multipart/form-data' data-product_id="<?php echo $product_id; ?>">
			
    <?php do_action('woocommerce_before_bid_button'); ?>
			
			<input type="hidden" name="bid" value="<?php echo esc_attr($product_id); ?>" />	
    <?php if($product->get_auction_type() == 'reverse' ) : ?>
				<div class="quantity buttons_added">
					<input type="button" value="+" class="plus" />	
					<input type="number" name="bid_value" data-auction-id="<?php echo esc_attr($product_id); ?>"  <?php if ($product->get_auction_sealed() != 'yes') { ?> value="<?php echo $product->bid_value() ?>" max="<?php echo $product->bid_value()  ?>"  <?php 
} ?> step="any" size="<?php echo strlen($product->get_curent_bid())+2 ?>" title="bid"  class="input-text  qty bid text left">
					<input type="button" value="-" class="minus" />
				</div>	
				 	<button type="submit" class="bid_button button alt"><?php echo apply_filters('bid_text', __('Bid', 'wc_simple_auctions'), $product); ?></button>
				 			
    <?php else : ?>	
				<div class="quantity buttons_added">
				 	<input type="button" value="+" class="plus" />		 	
					<input type="number" name="bid_value" data-auction-id="<?php echo esc_attr($product_id); ?>" <?php if ($product->get_auction_sealed() != 'yes') { ?>  value="<?php echo $product->bid_value()  ?>" min="<?php echo $product->bid_value()  ?>" <?php 
} ?>  step="any" size="<?php echo strlen($product->get_curent_bid())+2 ?>" title="bid"  class="input-text qty  bid text left">
					<input type="button" value="-" class="minus" />
				</div>	
		 	<button type="submit" class="bid_button button alt"><?php echo apply_filters('bid_text', __('Bid', 'wc_simple_auctions'), $product); ?></button>
		 	<?php 
endif; ?>
		 	
		 	<input type="hidden" name="place-bid" value="<?php echo $product_id; ?>" />
			<input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>" />
    <?php if (is_user_logged_in() ) { ?>
				<input type="hidden" name="user_id" value="<?php echo  get_current_user_id(); ?>" />
    <?php  
} ?> 
    <?php do_action('woocommerce_after_bid_button'); ?>
		</form>
		
				
    <?php do_action('woocommerce_after_bid_form'); ?>
		
		
	</div>			 	

<?php elseif (($product->is_closed() === false ) and ($product->is_started() === false )) :?>
	
	<div class="auction-time future" id="countdown"><?php echo apply_filters('auction_starts_text', __('Auction starts in:', 'wc_simple_auctions'), $product); ?> 
		<div class="auction-time-countdown future" data-time="<?php echo $product->get_seconds_to_auction() ?>" data-format="<?php echo get_option('simple_auctions_countdown_format') ?>"></div>
	</div>
	
	<p class="auction-starts"><?php echo apply_filters('time_text', __('Auction starts:', 'wc_simple_auctions'), $product_id); ?> <?php echo  date_i18n(get_option('date_format'),  strtotime($product->get_auction_start_time()));  ?>  <?php echo  date_i18n(get_option('time_format'),  strtotime($product->get_auction_start_time()));  ?></p>
	<p class="auction-end"><?php echo apply_filters('time_text', __('Auction ends:', 'wc_simple_auctions'), $product_id); ?> <?php echo  date_i18n(get_option('date_format'),  strtotime($product->get_auction_end_time()));  ?>  <?php echo  date_i18n(get_option('time_format'),  strtotime($product->get_auction_end_time()));  ?> </p>
	
<?php 
endif; ?>