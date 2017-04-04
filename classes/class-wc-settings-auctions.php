<?php
/**
 * WooCommerce Account Settings
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.1.0
 */

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
if (class_exists('WC_Settings_Page') ) :

    /**
 * WC_Settings_Accounts
 */
    class WC_Settings_Simple_Auctions extends WC_Settings_Page
    {

        /**
     * Constructor.
     */
        public function __construct() 
        {
            $this->id    = 'simple_auctions';
            $this->label = __('Auctions', 'wc_simple_auctions');

            add_filter('woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20);
            add_action('woocommerce_settings_' . $this->id, array( $this, 'output' ));
            add_action('woocommerce_settings_save_' . $this->id, array( $this, 'save' ));
        }

        /**
     * Get settings array
     *
     * @return array
     */
        public function get_settings() 
        {

            return apply_filters(
                'woocommerce_' . $this->id . '_settings', array(

                array(    'title' => __('Simple auction options', 'wc_simple_auctions'), 'type' => 'title','desc' => '', 'id' => 'simple_auction_options' ),
                array(
                'title'    => __('Default Auction Sorting', 'wc_simple_auctions'),
                'desc'     => __('This controls the default sort order of the auctions.', 'wc_simple_auctions'),
                'id'       => 'wsa_default_auction_orderby',
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => 'menu_order',
                'type'     => 'select',
                'options'  => apply_filters(
                    'wsa_default_auction_orderby_options', array(
                                                'menu_order' => __('Default sorting (custom ordering + name)', 'woocommerce'),
                                                'date'       => __('Sort by most recent', 'woocommerce'),
                                                'bid_asc' => __('Sort by current bid: Low to high', 'wc_simple_auctions'),
                                                'bid_desc' => __('Sort by current bid: High to low', 'wc_simple_auctions'),
                                                'auction_end' => __('Sort auction by ending soonest', 'wc_simple_auctions'),
                                                'auction_started' => __('Sort auction by recently started', 'wc_simple_auctions'),
                                                'auction_activity' => __('Sort auction by most active', 'wc_simple_auctions'),
                                                
                    ) 
                ),
                'desc_tip' =>  true,
                ),
                                        array(
                'title'             => __('Past auctions', 'wc_simple_auctions'),
                'desc'             => __('Show finished auctions.', 'wc_simple_auctions'),
                'type'                 => 'checkbox',
                'id'                => 'simple_auctions_finished_enabled',
                'default'             => 'no'                                            
                                        ),
                                        array(
                                        'title'             => __('Future auctions', 'wc_simple_auctions'),
                                        'desc'             => __('Show auctions that did not start yet.', 'wc_simple_auctions'),
                                        'type'                 => 'checkbox',
                                        'id'                => 'simple_auctions_future_enabled',
                                        'default'             => 'yes'
                                        ),
                                        array(
                                        'title'             => __("Do not show auctions on shop page", 'wc_simple_auctions'),
                                        'desc'             => __('Do not mix auctions and regular products on shop page. Just show auctions on the auction page (auctions base page)', 'wc_simple_auctions'),
                                        'type'                 => 'checkbox',
                                        'id'                => 'simple_auctions_dont_mix_shop',
                                        'default'             => 'yes'
                                        ),
                                        array(
                                        'title'             => __("Do not show auctions on product search page", 'wc_simple_auctions'),
                                        'desc'             => __('Do not mix auctions and regular products on product search page.', 'wc_simple_auctions'),
                                        'type'                 => 'checkbox',
                                        'id'                => 'simple_auctions_dont_mix_search',
                                        'default'             => 'no'
                                        ),
                                        array(
                                        'title'             => __("Do not show auctions on product category page", 'wc_simple_auctions'),
                                        'desc'             => __('Do not mix auctions and regular products on product category page. Just show auctions on the auction page (auctions base page)', 'wc_simple_auctions'),
                                        'type'                 => 'checkbox',
                                        'id'                => 'simple_auctions_dont_mix_cat',
                                        'default'             => 'yes'
                                        ),
                                        array(
                                        'title'             => __("Do not show auctions on product tag page", 'wc_simple_auctions'),
                                        'desc'             => __('Do not mix auctions and regular products on product tag page. Just show auctions on the auction page (auctions base page)', 'wc_simple_auctions'),
                                        'type'                 => 'checkbox',
                                        'id'                => 'simple_auctions_dont_mix_tag',
                                        'default'             => 'yes'
                                        ),
                                        array(
                                        'title'             => __("Countdown format", 'wc_simple_auctions'),
                                        'desc'                => __("The format for the countdown display. Default is yowdHMS", 'wc_simple_auctions'),
                                        'desc_tip'             => __(
                                            "Use the following characters (in order) to indicate which periods you want to display: 'Y' for years, 'O' for months, 'W' for weeks, 'D' for days, 'H' for hours, 'M' for minutes, 'S' for seconds.

Use upper-case characters for mandatory periods, or the corresponding lower-case characters for optional periods, i.e. only display if non-zero. Once one optional period is shown, all the ones after that are also shown.", 'wc_simple_auctions' 
                                        ),
                                        'type'                 => 'text',
                                        'id'                => 'simple_auctions_countdown_format',
                                        'default'             => 'yowdHMS'
                                        ),
                                        array(
                                        'title' => __('Auctions Base Page', 'wc_simple_auctions'),
                                        'desc'         => __('Set the base page for your auctions - this is where your auction archive will be.', 'wc_simple_auctions'),
                                        'id'         => 'woocommerce_auction_page_id',
                                        'type'         => 'single_select_page',
                                        'default'    => '',
                                        'class'        => 'chosen_select_nostd',
                                        'css'         => 'min-width:300px;',
                                        'desc_tip'    =>  true
                                        ),
                                        array(
                                        'title'             => __("Use ajax bid check", 'wc_simple_auctions'),
                                        'desc'             => __('Enables / disables ajax current bid checker (refresher) for auction - updates current bid value without refreshing page (increases server load, disable for best performance)', 'wc_simple_auctions'),
                                        'type'                 => 'checkbox',
                                        'id'                => 'simple_auctions_live_check',
                                        'default'             => 'yes'
                                        ),    
                                        array(
                                        'title'             => __("Ajax bid check interval", 'wc_simple_auctions'),
                                        'desc'             => __('Time between two ajax requests in seconds (bigger intervals means less load for server)', 'wc_simple_auctions'),
                                        'type'                 => 'text',
                                        'id'                => 'simple_auctions_live_check_interval',
                                        'default'             => '1'
                                        ),
                                        array(
                                        'title'             => __("Allow highest bidder to outbid himself", 'wc_simple_auctions'),
                                        //'desc' 				=> __( '', 'wc_simple_auctions' ),
                                        'type'                 => 'checkbox',
                                        'id'                => 'simple_auctions_curent_bidder_can_bid',
                                        'default'             => 'no'
                                        ),    

                                        array(
                                        'title'             => __("Allow watchlists", 'wc_simple_auctions'),
                                        //'desc' 				=> __( '', 'wc_simple_auctions' ),
                                        'type'                 => 'checkbox',
                                        'id'                => 'simple_auctions_watchlists',
                                        'default'             => 'yes'
                                        ),

                                        array(
                                        'title'             => __("Max bid amount", 'wc_simple_auctions'),
                                        'desc'                => __("Maximum value for single bid. Default value is ", 'wc_simple_auctions').wc_price('99999999999.99'),
                                        'type'                 => 'number',
                                        'id'                => 'simple_auctions_max_bid_amount',
                                        'default'             => ''
                                        ),
                                        array(
                                        'title'             => __("Allow Buy It Now after bidding has started", 'wc_simple_auctions'),
                                        'desc'                 => __('For auction listings with the Buy It Now option, you have the chance to purchase an item immediately, before bidding starts. After someone bids, the Buy It Now option disappears and bidding continues until the listing ends, with the item going to the highest bidder. If is not checked Buy It Now disappears when bid exceeds the Buy Now price for normal auction, or is lower than reverse auction.', 'wc_simple_auctions'),
                                            
                                        'type'                 => 'checkbox',
                                        'id'                => 'simple_auctions_alow_buy_now',
                                        'default'             => 'yes',
                                            
                                        ),

                                        array(
                                        'title'             => __("Set proxy auctions on by default", 'wc_simple_auctions'),
                                        'desc'                 => __('Check box for proxy auction is on by default. You have to uncheckit for normal auctions', 'wc_simple_auctions'),
                                            
                                        'type'                 => 'checkbox',
                                        'id'                => 'simple_auctions_proxy_auction_on',
                                        'default'             => 'no',
                                            
                                        ),


                                        array( 'type' => 'sectionend', 'id' => 'simple_auction_options'),

                )
            ); // End pages settings
        }
    }
    return new WC_Settings_Simple_Auctions();

endif;