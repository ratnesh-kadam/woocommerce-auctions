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

class WC_Email_SA_Auction_Failed extends WC_Email
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
        
        $this->id                 = 'auction_fail';
        $this->title             = __('Auction Fail', 'wc_auction_software');
        $this->description        = __('Auction Fail emails are sent when auction fails.', 'wc_auction_software');

        $this->template_html     = 'emails/auction_fail.php';
        $this->template_plain     = 'emails/plain/auction_fail.php';
        $this->template_base    = $woocommerce_auctions->plugin_path.  'templates/';

        $this->subject             = __('Auction Failed on {blogname}', 'wc_auction_software');
        $this->heading          = __('No interest in this auction!', 'wc_auction_software');

        // Triggers
        //die(); 
        add_action('woocommerce_simple_auction_fail_notification', array( $this, 'trigger' ));

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
    function trigger( $args ) 
    {
        global $woocommerce;
        
        if ($args ) {
            
            $args = wp_parse_args($args);
            
            extract($args);
            $this->auction_id = $auction_id;
            $this->object = wc_get_product($auction_id);
            $this->reason = $reason;
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
            'reason'                => $this->reason
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