<?php

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
/**
 * Auction Product Class
 *
 * @class WC_Product_Auction
 */
class WC_Product_Auction extends WC_Product
{

    public $post_type = 'product';
    public $product_type = 'auction';

    /**
     * Stores product data.
     * auction_start_price
     * @var array
     */
    protected $extra_data = array(
    'auction_current_bid'          => '',
    'auction_current_bider'        => '',
    'auction_bid_increment'        => '',
    'auction_item_condition'       => '',
    'auction_dates_from'           => '',
    'auction_dates_to'             => '',
    'auction_reserved_price'       => '',
    'auction_type'                 => '',
    'auction_closed'               => '',
    'auction_started'              => '',
    'auction_sealed'               => '',
    'auction_bid_count'            => '',
    'auction_max_bid'              => '',
    'auction_max_current_bider'    => '',
    'auction_fail_reason'          => '',
    'order_id'                     => '',
    'stop_mails'                   => '',
    'auction_proxy'                => '',
    'auction_start_price'          => '',
    'auction_wpml_language'        => '',
    'auction_relist_fail_time'     => '',
    'auction_relist_not_paid_time' => '',
    'auction_automatic_relist'     => '',
    'auction_relist_duration'      => '',
    'auction_payed'                => '',
    'number_of_sent_mails'         => '',
    'auction_relisted'             => '',
        
    );

    /**
     * __construct function.
     *
     * @access public
     * @param  mixed $product
     */
    public function __construct( $product ) 
    {
        global $sitepress;

        date_default_timezone_set("UTC");

        if(is_array($this->data)) {
            $this->data = array_merge($this->data, $this->extra_data); 
        }


        $this->auction_item_condition_array = apply_filters('simple_auction_item_condition', array( 'new' => __('New', 'wc_simple_auctions'), 'used'=> __('Used', 'wc_simple_auctions') ));

        parent::__construct($product);
        $this->is_closed();
        $this->is_started();
        $this->check_bid_count();


    }
    /**
     * Returns the unique ID for this object.
     * @return int
     */
    public function get_id() 
    {
        return $this->id; 
    }

    /**
     * Get internal type.
     *
     * @return string
     */
    public function get_type() 
    {
        return 'auction';
    }

    /**
     * Checks if a product is auction
     *
     * @access public
     * @return bool
     */
    function is_auction() 
    {

        return $this->get_type() == 'auction' ? true : false;
    }

    /**
     * Get current bid
     *
     * @access public
     * @return int
     */
    function get_curent_bid() 
    {
        
        if ($this->get_auction_current_bid()) {
            return apply_filters('woocommerce_simple_auctions_get_current_bid', (float)$this->get_auction_current_bid(), $this);
        }
        return apply_filters('woocommerce_simple_auctions_get_current_bid', (float)$this->get_auction_start_price(), $this);
        
    }

    /**
     * Get bid increment
     *
     * @access public
     * @return mixed
     */
    function get_increase_bid_value() 
    {
        
        if ($this->get_auction_bid_increment()) {
            return apply_filters('woocommerce_simple_auctions_get_increase_bid_value', $this->get_auction_bid_increment(), $this);
        } else {
            return false;
        }
        
    }

    /**
     * Get auction condition
     *
     * @access public
     * @return mixed
     */
    function get_condition() 
    {
        
        if ($this->get_auction_item_condition()) {
            return apply_filters('woocommerce_simple_auctions_get_condition', $this->auction_item_condition_array[$this->get_auction_item_condition()], $this);
        } else {
            return false;
        }
        
    }

    /**
     * Get auction end time
     *
     * @access public
     * @return mixed
     */
    function get_auction_end_time() 
    {
        
        if ($this->get_auction_dates_to()) {
            return apply_filters('woocommerce_simple_auctions_get_auction_end_time', $this->get_auction_dates_to(), $this);
        } else {
            return false;
        }
        
    }

