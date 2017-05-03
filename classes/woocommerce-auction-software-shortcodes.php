<?php
/**
 * Auction Software Shortcode
 */

class WC_Shortcode_Auction_Software extends WC_Shortcodes
{

        public function __construct() 
        {
            // Regular shortcodes

            add_shortcode('auctions', array( $this, 'auctions' ));
            add_shortcode('recent_auctions', array( $this, 'recent_auctions' ));
            add_shortcode('featured_auctions', array( $this, 'featured_auctions' ));
            add_shortcode('ending_soon_auctions', array( $this, 'ending_soon_auctions' ));
            add_shortcode('future_auctions', array( $this, 'future_auctions' ));
            add_shortcode('past_auctions', array( $this, 'past_auctions' ));
            add_shortcode('auctions_watchlist', array( $this, 'auctions_watchlist' ));
            add_shortcode('my_auctions_activity', array( $this, 'my_auctions_activity' ));
            add_shortcode('all_user_auctions', array( $this, 'all_user_auctions' ));


        }
        /**
     * Output featured products
     *
     * @access public
     * @param  array $atts
     * @return string
     */
        public function featured_auctions( $atts ) 
        {

            global $woocommerce_loop;

            extract(
                shortcode_atts(
                    array(
                    'per_page'     => '12',
                    'columns'     => '4',
                    'orderby' => 'date',
                    'order' => 'desc'
                    ), $atts
                )
            );

            $args = array(
            'post_type'    => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts'    => 1,
            'posts_per_page' => $per_page,
            'orderby' => $orderby,
            'order' => $order,
            'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'auction')),
            'auction_arhive' => true,
            'meta_query' => array(
            array(
            'key' => '_visibility',
            'value' => array('catalog', 'visible'),
            'compare' => 'IN'
            ),
            array(
            'key' => '_featured',
            'value' => 'yes'
            )
            )
            );


            ob_start();

            $products = new WP_Query($args);

            $woocommerce_loop['columns'] = $columns;

            if ($products->have_posts() ) : ?>

                <?php woocommerce_product_loop_start(); ?>

                <?php while ( $products->have_posts() ) : $products->the_post(); ?>

        <?php wc_get_template_part('content', 'product'); ?>

				<?php 
endwhile; // end of the loop. ?>

                <?php woocommerce_product_loop_end(); ?>

            <?php 
            endif;

            wp_reset_postdata();

            return '<div class="woocommerce">' . ob_get_clean() . '</div>';
        }

        /**
     * Recent Auction shortcode
     *
     * @access public
     * @param  array $atts
     * @return string
     */
        public function recent_auctions( $atts ) 
        {

            global $woocommerce_loop, $woocommerce;

            extract(
                shortcode_atts(
                    array(
                    'per_page'     => '12',
                    'columns'     => '4',
                    'orderby' => 'date',
                    'order' => 'desc'
                    ), $atts
                )
            );

            $meta_query = $woocommerce->query->get_meta_query();

            $args = array(
            'post_type'    => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts'    => 1,
            'posts_per_page' => $per_page,
            'orderby' => $orderby,
            'order' => $order,
            'meta_query' => $meta_query,
            'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'auction')),
            'auction_arhive' => true
            );

            ob_start();

            $products = new WP_Query($args);

            $woocommerce_loop['columns'] = $columns;

            if ($products->have_posts() ) : ?>

                <?php woocommerce_product_loop_start(); ?>

                <?php while ( $products->have_posts() ) : $products->the_post(); ?>

        <?php wc_get_template_part('content', 'product'); ?>

				<?php 
endwhile; // end of the loop. ?>

                <?php woocommerce_product_loop_end(); ?>
                <?php else : ?>
            <?php wc_get_template('loop/no-products-found.php'); ?>

    <?php 
endif;

        wp_reset_postdata();

        return '<div class="woocommerce">' . ob_get_clean() . '</div>';
        }

        /**
     * List multiple auctions shortcode
     *
     * @access public
     * @param  array $atts
     * @return string
     */
        public function auctions( $atts ) 
        {
            global $woocommerce_loop;

            if (empty($atts)) { return; 
            }

            extract(
                shortcode_atts(
                    array(
                    'columns'     => '4',
                    'orderby'   => 'title',
                    'order'     => 'asc'
                    ), $atts
                )
            );

            $args = array(
            'post_type'    => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts'    => 1,
            'orderby' => $orderby,
            'order' => $order,
            'posts_per_page' => -1,
            'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'auction')),
            'auction_arhive' => true,
            'meta_query' => array(
            array(
            'key'         => '_visibility',
            'value'     => array('catalog', 'visible'),
            'compare'     => 'IN'
            )
            )
            );

            if(isset($atts['skus'])) {
                $skus = explode(',', $atts['skus']);
                $skus = array_map('trim', $skus);
                $args['meta_query'][] = array(
                'key'         => '_sku',
                'value'     => $skus,
                'compare'     => 'IN'
                );
            }

            if(isset($atts['ids'])) {
                $ids = explode(',', $atts['ids']);
                $ids = array_map('trim', $ids);
                $args['post__in'] = $ids;
            }

            ob_start();

            $products = new WP_Query($args);

            $woocommerce_loop['columns'] = $columns;

            if ($products->have_posts() ) : ?>

                <?php woocommerce_product_loop_start(); ?>

                <?php while ( $products->have_posts() ) : $products->the_post(); ?>

        <?php wc_get_template_part('content', 'product'); ?>

				<?php 
endwhile; // end of the loop. ?>

                <?php woocommerce_product_loop_end(); ?>
                <?php else : ?>
        <?php wc_get_template('loop/no-products-found.php'); ?>

    <?php 
endif;

        wp_reset_postdata();

        return '<div class="woocommerce">' . ob_get_clean() . '</div>';
        }

