<?php
/*
 * Plugin Name: WooCommerce Auction Software
 * Plugin URI: https://club.wpeka.com/product/woocommerce-auctionsoftware/
 * Description: Easily extend WooCommerce with auction features and functionalities.
 * Version: 1.0.0
 * Author: WPEka
 * Author URI: https://club.wpeka.com/
 * Requires at least: 3.6
 * Tested up to: 4.7.4
 *
 * Text Domain: wc_simple_auctions
 * Domain Path: /lang/
 *
 * Copyright:
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

if (!defined('ABSPATH')) {
    exit;
}
define ( 'WPEKA_WAPFF_URL', plugin_dir_url(__FILE__));
// Exit if accessed directly
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Required minimum version of WordPress.
if (!function_exists('woo_simple_auction_required')) {
    function woo_simple_auction_required() 
    {
        global $wp_version;
        $plugin = plugin_basename(__FILE__);
        $plugin_data = get_plugin_data(__FILE__, false);

        if (version_compare($wp_version, "3.3", "<")) {
            if (is_plugin_active($plugin)) {
                deactivate_plugins($plugin);
                wp_die("'" . $plugin_data['Name'] . "' requires WordPress 3.3 or higher, and has been deactivated! Please upgrade WordPress and try again.<br /><br />Back to <a href='" . admin_url() . "'>WordPress Admin</a>.");
            }
        }
    }
    add_action('admin_init', 'woo_simple_auction_required');
}

// Checks if the WooCommerce plugins is installed and active.
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) OR is_plugin_active_for_network('woocommerce/woocommerce.php')) {

    if (!class_exists('WooCommerce_simple_auction')) {
        class WooCommerce_simple_auction
        {
            /**
             * @var string
             */
            public $version = '1.2.7';

            public $dbversion = '1.2';

            public static $plugin_file = __FILE__;
            
            public $plugin_prefix;
            public $plugin_url;
            public $plugin_path;
            public $plugin_basefile;
            public $auction_types;
            public $auction_item_condition;
            private $tab_data = false;
            public $bid;
            public $emails;
            /**
             * Gets things started by adding an action to initialize this plugin once
             * WooCommerce is known to be active and initialized
             */
            public function __construct() 
            {
                $this->plugin_prefix = 'wc_simple_auctions';
                $this->plugin_basefile = plugin_basename(__FILE__);
                $this->plugin_url = plugin_dir_url($this->plugin_basefile);
                $this->plugin_path = trailingslashit(dirname(__FILE__));


                $this->auction_types =  array('normal' => __('Normal', 'wc_simple_auctions'), 'reverse' => __('Reverse', 'wc_simple_auctions'));
                $this->auction_item_condition =  array('new' => __('New', 'wc_simple_auctions'), 'used' => __('Used', 'wc_simple_auctions'));

                add_action('woocommerce_init', array(&$this, 'init'));
                //require_once( ABSPATH .'wp-includes/pluggable.php');
            }
            /**
             * Run plugin installation
             * WooCommerce is known to be active and initialized
             */
            public function install() 
            {
                global $wpdb;
                $data_table = $wpdb->prefix . "simple_auction_log";
                $sql = " CREATE TABLE IF NOT EXISTS $data_table (
  						`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						  `userid` bigint(20) unsigned NOT NULL,
						  `auction_id` bigint(20) unsigned DEFAULT NULL,
						  `bid` decimal(32,4) DEFAULT NULL,
						  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						  `proxy` tinyint(1) DEFAULT NULL,
						  PRIMARY KEY (`id`)
						);";

                include_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta($sql);
                wp_insert_term('auction', 'product_type');
                wp_schedule_event(time(), 'twicedaily', 'simple_auction_send_reminders_email');

                if (get_option('simple_auctions_finished_enabled') == false) {
                    add_option('simple_auctions_finished_enabled', 'no');
                }

                if (get_option('simple_auctions_future_enabled') == false) {
                    add_option('simple_auctions_future_enabled', 'yes');
                }

                if (get_option('simple_auctions_dont_mix_shop') == false) {
                    add_option('simple_auctions_dont_mix_shop', 'yes');
                }

                if (get_option('simple_auctions_countdown_format') == false) {
                    add_option('simple_auctions_countdown_format', 'yowdHMS');
                }

                if (get_option('simple_auctions_live_check') == false) {
                    add_option('simple_auctions_live_check', 'yes');
                }

                if (get_option('simple_auctions_live_check_interval') == false) {
                    add_option('simple_auctions_live_check_interval', '1');
                }

                if (get_option('simple_auctions_curent_bidder_can_bid') == false) {
                    add_option('simple_auctions_curent_bidder_can_bid', 'no');
                }

                update_option('simple_auctions_database_version', $this->dbversion);
            }
            /**
             * Run plugin deactivation
             */
            public static function deactivation() 
            {
                wp_clear_scheduled_hook('simple_auction_send_reminders_email');
            }

            /**
             * Run plugin update
             * WooCommerce is known to be active and initialized
             */
            public function update() 
            {
                                    
                        
                if (get_site_option('simple_auctions_database_version') != $this->dbversion) {

                    global $wpdb;
                    $data_table = $wpdb->prefix . "simple_auction_log";
                    $sql = " CREATE TABLE $data_table (
	  						id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
							  userid bigint(20) unsigned NOT NULL,
							  auction_id bigint(20) unsigned DEFAULT NULL,
							  bid decimal(32,4) DEFAULT NULL,
							  date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
							  proxy tinyint(1) DEFAULT NULL,
							  PRIMARY KEY  (id)
							);";

                    include_once ABSPATH . 'wp-admin/includes/upgrade.php';
                    dbDelta($sql);

                    
                    update_option('simple_auctions_database_version', $this->dbversion);


                }

            }

            /**
             * Init WooCommerce Simple Auction plugin once we know WooCommerce is active
             */
            public function init() 
            {

                global $woocommerce;
                global $sitepress;

                $this->includes();

                add_action('init', array($this, 'load_plugin_textdomain'));
                add_action('admin_init', array($this, 'update'));
                add_action('widgets_init', array($this, 'register_widgets'));
                add_action('woocommerce_email', array($this, 'add_to_mail_class'));
                add_filter('plugin_row_meta', array($this, 'add_support_link'), 10, 2);
                //add_action('woocommerce_product_write_panel_tabs', array($this, 'product_write_panel_tab'));
                add_filter('woocommerce_product_data_tabs', array($this, 'product_write_panel_tab'));

                

                if (version_compare(WC_VERSION, '2.7', '<') ) {
                    add_action('woocommerce_product_write_panels', array($this, 'product_write_panel'));
                } else {
                    add_action('woocommerce_product_data_panels', array($this, 'product_write_panel'));    
                }

                add_filter('product_type_selector', array($this, 'add_product_type'));
                add_action('woocommerce_process_product_meta', array($this, 'product_save_data'), 80, 2);
                add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_script')); 
                add_action('woocommerce_email', array($this, 'add_to_mail_class'));
                add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_script'));
                add_action('init', array($this, 'woocommerce_simple_auctions_place_bid'));
                add_action('init', array($this, 'simple_auctions_cron'));
                add_filter('woocommerce_locate_template', array($this, 'woocommerce_locate_template'), 10, 3);
                add_filter('woocommerce_is_purchasable', array($this, 'auction_is_purchasable'), 10, 2);
                add_action('woocommerce_order_status_changed', array($this, 'auction_payed'), 10, 3);
                add_action('woocommerce_checkout_update_order_meta', array($this, 'auction_order'), 10, 2);
                add_action("wp_ajax_finish_auction", array($this, "ajax_finish_auction"));
                add_action("wp_ajax_delete_bid", array($this, "wp_ajax_delete_bid"));
                add_action("wp_ajax_remove_reserve_price", array($this, "wp_ajax_remove_reserve_price"));
                add_action("wc_ajax_finish_auction", array($this, "ajax_finish_auction"));

                add_action("template_redirect", array($this, "wsa_track_auction_view"));

                add_action("woocommerce_duplicate_product", array($this, "woocommerce_duplicate_product"));
                add_action("wp_ajax_get_price_for_auctions", array($this, "get_price_for_auctions"));
                add_action("wp_ajax_nopriv_get_price_for_auctions", array($this, "get_price_for_auctions"));
                add_action("wc_ajax_get_price_for_auctions", array($this, "get_price_for_auctions"));

                if (get_option('simple_auctions_watchlists', 'yes') == 'yes') {
                    add_action("wc_ajax_watchlist", array($this, "ajax_watchlist_auction"));
                }

                add_action("query_vars", array($this, "add_queryvars"));
                add_filter('pre_get_document_title', array($this, 'auction_filter_wp_title'), 10);

                if (is_admin()) {
                    if (version_compare(WC_VERSION, '2.6', '<') ) {
                        add_filter('manage_product_posts_columns', array($this, 'woocommerce_simple_auctions_order_column_auction'));
                        add_action('manage_product_posts_custom_column', array($this, 'woocommerce_simple_auctions_order_column_auction_content'), 10, 2);    
                    }else{
                        add_action('manage_product_posts_custom_column', array($this, 'render_product_columns'));
                    }
                    
                    add_filter('manage_edit-shop_order_columns', array($this, 'woocommerce_simple_auctions_order_column_auction'), 20);
                    add_action('manage_shop_order_posts_custom_column', array($this, 'woocommerce_simple_auctions_order_column_auction_content'), 10, 2);
                    if (version_compare($woocommerce->version, '2.1', ">=")) {
                        add_filter('woocommerce_get_settings_pages', array($this, 'auction_settings_class'));
                    } else {
                        add_filter('woocommerce_settings_tabs_array', array($this, 'add_setting_tab'));
                        add_filter('woocommerce_settings_tabs_simple_auctions', array($this, 'add_settings_tab_content'));
                        add_filter('woocommerce_update_options_simple_auctions', array($this, 'update_settings_tab_content'));
                    }
                    add_filter('woocommerce_get_settings_pages', array($this, 'auction_settings_class'));
                    add_action('add_meta_boxes', array($this, 'woocommerce_simple_auctions_meta'));
                    add_action('add_meta_boxes', array($this, 'woocommerce_simple_auctions_automatic_relist'));
                    add_action('admin_notices', array($this, 'woocommerce_simple_auctions_admin_notice'));
                    add_action('admin_init', array($this, 'woocommerce_simple_auctions_ignore_notices'));

                    add_action('restrict_manage_posts', array($this, 'admin_posts_filter_restrict_manage_posts'));
                    add_filter('parse_query', array($this, 'admin_posts_filter'));

                    add_action('admin_menu', array($this, 'add_auction_activity_page'));
                    
                    //add_action('admin_menu', array($this, 'add_auction_management_page'));
                    //add_action('admin_menu', array($this, 'add_auction_user_management_page'));
                    
                    add_filter('set-screen-option', array($this, 'wc_simple_auctions_set_option'), 10, 3);

                    add_filter('woocommerce_simple_auctions_settings', array($this, 'remove_ordering_setings'), 10);

                                        
                    if (current_user_can('delete_posts')) {
                        add_action('delete_post', array($this, 'del_auction_logs'), 10);
                    }

                }
                // Classes / actions loaded for the frontend and for ajax requests
                if (!is_admin() || defined('DOING_AJAX')) {
                    $this->bid = new WC_bid();

                    add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_script'));
                    add_action('woocommerce_product_tabs', array($this, 'auction_tab'));
                    add_action('woocommerce_product_tab_panels', array($this, 'auction_tab_panel'));
                    add_action('woocommerce_before_single_product', 'woocommerce__simple_auctions_winning_bid_message', 1);
                    add_action('woocommerce_after_shop_loop_item', array($this, 'add_pay_button'), 60);

                    add_action('woocommerce_before_shop_loop_item_title', array($this, 'add_winning_bage'), 60);
                    add_action('woocommerce_before_shop_loop_item_title', array($this, 'add_auction_bage'), 60);

                    add_action('wp_loaded', array($this, 'add_product_to_cart'));
                    add_filter('template_include', array($this, 'auctions_page_template'), 99);
                    
                    add_filter('body_class', array($this, 'output_body_class'));
                    add_action('woocommerce_product_query', array($this, 'remove_auctions_from_woocommerce_product_query'), 2);

                    add_action('woocommerce_product_query', array($this, 'pre_get_posts'), 99, 2);

                    add_filter('pre_get_posts', array($this, 'auction_arhive_pre_get_posts'));
                    add_action('pre_get_posts', array($this, 'query_auction_archive'), 1);

                    add_shortcode('woocommerce_simple_auctions_my_auctions', array($this, 'shortcode_my_auctions'));

                    if (get_option('simple_auctions_watchlists', 'yes') == 'yes') {
                        add_action('woocommerce_after_bid_form', array($this, 'add_watchlist_link'), 10);
                        add_action('woocommerce_after_shop_loop_item', array($this, 'add_to_watchlist'), 90);
                    }

                    add_filter('woocommerce_page_title', array($this, 'auction_page_title'));

                    add_filter('woocommerce_get_breadcrumb', array($this, 'woocommerce_get_breadcrumb'), 1, 2);

                    add_filter('woocommerce_catalog_orderby', array($this, 'auction_woocommerce_catalog_orderby'));

                    add_filter('wp_nav_menu_objects', array($this, 'wsa_nav_menu_item_classes'), 10);

                    add_action('woocommerce_login_form_end', array($this,'add_redirect_previous_page'));

    

                }

                if (function_exists('icl_object_id') && method_exists($sitepress, 'get_default_language')) {
                    add_action('woocommerce_simple_auctions_place_bid', array($this, 'sync_metadata_wpml'), 1);
                    add_action('woocommerce_simple_auction_close', array($this, 'sync_metadata_wpml'), 1);
                    add_action('woocommerce_process_product_meta', array($this, 'sync_metadata_wpml'), 85);
                    add_action('woocommerce_simple_auctions_outbid', array($this, 'add_language_wpml_meta'), 999);
                    add_action('woocommerce_simple_auctions_outbid', array($this, 'change_email_language'), 1);
                    add_action('woocommerce_simple_auction_close_buynow', array($this, 'change_email_language'), 1);
                    add_action('woocommerce_simple_auction_pay_reminder', array($this, 'change_email_language'), 1);
                    add_action('woocommerce_simple_auction_won', array($this, 'change_email_language'), 1);
                    add_filter('woocommerce_get_auction_page_id', array($this, 'auctionbase_page_wpml'), 10, 1);
                    add_filter('icl_ls_languages', array($this, 'translate_ls_auction_url'), 80, 1);
                    

                }

                $email_actions = array('woocommerce_simple_auctions_outbid', 'woocommerce_simple_auction_won', 'woocommerce_simple_auction_fail', 'woocommerce_simple_auction_reserve_fail', 'woocommerce_simple_auction_pay_reminder', 'woocommerce_simple_auction_close_buynow', 'woocommerce_simple_auction_close', 'woocommerce_simple_auctions_place_bid', 'woocomerce_before_relist_failed_auction', 'woocomerce_before_relist_not_paid_auction');
                foreach ($email_actions as $action) {
                    if (version_compare($woocommerce->version, '2.3', ">=")) {
                        add_action($action, array('WC_Emails', 'send_transactional_email'));
                    } else {
                        add_action($action, array($woocommerce, 'send_transactional_email'));
                    }

                }
                // wc-vendors email integration
                add_filter('woocommerce_email_recipient_auction_fail', array($this, 'add_vendor_to_email_recipients'), 10, 2);
                add_filter('woocommerce_email_recipient_auction_finished', array($this, 'add_vendor_to_email_recipients'), 10, 2);
                add_filter('woocommerce_email_recipient_auction_relist', array($this, 'add_vendor_to_email_recipients'), 10, 2);
                add_filter('woocommerce_email_recipient_bid_note', array($this, 'add_vendor_to_email_recipients'), 10, 2);

            }

            /**
             * Load Localisation files.
             */
            public function load_plugin_textdomain() 
            {
                /* Localisation */
                $locale = apply_filters('plugin_locale', get_locale(), 'wc_simple_auctions');
                load_textdomain('wc_simple_auctions', WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . '/lang/wc_simple_auctions-' . $locale . '.mo');
                load_plugin_textdomain('wc_simple_auctions', false, dirname(plugin_basename(__FILE__)) . '/lang/');
            }
            /**
             * Include WooCommerce Simple Auction files
             *
             * @access public
             * @return void
             */
            public function includes() 
            {

                include_once 'classes/class-wc-product-auction.php';
                include_once 'classes/dashboard.php';
                include_once 'classes/wc-auction-software-activity-list.php';
                include_once 'woocommerce-auction-software-functions.php';
                include_once 'classes/class-wca-query.php';
                
                $this->dashboard = new WooCommerce_simple_auction_Dashboard();
                if (defined('DOING_AJAX')) {
                    $this->ajax_includes();
                }

                if (!is_admin() || defined('DOING_AJAX')) {
                    $this->frontend_includes();
                }
            }
            /**
             * Include required ajax files
             *
             * @access public
             * @return void
             */
            public function ajax_includes() 
            {
                include_once 'woocommerce-auction-software-ajax.php'; // Ajax functions for admin and the front-end
            }
            
            public static function is_woocommerce_pre( $version ) {

		if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, $version, '<' ) ) {
			$woocommerce_is_pre_version = true;
		} else {
			$woocommerce_is_pre_version = false;
		}

		return $woocommerce_is_pre_version;
            }
            
            /**
             * Add to mail class
             *
             * @access public
             * @return object
             */
            public function add_to_mail_class($emails) 
            {

                include_once 'classes/emails/class-wc-email-auction-wining.php';
                include_once 'classes/emails/class-wc-email-auction-failed.php';
                include_once 'classes/emails/class-wc-email-outbid-note.php';
                include_once 'classes/emails/class-wc-email-customer-reserve-failed.php';
                include_once 'classes/emails/class-wc-email-auction-reminde-to-pay.php';
                include_once 'classes/emails/class-wc-email-auction-buy-now.php';
                include_once 'classes/emails/class-wc-email-auction-finished.php';
                include_once 'classes/emails/class-wc-email-auction-bid.php';
                include_once 'classes/emails/class-wc-email-auction-relist-user.php';
                include_once 'classes/emails/class-wc-email-auction-relist.php';
                include_once 'classes/emails/class-wc-email-customer-bid-note.php';

                $emails->emails['WC_Email_SA_Outbid_Note'] = new WC_Email_SA_Outbid_Note();
                $emails->emails['WC_Email_SA_Auction_Win'] = new WC_Email_SA_Auction_Win();
                $emails->emails['WC_Email_SA_Reminde_to_pay'] = new WC_Email_SA_Auction_Reminde_to_pay();
                $emails->emails['WC_Email_SA_Auction_Failed'] = new WC_Email_SA_Auction_Failed();
                $emails->emails['WC_Email_SA_Reserve_Failed'] = new WC_Email_SA_Auction_Reserve_Failed();
                $emails->emails['WC_Email_SA_Auction_Buy_Now'] = new WC_Email_SA_Auction_Buy_Now();
                $emails->emails['WC_Email_SA_Auction_Finished'] = new WC_Email_SA_Auction_Finished();
                $emails->emails['WC_Email_SA_Bid'] = new WC_Email_SA_Bid();
                $emails->emails['WC_Email_SA_Auction_Relist'] = new WC_Email_SA_Auction_Relist();
                $emails->emails['WC_Email_SA_Auction_Relist_User'] = new WC_Email_SA_Auction_Relist_User();
                $emails->emails['WC_Email_SA_Customerbid_Note'] = new WC_Email_SA_Customerbid_Note();

                return $emails;
            }

            /**
             * Search for [vendor] tag in recipients and replace it with author email
             */
            public function add_vendor_to_email_recipients($recipient, $object) 
            {
                $key = false;
                $author_info = false;
                $arrayrec = explode(',', $recipient);
                
                $post_id = method_exists($object, 'get_id') ? $object->get_id() : $object->id;
                $post_author = get_post_field('post_author', $post_id);
                if (!empty($post_author)) {
                    $author_info = get_userdata($post_author);
                    $key = array_search($author_info->user_email, $arrayrec);
                }

                if (!$key && $author_info) {
                    $recipient = str_replace('[vendor]', $author_info->user_email, $recipient);

                } else {
                    $recipient = str_replace('[vendor]', '', $recipient);
                }

                return $recipient;
            }

            /**
             * register_widgets function
             *
             * @access public
             * @return void
             */
            function register_widgets() 
            {
                // Include - no need to use autoload as WP loads them anyway
                include_once 'classes/widgets/class-woocommerce-simple-auctions-widget-featured-auctions.php';
                include_once 'classes/widgets/class-woocommerce-simple-auctions-widget-random-auctions.php';
                include_once 'classes/widgets/class-woocommerce-simple-auctions-widget-recent-auction.php';
                include_once 'classes/widgets/class-woocommerce-simple-auctions-widget-recently-auctions.php';
                include_once 'classes/widgets/class-woocommerce-simple-auctions-widget-ending-soon-auction.php';
                include_once 'classes/widgets/class-woocommerce-simple-auctions-widget-my-auctions.php';
                include_once 'classes/widgets/class-wc-widget-auction-search.php';
                include_once 'classes/widgets/class-woocommerce-simple-auctions-widget-future-auctions.php';
                include_once 'classes/widgets/class-woocommerce-simple-auctions-widget-watchlist.php';

                // Register widgets
                register_widget('WC_SA_Widget_Recent_Auction');
                register_widget('WC_SA_Widget_Featured_Auction');
                register_widget('WC_SA_Widget_Random_Auction');
                register_widget('WC_SA_Widget_Recently_Viewed_Auction');
                register_widget('WC_SA_Widget_Ending_Soon_Auction');
                register_widget('WC_SA_Widget_My_Auction');
                register_widget('WC_Widget_Auction_Search');
                register_widget('WC_SA_Widget_Future_Auctions');
                register_widget('WC_SA_Widget_Watchlist_Auction');

            }

            /**
             * Include required frontend files
             *
             * @access public
             * @return void
             */
            public function frontend_includes() 
            {
                // Functions
                include_once 'woocommerce-auction-software-templating.php';
                include_once 'woocommerce-auction-software-hooks.php';
                // Classes
                include_once 'classes/class-wc-bid.php';
                include_once 'classes/woocommerce-auction-software-shortcode-my-auctions.php';
                include_once 'classes/woocommerce-auction-software-shortcodes.php';
                $this->shortcodes = new WC_Shortcode_Auction_Software();
            }

            /**
             * Add link to plugin page
             *
             * @access public
             * @param  array, string
             * @return array
             */
            public function add_support_link($links, $file) 
            {
                if (!current_user_can('install_plugins')) {
                    return $links;
                }
                if ($file == $this->plugin_basefile) {
                    $links[] = '<a href="http://wpgenie.org/woocommerce-simple-auctions/documentation/" target="_blank">' . __('Docs', 'wc_simple_auctions') . '</a>';
                    $links[] = '<a href="http://codecanyon.net/user/wpgenie#contact" target="_blank">' . __('Support', 'wc_simple_auctions') . '</a>';
                    $links[] = '<a href="http://codecanyon.net/user/wpgenie/" target="_blank">' . __('More WooCommerce Extensions', 'wc_simple_auctions') . '</a>';
                }
                return $links;
            }

            /**
             * Add admin notice
             *
             * @access public
             * @param  array, string
             * @return array
             */
            public function woocommerce_simple_auctions_admin_notice() 
            {
                global $current_user;
                if (current_user_can('manage_options')) {
                    $user_id = $current_user->ID;
                    if (get_option('Woocommerce_simple_auction_cron_check') != "yes" && !get_user_meta($user_id, 'cron_check_ignore_notice')) {
                        echo '<div class="updated">
					   	<p>' . sprintf(__('Woocommerce Simple Auction recommends that you set up a cron job to check finished: <b>%s/?auction-cron=check</b>. Set it to every minute| <a href="%s">Hide Notice</a>', 'wc_simple_auctions'), get_bloginfo('url'), esc_attr(add_query_arg('cron_check_ignore', '0'))) . '</p>
						</div>';
                    }
                    if (get_option('Woocommerce_simple_auction_cron_mail') != "yes" && !get_user_meta($user_id, 'cron_mail_ignore_notice')) {
                        echo '<div class="updated">
					   	<p>' . sprintf(__('Woocommerce Simple Auction recommends that you set up a cron job to send emails: <b>%s/?auction-cron=mails</b>. Set it every 2 hours | <a href="%s">Hide Notice</a>', 'wc_simple_auctions'), get_bloginfo('url'), esc_attr(add_query_arg('cron_mail_ignore', '0'))) . '</p>
						</div>';
                    }

                    if (get_option('Woocommerce_simple_auction_cron_relist') != "yes" && !get_user_meta($user_id, 'cron_relist_ignore_notice')) {
                        echo '<div class="updated">
					   	<p>' . sprintf(__('For automated relisting feature please setup cronjob every 1 hour: <b>%s/?auction-cron=relist</b>. | <a href="%s">Hide Notice</a>', 'wc_simple_auctions'), get_bloginfo('url'), esc_attr(add_query_arg('cron_relist_ignore', '0'))) . '</p>
						</div>';
                    }
                }
            }

            /**
             * Add user meta to ignor notice about crons.
             * @access public
             */
            public function woocommerce_simple_auctions_ignore_notices() 
            {
                global $current_user;
                $user_id = $current_user->ID;

                /* If user clicks to ignore the notice, add that to their user meta */
                if (isset($_GET['cron_check_ignore']) && '0' == $_GET['cron_check_ignore']) {
                    add_user_meta($user_id, 'cron_check_ignore_notice', 'true', true);
                }
                if (isset($_GET['cron_mail_ignore']) && '0' == $_GET['cron_mail_ignore']) {
                    add_user_meta($user_id, 'cron_mail_ignore_notice', 'true', true);
                }
                if (isset($_GET['cron_relist_ignore']) && '0' == $_GET['cron_relist_ignore']) {
                    add_user_meta($user_id, 'cron_relist_ignore_notice', 'true', true);
                }
            }

            /**
             * Add product type
             * @param array
             * @return array
             */
            public function add_product_type($types) 
            {
                $types['auction'] = __('Auction', 'wc_simple_auctions');
                return $types;
            }

            /**
             * Add admin script
             * @access public
             * @return void
             */
            public function admin_enqueue_script($hook) 
            {
                global $post_type;
                if ($hook == 'post-new.php' || $hook == 'post.php') {
                    if ('product' == get_post_type()) {
                        wp_register_script(
                            'simple-auction-admin',
                            $this->plugin_url . '/js/auction-software-admin.js',
                            array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'timepicker-addon'),
                            '1',
                            true
                        );
                        wp_localize_script('simple-auction-admin', 'SA_Ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'SA_nonce' => wp_create_nonce('SAajax-nonce')));

                        wp_enqueue_script('simple-auction-admin');
                        wp_enqueue_script(
                            'timepicker-addon',
                            $this->plugin_url . '/js/jquery-ui-timepicker-addon.js',
                            array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'),
                            $this->version,
                            true
                        );
                        wp_enqueue_style('jquery-ui-datepicker');
                    }
                }
                wp_enqueue_style('simple-auction-admin', $this->plugin_url . '/css/admin.css');
            }

            /**
             * Add frontend scripts
             * @access public
             * @return void
             */
            public function frontend_enqueue_script() 
            {
                global $woocommerce;

                wp_enqueue_script('simple-auction-countdown', $this->plugin_url . 'js/jquery.countdown.min.js', array('jquery'), $this->version, false);

                wp_register_script('simple-auction-countdown-language', $this->plugin_url . 'js/jquery.countdown.language.js', array('jquery', 'simple-auction-countdown'), $this->version, false);

                $language_data = array('labels' => array(
                'Years' => __('Years', 'wc_simple_auctions'),
                'Months' => __('Months', 'wc_simple_auctions'),
                'Weeks' => __('Weeks', 'wc_simple_auctions'),
                'Days' => __('Days', 'wc_simple_auctions'),
                'Hours' => __('Hours', 'wc_simple_auctions'),
                'Minutes' => __('Minutes', 'wc_simple_auctions'),
                'Seconds' => __('Seconds', 'wc_simple_auctions'),
                ),
                'labels1' => array(
                'Year' => __('Year', 'wc_simple_auctions'),
                'Month' => __('Month', 'wc_simple_auctions'),
                'Week' => __('Week', 'wc_simple_auctions'),
                'Day' => __('Day', 'wc_simple_auctions'),
                'Hour' => __('Hour', 'wc_simple_auctions'),
                'Minute' => __('Minute', 'wc_simple_auctions'),
                'Second' => __('Second', 'wc_simple_auctions'),
                ),
                'compactLabels' => array(
                'y' => __('y', 'wc_simple_auctions'),
                'm' => __('m', 'wc_simple_auctions'),
                'w' => __('w', 'wc_simple_auctions'),
                'd' => __('d', 'wc_simple_auctions'),
                ),
                );

                wp_localize_script('simple-auction-countdown-language', 'data', $language_data);
                wp_enqueue_script('simple-auction-countdown-language');
                wp_register_script('simple-auction-frontend', $this->plugin_url . 'js/auction-software-frontend.js', array('jquery', 'simple-auction-countdown'), $this->version, false);

                $custom_data = array('finished' => __('Auction has finished!', 'wc_simple_auctions'), 'gtm_offset' => get_option('gmt_offset'), 'started' => __('Auction has started! Please refresh your page.', 'wc_simple_auctions'));
                $simple_auctions_live_check = get_option('simple_auctions_live_check');
                $simple_auctions_live_check_interval = get_option('simple_auctions_live_check_interval');

                if ($simple_auctions_live_check == 'yes') {
                    $custom_data['interval'] = isset($simple_auctions_live_check_interval) && is_numeric($simple_auctions_live_check_interval) ? $simple_auctions_live_check_interval : '1';
                }

                wp_localize_script('simple-auction-frontend', 'data', $custom_data);
                if (version_compare($woocommerce->version, '2.4', ">=")) {
                    wp_localize_script('simple-auction-frontend', 'SA_Ajax', array('ajaxurl' => esc_attr(add_query_arg('wc-ajax', '')), 'najax' => true));
                } else {
                    wp_localize_script('simple-auction-frontend', 'SA_Ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
                }
                wp_enqueue_script('simple-auction-frontend');
                wp_enqueue_style('simple-auction', $this->plugin_url . 'css/frontend.css', array('dashicons'));
            }
            /**
             * Write the auction tab on the product view page for WooCommerce v2.0+
             * In WooCommerce these are handled by templates.
             *
             * @access public
             * @param  array
             * @return array
             */
            public function auction_tab($tabs) 
            {
                global $product;
                if(method_exists($product, 'get_type') && $product->get_type() == 'auction') {
                    $tabs['simle_auction_history'] = array(
                    'title' => __('Auction history', 'wc_simple_auctions'),
                    'priority' => 25,
                    'callback' => array($this, 'auction_tab_callback'),
                    'content' => 'auction-history',
                    );
                }
                return $tabs;
            }
            /**
             * Auction call back from auction_tab
             *
             * @access public
             * @param  array
             * @return void
             */
            public function auction_tab_callback($tabs) 
            {
                wc_get_template('single-product/tabs/auction-history.php');
            }
            /**
             * Adds a new tab to the Product Data postbox in the admin product interface
             *
             * @return void
             */
            public function product_write_panel_tab($product_data_tabs) 
            {


                $auction_tab = array(
                'auction_tab' => array(
                  'label'  => __('Auction', 'wc_simple_auctions'),
                  'target' => 'auction_tab',
                  'class'  => array( 'auction_tab', 'show_if_auction' , 'show_if_auction', 'hide_if_grouped', 'hide_if_external','hide_if_variable','hide_if_simple' ),
                 ),
                );

                return $auction_tab + $product_data_tabs;

                
            }
            /**
             * Adds the panel to the Product Data postbox in the product interface
             *
             * @return void
             */
            public function product_write_panel() 
            {
                global $post;
                $product = wc_get_product($post->ID);

                echo '<div id="auction_tab" class="panel woocommerce_options_panel">';

                woocommerce_wp_select(array('id' => '_auction_item_condition', 'label' => __('Item condition', 'wc_simple_auctions'), 'options' => apply_filters('simple_auction_item_condition', $this->auction_item_condition)));
                woocommerce_wp_select(array('id' => '_auction_type', 'label' => __('Auction type', 'wc_simple_auctions'), 'options' => apply_filters('simple_auction_type', $this->auction_types)));
                
                $proxy =  in_array(get_post_meta($post->ID, '_auction_proxy', true), array( '0', 'yes')) ? get_post_meta($post->ID, '_auction_proxy', true) : get_option('simple_auctions_proxy_auction_on', 'no');
                
                woocommerce_wp_checkbox(array('value' => $proxy,'id' => '_auction_proxy', 'wrapper_class' => '', 'label' => __('Proxy bidding?', 'wc_simple_auctions'), 'description' => __('Enable proxy bidding', 'wc_simple_auctions'), 'desc_tip' => 'true'));
                woocommerce_wp_checkbox(array('id' => '_auction_sealed', 'wrapper_class' => '', 'label' => __('Sealed Bid?', 'wc_simple_auctions'), 'description' => __('In this type of auction all bidders simultaneously submit sealed bids so that no bidder knows the bid of any other participant. The highest bidder pays the price they submitted. If two bids with same value are placed for auction the one which was placed first wins the auction.', 'wc_simple_auctions'), 'desc_tip' => 'true'));
                woocommerce_wp_text_input(
                    array(
                    'id' => '_auction_start_price',
                    'class' => 'wc_input_price short',
                    'label' => __('Start Price', 'wc_simple_auctions') . ' (' . get_woocommerce_currency_symbol() . ')',
                    'data_type' => 'price',
                    'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0',
                    ))
                );
                woocommerce_wp_text_input(
                    array(
                    'id' => '_auction_bid_increment',
                    'class' => 'wc_input_price short', 'label' => __('Bid increment', 'wc_simple_auctions') . ' (' . get_woocommerce_currency_symbol() . ')',
                    'data_type' => 'price',
                    'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0',
                    ))
                );
                woocommerce_wp_text_input(
                    array(
                    'id' => '_auction_reserved_price',
                    'class' => 'wc_input_price short',
                    'label' => __('Reserve price', 'wc_simple_auctions') . ' (' . get_woocommerce_currency_symbol() . ')',
                    'data_type' => 'price',
                    'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0',
                    ), 'desc_tip' => 'true',
                    'description' => __('A reserve price is the lowest price at which you are willing to sell your item. If you don’t want to sell your item below a certain price, you can set a reserve price. The amount of your reserve price is not disclosed to your bidders, but they will see that your auction has a reserve price and whether or not the reserve has been met. If a bidder does not meet that price, you are not obligated to sell your item. ', 'wc_simple_auctions'))
                );
                woocommerce_wp_text_input(
                    array(
                    'id' => '_regular_price',
                    'name' => '_regular_price',
                    'class' => 'wc_input_price short',
                    'label' => __('Buy it now price', 'wc_simple_auctions') . ' (' . get_woocommerce_currency_symbol() . ')',
                    'data_type' => 'price',
                    'desc_tip' => 'true',
                    'description' => __('Buy it now disappears when bid exceeds the Buy now price for normal auction, or is lower than reverse auction', 'wc_simple_auctions'),
                    )
                );

                $auction_dates_from = ($date = get_post_meta($post->ID, '_auction_dates_from', true)) ? $date : '';
                $auction_dates_to = ($date = get_post_meta($post->ID, '_auction_dates_to', true)) ? $date : '';

                echo '	<p class="form-field auction_dates_fields">
							<label for="_auction_dates_from">' . __('Auction Dates', 'wc_simple_auctions') . '</label>
							<input type="text" class="short datetimepicker" name="_auction_dates_from" id="_auction_dates_from" value="' . $auction_dates_from . '" placeholder="' . _x('From&hellip; YYYY-MM-DD HH:MM', 'placeholder', 'wc_simple_auctions') . '" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
							<input type="text" class="short datetimepicker" name="_auction_dates_to" id="_auction_dates_to" value="' . $auction_dates_to . '" placeholder="' . _x('To&hellip; YYYY-MM-DD HH:MM', 'placeholder', 'wc_simple_auctions') . '" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
						</p>';
                if ((method_exists($product, 'get_type') && $product->get_type() == 'auction') && $product->get_auction_closed() && !$product->get_auction_payed()) {
                    echo '<p class="form-field relist_dates_fields"><a class="button relist" href="#" id="relistauction">' . __('Relist', 'wc_simple_auctions') . '</a></p>
                           <p class="form-field relist_auction_dates_fields"> <label for="_relist_auction_dates_from">' . __('Relist Auction Dates', 'wc_simple_auctions') . '</label>
							<input type="text" class="short datetimepicker" name="_relist_auction_dates_from" id="_relist_auction_dates_from" value="" placeholder="' . _x('From&hellip; YYYY-MM-DD HH:MM', 'placeholder', 'wc_simple_auctions') . '" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
							<input type="text" class="short datetimepicker" name="_relist_auction_dates_to" id="_relist_auction_dates_to" value="" placeholder="' . _x('To&hellip; YYYY-MM-DD HH:MM', 'placeholder', 'wc_simple_auctions') . '" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
                        </p>';
                }
                do_action('woocommerce_product_options_auction');

                echo "</div>";
            }
            /**
             * Saves the data inputed into the product boxes, as post meta data
       *
             * @param int      $post_id the post (product) identifier
             * @param stdClass $post    the post (product)
             */
            public function product_save_data($post_id, $post) 
            {
                global $wpdb, $woocommerce, $woocommerce_errors;
                $product_type = empty($_POST['product-type']) ? 'simple' : sanitize_title(stripslashes($_POST['product-type']));

                if ($product_type == 'auction'
                ) {

                    update_post_meta($post_id, '_manage_stock', 'yes');
                    update_post_meta($post_id, '_stock', '1');
                    update_post_meta($post_id, '_backorders', 'no');
                    update_post_meta($post_id, '_sold_individually', 'yes');
                    if (isset($_POST['_auction_item_condition'])) {
                        update_post_meta($post_id, '_auction_item_condition', stripslashes($_POST['_auction_item_condition']));
                    }

                    if (isset($_POST['_auction_type'])) {
                        update_post_meta($post_id, '_auction_type', stripslashes($_POST['_auction_type']));
                    }

                    if(isset($_POST['_auction_proxy'])) {
                        update_post_meta($post_id, '_auction_proxy', stripslashes($_POST['_auction_proxy']));
                    } else {
                        update_post_meta($post_id, '_auction_proxy', '0');
                    }
                    if (isset($_POST['_auction_sealed']) && !isset($_POST['_auction_proxy'])) {
                        update_post_meta($post_id, '_auction_sealed', stripslashes($_POST['_auction_sealed']));
                    } else {
                        update_post_meta($post_id, '_auction_sealed', 'no');
                    }
                    if (isset($_POST['_auction_start_price'])) {
                        update_post_meta($post_id, '_auction_start_price', wc_format_decimal(wc_clean($_POST['_auction_start_price'])));
                    }

                    if (isset($_POST['_auction_bid_increment'])) {
                        update_post_meta($post_id, '_auction_bid_increment', wc_format_decimal(wc_clean($_POST['_auction_bid_increment'])));
                    }

                    if (isset($_POST['_auction_reserved_price'])) {
                        update_post_meta($post_id, '_auction_reserved_price', wc_format_decimal(wc_clean($_POST['_auction_reserved_price'])));
                    }

                    if (isset($_POST['_regular_price'])) {
                        update_post_meta($post_id, '_regular_price', wc_format_decimal(wc_clean($_POST['_regular_price'])));
                    }

                    if (isset($_POST['_auction_dates_from'])) {
                        update_post_meta($post_id, '_auction_dates_from', stripslashes($_POST['_auction_dates_from']));
                    }

                    if (isset($_POST['_auction_dates_to'])) {
                        update_post_meta($post_id, '_auction_dates_to', stripslashes($_POST['_auction_dates_to']));
                    }

                    if (isset($_POST['_relist_auction_dates_from']) && isset($_POST['_relist_auction_dates_to']) && !empty($_POST['_relist_auction_dates_from']) && !empty($_POST['_relist_auction_dates_to'])) {
                        $this->do_relist($post_id, $_POST['_relist_auction_dates_from'], $_POST['_relist_auction_dates_to']);

                    }
                    if (isset($_POST['_auction_automatic_relist'])) {
                        update_post_meta($post_id, '_auction_automatic_relist', stripslashes($_POST['_auction_automatic_relist']));
                    }

                    if (isset($_POST['_auction_relist_fail_time'])) {
                        update_post_meta($post_id, '_auction_relist_fail_time', stripslashes($_POST['_auction_relist_fail_time']));
                    }

                    if (isset($_POST['_auction_relist_not_paid_time'])) {
                        update_post_meta($post_id, '_auction_relist_not_paid_time', stripslashes($_POST['_auction_relist_not_paid_time']));
                    }

                    if (isset($_POST['_auction_relist_duration'])) {
                        update_post_meta($post_id, '_auction_relist_duration', stripslashes($_POST['_auction_relist_duration']));
                    }

                    $auction_bid_count = get_post_meta($post_id, '_auction_bid_count', true);
                    if (empty($auction_bid_count)) {
                        update_post_meta($post_id, '_auction_bid_count', '0');
                    }

                }
            }

            function do_relist($post_id, $relist_from, $relist_to) 
            {

                update_post_meta($post_id, '_auction_dates_from', stripslashes($relist_from));
                update_post_meta($post_id, '_auction_dates_to', stripslashes($relist_to));
                update_post_meta($post_id, '_auction_relisted', current_time('mysql'));
                delete_post_meta($post_id, '_auction_closed');
                delete_post_meta($post_id, '_auction_started');
                delete_post_meta($post_id, '_auction_fail_reason');
                delete_post_meta($post_id, '_auction_current_bid');
                delete_post_meta($post_id, '_auction_current_bider');
                delete_post_meta($post_id, '_auction_max_bid');
                delete_post_meta($post_id, '_auction_max_current_bider');
                delete_post_meta($post_id, '_stop_mails');
                delete_post_meta($post_id, '_stop_mails');
                delete_post_meta($post_id, '_auction_bid_count');

                $order_id = get_post_meta($post_id, 'order_id', true);
                // check if the custom field has a value
                if (!empty($order_id)) {
                    $order = new WC_Order($order_id);
                    $order->update_status('failed', __('Failed because off relisting', 'wc_simple_auctions'));
                    delete_post_meta($post_id, '_order_id');
                }

                do_action('woocommerce_simple_auction_do_relist', $post_id, $relist_from, $relist_to);
            }

            function relist_auction($post_id) 
            {

                $product = wc_get_product($post_id);

                if ($product->get_auction_automatic_relist() == 'yes' && $product->is_finished() && $product->get_uction_relist_duration()) {

                    $from_time = date('Y-m-d H:i', current_time('timestamp'));
                    $to_time = date('Y-m-d H:i', current_time('timestamp') + ($product->get_uction_relist_duration() * 3600));

                    if ($product->get_auction_closed() == '1' && $product->get_auction_relist_fail_time() && $product->auction_relist_duration) {

                        if (current_time('timestamp') > (strtotime($product->get_auction_dates_to()) + ($product->get_auction_relist_fail_time * 3600))) {

                            do_action('woocomerce_before_relist_failed_auction', $post_id);
                            $this->do_relist($post_id, $from_time, $to_time);
                            do_action('woocomerce_after_relist_failed_auction', $post_id);
                            return;
                        }

                    }
                    if ($product->auction_closed == '2' && $product->get_auction_relist_not_paid_time() && $product->get_auction_relist_duration()) {

                        if (current_time('timestamp') > (strtotime($product->get_auction_dates_to()) + ($product->get_auction_relist_not_paid_time() * 3600))) {

                            do_action('woocomerce_before_relist_not_paid_auction', $post_id);
                            $this->do_relist($post_id, $from_time, $to_time);
                            do_action('woocomerce_after_relist_not_paid_auction', $post_id);
                            return;
                        }

                    }
                }

                return;
            }
            /**
             * Templating with plugin folder
             *
             * @param int      $post_id the post (product) identifier
             * @param stdClass $post    the post (product)
             */
            function woocommerce_locate_template($template, $template_name, $template_path) 
            {

                global $woocommerce;

                if (!$template_path) {
                    $template_path = $woocommerce->template_url;
                }

                                $plugin_path = $this->plugin_path . 'templates/';
                                $template_locate = locate_template(
                                    array(
                                            $template_path . $template_name,
                                            $template_name,
                                    )
                                );

                                // Modification: Get the template from this plugin, if it exists
                                if (!$template_locate && file_exists($plugin_path . $template_name)) {

                                    return $plugin_path . $template_name;

                                } else {

                                    return $template;

                                }
            }
            /**
             * Place bid action
             *
             * Checks for a valid request, does validation (via hooks) and then redirects if valid.
             *
             * @access public
             * @param  bool $url (default: false)
             * @return void
             */
            function woocommerce_simple_auctions_place_bid($url = false) 
            {

                if (empty($_REQUEST['place-bid']) || !is_numeric($_REQUEST['place-bid'])) {
                    return;
                }

                global $woocommerce;

                $product_id = apply_filters('woocommerce_place_bid_product_id', absint($_REQUEST['place-bid']));
                $bid = apply_filters('woocommerce_place_bid_bid', abs(round(str_replace(',', '.', $_REQUEST['bid_value']), wc_get_price_decimals())));
                $was_place_bid = false;
                $placed_bid = array();
                $placing_bid = wc_get_product($product_id);
                $product_type = method_exists($placing_bid, 'get_type') ? $placing_bid->get_type() : $placing_bid->product_type;
                $place_bid_handler = apply_filters('woocommerce_place_bid_handler', $product_type, $placing_bid);
                $quantity = 1;

                if ('auction' === $place_bid_handler) {

                    // Place bid
                    if ($this->bid->placebid($product_id, $bid)) {
                        woocommerce__simple_auctions_place_bid_message($product_id);
                        $was_place_bid = true;
                        $placed_bid[] = $product_id;
                    }
                    if (version_compare($woocommerce->version, '2.1', ">=")) {

                        if (wc_notice_count('error') == 0) {
                            wp_safe_redirect(esc_url(remove_query_arg(array('place-bid', 'quantity', 'product_id'), wp_get_referer())));
                            exit;
                        }
                        return;

                    } else {
                        wp_safe_redirect(esc_url(remove_query_arg(array('place-bid', 'quantity', 'product_id'), wp_get_referer())));
                        exit;
                    }

                } else {
                    wc_add_notice(__('Item is not for auction', 'wc_simple_auctions'), 'error');
                    return;
                }
            }
            /**
             * Close auction action
             *
             * Checks for a valid request, does validation (via hooks) and then redirects if valid.
             *
             * @access public
             * @param  bool $url (default: false)
             * @return void
             */
            public function add_product_to_cart() 
            {

                if (!is_admin()) {

                    if (!empty($_GET['pay-auction'])) {

                        $current_user = wp_get_current_user();

                        if(apply_filters('woocommerce_simple_auction_empty_cart', true)) {
                            WC()->cart->empty_cart();    
                        }
                        $product_id = $_GET['pay-auction'];
                        $product_data = wc_get_product($product_id);

                        if (!$product_data) {
                            wp_redirect(home_url());
                            exit;
                        }
                        if (!is_user_logged_in()) {
                            header('Location: ' . wp_login_url(WC()->cart->get_checkout_url() . '?pay-auction=' . $product_id));
                            exit;
                        }
                        if ($current_user->ID != $product_data->get_auction_current_bider()) {
                            wc_add_notice(sprintf(__('You can not buy this item because you did not win the auction! ', 'wc_simple_auctions'), $product_data->get_title()), 'error');
                            return false;
                        }
                        WC()->cart->add_to_cart($product_id);
                        wp_safe_redirect(remove_query_arg(array('pay-auction', 'quantity', 'product_id'), WC()->cart->get_checkout_url()));
                        exit;
                    }
                }
            }
            /**
             * Is auction payable
             *
             * Checks for a valid user who have won auction
             *
             * @access public
             * @param  bool object (default: false)
             * @return bool
             */
            function auction_is_purchasable($is_purchasable, $object) 
            {

                $object_type = method_exists($object, 'get_type') ? $object->get_type() : $object->product_type;
                if ($object_type == 'auction') {

                    if (!$object->get_auction_closed() && $object->get_auction_type() == 'normal' && ($object->get_price() < $object->get_curent_bid())) {
                        return false;
                    } elseif (!$object->get_auction_closed() && $object->get_auction_type() == 'reverse' && ($object->get_price() > $object->get_curent_bid())) {
                        return false;
                    }
                    
                    if(get_option('simple_auctions_alow_buy_now', 'yes') == 'no' AND $object->get_auction_bid_count() != '0' && $object->get_auction_closed() != '2') {
                        return false;
                    }
                    if (!$object->get_auction_closed() && !$object->get_auction_closed() && $object->get_price() !== '') {

                        return true;
                    }

                    if (!is_user_logged_in()) {
                        return false;
                    }

                    $current_user = wp_get_current_user();
                    if ($current_user->ID != $object->get_auction_current_bider()) {
                        return false;
                    }

                    if (!$object->get_auction_closed()) {
                        return false;
                    }
                    if ($object->get_auction_closed() != '2') {
                        return false;
                    }


                    return true;
                }
                return $is_purchasable;
            }
            /**
             * Add auction column in product list in wp-admin
             *
             * @access public
             * @param  array
             * @return array
             */
            function woocommerce_simple_auctions_order_column_auction($defaults) 
            {
                $defaults['auction'] = "<img src='" . $this->plugin_url . 'images/auction.png' . "' alt='" . __('Auction', 'wc_simple_auctions') . "' />";
                return $defaults;
            }
            /**
             * Add auction icons in product list in wp-admin
             *
             * @access public
             * @param  string, string
             * @return void
             */
            function woocommerce_simple_auctions_order_column_auction_content($column_name, $post_ID) 
            {

                if ($column_name == 'auction') {
                    $class = '';
                    $title = '';

                    $product_data = wc_get_product($post_ID);
                    $product_data_type = method_exists($product_data, 'get_type') ? $product_data->get_type() : $product_data->product_type;
                    if (is_object($product_data) && $product_data_type == 'auction') {
                        if ($product_data->is_closed()) {
                            $class .= ' finished ';
                            $title .= ' finished ';
                        }
                        if ($product_data->get_auction_fail_reason() == '1') {
                            $class .= ' no_bid fail ';
                            $title .= ' no bid, fai ';
                        }

                        if ($product_data->get_auction_fail_reason() == '2') {
                            $class .= ' no_reserve fail';
                            $title .= ' no reserve , fail ';
                        }
                        if ($product_data->get_auction_closed() == '3') {
                            $class .= ' sold ';
                            $title .= ' sold ';
                        }
                        if ($product_data->get_auction_payed()) {
                            $class .= ' payed ';
                            $title .= ' payed ';
                        }
                        echo "<img src='" . $this->plugin_url . 'images/auction-white.png' . "' title='" . sprintf(__('Auction %s', 'wc_simple_auctions'), $title) . "' class='$class' />";
                    }
                    if (get_post_meta($post_ID, '_auction', true)) {
                        echo "<img src='" . $this->plugin_url . 'images/auction.png' . "' title='" . sprintf(__('Auction %s', 'wc_simple_auctions'), $title) . "' class='order' />";
                    }
                }
            }

            /**
             * Ouput custom columns for products.
             *
             * @param string $column
             */
            public function render_product_columns( $column ) 
            {

                global $post, $the_product;

                if (empty( $the_product ) || $the_product->get_id() != $post->ID ) {
                    $the_product = wc_get_product($post);
                }

                if($column == 'product_type') {
                    $the_product_type = method_exists($the_product, 'get_type') ? $the_product->get_type() : $the_product->product_type;
                    if ('auction' == $the_product_type ) {
                        $class ='';
                        $title = '';
                        if ($the_product->is_closed()) {
                            $class .= ' finished ';
                            $title .= ' finished ';
                        }
                        if ($the_product->get_auction_fail_reason() == '1') {
                            $class .= ' no_bid fail ';
                            $title .= ' no bid, fai ';
                        }

                        if ($the_product->get_auction_fail_reason() == '2') {
                            $class .= ' no_reserve fail';
                            $title .= ' no reserve , fail ';
                        }
                        if ($the_product->get_auction_closed() == '3') {
                            $class .= ' sold ';
                            $title .= ' sold ';
                        }
                        if ($the_product->get_auction_payed()) {
                            $class .= ' payed ';
                            $title .= ' payed ';
                        }

                        echo '<span class="auction-status '.$class.'" title="'.sprintf(__('Auction %s', 'wc_simple_auctions'), $title) .'" ></span>';
                    }

                }

            }    
            /**
             * Add dropdown to filter auctions
             *
             * @param (wp_query object) $query
             *
             * @return Void
             */
            function admin_posts_filter_restrict_manage_posts() 
            {

                //only add filter to post type you want
                if (isset($_GET['post_type']) && $_GET['post_type'] == 'product') {
                    $values = array(
                    'Active' => 'active',
                    'Finished' => 'finished',
                    'Fail' => 'fail',
                    'Sold' => 'sold',
                    'Paid' => 'payed',
                    );
                    ?>
                    <select name="wsa_filter">
                    <option value=""><?php _e('Auction filter By ', 'wc_simple_auctions');?></option>
                <?php
                                    $current_v = isset($_GET['wsa_filter']) ? $_GET['wsa_filter'] : '';
                foreach ($values as $label => $value) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        $value,
                        $value == $current_v ? ' selected="selected"' : '',
                        $label
                    );
                }
                                ?>
                       </select>
                        <?php
                }
            }
            /**
             * If submitted filter by post meta
             *
             * make sure to change META_KEY to the actual meta key
             * and POST_TYPE to the name of your custom post type
    *
             * @param (wp_query object) $query
             *
             * @return Void
             */
            function admin_posts_filter($query) 
            {
                global $pagenow;

                if (isset($_GET['post_type']) && $_GET['post_type'] == 'product' && is_admin() && $pagenow == 'edit.php' && isset($_GET['wsa_filter']) && $_GET['wsa_filter'] != '') {

                    $taxquery = $query->get('tax_query');
                    if (!is_array($taxquery)) {
                        $taxquery = array();
                    }

                    $taxquery[] =
                    array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'auction',

                    );

                    $query->set('tax_query', $taxquery);

                    switch ($_GET['wsa_filter']) {
                    case 'active':
                        $query->query_vars['meta_query'] = array(
                        array(
                        'key' => '_auction_closed',
                        'compare' => 'NOT EXISTS',
                        ),
                        );

                        break;
                    case 'finished':
                        $query->query_vars['meta_query'] = array(
                        array(
                        'key' => '_auction_closed',
                        'compare' => 'EXISTS',
                        ),
                        );

                        break;
                    case 'fail':
                        $query->query_vars['meta_key'] = '_auction_closed';
                        $query->query_vars['meta_value'] = '1';

                        break;
                    case 'sold':
                        $query->query_vars['meta_query'] = array(
                        array(
                        'key' => '_auction_closed',
                        'value' => '2',
                        ),
                        array(
                        'key' => '_auction_payed',
                        'compare' => 'NOT EXISTS',
                        ),
                        );

                        break;
                    case 'payed':
                        $query->query_vars['meta_key'] = '_auction_payed';
                        $query->query_vars['meta_value'] = '1';
                        break;
                    }
                }
            }
            /**
             * Add settings tab in WooCommerce settings page
             *
             * @access public
             * @param  array
             * @return array
             */
            function add_setting_tab($tabs) 
            {
                $tabs['simple_auctions'] = __('Auctions', 'wc_simple_auctions');
                return $tabs;
            }
            /**
             * Adding content in tab for WooCommerce settings page
             *
             * @access public
             * @return void
             */
            function add_settings_tab_content() 
            {
                woocommerce_admin_fields($this->init_form_fields());
            }
            /**
             * Update content in tab for WooCommerce settings page
             *
             * @access public
             * @return void
             */
            function update_settings_tab_content() 
            {
                global $woocommerce_settings;
                woocommerce_update_options($this->init_form_fields());
            }
            /**
             *  WooCommerce settings content
             *
             * @return array (preferences array for woocommerce backend)
             */
            function init_form_fields() 
            {
                $woocommerce_settings['simple_auctions'] = array(
                array('title' => __('Simple auction options', 'wc_simple_auctions'), 'type' => 'title', 'desc' => '', 'id' => 'simple_auction_options'),
                array(
                'title' => __('Past auctions', 'wc_simple_auctions'),
                'desc' => __('Show finished auctions.', 'wc_simple_auctions'),
                'type' => 'checkbox',
                'id' => 'simple_auctions_finished_enabled',
                'default' => 'no',
                ),
                array(
                'title' => __('Future auctions', 'wc_simple_auctions'),
                'desc' => __('Show auctions that did not start yet.', 'wc_simple_auctions'),
                'type' => 'checkbox',
                'id' => 'simple_auctions_future_enabled',
                'default' => 'yes',
                ),
                array(
                'title' => __("Do not show auctions on shop page", 'wc_simple_auctions'),
                'desc' => __('Do not mix auctions and regular products on shop page. Just show auctions on the auction page (auctions base page)', 'wc_simple_auctions'),
                'type' => 'checkbox',
                'id' => 'simple_auctions_dont_mix_shop',
                'default' => 'yes',
                ),
                array(
                'title' => __("Do not show auctions on product search page", 'wc_simple_auctions'),
                'desc' => __('Do not mix auctions and regular products on product search page.', 'wc_simple_auctions'),
                'type' => 'checkbox',
                'id' => 'simple_auctions_dont_mix_search',
                'default' => 'no',
                ),
                array(
                'title' => __("Do not show auctions on product category page", 'wc_simple_auctions'),
                'desc' => __('Do not mix auctions and regular products on product category page. Just show auctions on the auction page (auctions base page)', 'wc_simple_auctions'),
                'type' => 'checkbox',
                'id' => 'simple_auctions_dont_mix_cat',
                'default' => 'yes',
                ),
                array(
                'title' => __("Do not show auctions on product tag page", 'wc_simple_auctions'),
                'desc' => __('Do not mix auctions and regular products on product tag page. Just show auctions on the auction page (auctions base page)', 'wc_simple_auctions'),
                'type' => 'checkbox',
                'id' => 'simple_auctions_dont_mix_tag',
                'default' => 'yes',
                ),
                array(
                'title' => __("Countdown format", 'wc_simple_auctions'),
                'desc' => __("The format for the countdown display. Default is yowdHMS", 'wc_simple_auctions'),
                'desc_tip' => __(
                    "Use the following characters (in order) to indicate which periods you want to display: 'Y' for years, 'O' for months, 'W' for weeks, 'D' for days, 'H' for hours, 'M' for minutes, 'S' for seconds.

Use upper-case characters for mandatory periods, or the corresponding lower-case characters for optional periods, i.e. only display if non-zero. Once one optional period is shown, all the ones after that are also shown.", 'wc_simple_auctions'
                ),
                'type' => 'text',
                'id' => 'simple_auctions_countdown_format',
                'default' => 'yowdHMS',
                ),
                array(
                'title' => __('Auctions Base Page', 'wc_simple_auctions'),
                'desc' => __('Set the base page for your auctions - this is where your auction archive will be.', 'wc_simple_auctions'),
                'id' => 'woocommerce_auction_page_id',
                'type' => 'single_select_page',
                'default' => '',
                'class' => 'chosen_select_nostd',
                'css' => 'min-width:300px;',
                'desc_tip' => true,
                ),

                array(
                'title' => __("Use ajax bid check", 'wc_simple_auctions'),
                'desc' => __('Enables / disables ajax current bid checker (refresher) for auction - updates current bid value without refreshing page (increases server load, disable for best performance)', 'wc_simple_auctions'),
                'type' => 'checkbox',
                'id' => 'simple_auctions_live_check',
                'default' => 'yes',
                ),
                array(
                'title' => __("Ajax bid check interval", 'wc_simple_auctions'),
                'desc' => __('Time between two ajax requests in seconds (bigger intervals means less load for server)', 'wc_simple_auctions'),
                'type' => 'text',
                'id' => 'simple_auctions_live_check_interval',
                'default' => '1',
                ),
                array(
                'title' => __("Allow highest bidder to outbid himself", 'wc_simple_auctions'),
                //'desc' 			=> __( '', 'wc_simple_auctions' ),
                'type' => 'checkbox',
                'id' => 'simple_auctions_curent_bidder_can_bid',
                'default' => 'no',
                ),

                array(
                'title' => __("Allow watchlists", 'wc_simple_auctions'),
                //'desc' 				=> __( '', 'wc_simple_auctions' ),
                'type' => 'checkbox',
                'id' => 'simple_auctions_watchlists',
                'default' => 'yes',
                ),
                array(
                                            'title'             => __("Max bid amount", 'wc_simple_auctions'),
                                            'desc'                => __("Maximum value for single bid. Default value is ", 'wc_simple_auctions').wc_price('999999999999999999.99'),
                                            'type'                 => 'number',
                                            'id'                => 'simple_auctions_max_bid_amount',
                                            'default'             => ''
                                        ),
                array(
                                            'title'             => __("Allow Buy It Now after bidding has started", 'wc_simple_auctions'),
                                            'desc'                 => __('For auction-style listings with the Buy It Now option, you have the chance to purchase an item immediately, before bidding starts. After someone bids, the Buy It Now option disappears and bidding continues until the listing ends, with the item going to the highest bidder.', 'wc_simple_auctions'),
                                            'type'                 => 'checkbox',
                                            'id'                => 'simple_auctions_alow_buy_now',
                                            'default'             => 'yes'
                                        ),

                array('type' => 'sectionend', 'id' => 'simple_auction_options'),
                );
                return $woocommerce_settings['simple_auctions'];
            }

            function auction_settings_class($settings) 
            {
                $settings[] = include 'classes/class-wc-settings-auctions.php';
                return $settings;
            }
            /**
             *  Shortcode for my auctions
             *
             * @access public
             * @param  array
             * @return
             */
            function shortcode_my_auctions($atts) 
            {
                global $woocommerce;
                return WC_Shortcodes::shortcode_wrapper(array('WC_Shortcode_Auction_Software_My_Auctions', 'output'), $atts);
            }
            /**
             *  Add meta box to the product editing screen
             *
             * @access public
             */
            function woocommerce_simple_auctions_meta() 
            {
                global $woocommerce, $post;
                $product_data = wc_get_product($post->ID);
                if ($product_data) {
                    if (method_exists($product_data, 'get_type') && $product_data->get_type() == 'auction') {
                        add_meta_box('Auction', __('Auction', 'wc_simple_auctions'), array($this, 'woocommerce_simple_auctions_meta_callback'), 'product', 'normal');
                    }
                }

            }

            /**
             *  Callback for adding a meta box to the product editing screen used in woocommerce_simple_auctions_meta
             *
             * @access public
             */
            function woocommerce_simple_auctions_meta_callback() 
            {

                global $woocommerce, $post;
                $product_data = wc_get_product($post->ID);
                $heading = esc_html(apply_filters('woocommerce_auction_history_heading', __('Auction History', 'wc_simple_auctions')));
            

                ?>
                    <?php if (!empty($product_data->get_auction_relisted())) {?>
                        <p><?php _e('Auction has been relisted on:', 'wc_simple_auctions')?> <?php echo $product_data->get_auction_relisted() ?></p>
                    <?php 
}?>
        <?php if (($product_data->is_closed() === true) and ($product_data->is_started() === true)) : ?>
						<p><?php _e('Auction has finished', 'wc_simple_auctions')?></p>
        <?php if ($product_data->get_auction_fail_reason() == '1') {
            echo "<p>";
            _e('Auction failed because there were no bids', 'wc_simple_auctions');
            echo "</p>";
} elseif ($product_data->get_auction_fail_reason() == '2') {
    echo "<p class='reservefail'>";
    _e('Auction failed because item did not make it to reserve price', 'wc_simple_auctions');
    echo ' <a class="removereserve" href="#" data-postid="' . $post->ID . '">';
    _e('Remove reserve price', 'wc_simple_auctions');
    echo ' </a>';
    echo "</p>";
}
if ($product_data->get_auction_closed() == '3') {?>
						<p><?php _e('Product sold for buy now price', 'wc_simple_auctions')?>: <span><?php echo wc_price($product_data->get_regular_price()) ?></span></p>
        <?php 
} elseif ($product_data->get_auction_current_bider()) {
    ?>
   <p><?php _e('Highest bidder was', 'wc_simple_auctions')?>: <span class="higestbider"><a href='<?php get_edit_user_link($product_data->get_auction_current_bider())?>'><?php echo get_userdata($product_data->get_auction_current_bider())->display_name ?></a></span></p>
   <p><?php _e('Highest bid was', 'wc_simple_auctions')?>: <span class="higestbid" ><?php echo wc_price($product_data->get_curent_bid()) ?></span></p>

        <?php if ($product_data->get_auction_payed()) {?>
						<p><?php _e('Order has been paid, order ID is', 'wc_simple_auctions')?>: <span><a href='post.php?&action=edit&post=<?php echo $product_data->get_order_id() ?>'><?php echo $product_data->get_order_id() ?></a></span></p>
        <?php 
} elseif ($product_data->get_order_id()) {
                  $order_status_obj = wp_get_post_terms($product_data->get_order_id(), 'shop_order_status');

    if ($order_status_obj && !is_wp_error($order_status_obj)) {
        $order_status_first = current($order_status_obj);
        $order_status = sanitize_title($order_status_first->slug);
    } else {
        $order_status = sanitize_title(apply_filters('woocommerce_default_order_status', 'pending'));
    }

        ?>
      <p><?php _e('Order has been made, order status is', 'wc_simple_auctions')?>: <a href='post.php?&action=edit&post=<?php echo $product_data->get_order_id() ?>'><?php echo $order_status ?></a><span>
        <?php 
}?>
						<p></p>
        <?php 
}?>
            <?php if ($product_data->get_number_of_sent_mails()) {
                $dates_of_sent_mail = get_post_meta($product_data->id, '_dates_of_sent_mails', false);
        ?>
							<p><?php _e('Number of sent reminder emails', 'wc_simple_auctions')?>: <span> <?php echo $product_data->get_number_of_sent_mails() ?></span></p>
							<p><?php _e('Last reminder mail was sent on', 'wc_simple_auctions')?>: <span> <?php echo date('Y-m-d', end($dates_of_sent_mail)) ?></span></p>
							<p class="reminder-status"><?php _e('Reminder status', 'wc_simple_auctions')?>: <?php if ($product_data->get_stop_mails()) {?><span class="error"><?php _e('Stopped', 'wc_simple_auctions')?></span><?php 
} else {?><span class="ok"><?php _e('Running', 'wc_simple_auctions')?></span><?php 
}?> </p>
        <?php 
}?>

        <?php endif;?>
        <?php if (($product_data->is_closed() === false) and ($product_data->is_started() === true)) : ?>

        <?php if ($product_data->get_auction_proxy()) {?>
							<p><?php _e('This is proxy auction', 'wc_simple_auctions')?></p>
        <?php if ($product_data->get_auction_max_bid() && $product_data->get_auction_max_current_bider()) {?>
								<p><?php _e('Maximum bid is', 'wc_simple_auctions')?> <?php echo $product_data->get_auction_max_bid() ?> <?php _e('by', 'wc_simple_auctions')?> <a href='"<?php echo get_edit_user_link($product_data->get_auction_max_current_bider()) ?>"'><?php echo get_userdata($product_data->get_auction_max_current_bider())->display_name; ?></a>  </p>
        <?php 
}?>
        <?php 
}?>
        <?php 
endif;?>
           <h2><?php echo $heading; ?></h2>
           <table class="auction-table widefat fixed">
            <?php

                                        $auction_history = apply_filters('woocommerce__auction_history_data', $product_data->auction_history());

            if (!empty($auction_history)  ) : ?>

           <thead>
            <tr>
             <th><?php _e('Date', 'wc_simple_auctions')?></th>
             <th><?php _e('Bid', 'wc_simple_auctions')?></th>
             <th><?php _e('User', 'wc_simple_auctions')?></th>
             <th><?php _e('Auto', 'wc_simple_auctions')?></th>
             <th class="actions"><?php _e('Actions', 'wc_simple_auctions')?></th>
            </tr>
           </thead>

            <?php foreach ($auction_history as $history_value) {
                if ($history_value->date < $product_data->get_auction_relisted() 
                    && !isset($displayed_relist)         
                ) {
                    echo "<tr>";
                    echo '<td class="date">' . $product_data->get_auction_start_time() . '</td>';
                    echo '<td colspan="4"  class="relist">';
                    echo __('Auction relisted', 'wc_simple_auctions');
                    echo '</td>';
                    echo "</tr>";
                    $displayed_relist = true;
                }

                echo "<tr>";
                echo "<td class='date'>$history_value->date</td>";
                echo "<td class='bid'>$history_value->bid</td>";
                echo "<td class='username'><a href='" . get_edit_user_link($history_value->userid) . "'>" . get_userdata($history_value->userid)->display_name . "</a></td>";
                if ($history_value->proxy == 1) {
                    echo " <td class='proxy'>" . __('Auto', 'wc_simple_auctions') . "</td>";
                } else {
                    echo " <td class='proxy'></td>";
                }

                echo "<td class='action'> <a href='#' data-id='" . $history_value->id . "' data-postid='" . $post->ID . "'   >" . __('Delete', 'wc_simple_auctions') . "</a></td>";
                echo "</tr>";

}?>
    <?php 
            endif;?>
             <tr class="start">
            <?php if ($product_data->is_started() === true) {
                echo '<td class="date">' . $product_data->get_auction_start_time() . '</td>';
                echo '<td colspan="4"  class="started">';
                echo apply_filters('auction_history_started_text', __('Auction started', 'wc_simple_auctions'), $product_data);
                echo '</td>';

} else {
       echo '<td  class="date">' . $product_data->get_auction_start_time() . '</td>';
       echo '<td colspan="4"  class="starting">';
       echo apply_filters('auction_history_starting_text', __('Auction starting', 'wc_simple_auctions'), $product_data);
       echo '</td>';
}?>
                </tr>


              </table>
              </ul><?php
            }

            /**
             *  Add auction relist meta box to the product editing screen
             *
             * @access public
             */
            function woocommerce_simple_auctions_automatic_relist() 
            {
                global $woocommerce, $post;

                add_meta_box('Automatic_relist_auction', __('Automatic relist auction', 'wc_simple_auctions'), array($this, 'woocommerce_simple_auctions_automatic_relist_callback'), 'product', 'normal');

            }
            /**
             *  Callback for adding a meta box to the product editing screen used for automatic relist
             *
             * @access public
             */
            function woocommerce_simple_auctions_automatic_relist_callback() 
            {

                global $woocommerce, $post;
                $product_data = wc_get_product($post->ID);
                $heading = esc_html(apply_filters('woocommerce_auction_history_heading', __('Auction automatic relist', 'wc_simple_auctions')));
                echo '<div class="woocommerce_options_panel ">';
                woocommerce_wp_checkbox(array('id' => '_auction_automatic_relist', 'wrapper_class' => '', 'label' => __('Automatic relist auction', 'wc_simple_auctions'), 'description' => __('Enable automatic relisting', 'wc_simple_auctions')));
                woocommerce_wp_text_input(
                    array('id' => '_auction_relist_fail_time', 'class' => 'wc_input_price short', 'label' => __('Relist if fail after n hours', 'wc_simple_auctions'), 'type' => 'number', 'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0',
                    ))
                );
                woocommerce_wp_text_input(
                    array('id' => '_auction_relist_not_paid_time', 'class' => 'wc_input_price short', 'label' => __('Relist if not paid after n hours', 'wc_simple_auctions'), 'type' => 'number', 'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0',
                    ))
                );
                woocommerce_wp_text_input(
                    array('id' => '_auction_relist_duration', 'class' => 'wc_input_price short', 'label' => __('Relist auction duration in h', 'wc_simple_auctions'), 'type' => 'number', 'custom_attributes' => array(
                    'step' => 'any',
                    'min' => '0',
                    ))
                );

                echo '</div>';
            }

            /**
             *  Add pay button for auctions that user won
             *
             * @access public
             */
            function add_pay_button() 
            {
                if (is_user_logged_in()) {
                    wc_get_template('loop/pay-button.php');
                }

            }

            /**
             *  Add pay button for auctions that user won
             *
             * @access public
             */
            function add_to_watchlist() 
            {

                global $watchlist;

                if (isset($watchlist) && $watchlist == true) {
                    wc_get_template('single-product/watchlist-link.php');
                }

            }

            /**
             *  Add winning badge for auctions that current user is winning
             *
             * @access public
             */
            function add_winning_bage() 
            {
                if (is_user_logged_in()) {
                    wc_get_template('loop/winning-bage.php');
                }

            }

            /**
             *   Add auction badge for auction product
             *
             * @access public
             */
            function add_auction_bage() 
            {
                wc_get_template('loop/auction-bage.php');
            }
            /**
             *   Add auction badge for auction product
             *
             * @access public
             */
            function add_watchlist_link() 
            {
                wc_get_template('single-product/watchlist-link.php');
            }

            /**
             * Get template for auctions archive page
             *
             * @access public
             * @param  string
             * @return string
             */
            function auctions_page_template($template) 
            {
                global $wp_version;
                if (get_query_var('is_auction_archive', false)) {

                    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30, 0);

                    if (version_compare($wp_version, '4.2', '>=')) {

                        add_action('woocommerce_before_shop_loop', 'woocommerce_auctions_ordering', 30, 0);

                    }
                    $template  = locate_template(WC()->template_path().'archive-product-auctions.php');
                    if($template) {
                        wc_get_template('archive-product-auctions.php');
                    } else {
                        wc_get_template('archive-product.php');
                    }



                    return false;
                }
                return $template;
            }

            /**
             * Output body classes for auctions archive page
             *
             * @access public
             * @param  array
             * @return array
             */
            function output_body_class($classes) 
            {
                if (is_page(wc_get_page_id('auction'))) {
                    $classes[] = 'woocommerce auctions-page';
                }
                return $classes;
            }

            function add_queryvars($qvars) 
            {
                $qvars[] = 'search_auctions';

                return $qvars;
            }

            /**
             * Modify product query based on settings
             *
             * @access public
             * @param  object
             * @return object
             */
            function remove_auctions_from_woocommerce_product_query($q) 
            {

                // We only want to affect the main query
                if (!$q->is_main_query()) {
                    return;
                }

                if (!$q->is_post_type_archive('product') && !$q->is_tax(get_object_taxonomies('product'))) {
                    return;
                }

                $simple_auctions_dont_mix_shop = get_option('simple_auctions_dont_mix_shop');

                $simple_auctions_dont_mix_cat = get_option('simple_auctions_dont_mix_cat');
                if ($simple_auctions_dont_mix_cat != 'yes' && is_product_category()) {
                    return;
                }

                $simple_auctions_dont_mix_tag = get_option('simple_auctions_dont_mix_tag');
                if ($simple_auctions_dont_mix_tag != 'yes' && is_product_tag()) {
                    return;
                }


                $simple_auctions_dont_mix_search = get_option('simple_auctions_dont_mix_search');

                if (!is_admin() && $q->is_main_query() && $q->is_search()) {

                    if (isset($q->query['search_auctions']) && $q->query['search_auctions'] == true) {
                        $taxquery = $q->get('tax_query');
                        if (!is_array($taxquery)) {
                            $taxquery = array();
                        }
                        $taxquery[] =
                        array(
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => 'auction',

                        );

                        $q->set('tax_query', $taxquery);
                        $q->query['auction_arhive'] = true;

                    } elseif ($simple_auctions_dont_mix_search == 'yes') {

                        $taxquery = $q->get('tax_query');
                        if (!is_array($taxquery)) {
                            $taxquery = array();
                        }
                        $taxquery[] =
                        array(
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => 'auction',
                        'operator' => 'NOT IN',
                        );

                        $q->set('tax_query', $taxquery);

                    }

                    return;

                }

                if ($simple_auctions_dont_mix_shop == 'yes' && (!isset($q->query_vars['is_auction_archive']) OR $q->query_vars['is_auction_archive'] !== 'true')) {
                    $taxquery = $q->get('tax_query');
                    if (!is_array($taxquery)) {
                        $taxquery = array();
                    }
                    $taxquery[] =
                    array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'auction',
                    'operator' => 'NOT IN',
                    );

                    $q->set('tax_query', $taxquery);

                }

            }

            /**
             * Modify query based on settings
             *
             * @access public
             * @param  object
             * @return object
             */
            function pre_get_posts($q) 
            {

                $auction = array();
                $simple_auctions_finished_enabled = get_option('simple_auctions_finished_enabled');
                $simple_auctions_future_enabled = get_option('simple_auctions_future_enabled');
                $simple_auctions_dont_mix_shop = get_option('simple_auctions_dont_mix_shop');
                $simple_auctions_dont_mix_cat = get_option('simple_auctions_dont_mix_cat');
                $simple_auctions_dont_mix_tag = get_option('simple_auctions_dont_mix_tag');

                if (isset($q->query_vars['is_auction_archive']) && $q->query_vars['is_auction_archive'] == 'true') {

                                    $taxquery = $q->get('tax_query');
                    if (!is_array($taxquery)) {
                        $taxquery = array();
                    }
                                    $taxquery[] =
                                    array(
                                            'taxonomy' => 'product_type',
                                            'field' => 'slug',
                                            'terms' => 'auction',
                                    );

                                    $q->set('tax_query', $taxquery);
                                    add_filter('woocommerce_is_filtered', array($this, 'add_is_filtered'), 99); // hack for displaying when Shop Page Display is set to show categories
                }

                if (isset($q->query_vars['is_auction_archive']) && $q->query_vars['is_auction_archive'] == 'true') {
                    $orderby_value = isset($_GET['orderby']) ? wc_clean($_GET['orderby']) : apply_filters('wsa_default_auction_orderby', get_option('wsa_default_auction_orderby'));
                } else {
                    $orderby_value = isset($_GET['orderby']) ? wc_clean($_GET['orderby']) : false;
                }

                switch ($orderby_value) {

                case 'bid_desc':

                    $q->set('post_type', 'product');
                    $q->set('post_status', 'publish');
                    $q->set('ignore_sticky_posts', 1);
                    $q->set('tax_query', array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'auction')));
                    $meta_query = array(
                    array('relation' => 'OR',
                    'auction_current_bid' => array(
                    'key' => '_auction_current_bid',
                    'type' => 'numeric',
                    ),
                    'auction_start_price' => array(
                    'key' => '_auction_start_price',
                    'type' => 'numeric',
                    ),
                    ),
                    array(
                    'relation' => 'OR',
                    array(
                    'key' => '_auction_sealed',
                    'compare' => 'NOT EXISTS',
                    ),
                    array(
                    'key' => '_auction_sealed',
                    'value' => 'no',
                    ),

                    ));
                    $q->set('meta_query', $meta_query);
                    $q->set('orderby', array('auction_start_price' => 'desc', 'auction_current_bid' => 'desc'));

                    break;

                case 'bid_asc':

                    $q->set('post_type', 'product');
                    $q->set('post_status', 'publish');
                    $q->set('ignore_sticky_posts', 1);
                    $q->set('tax_query', array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'auction')));
                    $meta_query = array(
                    array('relation' => 'OR',
                    'auction_current_bid' => array(
                    'key' => '_auction_current_bid',
                    'type' => 'numeric',
                    ),
                    'auction_start_price' => array(
                    'key' => '_auction_start_price',
                    'type' => 'numeric',
                    ),
                    ),
                    array(
                    'relation' => 'OR',
                    array(
                    'key' => '_auction_sealed',
                    'compare' => 'NOT EXISTS',
                    ),
                    array(
                    'key' => '_auction_sealed',
                    'value' => 'no',
                    ),
                    ));
                    $q->set('meta_query', $meta_query);
                    $q->set('orderby', array('auction_start_price' => 'Asc', 'auction_current_bid' => 'Asc'));

                    break;

                case 'auction_end':

                    $q->set('post_type', 'product');
                    $q->set('post_status', 'publish');
                    $q->set('ignore_sticky_posts', 1);
                    $q->set('tax_query', array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'auction')));
                    $time = date('Y-m-d h:i');
                    $meta_query = array(
                    'auction_end_date' => array(
                    'key' => '_auction_dates_to',
                    'value' => $time,
                    'type' => 'DATETIME',
                    'compare' => '>=',
                    ),
                    array(
                    'key' => '_auction_started',
                    'compare' => 'NOT EXISTS'),
                    array(

                    'key' => '_auction_closed',
                    'compare' => 'NOT EXISTS',
                    ),

                    );
                    $q->set('meta_query', $meta_query);
                    $q->set('orderby', array('auction_end_date' => 'Asc'));

                    break;

                case 'auction_started':

                    $q->set('post_type', 'product');
                    $q->set('post_status', 'publish');
                    $q->set('ignore_sticky_posts', 1);
                    $q->set('tax_query', array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'auction')));
                    $time = date('Y-m-d h:i');
                    $meta_query = array(
                    'auction_start_date' => array(
                    'key' => '_auction_dates_from',
                    'value' => $time,
                    'type' => 'DATETIME',
                    'compare' => '<=',
                    ),
                    array(
                    'key' => '_auction_started',
                    'compare' => 'NOT EXISTS'),
                    array(

                    'key' => '_auction_closed',
                    'compare' => 'NOT EXISTS',
                    ));
                    $q->set('meta_query', $meta_query);
                    $q->set('orderby', array('auction_start_date' => 'desc'));

                    break;

                case 'auction_activity':

                    $q->set('post_type', 'product');
                    $q->set('post_status', 'publish');
                    $q->set('ignore_sticky_posts', 1);
                    $q->set('tax_query', array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'auction')));
                    $meta_query = array(
                    array(
                    'relation' => 'OR',
                    'auction_activity' => array(
                    'key' => '_auction_bid_count',
                    'compare' => 'EXISTS',
                    'type' => 'UNSIGNED',
                    ),
                    ));
                    $q->set('meta_query', $meta_query);
                    $q->set('orderby', array('auction_activity' => 'desc'));

                    break;
                }

                if ((         ($simple_auctions_future_enabled != 'yes' && (!isset($q->query['show_future_auctions']) or !$q->query['show_future_auctions']))
                    OR (isset($q->query['show_future_auctions']) && $q->query['show_future_auctions'] == false)                )               
                ) {

                    $metaquery = $q->get('meta_query');

                    if (!is_array($metaquery)) {
                        $metaquery = array();
                    }

                    $metaquery[] =
                    array(

                    'key' => '_auction_started',
                    'compare' => 'NOT EXISTS',

                    );
                    $q->set('meta_query', $metaquery);
                }

                if (($simple_auctions_finished_enabled != 'yes' && (!isset($q->query['show_past_auctions']) or !$q->query['show_past_auctions'])
                    OR (isset($q->query['show_past_auctions']) && $q->query['show_past_auctions'] == false)        )
                ) {

                    $metaquery = $q->get('meta_query');
                    if (!is_array($metaquery)) {
                        $metaquery = array();
                    }

                    $metaquery[] =
                    array(

                    'key' => '_auction_closed',
                    'compare' => 'NOT EXISTS',
                    );

                    $q->set('meta_query', $metaquery);

                }

                if ($simple_auctions_dont_mix_cat != 'yes' && is_product_category()) {
                    return;
                }

                if ($simple_auctions_dont_mix_tag != 'yes' && is_product_tag()) {
                    return;
                }

                if (!isset($q->query['auction_arhive']) && !$q->is_main_query()) {

                    if ($simple_auctions_dont_mix_shop == 'yes') {

                        $taxquery = $q->get('tax_query');
                        if (!is_array($taxquery)) {
                            $taxquery = array();
                        }
                        $taxquery[] =
                        array(
                        'taxonomy' => 'product_type',
                        'field' => 'slug',
                        'terms' => 'auction',
                        'operator' => 'NOT IN',
                        );

                        $q->set('tax_query', $taxquery);
                        return;
                    }

                    return;
                }

            }

            function auction_arhive_pre_get_posts($q) 
            {
                if (isset($q->query['auction_arhive']) OR (!isset($q->query['auction_arhive']) && (isset($q->query['post_type']) && $q->query['post_type'] == 'product' && !$q->is_main_query()))) {
                    $this->pre_get_posts($q);
                }
            }

            function query_auction_archive($q) 
            {

                if (!$q->is_main_query()) {
                    return;
                }

                if (isset($q->queried_object->ID) && $q->queried_object->ID === wc_get_page_id('auction')) {

                    $q->set('post_type', 'product');
                    $q->set('page', '');
                    $q->set('pagename', '');
                    $q->set('auction_arhive', 'true');
                    $q->set('is_auction_archive', 'true');

                    // Fix conditional Functions
                    $q->is_archive = true;
                    $q->is_post_type_archive = true;
                    $q->is_singular = false;
                    $q->is_page = false;
                }

                if (($q->is_page() && 'page' === get_option('show_on_front') && absint($q->get('page_id')) === wc_get_page_id('auction')) OR ($q->is_home() && absint(get_option('page_on_front')) === wc_get_page_id('auction'))) {

                    $q->set('post_type', 'product');

                    // This is a front-page shop
                    $q->set('post_type', 'product');
                    $q->set('page_id', '');
                    $q->set('auction_arhive', 'true');
                    $q->set('is_auction_archive', 'true');

                    if (isset($q->query['paged'])) {
                        $q->set('paged', $q->query['paged']);
                    }

                    // Define a variable so we know this is the front page shop later on
                    define('AUCTIONS_IS_ON_FRONT', true);

                    // Get the actual WP page to avoid errors and let us use is_front_page()
                    // This is hacky but works. Awaiting https://core.trac.wordpress.org/ticket/21096
                    global $wp_post_types;

                    $auction_page = get_post(wc_get_page_id('auction'));

                    $wp_post_types['product']->ID = $auction_page->ID;
                    $wp_post_types['product']->post_title = $auction_page->post_title;
                    $wp_post_types['product']->post_name = $auction_page->post_name;
                    $wp_post_types['product']->post_type = $auction_page->post_type;
                    $wp_post_types['product']->ancestors = get_ancestors($auction_page->ID, $auction_page->post_type);

                    // Fix conditional Functions like is_front_page
                    $q->is_singular = false;
                    $q->is_post_type_archive = true;
                    $q->is_archive = true;
                    $q->is_page = true;

                    // Remove post type archive name from front page title tag
                    add_filter('post_type_archive_title', '__return_empty_string', 5);

                    
                    // Fix WP SEO
                    if (class_exists('WPSEO_Meta') ) {
                        add_filter('wpseo_metadesc', array( $this, 'wpseo_metadesc' ));
                        add_filter('wpseo_metakey', array( $this, 'wpseo_metakey' ));
                    }
                    
                }

            }

            /**
             * WP SEO meta description.
             *
             * Hooked into wpseo_ hook already, so no need for function_exist.
             *
             * @access public
             * @return string
             */
            public function wpseo_metadesc() 
            {
                return WPSEO_Meta::get_value('metadesc', wc_get_page_id('auction'));
            }

            /**
             * WP SEO meta key.
             *
             * Hooked into wpseo_ hook already, so no need for function_exist.
             *
             * @access public
             * @return string
             */
            public function wpseo_metakey() 
            {
                return WPSEO_Meta::get_value('metakey', wc_get_page_id('auction'));
            }

            function send_reminders_email() 
            {

                global $woocommerce;

                $remind_to_pay_settings = get_option('woocommerce_remind_to_pay_settings');
                if ($remind_to_pay_settings['enabled'] != 'yes') {
                    exit();
                }

                $interval = (isset($remind_to_pay_settings['interval'])) ? (int) $remind_to_pay_settings['interval'] : 7;
                $stopsending = (isset($remind_to_pay_settings['stopsending'])) ? (int) $remind_to_pay_settings['stopsending'] : 5;
                $args = array(
                'post_type' => 'product',
                'posts_per_page' => '100	',
                'show_past_auctions' => true,
                'tax_query' => array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'auction')),
                'meta_query' => array(
                'relation' => 'AND',
                array(
                'key' => '_auction_closed',
                'value' => '2',
                ),
                array(
                'key' => '_auction_payed',
                'compare' => 'NOT EXISTS',
                ),
                array(
                'key' => '_stop_mails',
                'compare' => 'NOT EXISTS',
                ),
                ),
                );

                $the_query = new WP_Query($args);

                if ($the_query->have_posts()) {

                    while ($the_query->have_posts()):

                        $the_query->the_post();
                        $number_of_sent_mail = get_post_meta($the_query->post->ID, '_number_of_sent_mails', true);
                        $dates_of_sent_mail = get_post_meta($the_query->post->ID, '_dates_of_sent_mails', false);
                        $n_days = (int) $remind_to_pay_settings['interval'];

                        if ((int) $number_of_sent_mail >= $stopsending) {

                            update_post_meta($the_query->post->ID, '_stop_mails', '1');

                        } elseif (!$dates_of_sent_mail or ((int) end($dates_of_sent_mail) < strtotime('-' . $interval . ' days'))) {

                            update_post_meta($the_query->post->ID, '_number_of_sent_mails', $number_of_sent_mail + 1);
                            add_post_meta($the_query->post->ID, '_dates_of_sent_mails', time(), false);
                            do_action('woocommerce_simple_auction_pay_reminder', $the_query->post->ID);

                        }

                    endwhile;
                    wp_reset_postdata();
                }
            }

            /**
             * Auction paid
             *
             * Checks for a auction product in order to verify that it is paid and assign order id to auction product and auction paid meta
             *
             * @access public
             * @param  int, string, string
             * @return void
             */
            function auction_payed($order_id, $old_status, $new_status) 
            {

                if ($new_status != 'completed') {
                    return false;
                }

                $order = new WC_Order($order_id);

                if ($order) {
                    $order_items = $order->get_items();

                    if ($order_items) {

                        foreach ($order_items as $item_id => $item) {

                            $item_meta     = method_exists($order, 'wc_get_order_item_meta') ? $order->wc_get_order_item_meta($item_id) : $order->get_item_meta($item_id);
                            $product_data = wc_get_product($item_meta["_product_id"][0]);
                            if (method_exists($product_data, 'get_type') && $product_data->get_type() == 'auction') {
                                 update_post_meta($item_meta["_product_id"][0], '_auction_payed', 1, true);
                                 update_post_meta($item_meta["_product_id"][0], '_order_id', $order_id, true);
                                 update_post_meta($item_meta["_product_id"][0], '_stop_mails', '1');
                            }
                        }
                    }
                }
            }

            /**
             * Auction order
             *
             * Checks for auction product in order and assign order id to auction product
             *
             * @access public
             * @param  int, array
             * @return void
             */
            function auction_order($order_id, $posteddata) 
            {
                $order = new WC_Order($order_id);

                if ($order) {
                    $order_items = $order->get_items();
                    if ($order_items) {

                        foreach ($order_items as $item_id => $item) {
                            $item_meta     = method_exists($order, 'wc_get_order_item_meta') ? $order->wc_get_order_item_meta($item_id) : $order->get_item_meta($item_id);
                            $product_data = wc_get_product($item_meta["_product_id"][0]);
                            if (method_exists($product_data, 'get_type') && $product_data->get_type() == 'auction') {
                                update_post_meta($order_id, '_auction', '1');
                                update_post_meta($item_meta["_product_id"][0], '_order_id', $order_id, true);
                                update_post_meta($item_meta["_product_id"][0], '_stop_mails', '1');
                                if (!$product_data->is_finished()) {
                                    update_post_meta($item_meta["_product_id"][0], '_auction_closed', '3');
                                    update_post_meta($item_meta["_product_id"][0], '_buy_now', '1');
                                    update_post_meta($item_meta["_product_id"][0], '_auction_dates_to', date('Y-m-h h:s'));
                                    do_action('woocommerce_simple_auction_close_buynow', $product_data->id);
                                }
                            }
                        }
                    }
                }
            }

            /**
             * Delete logs when auction is deleted
             *
             * @access public
             * @param  string
             * @return void
             */
            function del_auction_logs($post_id) 
            {
                global $wpdb;

                $logs = $wpdb->get_var($wpdb->prepare('SELECT auction_id FROM ' . $wpdb->prefix . 'simple_auction_log WHERE auction_id = %d', $post_id));

                if ($logs) {
                    return $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'simple_auction_log WHERE auction_id = %d', $post_id));
                }

                return true;
            }

            /**
             * Ajax finish auction
             *
             * Function for finishing auction with ajax when countdown is down to zero
             *
             * @access public
             * @param  array
             * @return string
             */
            function ajax_finish_auction() 
            {

                if (isset($_POST["post_id"])) {
                    sleep(3);

                    $product_data = wc_get_product($_POST["post_id"]);

                    if ($product_data->is_closed()) {

                        if (isset($_POST["ret"]) && $_POST["ret"] != '0') {

                            if ($product_data->is_reserved()) {
                                if (!$product_data->is_reserve_met()) {

                                        _e("Reserve price has not been met", 'wc_simple_auctions');
                                        die();
                                }
                            }
                            if ($product_data->get_auction_current_bider()) {
                                    echo "<div>";
                                    printf(__("Winning bid is %s by %s.", 'wc_simple_auctions'), wc_price($product_data->get_curent_bid()), get_userdata($product_data->get_auction_current_bider())->display_name);
                                    echo "</div>";
                            } else {
                                    echo "<div>";
                                    _e("There were no bids for this auction.", 'wc_simple_auctions');
                                        echo "</div>";
                                    die();
                            }
                        }

                    } else {

                        echo "<div>";
                        if (isset($_POST["future"]) && $_POST["future"] == 'true') {
                                printf(__("Auction has started please refresh page.", 'wc_simple_auctions'));
                        } elseif (isset($_POST["future"]) && $_POST["future"] == 'false') {
                            printf(__("Auction has finished please refresh page.", 'wc_simple_auctions'));
                        } else {
                             printf(__("Please refresh page.", 'wc_simple_auctions'));
                        }

                        echo "</div>";
                    }
                }
                            die();
            }

            /**
             * Ajax watch list auction
             *
             * Function for adding or removing auctions to wishlist
             *
             * @access public
             * @param  array
             * @return string
             */
            function ajax_watchlist_auction() 
            {

                            

                if (is_user_logged_in()) {
                    global $product;
                    $post_id = intval($_GET["post_id"]);
                    $user_ID = get_current_user_id();
                    $product = wc_get_product($post_id);


                    if ($product) {

                        if ($product->is_user_watching()) {
                            delete_post_meta($post_id, '_auction_watch', $user_ID);
                            delete_user_meta($user_ID, '_auction_watch', $post_id);
                        } else {

                             add_post_meta($post_id, '_auction_watch', $user_ID);
                             add_user_meta($user_ID, '_auction_watch', $post_id);
                        }
                        wc_get_template('single-product/watchlist-link.php');
                    }

                } else {

                    echo "<p>";
                    printf(__('Sorry, you must be logged in to add auction to watchlist. <a href="%s" class="button">Login &rarr;</a>', 'wc_simple_auctions'), get_permalink(wc_get_page_id('myaccount')));
                    echo "</p>";
                }

                            exit;
            }

            /**
             * Ajax get price for auctions
             *
             * Function for getiing pices changes for auctions
    *
             * @access public
             * @param  array
             * @return json
             */
            function get_price_for_auctions() 
            {
                                $return = "";

                if (isset($_POST["data"])) {

                    $data = $_POST["data"];

                    foreach ($data as $key => $value) {
                        $product_data = wc_get_product($key);

                        if($product_data->get_auction_sealed() != 'yes') {

                            if ($value['status'] == 'running') {
                                if ($product_data->is_closed()) {
                                    $return[$key]['curent_bid'] = $product_data->get_price_html();
                                    $return[$key]['curent_bider'] = $product_data->get_auction_current_bider();
                                    if ($product_data->is_reserved() === true) {
                                        if ($product_data->is_reserve_met() === false) {
                                            $return[$key]['reserve'] = apply_filters('reserve_bid_text', __("Reserve price has not been met", 'wc_simple_auctions'));
                                        } elseif ($product_data->is_reserve_met() === true) {
                                            $return[$key]['reserve'] = apply_filters('reserve_met_bid_text', __("Reserve price has been met", 'wc_simple_auctions'));
                                        }

                                    }
                                } else {
                                    if ($value['price'] != $product_data->get_auction_current_bid()) {
                                        $return[$key]['curent_bid'] = $product_data->get_price_html();
                                        $return[$key]['curent_bider'] = $product_data->get_auction_current_bider();
                                        $return[$key]['bid_value'] = $product_data->bid_value();
                                        $return[$key]['timer'] = $product_data->get_seconds_remaining();
                                        $return[$key]['activity'] = $product_data->auction_history_last($key);
                                        if ($product_data->is_reserved() === true) {
                                            if ($product_data->is_reserve_met() === false) {
                                                $return[$key]['reserve'] = apply_filters('reserve_bid_text', __("Reserve price has not been met", 'wc_simple_auctions'));
                                            } elseif ($product_data->is_reserve_met() === true) {
                                                $return[$key]['reserve'] = apply_filters('reserve_met_bid_text', __("Reserve price has been met", 'wc_simple_auctions'));
                                            }

                                        }

                                    }
                                }

                            } elseif ($value['status'] == 'future') {
                                if ($product_data->is_started()) {
                                    $return[$key]['curent_bid'] = $product_data->get_price_html();
                                }

                            }
                        }

                    }
                }
                wp_send_json($return);
                die();
            }

            /**
             * Ajax delete bid
             *
             * Function for deleting bid in wp admin
             *
             * @access public
             * @param  array
             * @return string
             */
            function wp_ajax_delete_bid() 
            {

                            global $wpdb;

                if (!current_user_can('edit_product', $_POST["postid"])) {
                    die();
                }

                if ($_POST["postid"] && $_POST["logid"]) {
                     $product_data = wc_get_product($_POST["postid"]);
                     $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "simple_auction_log WHERE id=%d", $_POST["logid"]));
                    if (!is_null($log)) {
                        if ($product_data->get_auction_type() == 'normal') {
                            if (($log->bid == $product_data->get_auction_current_bid()) && ($log->userid == $product_data->get_auction_current_bider())) {

                                if($product_data->get_auction_relisted()) {
                                    $time = 'AND `date` > \''.$product_data->get_auction_relisted().'\'';
                                } else{
                                      $time = ' ';
                                }

                                    $newbid = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "simple_auction_log WHERE auction_id =%d ". $time." ORDER BY  `date` desc , `bid`  desc LIMIT 1, 1 ", $_POST["postid"]));
                                if (!is_null($newbid)) {
                                        update_post_meta($_POST["postid"], '_auction_current_bid', $newbid->bid);
                                        update_post_meta($_POST["postid"], '_auction_current_bider', $newbid->userid);
                                        delete_post_meta($_POST["postid"], '_auction_max_bid');
                                        delete_post_meta($_POST["postid"], '_auction_max_current_bider');
                                        do_action('woocommerce_simple_auction_delete_bid', array('product_id' => $_POST["postid"], 'delete_user_id' => $log->userid, 'new_max_bider_id ' => $newbid->userid));
                                } else {
                                            delete_post_meta($_POST["postid"], '_auction_current_bid');
                                        delete_post_meta($_POST["postid"], '_auction_current_bider');
                                        delete_post_meta($_POST["postid"], '_auction_max_bid');
                                            delete_post_meta($_POST["postid"], '_auction_max_current_bider');
                                        do_action('woocommerce_simple_auction_delete_bid', array('product_id' => $_POST["postid"], 'delete_user_id' => $log->userid, 'new_max_bider_id ' => false));
                                }
                                    $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "simple_auction_log WHERE id= %d", $_POST["logid"]));
                                    update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data->get_auction_bid_count() - 1));
                                    $return['action'] = 'deleted';

                            } else {
                                   $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "simple_auction_log WHERE id= %d", $_POST["logid"]));
                                   update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data->get_auction_bid_count() - 1));
                                   $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "simple_auction_log WHERE id= %d", $_POST["logid"]));
                                   do_action('woocommerce_simple_auction_delete_bid', array('product_id' => $_POST["postid"], 'delete_user_id' => $log->userid));
                                   $return['action'] = 'deleted';

                            }

                        } elseif ($product_data->get_auction_type() == 'reverse') {
                            if (($log->bid == $product_data->get_auction_current_bid()) && ($log->userid == $product_data->get_auction_current_bider())) {

                                if($product_data->get_auction_relisted()) {
                                    $time = 'AND `date` > \''.$product_data->get_auction_relisted().'\'';
                                } else{
                                    $time = ' ';
                                }

                                    $newbid = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "simple_auction_log WHERE auction_id =%d  ". $time."  ORDER BY  `date` desc , `bid`  asc desc LIMIT 1, 1 ", $_POST["postid"]));
                                if (!is_null($newbid)) {
                                        update_post_meta($_POST["postid"], '_auction_current_bid', $newbid->bid);
                                        update_post_meta($_POST["postid"], '_auction_current_bider', $newbid->userid);
                                        delete_post_meta($_POST["postid"], '_auction_max_bid');
                                        delete_post_meta($_POST["postid"], '_auction_max_current_bider');
                                        do_action('woocommerce_simple_auction_delete_bid', array('product_id' => $_POST["postid"], 'delete_user_id' => $log->userid, 'new_max_bider_id ' => $newbid->userid));
                                } else {
                                            delete_post_meta($_POST["postid"], '_auction_current_bid');
                                        delete_post_meta($_POST["postid"], '_auction_current_bider');
                                        do_action('woocommerce_simple_auction_delete_bid', array('product_id' => $_POST["postid"], 'delete_user_id' => $log->userid, 'new_max_bider_id ' => false));
                                }
                                    $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "simple_auction_log WHERE id= %d", $_POST["logid"]));
                                    update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data->get_auction_bid_count() - 1));
                                    $return['action'] = 'deleted';

                            } else {
                                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "simple_auction_log  WHERE id= %d", $_POST["logid"]));
                                update_post_meta($_POST["postid"], '_auction_bid_count', absint($product_data->get_auction_bid_count() - 1));
                                do_action('woocommerce_simple_auction_delete_bid', array('product_id' => $_POST["postid"], 'delete_user_id' => $log->userid));
                                $return['action'] = 'deleted';

                            }

                        }
                           $product = wc_get_product($_POST["postid"]);
                        if ($product) {
                            $return['auction_current_bid'] = wc_price($product->get_curent_bid());
                            if($product->get_auction_current_bider()) {
                                $return['auction_current_bider'] = '<a href="' . get_edit_user_link($product->get_auction_current_bider()) . '">' . get_userdata($product->get_auction_current_bider())->display_name . '</a>';    
                            }
                                                    
                        }

                        if (isset($return)) {
                            wp_send_json($return);
                        }

                            exit;

                    }

                }
                            $return['action'] = 'failed';
                if (isset($return)) {
                     wp_send_json($return);
                }

                            exit;

            }

                    /**
                     * Ajax remove reserved price
                     *
                     * Function for removing reserved price
                     *
                     * @access public
                     * @param  array
                     * @return string
                     */
            function wp_ajax_remove_reserve_price() 
            {
                $post_id = intval($_POST["postid"]);
                if (!current_user_can('edit_product', $post_id)) {
                        die();
                }

                $product_data = wc_get_product($post_id);

                if ($product_data) {

                    if ($product_data->is_closed()) {
                        if ($product_data->get_auction_closed() == '1') {
                            if ($product_data->get_auction_fail_reason() == '2') {

                                    delete_post_meta($post_id, '_auction_reserved_price');
                                    delete_post_meta($post_id, '_auction_closed');
                                    delete_post_meta($post_id, '_auction_fail_reason');
                                    $return['succes'] = __('Reserve price removed! Please refresh page.', 'wc_simple_auctions');
                                if (isset($return)) {
                                        wp_send_json($return);
                                }

                                    exit;
                            }
                        }

                    } else {
                            $return['error'] = __('Auction is still active!', 'wc_simple_auctions');
                    }

                }
                $return['error'] = __('Reserve price not removed', 'wc_simple_auctions');
                if (isset($return)) {
                        wp_send_json($return);
                }

                exit;

            }

            /**
             * Duplicate post
             *
             * Clear metadata when copy auction
             *
             * @access public
             * @param  array
             * @return string
             */
            function woocommerce_duplicate_product($postid) 
            {

                            $product = wc_get_product($postid);

                if (!$product) {
                    return false;
                }

                if (!(method_exists($product_data, 'get_type') && $product_data->get_type() == 'auction') ) {
                    return false;
                }

                            delete_post_meta($postid, '_auction_current_bid');
                            delete_post_meta($postid, '_auction_current_bider');
                            delete_post_meta($postid, '_auction_max_bid');
                            delete_post_meta($postid, '_auction_max_current_bider');
                            delete_post_meta($postid, '_auction_bid_count');
                            delete_post_meta($postid, '_auction_closed');
                            delete_post_meta($postid, '_auction_started');
                            delete_post_meta($postid, '_auction_fail_reason');
                            delete_post_meta($postid, '_auction_dates_to');
                            delete_post_meta($postid, '_auction_dates_from');
                            delete_post_meta($postid, '_order_id');
                            delete_post_meta($postid, '_stop_mails');
                            delete_post_meta($postid, '_auction_payed');
                            delete_post_meta($postid, '_stock_status');
                            update_post_meta($postid, '_stock_status', 'instock');
                            update_post_meta($postid, '_stock', '1');

                            return true;
            }

            /**
             * Cron action
             *
             * Checks for a valid request, check auctions and closes auction if finished
             *
             * @access public
             * @param  bool $url (default: false)
             * @return void
             */
            function simple_auctions_cron($url = false) 
            {

                if (empty($_REQUEST['auction-cron'])) {
                    return;
                }

                if ($_REQUEST['auction-cron'] == 'check') {
                    update_option('Woocommerce_simple_auction_cron_check', 'yes');
                    set_time_limit(0);
                    ignore_user_abort(1);
                    global $woocommerce;
                    $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => '-1',
                    'meta_query' => array(
                    'relation' => 'AND', // Optional, defaults to "AND"
                    array(
                    'key' => '_auction_closed',
                    'compare' => 'NOT EXISTS',
                    ),
                    array(
                    'key' => '_auction_dates_to',
                    'compare' => 'EXISTS',
                    ),

                    ),
                    'meta_key' => '_auction_dates_to',
                    'orderby' => 'meta_value',
                    'order' => 'ASC',
                    'tax_query' => array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'auction')),
                    'auction_arhive' => true,
                    'show_past_auctions' => true,

                    );
                    for ($i = 0; $i < 3; $i++) {
                        $the_query = new WP_Query($args);
                        $time = microtime(1);

                        if ($the_query->have_posts()) {
                            while ($the_query->have_posts()): $the_query->the_post();

                                $product_data = wc_get_product($the_query->post->ID);

                                if (method_exists($product_data, 'get_type') && $product_data->get_type() == 'auction' ) {

                                    $product_data->is_closed();
                                }
                            endwhile;
                        }
                        $time = microtime(1) - $time;
                        $i < 3 and sleep(20 - $time);
                    }
                }
                if ($_REQUEST['auction-cron'] == 'mails') {
                    update_option('Woocommerce_simple_auction_cron_mail', 'yes');
                    set_time_limit(0);
                    ignore_user_abort(1);
                    $remind_to_pay_settings = get_option('woocommerce_remind_to_pay_settings');
                    if ($remind_to_pay_settings['enabled'] != 'yes') {
                        exit();
                    }

                    $interval = (isset($remind_to_pay_settings['interval'])) ? (int) $remind_to_pay_settings['interval'] : 7;
                    $stopsending = (isset($remind_to_pay_settings['stopsending'])) ? (int) $remind_to_pay_settings['stopsending'] : 5;
                    $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => '100	',
                    'show_past_auctions' => true,
                    'tax_query' => array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'auction')),
                    'meta_query' => array(
                    'relation' => 'AND',
                    array(
                                'key' => '_auction_closed',
                                'value' => '2',
                    ),
                    array(
                    'key' => '_auction_payed',
                    'compare' => 'NOT EXISTS',
                    ),
                    array(
                    'key' => '_stop_mails',
                    'compare' => 'NOT EXISTS',
                    ),
                    ),
                    'auction_arhive' => true,
                    'show_past_auctions' => true,
                    );

                    $the_query = new WP_Query($args);

                    if ($the_query->have_posts()) {

                        while ($the_query->have_posts()):

                            $the_query->the_post();
                            $number_of_sent_mail = get_post_meta($the_query->post->ID, '_number_of_sent_mails', true);
                            $dates_of_sent_mail = get_post_meta($the_query->post->ID, '_dates_of_sent_mails', false);
                            $n_days = (int) $remind_to_pay_settings['interval'];

                            $product_data = wc_get_product($the_query->post->ID);

                            if ((int) $number_of_sent_mail >= $stopsending) {

                                 update_post_meta($the_query->post->ID, '_stop_mails', '1');

                            } elseif ((!$dates_of_sent_mail or ((int) end($dates_of_sent_mail) < strtotime('-' . $interval . ' days'))) AND (strtotime($product_data->get_auction_dates_to()) < strtotime('-' . $interval . ' days'))) {

                                update_post_meta($the_query->post->ID, '_number_of_sent_mails', $number_of_sent_mail + 1);
                                add_post_meta($the_query->post->ID, '_dates_of_sent_mails', time(), false);

                                do_action('woocommerce_simple_auction_pay_reminder', $the_query->post->ID);
                            }

                        endwhile;
                                            wp_reset_postdata();
                    }
                }
                if ($_REQUEST['auction-cron'] == 'relist') {
                    update_option('Woocommerce_simple_auction_cron_relist', 'yes');
                    set_time_limit(0);
                    ignore_user_abort(1);
                    $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => '200',
                    'show_past_auctions' => true,
                    'tax_query' => array(array('taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'auction')),
                    'meta_query' => array(
                    'relation' => 'AND',
                    array(
                    'key' => '_auction_closed',
                    'compare' => 'EXISTS',
                    ),
                    array(
                    'key' => '_auction_payed',
                    'compare' => 'NOT EXISTS',
                    ),
                    array(
                    'key' => '_auction_automatic_relist',
                    'value' => 'yes',
                    ),
                    ),
                    'auction_arhive' => true,
                    'show_past_auctions' => true,
                    );

                    $the_query = new WP_Query($args);

                    if ($the_query->have_posts()) {

                        while ($the_query->have_posts()){

                            $the_query->the_post();
                            $this->relist_auction($the_query->post->ID);

                        }

                                            wp_reset_postdata();
                    }
                }

                exit;
            }

            /**
             * Sync meta with wpml
             *
             * Sync meta trough translated post
             *
             * @access public
             * @param  bool (default: false)
             * @return void
             */
            function sync_metadata_wpml($data) 
            {

                global $sitepress;

                $deflanguage = $sitepress->get_default_language();
                if (is_array($data)) {
                    $product_id = $data['product_id'];
                } else {
                    $product_id = $data;
                }

                $meta_values = get_post_meta($product_id);
                $orginalid = $sitepress->get_original_element_id($product_id, 'post_product');
                $trid = $sitepress->get_element_trid($product_id, 'post_product');
                $all_posts = $sitepress->get_element_translations($trid, 'post_product');

                unset($all_posts[$deflanguage]);

                if (!empty($all_posts)) {
                    foreach ($all_posts as $key => $translatedpost) {
                        if (isset($meta_values['_auction_current_bid'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_current_bid', $meta_values['_auction_current_bid'][0]);
                        }

                        if (isset($meta_values['_auction_current_bider'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_current_bider', $meta_values['_auction_current_bider'][0]);
                        }

                        if (isset($meta_values['_auction_max_bid'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_max_bid', $meta_values['_auction_max_bid'][0]);
                        }

                        if (isset($meta_values['_auction_max_current_bider'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_max_current_bider', $meta_values['_auction_max_current_bider'][0]);
                        }

                        if (isset($meta_values['_auction_bid_count'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_bid_count', $meta_values['_auction_bid_count'][0]);
                        }

                        if (isset($meta_values['_auction_closed'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_closed', $meta_values['_auction_closed'][0]);
                        }

                        if (isset($meta_values['_auction_started'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_started', $meta_values['_auction_started'][0]);
                        }

                        if (isset($meta_values['_auction_fail_reason'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_fail_reason', $meta_values['_auction_fail_reason'][0]);
                        }

                        if (isset($meta_values['_auction_dates_to'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_dates_to', $meta_values['_auction_dates_to'][0]);
                        }

                        if (isset($meta_values['_auction_dates_from'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_dates_from', $meta_values['_auction_dates_from'][0]);
                        }

                        if (isset($meta_values['_order_id'][0])) {
                            update_post_meta($translatedpost->element_id, '_order_id', $meta_values['_order_id'][0]);
                        }

                        if (isset($meta_values['_stop_mails'][0])) {
                            update_post_meta($translatedpost->element_id, '_stop_mails', $meta_values['_stop_mails'][0]);
                        }

                        if (isset($meta_values['_auction_item_condition'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_item_condition', $meta_values['_auction_item_condition'][0]);
                        }

                        if (isset($meta_values['_auction_type'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_type', $meta_values['_auction_type'][0]);
                        }

                        if (isset($meta_values['_auction_proxy'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_proxy', $meta_values['_auction_proxy'][0]);
                        }

                        if (isset($meta_values['_auction_start_price'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_start_price', $meta_values['_auction_start_price'][0]);
                        }

                        if (isset($meta_values['_auction_bid_increment'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_bid_increment', $meta_values['_auction_bid_increment'][0]);
                        }

                        if (isset($meta_values['_auction_reserved_price'][0])) {
                            update_post_meta($translatedpost->element_id, '_auction_reserved_price', $meta_values['_auction_reserved_price'][0]);
                        }

                        if (isset($meta_values['_regular_price'][0])) {
                            update_post_meta($translatedpost->element_id, '_regular_price', $meta_values['_regular_price'][0]);
                        }

                        if (isset($meta_values['_auction_wpml_language'][0])) {
                            update_post_meta($translatedpost->element_id, '_lottery_wpml_language', $meta_values['_auction_wpml_language'][0]);
                        }

                    }
                }
            }

            /**
             * Add last alnguage in use to custom meta of auction
             *
             * @access public
             * @param  int
             * @return void
             */
            function add_language_wpml_meta($data) 
            {
                $language = isset($_SESSION['wpml_globalcart_language']) ? $_SESSION['wpml_globalcart_language'] : ICL_LANGUAGE_CODE;
                update_post_meta($data['product_id'], '_lottery_wpml_language', $language);
            }

            function change_email_language($product_id) 
            {

                            global $sitepress, $woocommerce;

                            $lang = get_post_meta($product_id, '_lottery_wpml_language', true);

                if ($lang) {
                    $sitepress->switch_lang($lang, true);
                    unload_textdomain('woocommerce');
                    unload_textdomain('default');
                    wc()->load_plugin_textdomain();
                    load_default_textdomain();
                    global $wp_locale;
                    $wp_locale = new WP_Locale();
                }
            }

            /**
                         * Add options page
                         *
             * @access public
             * @param  void
             * @return void
            */
            public function add_auction_activity_page() 
            {

                $hook = add_submenu_page('woocommerce', 'Auctions activity', 'Auctions Activity', 'manage_woocommerce', 'auctions-activity', array($this, 'create_admin_page'));

                add_action("load-$hook", array($this, 'log_list_add_options'));
            }

            public function log_list_add_options() 
            {
                $option = 'per_page';
                $args = array(
                'label' => 'Logs',
                'default' => 20,
                'option' => 'logs_per_page',
                );
                add_screen_option($option, $args);
            }
            
            function wc_simple_auctions_set_option($status, $option, $value) 
            {
                if ('logs_per_page' == $option) {
                    return $value;
                }

                return $status;
            }

            /**
                         * Options page callback
                         *
             * @access public
             * @param  void
             * @return void
            */
            public function create_admin_page() 
            {

                            printf('<div class="wrap" id="wpse-list-table"><h2>%s</h2>', __('Auction activity', 'wc_simple_auctions'));
                            echo '<form id="wpse-list-table-form" method="get">';

                            $wp_list_table = new wc_auction_software_List_Table();
                            $wp_list_table->prepare_items();
                            $wp_list_table->search_box('search', 'search_id');
                foreach ($_GET as $key => $value) {
                    if ('s' !== $key) {
                        echo "<input type='hidden' name='$key' value='$value' />";
                    }
                }

                            $wp_list_table->display();
                            echo '</form>';
                            echo '</div>';
                            echo '<div class="clear"></div>';

            }

            /**
                         * Fix for auction base page title
             *
             * @param  string
             * @return string
             */
            function auction_page_title($title) 
            {
                if (get_query_var('is_auction_archive', false) == 'true') {

                    $auction_page_id = wc_get_page_id('auction');
                    $title = get_the_title($auction_page_id);

                }

                   return $title;
            }

            /**
             * Fix for auction base page breadcrumbs
             *
             * @param  string
             * @return string
             */
            public function woocommerce_get_breadcrumb($crumbs, $WC_Breadcrumb) 
            {

                if (get_query_var('is_auction_archive', false) == 'true') {

                    $auction_page_id = wc_get_page_id('auction');
                    $crumbs[1] = array(get_the_title($auction_page_id), get_permalink($auction_page_id));
                }

                   return $crumbs;
            }

            /**
            * Add ordering for auctions
            *
            * @param  array
            * @return array
            */
            function auction_woocommerce_catalog_orderby($data) 
            {

                            $simple_auctions_dont_mix_shop = get_option('simple_auctions_dont_mix_shop');

                            $simple_auctions_dont_mix_cat = get_option('simple_auctions_dont_mix_cat');
                            $simple_auctions_dont_mix_tag = get_option('simple_auctions_dont_mix_tag');

                               $is_auction_archive  =  get_query_var('is_auction_archive', false);

                if ((is_shop() && $simple_auctions_dont_mix_shop == 'yes')  && $is_auction_archive !== 'true') {
                    return $data;
                }
                if ((is_product_category() && ( $simple_auctions_dont_mix_shop == 'yes' or $simple_auctions_dont_mix_cat == 'yes' ))  && $is_auction_archive !== 'true') {
                     return $data;
                }
                if ((is_product_tag() && ( $simple_auctions_dont_mix_shop == 'yes' or $simple_auctions_dont_mix_tag == 'yes' ))  && $is_auction_archive !== 'true') {
                     return $data;
                }

                            $data['bid_asc'] = __('Sort by current bid: Low to high', 'wc_simple_auctions');
                            $data['bid_desc'] = __('Sort by current bid: High to low', 'wc_simple_auctions');
                            $data['auction_end'] = __('Sort auction by ending soonest', 'wc_simple_auctions');
                            $data['auction_started'] = __('Sort auction by recently started', 'wc_simple_auctions');
                            $data['auction_activity'] = __('Sort auction by most active', 'wc_simple_auctions');

                            return $data;
            }

            /**
             * Remove option for auction ordering for wordpress < 4.2
             *
             * @param  array
             * @return array
             */
            function remove_ordering_setings($data) 
            {
                global $wp_version;

                if (version_compare($wp_version, '4.2', '<=')) {

                    unset($data['1']);

                }

                return $data;

            }

            /**
             * Fix active class in nav for auction  page.
             *
             * @param  array $menu_items
             * @return array
             */
            function wsa_nav_menu_item_classes($menu_items) 
            {

                if (!get_query_var('is_auction_archive', false)) {
                    return $menu_items;
                }

                $auction_page = (int) wc_get_page_id('auction');

                                
                foreach ((array) $menu_items as $key => $menu_item) {

                    $classes = (array) $menu_item->classes;

                    // Unset active class for blog page

                    $menu_items[$key]->current = false;

                    if (in_array('current_page_parent', $classes)) {
                        unset($classes[array_search('current_page_parent', $classes)]);
                    }

                    if (in_array('current-menu-item', $classes)) {
                        unset($classes[array_search('current-menu-item', $classes)]);
                    }

                    if (in_array('current_page_item', $classes)) {
                        unset($classes[array_search('current_page_item', $classes)]);
                    }
                
                    // Set active state if this is the shop page link
                    if ($auction_page == $menu_item->object_id && 'page' === $menu_item->object) {
                        $menu_items[$key]->current = true;
                        $classes[] = 'current-menu-item';
                        $classes[] = 'current_page_item';

                    }

                    $menu_items[$key]->classes = array_unique($classes);

                }

                return $menu_items;
            }

            /**
             * Track auction views
             *
             * @param  void
             * @return int
             */
            function wsa_track_auction_view() 
            {

                if (!is_singular('product') || !is_active_widget(false, false, 'recently_viewed_auctions', true)) {
                    return;
                }

                global $post;

                if (empty($_COOKIE['woocommerce_recently_viewed_auctions'])) {
                    $viewed_products = array();
                } else {
                    $viewed_products = (array) explode('|', $_COOKIE['woocommerce_recently_viewed_auctions']);
                }

                if (!in_array($post->ID, $viewed_products)) {
                    $viewed_products[] = $post->ID;
                }

                if (sizeof($viewed_products) > 15) {
                    array_shift($viewed_products);
                }

                // Store for session only
                wc_setcookie('woocommerce_recently_viewed_auctions', implode('|', $viewed_products));
            }

            /**
             * Add wpml support for auction base page
             *
             * @param  int
             * @return int        
             */
            function auctionbase_page_wpml($page_id)
            {

                            global $sitepress;

                if (function_exists('icl_object_id') ) {
                    $id = icl_object_id($page_id, 'page', false);

                }  else {
                    $id = $page_id;
                }
                            return $id;

            }


            /**
             * Set is filtered is true to skip displaying categories only on page
             *
             * @access public
             * @return bolean
             */

            function add_is_filtered($id)
            {

                return true;

            }    

            
            function auction_filter_wp_title( $title ) 
            {
                
                 global $paged, $page;
                    
                if (!get_query_var('is_auction_archive', false)) {
                    return $title;
                }

                $auction_page_id = wc_get_page_id('auction');
                $title = get_the_title($auction_page_id);
                 
                 return $title;
            }

            /**
             * Get main product id for multilanguage purpose
             *
             * @access public
             * @return int
             */

            function get_main_wpml_id($id)
            {

                global $sitepress;

                if (function_exists('icl_object_id') && method_exists($sitepress, 'get_default_language') ) { 
                    $id = icl_object_id($id, 'page', false);
                }
                                  

                return $id;

            }

             /**
             * Translate auction base page url
             */
            function translate_ls_auction_url($languages, $debug_mode = false) 
            {
                global $sitepress;
                global $wp_query;
                

                $auction_page = (int) wc_get_page_id('auction');
                

                foreach ($languages as $language) {
                    // shop page
                    // obsolete?
                    if (get_query_var('is_auction_archive', false) || $debug_mode ) {

                       
                         $sitepress->switch_lang($language['language_code']);
                         $url = get_permalink(apply_filters('translate_object_id', $auction_page, 'page', true, $language['language_code']));
                         $sitepress->switch_lang();
                         $languages[$language['language_code']]['url'] = $url;

                    }
                }

                return $languages;
            }

            function add_redirect_previous_page()
            {
                echo '<input type="hidden" name="redirect" value="'.$_SERVER["HTTP_REFERER"].'" >';
            }    


        }
    }

    // Instantiate plugin class and add it to the set of globals.
    $woocommerce_auctions = new WooCommerce_simple_auction();
    register_activation_hook(__FILE__, array($woocommerce_auctions, 'install'));
    register_deactivation_hook(__FILE__, array($woocommerce_auctions, 'deactivation'));

    function simple_auction_send_reminders_email() 
    {
                global $woocommerce_auctions;
        $woocommerce_auctions->send_reminders_email();
    }

} else {

    add_action('admin_notices', 'wc_auction_error_notice');
    function wc_auction_error_notice() 
    {
        global $current_screen;
        if ($current_screen->parent_base == 'plugins') {
            echo '<div class="error"><p>WooCommerce Simple Auctions ' . __('requires <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="' . admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce') . '" target="_blank">WooCommerce</a> first.', 'wc_simple_auctions') . '</p></div>';
        }
    }
    $plugin = plugin_basename(__FILE__);
    if (is_plugin_active($plugin)) {
        deactivate_plugins($plugin);
    }
}