    /**
     * Get auction start time
     *
     * @access public
     * @return mixed
     */
    function get_auction_start_time() 
    {
        
        if ($this->get_auction_dates_from()) {
            return apply_filters('woocommerce_simple_auctions_get_auction_start_time', $this->get_auction_dates_from(), $this);
        } else {
            return false;
        }
        
    }

    /**
     * Get remaining seconds till auction end
     *
     * @access public
     * @return mixed
     */
    function get_seconds_remaining() 
    {
        
        if ($this->get_auction_dates_to()) {

            return apply_filters('woocommerce_simple_auctions_get_seconds_remaining', strtotime($this->get_auction_dates_to())  -  (get_option('gmt_offset')*3600),  $this);
                
        } else {
            return false;
        }
        
    }

    /**
     * Get seconds till auction starts
     *
     * @access public
     * @return mixed
     */
    function get_seconds_to_auction() 
    {
        
        if ($this->get_auction_dates_from()) {
            return apply_filters('woocommerce_simple_auctions_get_seconds_to_auction', strtotime($this->get_auction_dates_from()) - (get_option('gmt_offset')*3600), $this);
        } else {
            return false;
        }
        
    }

    /**
     * Has auction started
     *
     * @access public
     * @return mixed
     */
    function is_started() 
    {
        global $sitepress;

        $id = $this->get_main_wpml_product_id();

        if (!empty($this->get_auction_dates_from()) ) {
            $date1 = new DateTime($this->get_auction_dates_from());
            $date2 = new DateTime(current_time('mysql'));
            if ($date1 < $date2) {
                delete_post_meta($id, '_auction_started');
                do_action('woocommerce_simple_auction_started', $id);

            } else{
                update_post_meta($id, '_auction_started', '0');
            }

            return ($date1 < $date2) ;
        } else {
            update_post_meta($id, '_auction_started', '0');
            return false;
        }
    }

