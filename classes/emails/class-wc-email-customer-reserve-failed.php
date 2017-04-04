<?php

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
/**
 * Customer Outbid Email
 *
 * Customer note emails are sent when you add a note to an order.
 *
 * @class   WC_Email_SA_Outbid
 * @extends WC_Email
 */

class WC_Email_SA_Auction_Reserve_Failed extends WC_Email
{

    

    /**
 * @var string 
*/
    var $title;

    /**
 * @var string 
*/
    var $auction_id;
    
    /**
 * @var string 
*/
    var $reason;

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    function __construct() 
    {
        
        global $woocommerce_auctions;

        $this->id                 = 'Reserve_fail';
        $this->title             = __('Reserve Fail', 'wc_simple_auctions');
        $this->description        = __('Reserve Fail emails are sent to user when the auction is finished but didn\'t make the reserve price', 'wc_simple_auctions');

        $this->template_html     = 'emails/reserve_fail.php';
        $this->template_plain     = 'emails/plain/reserve_fail.php';
        $this->template_base    = $woocommerce_auctions->plugin_path.  'templates/';

        $this->subject             = __('Auction didn\'t succeed {blogname}', 'wc_simple_auctions');
        $this->heading          = __('Auction didn\'t make it to the reserve price!', 'wc_simple_auctions');

        // Triggers
        //die(); 
        add_action('woocommerce_simple_auction_reserve_fail_notification', array( $this, 'trigger' ));

        // Call parent constructor
        parent::__construct();
        
        // Other settings
        
    }

    /**
     * trigger function.
     *
     * @access public
     * @return void
     */
    function trigger( $args ) 
    {
        global $woocommerce;
        
        if ($args ) {
            
            $args = wp_parse_args($args);
            
            extract($args);
            if ($user_id ) {
                $this->object         = new WP_User($user_id);
                $this->recipient    = $this->object->user_email;
            }
            if ($product_id ) {
                $product_data = wc_get_product($product_id);
                $this->auction_id = $product_id;
                $this->current_bid = $product_data->get_curent_bid();
            }
        }    
            
            
        if (! $this->is_enabled() || ! $this->get_recipient() ) {
            return; 
        }
        
        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        
    }

    /**
     * get_content_html function.
     *
     * @access public
     * @return string
     */
    function get_content_html() 
    {
        ob_start();
        wc_get_template(
            $this->template_html, array(
            'email_heading'         => $this->get_heading(),
            'blogname'                => $this->get_blogname(),
            'product_id'            => $this->auction_id,
                
            ) 
        );
        
        return ob_get_clean();
    }

    /**
     * get_content_plain function.
     *
     * @access public
     * @return string
     */
    function get_content_plain() 
    {
        ob_start();
        wc_get_template(
            $this->template_plain, array(
            'email_heading'         => $this->get_heading(),
            'blogname'                => $this->get_blogname(),
            'product_id'            => $this->auction_id,
                
            ) 
        );
        return ob_get_clean();
    }
}