<?php
/**
 * WooCommerce Auctions Query Handler
 *
 * @version		2.0
 * @author 		Prospress
 */
class WCA_Query extends WC_Query {

	public function __construct() {
                add_action( 'init', array( $this, 'add_endpoints' ) );

		add_filter( 'the_title', array( $this, 'change_endpoint_title' ), 11, 1 );

		if ( ! is_admin() ) {
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
			add_filter( 'woocommerce_get_breadcrumb', array( $this, 'add_breadcrumb' ), 10 );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 11 );

			// Inserting your new tab/page into the My Account page.
			add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_items' ) );
			
                        add_action( 'woocommerce_account_auctions_endpoint', array( $this, 'endpoint_content' ) );
                        
                        add_action( 'woocommerce_account_my_auctions_endpoint', array( $this, 'my_auctions_endpoint_content' ) );
                        
                        add_action( 'woocommerce_account_my_watchlist_endpoint', array( $this, 'my_watchlist_endpoint_content' ) );
		
                }

		$this->init_query_vars();
	}

	/**
	 * Init query vars by loading options.
	 *
	 * @since 2.0
	 */
	public function init_query_vars() {
            $this->query_vars = array(
			'view-auctions' => get_option( 'woocommerce_myaccount_view_auctions_endpoint', 'view-auctions' ),
		);
		if ( ! WooCommerce_simple_auction::is_woocommerce_pre( '2.6' ) ) {
			$this->query_vars['auctions'] = get_option( 'woocommerce_myaccount_auctions_endpoint', 'auctions' );
               	}
	}

	/**
	 * Adds breadcrumb when viewing Auctions
	 *
	 * @param  array $crumbs
	 * @return array $crumbs
	 */
	public function add_breadcrumb( $crumbs ) {

		foreach ( $this->query_vars as $key => $query_var ) {
			if ( $this->is_query( $query_var ) ) {
				$crumbs[] = array( $this->get_endpoint_title( $key ) );
			}
		}
		return $crumbs;
	}

	/**
	 * Changes page title on view auctions page
	 *
	 * @param  string $title original title
	 * @return string        changed title
	 */
	public function change_endpoint_title( $title ) {

		if ( in_the_loop() && is_account_page() ) {
			foreach ( $this->query_vars as $key => $query_var ) {
				if ( $this->is_query( $query_var ) ) {
					$title = $this->get_endpoint_title( $key );

					// unhook after we've returned our title to prevent it from overriding others
					remove_filter( 'the_title', array( $this, __FUNCTION__ ), 11 );
				}
			}
		}
		return $title;
	}

	/**
	 * Set the auctions page title when viewing a auctions.
	 *
	 * @since 2.0
	 * @param $title
	 */
	public function get_endpoint_title( $endpoint ) {
		global $wp;

		switch ( $endpoint ) {
			case 'auctions':
				$title = __( 'Auctions', 'wc_auction_software' );
				break;
			default:
				$title = '';
				break;
		}
		return $title;
	}

	/**
	 * Insert the new endpoint into the My Account menu.
	 *
	 * @param array $items
	 * @return array
	 */
	public function add_menu_items( $menu_items ) {
                // Add our menu item after the Orders tab if it exists, otherwise just add it to the end
		if ( array_key_exists( 'orders', $menu_items ) ) {
			$menu_items = $this->wca_array_insert_after( 'orders', $menu_items, 'auctions', __( 'Auctions', 'wc_auction_software' ) );
            	} else {
			$menu_items['auctions'] = __( 'Add Auction', 'wc_auction_software' );
            	}
		return $menu_items;
	}

	/**
	 * Endpoint HTML content.
	 */
	public function endpoint_content() {
            wc_get_template( 'myaccount/auctions.php', array(), '', plugin_dir_path(WooCommerce_simple_auction::$plugin_file ) . 'templates/' );
	}

	/**
	 * Check if the current query is for a type we want to override.
	 *
	 * @param  string $query_var the string for a query to check for
	 * @return bool
	 */
	protected function is_query( $query_var ) {
		global $wp;

		if ( is_main_query() && is_page() && isset( $wp->query_vars[ $query_var ] ) ) {
			$is_view_auctions_query = true;
		} else {
			$is_view_auctions_query = false;
		}

		return apply_filters( 'wca_query_is_query', $is_view_auctions_query, $query_var );
	}

	/**
	 * Fix for endpoints on the homepage
	 *
	 * Based on WC_Query->pre_get_posts(), but only applies the fix for endpoints on the homepage from it
	 * instead of duplicating all the code to handle the main product query.
	 *
	 * @param mixed $q query object
	 */
	public function pre_get_posts( $q ) {
		// We only want to affect the main query
		if ( ! $q->is_main_query() ) {
			return;
		}

		if ( $q->is_home() && 'page' === get_option( 'show_on_front' ) && absint( get_option( 'page_on_front' ) ) !== absint( $q->get( 'page_id' ) ) ) {
			$_query = wp_parse_args( $q->query );
			if ( ! empty( $_query ) && array_intersect( array_keys( $_query ), array_keys( $this->query_vars ) ) ) {
				$q->is_page     = true;
				$q->is_home     = false;
				$q->is_singular = true;
				$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
				add_filter( 'redirect_canonical', '__return_false' );
			}
		}
	}
        
        public function wca_array_insert_after( $needle, $haystack, $new_key, $new_value ) {

	if ( array_key_exists( $needle, $haystack ) ) {

		$new_array = array();

		foreach ( $haystack as $key => $value ) {

			$new_array[ $key ] = $value;

			if ( $key === $needle ) {
				$new_array[ $new_key ] = $new_value;
			}
		}

		return $new_array;
	}

	return $haystack;
}

}
new WCA_Query();