    /**
     * Does auction have reserve price
     *
     * @access public
     * @return bool
     */
    function is_reserved() 
    {
        if (!empty($this->get_auction_reserved_price())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Has auction met reserve price
     *
     * @access public
     * @return mixed
     */
    function is_reserve_met() 
    {


        if (!empty($this->get_auction_reserved_price())) {
            if($this->get_auction_type() == 'reverse' ) {
                return ( (float)$this->get_auction_reserved_price() >= (float)$this->get_auction_current_bid());
            } else {
                return ( (float)$this->get_auction_reserved_price() <= (float)$this->get_auction_current_bid());
            }
        }
        return true;
    }

    /**
     * Has auction finished
     *
     * @access public
     * @return mixed
     */
    function is_finished() 
    {
        if (!empty($this->get_auction_dates_to()) ) {
            $date1 = new DateTime($this->get_auction_dates_to());
            $date2 = new DateTime(current_time('mysql'));

            if($date1 < $date2) {
                do_action('woocommerce_simple_auction_finished', $this->get_id());
                return true;
            } else{
                return false;
            }


        } else {
            return false;
        }
    }

    /**
     * Is auction closed
     *
     * @access public
     * @return bool
     */
    function is_closed() 
    {

        $id = $this->get_main_wpml_product_id();
                
        if (!empty($this->get_auction_closed())) {

            return true;

        } else {

            if ($this->is_finished() && $this->is_started() ) {


                global $woocommerce, $product, $post;

                if (!$this->get_auction_current_bider() && !$this->get_auction_current_bid()) {
                    update_post_meta($id, '_auction_closed', '1');
                    update_post_meta($id, '_auction_fail_reason', '1');
                    $order_id = false;
                    do_action('woocommerce_simple_auction_close',  $id);
                    do_action('woocommerce_simple_auction_fail', array('auction_id' => $id , 'reason' => __('There was no bid', 'wc_simple_auctions') ));
                    return false;
                }
                if ($this->is_reserve_met() == false) {
                    update_post_meta($id, '_auction_closed', '1');
                    update_post_meta($id, '_auction_fail_reason', '2');
                    $order_id = false;
                    do_action('woocommerce_simple_auction_close',  $id);
                    do_action('woocommerce_simple_auction_reserve_fail', array('user_id' => $this->get_auction_current_bider(),'product_id' => $id ));
                    do_action('woocommerce_simple_auction_fail', array('auction_id' => $id , 'reason' => __('The item didn\'t make it to reserve price', 'wc_simple_auctions') ));
                    return false;
                }
                update_post_meta($id, '_auction_closed', '2');
                add_user_meta($this->get_auction_current_bider(), '_auction_win', $id);
                do_action('woocommerce_simple_auction_close', $id);
                do_action('woocommerce_simple_auction_won', $id);

                return true;

            } else {

                return false;

            }
        }
    }

    /**
     * Get auction history
     *
     * @access public
     * @return object
     */
    function auction_history($datefrom = false, $user_id = false) 
    {
        global $wpdb;
        global $sitepress;
        $wheredatefrom ='';

        $id = $this->get_main_wpml_product_id();

        $relisteddate = get_post_meta($id, '_auction_relisted', true);
        if(!is_admin() && !empty($relisteddate)) {
            $datefrom = $relisteddate;
        }

        if($datefrom) {
            $wheredatefrom =" AND CAST(date AS DATETIME) > '$datefrom' ";
        }

        if($user_id) {
            $wheredatefrom =" AND userid = $user_id";
        }

        if($this->get_auction_type() == 'reverse' ) {
            $history = $wpdb->get_results('SELECT * 	FROM '.$wpdb->prefix.'simple_auction_log  WHERE auction_id =' . $id . $wheredatefrom.' ORDER BY  `date` desc , `bid`  asc, `id`  desc   ');
        } else {
            $history = $wpdb->get_results('SELECT * 	FROM '.$wpdb->prefix.'simple_auction_log  WHERE auction_id =' . $id . $wheredatefrom.' ORDER BY  `date` desc , `bid`  desc ,`id`  desc  ');
        }
        return $history;
    }


    /**
     * Get auction history line
     *
     * @access public
     * @return object
     */
    function auction_history_last($id) 
    {
        global $wpdb;
        global $sitepress;

        $history_value = $wpdb->get_row('SELECT * 	FROM '.$wpdb->prefix.'simple_auction_log  WHERE auction_id =' . $id .' ORDER BY  `date` desc ');

        $data = "<tr>";
            $data .= "<td class='date'>$history_value->date</td>";
            $data .= "<td class='bid'>".wc_price($history_value->bid)."</td>";
            $data .= "<td class='username'>".get_userdata($history_value->userid)->display_name."</td>";
        if ($history_value->proxy == 1) {
            $data .= " <td class='proxy'>".__('Auto', 'wc_simple_auctions')."</td>"; 
        }
        else {
            $data .= " <td class='proxy'></td>"; 
        }
         $data .= "</tr>";

        return $data;
    }

    /**
     * Returns price in html format.
     *
     * @access public
     * @param  string $price (default: '')
     * @return string
     */
    public function get_price_html( $price = '' ) 
    {
        $id = $this->get_main_wpml_product_id();

        if ($this->is_closed() && $this->is_started() ) {
            if ($this->get_auction_closed() == '3') {
                $price = __('<span class="sold-for auction">Sold for</span>: ', 'wc_simple_auctions').wc_price($this->get_price());
            }
            else{
                if ($this->get_auction_current_bid()) {
                    if ($this->is_reserve_met() == false) {
                        $price = __('<span class="winned-for auction">Auction item did not make it to reserve price</span> ', 'wc_simple_auctions');
                    } else{
                        $price = __('<span class="winned-for auction">Winning Bid:</span> ', 'wc_simple_auctions').wc_price($this->get_auction_current_bid());
                    }
                }
                else{
                    $price = __('<span class="winned-for auction">Auction Ended</span> ', 'wc_simple_auctions');
                }


            }

        } elseif(!$this->is_started()) {
            $price = '<span class="auction-price" data-auction-id="'.$id.'" data-bid="'.$this->get_auction_current_bid().'" data-status="future">'.__('<span class="starting auction">Starting bid:</span> ', 'wc_simple_auctions').wc_price($this->get_curent_bid()).'</span>';
        } else {
            if($this->get_auction_sealed() == 'yes') {
                $price = '<span class="auction-price" data-auction-id="'.$id.'"  data-status="running">'.__('<span class="current auction">This is sealed bid auction.</span> ', 'wc_simple_auctions').'</span>';
            } else{
                if (!$this->get_auction_current_bid()) {
                    $price = '<span class="auction-price" data-auction-id="'.$id.'" data-bid="'.$this->get_auction_current_bid().'" data-status="running">'.__('<span class="current auction">Starting bid:</span> ', 'wc_simple_auctions').wc_price($this->get_curent_bid()).'</span>';
                } else {
                    $price = '<span class="auction-price" data-auction-id="'.$id.'" data-bid="'.$this->get_auction_current_bid().'" data-status="running">'.__('<span class="current auction">Current bid:</span> ', 'wc_simple_auctions').wc_price($this->get_curent_bid()).'</span>';
                }
            }

        }
        return apply_filters('woocommerce_get_price_html', $price, $this);
    }

    /**
     * Returns product's price.
     *
     * @access public
     * @return string
     */
    function get_price($context = 'view') 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {

            if ($this->is_closed()) {

                if ($this->get_auction_closed() == '3') {
                    return apply_filters('woocommerce_get_price', $this->regular_price, $this);
                }
                if ($this->is_reserve_met()) {

                    return apply_filters('woocommerce_get_price', $this->auction_current_bid, $this);
                }
            }
            return apply_filters('woocommerce_get_price', $this->price, $this);
        } else {
            if ($this->is_closed()) {

                if ($this->get_auction_closed() == '3') {
                    return $this->get_prop('regular_price', $context);
                }
                if ($this->is_reserve_met()) {

                    return $this->get_prop('auction_current_bid', $context);
                    
                }
            }
            return $this->get_prop('price', $context);

        }

        
    }

    /**
     * Get the add to url used mainly in loops.
     *
     * @access public
     * @return string
     */
    public function add_to_cart_url() 
    {
        $id = $this->get_main_wpml_product_id();
        return apply_filters('woocommerce_product_add_to_cart_url', get_permalink($id), $this);
    }

    /**
     * Wrapper for get_permalink
     * @return string
     */
    public function get_permalink() 
    {
        $id = $this->get_main_wpml_product_id();
        return get_permalink($id);
    }

    /**
     * Get the add to cart button text
     *
     * @access public
     * @return string
     */
    public function add_to_cart_text() 
    {
        if (!$this->is_finished() && $this->is_started() ) {
            $text = __('Bid now', 'wc_simple_auctions');
        } elseif($this->is_finished()  ) {
            $text = __('Auction finished', 'wc_simple_auctions');
        } elseif(!$this->is_finished() && !$this->is_started()  ) {
            $text =  __('Auction not started', 'wc_simple_auctions');
        }

        return apply_filters('woocommerce_product_add_to_cart_text', $text, $this);
    }

    /**
     * Get the bid value
     *
     * @access public
     * @return string
     */
    public function bid_value() 
    {
        $auction_bid_increment = ($this->get_auction_bid_increment()) ? $this->get_auction_bid_increment() : 1;

        if((int)$this->get_auction_bid_count() == '0'   ) {
            return $this->get_curent_bid();
        } else  {
            if($this->get_auction_type() == 'reverse' ) {

                return apply_filters('woocommerce_simple_auctions_bid_value', round(wc_format_decimal($this->get_curent_bid()) - wc_format_decimal($auction_bid_increment), wc_get_price_decimals()), $this);
            }else{
                
                return apply_filters('woocommerce_simple_auctions_bid_value', round(wc_format_decimal($this->get_curent_bid()) + wc_format_decimal($auction_bid_increment), wc_get_price_decimals()), $this);
            }
        }

        return false;
    }

    
    /**
     * Get the title of the post.
     *
     * @access public
     * @return string
     */
    public function get_title() 
    {
        $id = $this->get_main_wpml_product_id();

        return apply_filters('woocommerce_product_title', get_the_title($id), $this);
    }

    /**
     * Check if auctions is on user watchlist
     *
     * @access public
     * @return string
     */
    public function is_user_watching( $user_ID = false)
    {

        $id = $this->get_main_wpml_product_id();

        if(!$user_ID) {
            $user_ID = get_current_user_id();
        }

        $users_watching_auction = get_post_meta($id, '_auction_watch', false);


        if(is_array($users_watching_auction)) {
            return in_array($user_ID, $users_watching_auction);
        }

        return false;

        

    }



    /**
     * Get main product id for multilanguage purpose
     *
     * @access public
     * @return int
     */

    function get_main_wpml_product_id()
    {

        global $sitepress;

        if (function_exists('icl_object_id') && function_exists('pll_default_language')) { // Polylang with use of WPML compatibility mode
            $id = icl_object_id($this->id, 'product', false, pll_default_language());
        }
        elseif (function_exists('icl_object_id') && method_exists($sitepress, 'get_default_language')) { // WPML
            $id = icl_object_id($this->id, 'product', false, $sitepress->get_default_language());
        }
        else {
            $id = $this->id;
        }

        return $id;

    }

    /**
     * Get if user is biding on auction
     *
     * @access public
     * @return int
     */
    public function is_user_biding( $auction_id , $user_ID = false)
    {

        global $wpdb;

        $id = $this->get_main_wpml_product_id();

        if(!$user_ID) {
            $user_ID = get_current_user_id();
        }

        $bid_count = $wpdb->get_var('SELECT COUNT(*) 	FROM '.$wpdb->prefix.'simple_auction_log  WHERE auction_id =' . $auction_id .' and userid = '.$user_ID);



        return  apply_filters('woocommerce_simple_auctions_is_user_biding', absint($bid_count), $this);

    }

    /**
     * Get user max bid
     *
     * @access public
     * @return float
     */
    public function get_user_max_bid( $auction_id , $user_ID = false)
    {

        global $wpdb;

        $id = $this->get_main_wpml_product_id();

        if(!$user_ID) {
            $user_ID = get_current_user_id();
        }

        $maxbid = $wpdb->get_var('SELECT bid FROM '.$wpdb->prefix.'simple_auction_log  WHERE auction_id =' . $auction_id .' and userid = '.$user_ID.'  ORDER BY  `bid` desc');



        return apply_filters('woocommerce_simple_auctions_get_user_max_bid', $maxbid, $this);

    }

    /**
     * Get is auction is sealed
     *
     * @access public
     * @return bolean
     */
    function is_sealed()
    {
        if ($this->is_closed()) {
            return false;
        }
        return apply_filters('woocommerce_simple_auctions_is_sealed', $this->get_auction_sealed() == 'yes', $this);
    }

    function check_bid_count()
    {
        $id = $this->get_main_wpml_product_id();
        
        if ($this->get_auction_bid_count() == '') {
            
            update_post_meta($id, '_auction_bid_count', '0');
        } 
            
        return;
        
    }


    /**
     * Get get_auction_current_bid
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_current_bid( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_current_bid;
        } else {
            return $this->get_prop('auction_current_bid', $context);    
        }
        
    }

    /**
     * Get get_auction_current_bider
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_current_bider( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_current_bider;
        } else {
            return $this->get_prop('auction_current_bider', $context);    
        }
        
    }

     /**
     * Get get_auction_bid_increment
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_bid_increment( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_bid_increment;
        } else {
            return $this->get_prop('auction_bid_increment', $context);    
        }
        
    }
  
    /**
     * Get get_auction_item_condition
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_item_condition( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_item_condition;
        } else {
            return $this->get_prop('auction_item_condition', $context);    
        }
        
    }
    /**
     * Get get_auction_dates_from
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_dates_from( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_dates_from;
        } else {
            return $this->get_prop('auction_dates_from', $context);    
        }
        
    }
    /**
     * Get get_auction_dates_to
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_dates_to( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_dates_to;
        } else {
            return $this->get_prop('auction_dates_to', $context);    
        }
        
    }
    /**
     * Get get_auction_reserved_price
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_reserved_price( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_reserved_price;
        } else {
            return $this->get_prop('auction_reserved_price', $context);    
        }
        
    }

    /**
     * Get get_auction_type
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_type( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_type;
        } else {
            return $this->get_prop('auction_type', $context);    
        }
        
    }
    /**
     * Get get_auction_closed
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_closed( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_closed;
        } else {
            return $this->get_prop('auction_closed', $context);    
        }
        
    }

     /**
     * Get get_auction_started
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_started( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_started;
        } else {
            return $this->get_prop('auction_started', $context);    
        }
        
    }

     /**
     * Get get_auction_sealed
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_sealed( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_sealed;
        } else {
            return $this->get_prop('auction_sealed', $context);    
        }
        
    }

    /**
     * Get get_auction_bid_count
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_bid_count( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_bid_count;
        } else {
            return $this->get_prop('auction_bid_count', $context);    
        }
        
    }

    /**
     * Get get_auction_max_bid
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_max_bid( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_max_bid;
        } else {
            return $this->get_prop('auction_max_bid', $context);    
        }
        
    }

    /**
     * Get get_auction_max_current_bider
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_max_current_bider( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_max_current_bider;
        } else {
            return $this->get_prop('auction_max_current_bider', $context);    
        }
        
    }

    /**
     * Get get_auction_fail_reason
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_fail_reason( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_fail_reason;
        } else {
            return $this->get_prop('auction_fail_reason', $context);    
        }
        
    }

    /**
     * Get get_order_id
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_order_id( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->order_id;
        } else {
            return $this->get_prop('order_id', $context);    
        }
        
    }

    /**
     * Get get_stop_mails
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_stop_mails( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->stop_mails;
        } else {
            return $this->get_prop('stop_mails', $context);    
        }
        
    }

    /**
     * Get get_auction_proxy
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_proxy( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_proxy;
        } else {
            return $this->get_prop('auction_proxy', $context);    
        }
        
    }

    /**
     * Get get_auction_start_price
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_start_price( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_start_price;
        } else {
            return $this->get_prop('auction_start_price', $context);    
        }
        
    }

    /**
     * Get get_auction_wpml_language
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_wpml_language( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_wpml_language;
        } else {
            return $this->get_prop('auction_wpml_language', $context);    
        }
        
    }

    /**
     * Get get_auction_relist_fail_time
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_relist_fail_time( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_relist_fail_time;
        } else {
            return $this->get_prop('auction_relist_fail_time', $context);    
        }
        
    }

    /**
     * Get get_auction_relist_not_paid_time
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_relist_not_paid_time( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_relist_not_paid_time;
        } else {
            return $this->get_prop('auction_relist_not_paid_time', $context);    
        }
        
    }

    /**
     * Get get_auction_automatic_relist
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_automatic_relist( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_automatic_relist;
        } else {
            return $this->get_prop('auction_automatic_relist', $context);    
        }
        
    }

    /**
     * Get get_auction_relist_duration
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_relist_duration( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_relist_duration;
        } else {
            return $this->get_prop('auction_relist_duration', $context);    
        }
        
    }
    
     /**
     * Get get_auction_payed
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_payed( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_payed;
        } else {
            return $this->get_prop('auction_payed', $context);    
        }
        
    }

    /**
     * Get get_number_of_sent_mails
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_number_of_sent_mails( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->number_of_sent_mails;
        } else {
            return $this->get_prop('number_of_sent_mails', $context);    
        }
        
    }
    
    /**
     * Get get_auction_relisted
     *
     * @since  1.2.8
     * @param  string $context
     * @return string
     */
    public function get_auction_relisted( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
            return $this->auction_relisted;
        } else {
            return $this->get_prop('auction_relisted', $context);    
        }
        
    }

