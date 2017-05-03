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

class WC_Email_SA_Auction_Relist extends WC_Email
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
        
        $this->id                 = 'auction_relist';
        $this->title             = __('Auction relist admin notice', 'wc_auction_software');
        $this->description        = __('Auction relist admin emails are sent to admin when auction is relisted.', 'wc_auction_software');

        $this->template_html     = 'emails/auction_relist_admin.php';
        $this->template_plain     = 'emails/plain/auction_relist_admin.php';
        $this->template_base    = $woocommerce_auctions->plugin_path.  'templates/';

        $this->subject             = __('Auction Relisted on {blogname}', 'wc_auction_software');
        $this->heading          = __('Auction relisted!', 'wc_auction_software');

        // Triggers
        //die(); 
        add_action('woocomerce_before_relist_failed_auction_notification', array( $this, 'trigger' ));
        add_action('woocomerce_before_relist_not_paid_auction_notification', array( $this, 'trigger' ));

        // Call parent constructor
        parent::__construct();
        
        // Other settings
        $this->recipient = $this->get_option('recipient');

        if (! $this->recipient ) {
            $this->recipient = get_option('admin_email'); 
        }
    }

    /**
     * trigger function.
     *
     * @access public
     * @return void
     */
    function trigger( $auction_id ) 
    {
        global $woocommerce;
        $product = wc_get_product($auction_id);
        
        if ($auction_id ) {
                                            
            $this->auction_id = $auction_id;
            $this->object = wc_get_product($auction_id);
                        
        }
        
        if($product->get_auction_closed() == '1') {
            $this->reason =  sprintf(__('Failed auction relisted after %s hours'), $product->get_auction_relist_fail_time()); 
        } else {
            $this->reason =  sprintf(__(' user %s has not paid for auction in %s hours.'), get_userdata($product->get_auction_current_bider())->username,  $product->get_auction_relist_not_paid_time()); 
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
                'reason'                => $this->reason,
                
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
                'reason'                => $this->reason,
                
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