        /**
     * Recent Auction shortcode
     *
     * @access public
     * @param  array $atts
     * @return string
     */
        public function ending_soon_auctions( $atts ) 
        {

            global $woocommerce_loop, $woocommerce;

            extract(
                shortcode_atts(
                    array(
                    'per_page'     => '12',
                    'columns'     => '4',

                    'order' => 'desc'
                    ), $atts
                )
            );

            $meta_query = $woocommerce->query->get_meta_query();
               $meta_query []= array(
                               'key' => '_auction_closed',
                               'compare' => 'NOT EXISTS'    );
            $args = array(
            'post_type'    => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts'    => 1,
            'posts_per_page' => $per_page,
            'orderby' => 'meta_value',
            'order' => $order,
            'meta_query' => $meta_query,
            'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'auction')),
            'meta_key' => '_auction_dates_to',
            'auction_arhive' => true
            );

            ob_start();

            $products = new WP_Query($args);

            $woocommerce_loop['columns'] = $columns;

            if ($products->have_posts() ) : ?>

                <?php woocommerce_product_loop_start(); ?>

                <?php while ( $products->have_posts() ) : $products->the_post(); ?>

        <?php wc_get_template_part('content', 'product'); ?>

				<?php 
endwhile; // end of the loop. ?>

                <?php woocommerce_product_loop_end(); ?>
                <?php else : ?>
            <?php wc_get_template('loop/no-products-found.php'); ?>

    <?php 
endif;

        wp_reset_postdata();

        return '<div class="woocommerce">' . ob_get_clean() . '</div>';
        }
        /**
     * Recent Auction shortcode
     *
     * @access public
     * @param  array $atts
     * @return string
     */
        public function future_auctions( $atts ) 
        {

            global $woocommerce_loop, $woocommerce;

            extract(
                shortcode_atts(
                    array(
                    'per_page'     => '12',
                    'columns'     => '4',

                    'order' => 'desc'
                    ), $atts
                )
            );

            $meta_query = $woocommerce->query->get_meta_query();
               $meta_query []= array(
                               'key' => '_auction_closed',
                               'compare' => 'NOT EXISTS'    );

               $meta_query []=  array( 'key' => '_auction_started',
                                    'value'=> '0',);
            $args = array(
            'post_type'    => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts'    => 1,
            'posts_per_page' => $per_page,
            'orderby' => 'meta_value',
            'order' => $order,
            'meta_query' => $meta_query,
            'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'auction')),
            'meta_key' => '_auction_dates_to',
            'auction_arhive' => true,
            'show_future_auctions' => true
            );



            ob_start();

            $products = new WP_Query($args);

            $woocommerce_loop['columns'] = $columns;

            if ($products->have_posts() ) : ?>

                <?php woocommerce_product_loop_start(); ?>

                <?php while ( $products->have_posts() ) : $products->the_post(); ?>

        <?php wc_get_template_part('content', 'product'); ?>

				<?php 
endwhile; // end of the loop. ?>

                <?php woocommerce_product_loop_end(); ?>
                <?php else : ?>
            <?php wc_get_template('loop/no-products-found.php'); ?>

    <?php 
endif;

        wp_reset_postdata();

        return '<div class="woocommerce">' . ob_get_clean() . '</div>';
        }

        /**
* Recent Auction shortcode
*
* @access public
* @param  array $atts
* @return string
*/
        public function past_auctions( $atts ) 
        {

            global $woocommerce_loop, $woocommerce;

            extract(
                shortcode_atts(
                    array(
                    'per_page'     => '12',
                    'columns'     => '4',

                    'order' => 'desc'
                    ), $atts
                )
            );

            $meta_query = $woocommerce->query->get_meta_query();

            $meta_query []= array(
                                    'key' => '_auction_closed',
                                    'compare' => 'EXISTS'    );


            $args = array(
            'post_type'    => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts'    => 1,
            'posts_per_page' => $per_page,
            'orderby' => 'meta_value',
            'order' => $order,
            'meta_query' => $meta_query,
            'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'auction')),
            'meta_key' => '_auction_dates_to',
            'auction_arhive' => true,
            'show_past_auctions' => true
            );



            ob_start();

            $products = new WP_Query($args);

            $woocommerce_loop['columns'] = $columns;

            if ($products->have_posts() ) : ?>

                <?php woocommerce_product_loop_start(); ?>

                <?php while ( $products->have_posts() ) : $products->the_post(); ?>

			 <?php wc_get_template_part('content', 'product'); ?>

    <?php 
endwhile; // end of the loop. ?>

                <?php woocommerce_product_loop_end(); ?>
            <?php else : ?>
        <?php wc_get_template('loop/no-products-found.php'); ?>

    <?php 
endif;

 wp_reset_postdata();

 return '<div class="woocommerce">' . ob_get_clean() . '</div>';
        }