    /**
     * Returns the product's regular price.
     *
     * @param  string $context
     * @return string price
     */
    public function get_regular_price( $context = 'view' ) 
    {
        if (version_compare(WC_VERSION, '2.7', '<') ) {
             return $this->regular_price;
        } else {
            return $this->get_prop('regular_price', $context);    
        }
    }
    

     /*
    |--------------------------------------------------------------------------
    | Setters
    |--------------------------------------------------------------------------
    |
    | Functions for setting product data. These should not update anything in the
    | database itself and should only change what is stored in the class
    | object.
    */
      
    /**
     * Set auction_current_bid.
     *
     * @since 1.2.8
     * @param string $auction_current_bid
     */
    public function set_auction_current_bid( $auction_current_bid ) 
    {
        $this->set_prop('auction_current_bid', $auction_current_bid);
    }

    /**
     * Set auction_current_bider.
     *
     * @since 1.2.8
     * @param string $auction_current_bider
     */
    public function set_auction_current_bider( $auction_current_bider ) 
    {
        $this->set_prop('auction_current_bider', $auction_current_bider);
    }

     /**
     * Set auction_bid_increment.
     *
     * @since 1.2.8
     * @param string $auction_bid_increment
     */
     public function set_auction_bid_increment( $auction_bid_increment ) 
     {
        $this->set_prop('auction_bid_increment', $auction_bid_increment);
     }

