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

class WC_Email_SA_Outbid_Note extends WC_Email
{

    
    /**
 * @var string 
*/
    var $current_bid;

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
    var $outbiddeduser_id;
    

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    function __construct() 
    {
        
        global $woocommerce_auctions,$sitepress;
        
        
        

        $this->id                 = 'outbid_note';
        $this->title             = __('Outbid note', 'wc_simple_auctions');
        $this->description        = __('Outbid emails are sent when your users bid has been outbid.', 'wc_simple_auctions');

        $this->template_html     = 'emails/outbid.php';
        $this->template_plain     = 'emails/plain/outbid.php';
        $this->template_base    = $woocommerce_auctions->plugin_path. 'templates/';

        $this->subject             = __('Outbid item on {blogname}', 'wc_simple_auctions');
        $this->heading          = __('You have been outbid', 'wc_simple_auctions');

        // Triggers
         
        add_action('woocommerce_simple_auctions_outbid_notification', array( $this, 'trigger' ));

        // Call parent constructor
        parent::__construct();
        
        
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
            if ($outbiddeduser_id ) {
                $this->outbiddeduser_id = $outbiddeduser_id;    
                $this->object         = new WP_User($outbiddeduser_id);
                $this->recipient    = $this->object->user_email;
            }
            if ($product_id ) {
                $product_data = wc_get_product($product_id);
                $this->auction_id = $product_id;
                $this->current_bid = $product_data->get_curent_bid();
            }

            if($outbiddeduser_id == $product_data->get_auction_current_bider()) {
                return;
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
        global $woocommerce;
        ob_start();
        wc_get_template(
            $this->template_html, array(
            'email_heading'         => $this->get_heading(),
            'blogname'                => $this->get_blogname(),
            'current_bid'             => $this->current_bid,
            'product_id'            => $this->auction_id,
            'outbiddeduser_id'        => $this->outbiddeduser_id

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
        global $woocommerce;
        
        ob_start();
        wc_get_template(
            $this->template_plain, array(
            'email_heading'         => $this->get_heading(),
            'blogname'                => $this->get_blogname(),
            'current_bid'             => $this->current_bid,
            'product_id'            => $this->auction_id
            ) 
        );
        return ob_get_clean();
    }
}