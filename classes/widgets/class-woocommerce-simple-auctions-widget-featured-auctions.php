<?php
/**
 * Featured Auctions Widget
 *
 * Gets and displays featured auctions in an unordered list
 *
 * @category Widgets
 * @version  1.0.0
 * @extends  WP_Widget
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
class WC_SA_Widget_Featured_Auction extends WP_Widget
{
        
    var $woo_widget_cssclass;
    var $woo_widget_description;
    var $woo_widget_idbase;
    var $woo_widget_name;

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    function __construct() 
    {

        /* Widget variable settings. */
        $this->woo_widget_cssclass = 'woocommerce widget_featured_auctions';
        $this->woo_widget_description = __('Display a list of featured auctions on your site.', 'wc_simple_auctions');
        $this->woo_widget_idbase = 'woocommerce_featured_auctions';
        $this->woo_widget_name = __('WooCommerce Featured Auction', 'wc_simple_auctions');

        /* Widget settings. */
        $widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

        parent::__construct('featured-auctions', $this->woo_widget_name, $widget_ops);    
        
        

        add_action('save_post', array( $this, 'flush_widget_cache' ));
        add_action('deleted_post', array( $this, 'flush_widget_cache' ));
        add_action('switch_theme', array( $this, 'flush_widget_cache' ));
    }

    /**
     * Widget function
     *
     * @see    WP_Widget
     * @access public
     * @param  array $args
     * @param  array $instance
     * @return void
     */
    function widget($args, $instance) 
    {
        global $woocommerce;

        $cache = wp_cache_get('widget_featured_auction', 'widget');

        if (!is_array($cache) ) { $cache = array(); 
        }

        if (isset($cache[$args['widget_id']]) ) {
            echo $cache[$args['widget_id']];
            return;
        }

        ob_start();
        extract($args);

        $title = apply_filters('widget_title', empty($instance['title']) ? __('Featured Auctions', 'wc_simple_auctions') : $instance['title'], $instance, $this->id_base);
        if (!$number = (int) $instance['number'] ) {
            $number = 10; 
        }
        else if ($number < 1 ) {
            $number = 1; 
        }
        else if ($number > 15 ) {
            $number = 15; 
        }
        
        $query_args = array('posts_per_page' => $number, 'no_found_rows' => 1, 'post_status' => 'publish', 'post_type' => 'product' );
        $query_args['meta_query'] = $woocommerce->query->get_meta_query();
        $query_args['meta_query'][] = array(
        'key' => '_featured',
        'value' => 'yes'
        );
        $query_args['tax_query'] = array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'auction')); 
        $query_args['auction_arhive'] = true;     
        $r = new WP_Query($query_args);

        if ($r->have_posts()) : 
            $hide_time = empty( $instance['hide_time'] ) ? 0 : 1;
        
        ?>
		
        <?php echo $before_widget; ?>
        <?php if ($title ) { echo $before_title . $title . $after_title; 
} ?>
        <ul class="product_list_widget">
        <?php while ($r->have_posts()) : $r->the_post(); global $product;
            $time = '';
            $timetext = __('Time left', 'wc_simple_auctions');
            $datatime = $product->get_seconds_remaining();
            $product_id = $product->get_id();
            if(!$product->is_started()) {
                $timetext = __('Starting in', 'wc_simple_auctions');
                $datatime = $product->get_seconds_to_auction();
            }
            if($hide_time != 1 && !$product->is_closed()) {
                $time = '<span class="time-left">'.apply_filters('time_text', $timetext, $product_id).'</span>
			<div class="auction-time-countdown" data-time="'.$datatime.'" data-auctionid="'.$product_id.'" data-format="'.get_option('simple_auctions_countdown_format').'"></div>'; 
            }
            if($product->is_closed()) {
                $time = '<span class="has-finished">'.apply_filters('time_text', __('Auction finished', 'wc_simple_auctions'), $product_id).'</span>'; 
            }    
    ?>

		<li><a href="<?php echo esc_url(get_permalink($r->post->ID)); ?>" title="<?php echo esc_attr($r->post->post_title ? $r->post->post_title : $r->post->ID); ?>">
    <?php echo $product->get_image(); ?>
    <?php if ($r->post->post_title ) { echo get_the_title($r->post->ID); 
} else { echo $r->post->ID; 
} ?>
		</a> <?php echo $product->get_price_html(); ?>
    <?php echo $time ?>
		</li>

    <?php 
endwhile; ?>
        </ul>
        <?php echo $after_widget; ?>

        <?php 
        endif;

        $content = ob_get_clean();

        if (isset( $args['widget_id'] ) ) { $cache[$args['widget_id']] = $content; 
        }

        echo $content;

        wp_cache_set('widget_featured_auctions', $cache, 'widget');
        wp_reset_postdata();
    }


    /**
     * Update function
     *
     * @see    WP_Widget->update
     * @access public
     * @param  array $new_instance
     * @param  array $old_instance
     * @return array
     */
    function update( $new_instance, $old_instance ) 
    {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['number'] = (int) $new_instance['number'];
        $instance['hide_time'] = empty( $new_instance['hide_time'] ) ? 0 : 1;
        $this->flush_widget_cache();

        $alloptions = wp_cache_get('alloptions', 'options');
        if (isset($alloptions['widget_featured_auctions']) ) { delete_option('widget_featured_auctions'); 
        }

        return $instance;
    }


    /**
     * Flush widget cache
     *
     * @access public
     * @return void
     */
    function flush_widget_cache() 
    {
        wp_cache_delete('widget_featured_auctions', 'widget');
    }


    /**
     * Form function
     *
     * @see    WP_Widget->form
     * @access public
     * @param  array $instance
     * @return void
     */
    function form( $instance ) 
    {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $hide_time = empty( $instance['hide_time'] ) ? 0 : 1;
        if (!isset($instance['number']) || !$number = (int) $instance['number'] ) {
            $number = 2; 
        }
        ?>
     <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'wc_simple_auctions'); ?></label>
     <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

     <p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of auctions to show:', 'wc_simple_auctions'); ?></label>
     <input id="<?php echo esc_attr($this->get_field_id('number')); ?>" name="<?php echo esc_attr($this->get_field_name('number')); ?>" type="text" value="<?php echo esc_attr($number); ?>" size="3" /></p>
		
     <p><input type="checkbox" class="checkbox" id="<?php echo esc_attr($this->get_field_id('hide_time')); ?>" name="<?php echo esc_attr($this->get_field_name('hide_time')); ?>"<?php checked($hide_time); ?> />
     <label for="<?php echo $this->get_field_id('hide_time'); ?>"><?php _e('Hide time left', 'wc_simple_auctions'); ?></label></p>
        <?php
    }
}