        /**
     * Set auction_item_condition.
     *
     * @since 1.2.8
     * @param string $auction_item_condition
     */
        public function set_auction_item_condition( $auction_item_condition ) 
        {
            $this->set_prop('auction_item_condition', $auction_item_condition);
        }

        /**
     * Set auction_dates_from.
     *
     * @since 1.2.8
     * @param string $auction_dates_from
     */
        public function set_auction_dates_from( $auction_dates_from ) 
        {
            $this->set_prop('auction_dates_from', $auction_dates_from);
        }

        /**
     * Set auction_dates_to.
     *
     * @since 1.2.8
     * @param string $auction_dates_to
     */
        public function set_auction_dates_to( $auction_dates_to ) 
        {
            $this->set_prop('auction_dates_to', $auction_dates_to);
        }

        /**
     * Set auction_reserved_price.
     *
     * @since 1.2.8
     * @param string $auction_reserved_price
     */
        public function set_auction_reserved_price( $auction_reserved_price ) 
        {
            $this->set_prop('auction_reserved_price', $auction_reserved_price);
        }

        /**
     * Set auction_type.
     *
     * @since 1.2.8
     * @param string $auction_type
     */
        public function set_auction_type( $auction_type ) 
        {
            $this->set_prop('auction_type', $auction_type);
        }

