<?php
/**
 * Shortcode [woocommerce_auction_software_my_auctions]
 */

class WC_Shortcode_Auction_Software_My_Auctions
{

    /**
     * Get shortcode content
     *
     * @access public
     * @param  array $atts
     * @return string
     */
    public static function get( $atts ) 
    {
        global $woocommerce;
        return WC_Shortcodes::shortcode_wrapper(array( __CLASS__, 'output' ), $atts);
    }

    /**
     * Output shortcode
     *
     * @access public
     * @param  array $atts
     * @return void
     */
    public static function output( $atts ) 
    {
        global $woocommerce, $wpdb;

        if (is_user_logged_in() ) {

            extract(
                shortcode_atts(
                    array(
                    'show_buy_it_now'     => 'false',
                    ), $atts
                )
            );


                

            $user_id  = get_current_user_id();
            $postids = array();
            $userauction     = $wpdb->get_results("SELECT DISTINCT auction_id FROM ".$wpdb->prefix."simple_auction_log WHERE userid = $user_id ", ARRAY_N);
            if(isset($userauction) && !empty($userauction)) {
                foreach ($userauction as $auction) {
                    $postids []= $auction[0];
                    
                }
            }
            
            ?>
         <div class="simple-auctions active-auctions clearfix">
          <h2><?php _e('Active auctions', 'wc_auction_software'); ?></h2>
				
        <?php
                
                $args = array(
                    'post__in'             => $postids ,
                    'post_type'         => 'product',
                    'posts_per_page'     => '-1',
                    'order'        => 'ASC',
                    'orderby'    => 'meta_value',
                    //'meta_key' 	=> '_auction_dates_to',
                    'tax_query'         => array(
                        array(
                            'taxonomy' => 'product_type',
                            'field' => 'slug',
                            'terms' => 'auction'
                        )
                    ),
                    'meta_query' => array(
                                                  
                            array(
                               'key' => '_auction_closed',
                            
                               'compare' => 'NOT EXISTS'
                            )
                       ),
                    'auction_arhive' => true,      
                    'show_past_auctions'     =>  false,      
                );
                //var_dump($args);
                $activeloop = new WP_Query($args);
                
                //var_dump($activeloop);
                if ($activeloop->have_posts() && !empty($postids) ) {
                    woocommerce_product_loop_start();
                    while ( $activeloop->have_posts() ):$activeloop->the_post();
                        wc_get_template_part('content', 'product');
                    endwhile;
                    woocommerce_product_loop_end(); 
                        
                } else {
                    _e("You are not participating in auction.", "wc_auction_software");
                }
    
                wp_reset_postdata();
                
            ?>			
         </div>
         <div class="simple-auctions active-auctions clearfix">
          <h2><?php _e('Won auctions', 'wc_auction_software'); ?></h2>
				
        <?php
                $auction_closed_type[] = '2';
        if($show_buy_it_now == 'true') {
            $auction_closed_type[] = '3';
        }


                $args = array(
                    'post_type'         => 'product',
                    'posts_per_page'     => '-1',
                    'order'        => 'ASC',
                    'orderby'    => 'meta_value',
                    'meta_key'     => '_auction_dates_to',
                    'meta_query' => array(
                           array(
                               'key' => '_auction_closed',
                               'value' => $auction_closed_type,
                               'compare' => 'IN' 
                           ),
                            array(
                               'key' => '_auction_current_bider',
                               'value' => $user_id,
                            )
                       ),
                    'show_past_auctions'     =>  true,
                    'auction_arhive' => true,     
                );
                
                $winningloop = new WP_Query($args);
    
        if ($winningloop->have_posts() && !empty($postids) ) {
               woocommerce_product_loop_start();
            while ( $winningloop->have_posts()): $winningloop->the_post();
                wc_get_template_part('content', 'product');
            endwhile;
                woocommerce_product_loop_end(); 
        } else {
            _e("You did not win any auctions yet.", "wc_auction_software");
        }
    
          wp_reset_postdata();
          echo "</div>";
                        
        } else  {
            echo '<div class="woocommerce"><p class="woocommerce-info">'.__('Please log in to see your auctions.', 'wc_auction_software').'</p></div>';
        }

    }
            
}
