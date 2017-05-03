<?php

if (! defined('ABSPATH') ) { exit; // Exit if accessed directly
}
/**
 * Customer Outbid Email
 *
 * Customer note emails are sent for remind to pay.
 *
 * @class   WC_Email_SA_Auction_Reminde_to_pay
 * @extends WC_Email
 */

class WC_Email_SA_Auction_Reminde_to_pay extends WC_Email
{

    
    /**
 * @var string 
*/
    var $winning_bid;

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
    var $checkout_url;    

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    function __construct() 
    {
        
        global $woocommerce_auctions;

        $this->id                 = 'remind_to_pay';
        $this->title             = __('Reminder to Pay', 'wc_auction_software');
        $this->description        = __('Reminder for the customer that won the auction to pay.', 'wc_auction_software');

        $this->template_html     = 'emails/auction_remind_to_pay.php';
        $this->template_plain     = 'emails/plain/auction_remind_to_pay.php';
        $this->template_base    = $woocommerce_auctions->plugin_path.  'templates/';
        

        $this->subject             = __('Payment reminder won on {blogname}', 'wc_auction_software');
        $this->heading          = __('Reminder for you to pay the auction that you won.', 'wc_auction_software');
        
        $this->interval            = '7';
        $this->stopsending      = '5';
        
        $this->checkout_url     = simple_auction_get_checkout_url();

        // Triggers
        //die(); 
        add_action('woocommerce_simple_auction_pay_reminder_notification', array( $this, 'trigger' ));

        // Call parent constructor
        parent::__construct();
    }

    /**
     * trigger function.
     *
     * @access public
     * @return void
     */
    function trigger( $product_id ) 
    {
        global $woocommerce;
    
        
        if ($product_id ) {
            $product_data = wc_get_product($product_id);
                
            $customer_user = absint(get_post_meta($product_id, '_auction_current_bider', true));
            
            if ($product_data ) {
                if ($customer_user ) {
                    $this->object         = new WP_User($customer_user);
                    $this->recipient    = $this->object->user_email;
                            
                }
                        
                $this->auction_id = $product_id;
                $this->winning_bid = $product_data->get_curent_bid();        
                    
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
            'current_bid'             => $this->winning_bid,
            'product_id'            => $this->auction_id,
            'checkout_url'            => $this->checkout_url 
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
            'current_bid'             => $this->winning_bid,
            'product_id'            => $this->auction_id,
            'checkout_url'            => $this->checkout_url 
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
        'interval' => array(
        'title'         => __('Send mail intervals in days', 'woocommerce'),
        'type'             => 'number',
        'custom_attributes' => array('step'     => '1',        'min'    => '0'),
        'description'     => sprintf(__('Send reminder mail intervals in days default is <code>%s</code>.', 'woocommerce'), $this->interval),
        'placeholder'     => '',
        'default'         => ''
        ),
        'stopsending' => array(
        'title'         => __('Stop sending reminder', 'woocommerce'),
        'type'             => 'number',
        'custom_attributes' => array('step'     => '1',        'min'    => '0'),
        'description'     => sprintf(__('Stop sending reminder mail after number of emails is sent  default is <code>%s</code>.', 'woocommerce'), $this->stopsending),
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