        /**
     * Set auction_closed.
     *
     * @since 1.2.8
     * @param string $auction_closed
     */
        public function set_auction_closed( $auction_closed ) 
        {
            $this->set_prop('auction_closed', $auction_closed);
        }

        /**
     * Set auction_started.
     *
     * @since 1.2.8
     * @param string $auction_started
     */
        public function set_auction_started( $auction_started ) 
        {
            $this->set_prop('auction_started', $auction_started);
        }

        /**
     * Set auction_sealed.
     *
     * @since 1.2.8
     * @param string $auction_sealed
     */
        public function set_auction_sealed( $auction_sealed ) 
        {
            $this->set_prop('auction_sealed', $auction_sealed);
        }

        /**
     * Set auction_bid_count.
     *
     * @since 1.2.8
     * @param string $auction_bid_count
     */
        public function set_auction_bid_count( $auction_bid_count ) 
        {
            $this->set_prop('auction_bid_count', $auction_bid_count);
        }

        /**
     * Set auction_max_bid.
     *
     * @since 1.2.8
     * @param string $auction_max_bid
     */
        public function set_auction_max_bid( $auction_max_bid ) 
        {
            $this->set_prop('auction_max_bid', $auction_max_bid);
        }

        /**
     * Set auction_max_current_bider.
     *
     * @since 1.2.8
     * @param string $auction_max_current_bider
     */
        public function set_auction_max_current_bider( $auction_max_current_bider ) 
        {
            $this->set_prop('auction_max_current_bider', $auction_max_current_bider);
        }

