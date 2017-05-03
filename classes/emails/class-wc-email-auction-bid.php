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
class WC_Email_SA_Bid extends WC_Email
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
     * Constructor
     *
     * @access public
     * @return void
     */
    function __construct() 
    {
        
        global $woocommerce_auctions;

        $this->id                 = 'bid_note';
        $this->title             = __('Bid note', 'wc_auction_software');
        $this->description        = __('Send bid email notification to admin when user(s) place a bid.', 'wc_auction_software');
        $this->template_html     = 'emails/bid.php';
        $this->template_plain     = 'emails/plain/bid.php';
        $this->template_base    = $woocommerce_auctions->plugin_path. 'templates/';
        $this->subject             = __('Bid item on {site_title}', 'wc_auction_software');
        $this->heading          = __('User placed a bid', 'wc_auction_software');

        // Triggers		 
        add_action('woocommerce_simple_auctions_place_bid_notification', array( $this, 'trigger' ));
        
        // Other settings
        $this->recipient = $this->get_option('recipient');

        if (! $this->recipient ) {
            $this->recipient = get_option('admin_email'); 
        }

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
            if ($product_id ) {
                $product_data = wc_get_product($product_id);
                $this->object = $product_data;
                $this->auction_id = $product_id;
                $this->current_bid = $product_data->get_curent_bid();

                // Find/replace
                $this->find['currentbidder']      = '{current_bidder}';
                $this->replace['currentbidder']   = get_userdata($product_data->get_auction_current_bider())->display_name;


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
            'product_id'            => $this->auction_id
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
    /**
     * Initialise Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() 
    {
        $this->form_fields = array(
        'enabled' => array(
        'title'         => __('Enable/Disable', 'woocommerce'),
        'type'             => 'checkbox',
        'label'         => __('Enable this email notification', 'woocommerce'),
        'default'         => 'yes'
        ),
        'recipient' => array(
        'title'         => __('Recipient(s)', 'woocommerce'),
        'type'             => 'text',
        'description'     => sprintf(__('Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', 'woocommerce'), esc_attr(get_option('admin_email'))),
        'placeholder'     => '',
        'default'         => ''
        ),
        'subject' => array(
        'title'         => __('Subject', 'woocommerce'),
        'type'             => 'text',
        'description'     => sprintf(__('This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce'), $this->subject),
        'placeholder'     => '',
        'default'         => ''
        ),
        'heading' => array(
        'title'         => __('Email Heading', 'woocommerce'),
        'type'             => 'text',
        'description'     => sprintf(__('This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce'), $this->heading),
        'placeholder'     => '',
        'default'         => ''
        ),
        'email_type' => array(
        'title'         => __('Email type', 'woocommerce'),
        'type'             => 'select',
        'description'     => __('Choose which format of email to send.', 'woocommerce'),
        'default'         => 'html',
        'class'            => 'email_type',
        'options'        => array(
        'plain'             => __('Plain text', 'woocommerce'),
        'html'             => __('HTML', 'woocommerce'),
        'multipart'     => __('Multipart', 'woocommerce'),
        )
        )
        );
    }
}