        /**
     * Watchlist shortcode
     *
     * @access public
     * @param  array $atts
     * @return string
     */
        public function auctions_watchlist( $atts ) 
        {

            global $woocommerce_loop, $woocommerce, $watchlist;;

            extract(
                shortcode_atts(
                    array(
                    'per_page'     => '-1',
                    'columns'     => '4',

                    'order' => 'desc'
                    ), $atts
                )
            );

            $meta_query = $woocommerce->query->get_meta_query();

            $user_ID = get_current_user_id();
            $watchlist_ids = get_user_meta($user_ID, '_auction_watch');

            $args = array(
            'post_type'    => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts'    => 1,
            'posts_per_page' => $per_page,
            'orderby' => 'meta_value',
            'order' => $order,
            'meta_query' => $meta_query,
            'tax_query' => array(array('taxonomy' => 'product_type' , 'field' => 'slug', 'terms' => 'auction')),
            'meta_key' => '_auction_dates_to',
            'auction_arhive' => true,
            'show_future_auctions' => true,
            'post__in' => $watchlist_ids
            );



            ob_start();

            if (is_user_logged_in() ) {

                $products = new WP_Query($args);

                $woocommerce_loop['columns'] = $columns;

                $watchlist = true;

                if ($products->have_posts() && !empty($watchlist_ids) ) : ?>

                    <?php woocommerce_product_loop_start(); ?>

                    <?php while ( $products->have_posts() ) : $products->the_post(); ?>

        <?php wc_get_template_part('content', 'product'); ?>

        <?php 
endwhile; // end of the loop. ?>

                    <?php woocommerce_product_loop_end(); ?>
                    <?php else : ?>
                <?php wc_get_template('loop/no-products-found.php'); ?>

    <?php 
endif;
            $watchlist = false;
            wp_reset_postdata();
            } else  {
                echo '<p class="woocommerce-info">'.__('Please log in to see your auction watchlist', 'wc_auction_software').'.</p>';
            }

            return '<div class="woocommerce">' . ob_get_clean() . '</div>';
        }

        /**
     * my_auctions_activity shortcode
     *
     * @access public
     * @param  array $atts
     * @return string
     */
        public function my_auctions_activity( $atts ) 
        {
            global $wpdb;

            if (is_user_logged_in() ) {

                extract(
                    shortcode_atts(
                        array(
                        'limit'     => '',

                        ), $atts
                    )
                );

                if(!empty($limit)) {
                    $limit = 'LIMIT '.intval($limit);
                }


                $user_id = get_current_user_id();
                $useractivity = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."simple_auction_log WHERE userid = $user_id  ORDER BY date DESC " . $limit);


                echo '<table class="my_auctions_activity">';
                echo '<tr>';
                echo '<th>'.__('Date', 'wc_auction_software').'</th>';
                echo '<th>'.__('Auction', 'wc_auction_software').'</th>';
                echo '<th>'.__('Bid', 'wc_auction_software').'</th>';
                echo '<th>'.__('Status', 'wc_auction_software').'</th>';
                echo '</tr>';

                foreach ($useractivity as $key => $value) {
                    $class = '';
                    $product = wc_get_product($value->auction_id);

                    if($product && method_exists($product, 'get_type') && $product->get_type() == 'auction') {
                        if($product->is_closed()) {
                            $class .='closed ';
                        }

                        if($product->get_auction_current_bider() == $user_id) {
                            $class .='winning ';
                        }

                        echo '<tr class="'.$class.'">';
                        echo '<td>'.$value->date.'</td>';
                        echo '<td><a href="'.get_permalink($value->auction_id).'">'.get_the_title($value->auction_id).'</a></td>';
                        echo '<td>'.$value->bid.'</td>';
                        echo '<td>'. $product->get_price_html() .'</td>';
                        echo '</tr>';
                    }



                }
                echo '</table>';
            } else  {
                echo '<div class="woocommerce"><p class="woocommerce-info">'.__('Please log in to see your auctions activity.', 'wc_auction_software').'</p></div>';
            }
        }

        /**
     * all_user_auctions shortcode
     *
     * @access public
     * @param  array $atts
     * @return string
     */
        public function all_user_auctions( $atts ) 
        {
            global $wpdb;

            if (is_user_logged_in() ) {

                extract(
                    shortcode_atts(
                        array(
                        'limit'     => '',

                        ), $atts
                    )
                );

                if(!empty($limit)) {
                    $limit = 'LIMIT '.intval($limit);
                }


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
              <h2><?php _e('All user auctions', 'wc_auction_software'); ?></h2>

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
                    'show_past_auctions'     =>  true,
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
                <?php


            }
        }

}