        /**
     * Set auction_fail_reason.
     *
     * @since 1.2.8
     * @param string $auction_fail_reason
     */
        public function set_auction_fail_reason( $auction_fail_reason ) 
        {
            $this->set_prop('auction_fail_reason', $auction_fail_reason);
        }

        /**
     * Set order_id.
     *
     * @since 1.2.8
     * @param string $order_id
     */
        public function set_order_id( $order_id ) 
        {
            $this->set_prop('order_id', $order_id);
        }
        /**
     * Set stop_mails.
     *
     * @since 1.2.8
     * @param string $stop_mails
     */
        public function set_stop_mails( $stop_mails ) 
        {
            $this->set_prop('stop_mails', $stop_mails);
        }

        /**
     * Set auction_proxy.
     *
     * @since 1.2.8
     * @param string $auction_proxy
     */
        public function set_auction_proxy( $auction_proxy ) 
        {
            $this->set_prop('auction_proxy', $auction_proxy);
        }

        /**
     * Set auction_start_price.
     *
     * @since 1.2.8
     * @param string $auction_start_price
     */
        public function set_auction_start_price( $auction_start_price ) 
        {
            $this->set_prop('auction_start_price', $auction_start_price);
        }

        /**
     * Set auction_wpml_language.
     *
     * @since 1.2.8
     * @param string $auction_wpml_language
     */
        public function set_auction_wpml_language( $auction_wpml_language ) 
        {
            $this->set_prop('auction_wpml_language', $auction_wpml_language);
        }

        /**
     * Set auction_relist_fail_time.
     *
     * @since 1.2.8
     * @param string $auction_relist_fail_time
     */
        public function set_auction_relist_fail_time( $auction_relist_fail_time ) 
        {
            $this->set_prop('auction_relist_fail_time', $auction_relist_fail_time);
        }
    
        /**
     * Set auction_relist_not_paid_time.
     *
     * @since 1.2.8
     * @param string $auction_relist_not_paid_time
     */
        public function set_auction_relist_not_paid_time( $auction_relist_not_paid_time ) 
        {
            $this->set_prop('auction_relist_not_paid_time', $auction_relist_not_paid_time);
        }

        /**
     * Set auction_automatic_relist.
     *
     * @since 1.2.8
     * @param string $auction_automatic_relist
     */
        public function set_auction_automatic_relist( $auction_automatic_relist ) 
        {
            $this->set_prop('auction_automatic_relist', $auction_automatic_relist);
        }

        /**
     * Set auction_relist_duration.
     *
     * @since 1.2.8
     * @param string $auction_relist_duration
     */
        public function set_auction_relist_duration( $auction_relist_duration ) 
        {
            $this->set_prop('auction_relist_duration', $auction_relist_duration);
        }

        /**
     * Set auction_payed.
     *
     * @since 1.2.8
     * @param string $auction_payed
     */
        public function set_auction_payed( $auction_payed ) 
        {
            $this->set_prop('auction_payed', $auction_payed);
        }

        /**
     * Set number_of_sent_mails.
     *
     * @since 1.2.8
     * @param string $number_of_sent_mails
     */
        public function set_number_of_sent_mails( $number_of_sent_mails ) 
        {
            $this->set_prop('number_of_sent_mails', $number_of_sent_mails);
        }

        /**
     * Set auction_relisted.
     *
     * @since 1.2.8
     * @param string $auction_relisted
     */
        public function set_auction_relisted( $auction_relisted ) 
        {
            $this->set_prop('auction_relisted', $auction_relisted);
        }

        /**
     * Set the product's regular price.
     *
     * @since 2.7.0
     * @param string $price Regular price.
     */
        public function set_regular_price( $price ) 
        {
            $this->set_prop('regular_price', wc_format_decimal($price));
        }



}
