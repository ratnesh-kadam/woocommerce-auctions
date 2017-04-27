<?php
/**
 * My Account > Auctions page
 *
 * @author		WPEka
 * @category	WooCommerce Auctions/Templates
 * @version	1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
                global $wpdb;
		$current_user = wp_get_current_user();
		$userId = $current_user->ID;
                
		wp_register_style( 'wpeka-wapff-style', WPEKA_WAPFF_URL .'css/style.css' );
		wp_enqueue_style( 'wpeka-wapff-style' );
		wp_register_style( 'wpeka-wapff-font', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css' );
		wp_enqueue_style( 'wpeka-wapff-font' );
		wp_register_style( 'wpeka-wapff-bootstraps', WPEKA_WAPFF_URL .'css/bootstrap.css' );
		wp_enqueue_style( 'wpeka-wapff-bootstraps' );
		wp_register_style( 'wpeka-wapff-editors', WPEKA_WAPFF_URL .'css/editor.css' );
		wp_enqueue_style( 'wpeka-wapff-editors' );
		wp_register_style( 'wpeka-wapff-jquics', 'http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'wpeka-wapff-jquics' );
		wp_register_script('wpeka-wapff-script', WPEKA_WAPFF_URL .'js/script.js');
		wp_enqueue_script('wpeka-wapff-script');
		wp_register_script('wpeka-wapff-bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js');
		wp_enqueue_script('wpeka-wapff-bootstrap');
		wp_register_script('wpeka-wapff-editor', WPEKA_WAPFF_URL .'js/editor.js');
		wp_enqueue_script('wpeka-wapff-editor');
		wp_register_script('wpeka-wapff-jqui', 'http://code.jquery.com/ui/1.11.4/jquery-ui.js');
		wp_enqueue_script('wpeka-wapff-jqui');
		wp_register_script('wpeka-wapff-timepicker-script', WPEKA_WAPFF_URL .'js/jquery-ui-timepicker-addon.js');
		wp_enqueue_script('wpeka-wapff-timepicker-script');
		
		// upload
		wp_register_style( 'wpeka-wapff-uploads', WPEKA_WAPFF_URL .'upload/style.css' );
		wp_enqueue_style( 'wpeka-wapff-uploads' );
		
		wp_register_script('wpeka-wapff-widget', WPEKA_WAPFF_URL .'upload/jquery.ui.widget.js');
		wp_enqueue_script('wpeka-wapff-widget');
		wp_register_script('wpeka-wapff-load-image', WPEKA_WAPFF_URL .'upload/load-image.all.min.js');
		wp_enqueue_script('wpeka-wapff-load-image');
		wp_register_script('wpeka-wapff-canvas', WPEKA_WAPFF_URL .'upload/canvas-to-blob.min.js');
		wp_enqueue_script('wpeka-wapff-canvas');
		wp_register_script('wpeka-wapff-iframe', WPEKA_WAPFF_URL .'upload/jquery.iframe-transport.js');
		wp_enqueue_script('wpeka-wapff-iframe');
		wp_register_script('wpeka-wapff-fileupload', WPEKA_WAPFF_URL .'upload/jquery.fileupload.js');
		wp_enqueue_script('wpeka-wapff-fileupload');
		wp_register_script('wpeka-wapff-fileuploadprocess', WPEKA_WAPFF_URL .'upload/jquery.fileupload-process.js');
		wp_enqueue_script('wpeka-wapff-fileuploadprocess');
		wp_register_script('wpeka-wapff-fileuploadimage', WPEKA_WAPFF_URL .'upload/jquery.fileupload-image.js');
		wp_enqueue_script('wpeka-wapff-fileuploadimage');
		wp_register_script('wpeka-wapff-fileuploadvalidate', WPEKA_WAPFF_URL .'upload/jquery.fileupload-validate.js');
		wp_enqueue_script('wpeka-wapff-fileuploadvalidate');
		wp_register_script('wpeka-wapff-scriptupload', WPEKA_WAPFF_URL .'upload/scriptupload.js');
		wp_enqueue_script('wpeka-wapff-scriptupload');
		// end upload
		wp_enqueue_style('thickbox'); // call to media files in wp
		wp_enqueue_script('thickbox');
		wp_enqueue_script( 'media-upload'); 
		wp_enqueue_media();
                $mpa = array(
				'author'     =>  $userId,
				'post_type'  => 'product',
                                'post_status'      => 'any'
			);
                	$myProducts = get_posts( $mpa );
                        $only_admin_listing = get_option('simple_auctions_only_admin_listing');

?>
<div class="wpeka_wapff_block" id="wpeka_wapff_block">
				<input id="tab1" type="radio" name="tabs" checked>
				<label for="tab1">My Auctions</label>
<?php	if( 'no' === $only_admin_listing ){ ?>					
				<input id="tab2" type="radio" name="tabs">
				<label for="tab2">Upload Auction</label>
<?php } ?>
<section id="content1">
					<?php
					if($myProducts){
						?>
						<div class="wpeka_myproducts_head">
							<span class="wpeka_myproducts_name">Product Name</span>	
							<span class="wpeka_myproducts_status">Status</span>	
							<span class="wpeka_myproducts_date">Created Date</span>	
							<span class="wpeka_myproducts_view">View</span>	
						</div>
						<?php
						foreach($myProducts as $pro){
							?>
							<div class="wpeka_myproducts_content">
								<span class="wpeka_myproducts_cname"><?php echo $pro->post_title ?></span>	
								<span class="wpeka_myproducts_cstatus"><?php echo $pro->post_status ?></span>	
								<span class="wpeka_myproducts_cdate"><?php echo $pro->post_date ?></span>	
								<span class="wpeka_myproducts_cview"><a target="_blank" href="<?php echo $pro->guid ?>"><i class="fa fa-external-link" aria-hidden="true"></i></a></span>
							</div>
							<?php
						}
					}
					?>
				</section>
<?php	if( 'no' === $only_admin_listing ){ ?>
				<section id="content2">
<div class="wapff_upload_form">
						<div class="wapff_product_name">
							<label for="wapff_product_name">Auction Name</label>
							<input name="wapff_product_name" id="wapff_product_name">
						</div>
						<div class="wapff_product_sdes">
							<label for="wapff_product_sdes">Short Description</label>
							<textarea id="wapff_product_sdes"></textarea>
						</div>
						<div class="wapff_product_des">
							<label for="wapff_product_des">Auction Description</label>
							<textarea id="wapff_product_des"></textarea>
						</div>
						<div class="wapff_product_data">
							<label for="wapff_product_data">Auctions Data</label>
							<div class="wapff_product_data1">
								<div class="wapff_product_virtual">
									<label for="wapff_product_virtual">Virtual</label>
									<input type="checkbox" id="wapff_product_virtual" name="wapff_product_virtual" value="0">
								</div>
								<div class="wapff_product_downloadable">
									<label for="wapff_product_downloadable">Downloadable</label>
									<input type="checkbox" id="wapff_product_downloadable" name="wapff_product_downloadable" value="0">
								</div>
							</div>
							<div class="wapff_product_data2">
								<div class="wapff_product_data2_left">
									<div class="active"><i class="fa" aria-hidden="true"></i> Auction</div>
									<div class=""><i class="fa" aria-hidden="true"></i> Inventory</div>
									<div class="shipping_options"><i class="fa" aria-hidden="true"></i> Shipping</div>
									<div class=""><i class="fa" aria-hidden="true"></i> Advanced</div>
								</div>
								<div class="wapff_product_data2_right">
									<div class="wapff_product_data2_right1 active">
										<div class="product_item_condition">
											<label for="product_item_condition">Item Condition</label>
											<select name="product_item_condition" id="product_item_condition">
												<option value="new">New</option>
												<option value="used">Used</option>
											<select>
										</div>
										<div class="product_auction_type">
											<label for="product_auction_type">Auction Type</label>
											<select name="product_auction_type" id="product_auction_type">
												<option value="new">Simple</option>
												<option value="used">Reverse</option>
											<select>
										</div>
										<div class="product_start_price">
											<label for="product_start_price">Start Price</label>
											<input id="product_start_price" name="product_start_price">
										</div>
										<div class="product_bid_increment">
											<label for="product_bid_increment">Bid Increment</label>
											<input id="product_bid_increment" name="product_bid_increment">
										</div>
										<div class="product_reserve_price">
											<label for="product_reserve_price">Reserve Price</label>
											<input id="product_reserve_price" name="product_reserve_price">
										</div>
										<div class="product_buy_it_now_price">
											<label for="product_buy_it_now_price">Buy it now price</label>
											<input id="product_buy_it_now_price" name="product_buy_it_now_price">
										</div>
                                                                                <div class="product_date_price">
                                                                                        <label for="product_date_price1">Auction Dates</label>
                                                                                        <input type="text" class="short datetimepicker" name="product_date_price1" id="product_date_price1" placeholder="YYYY-MM-DD HH:MM" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
                                                                                        <input type="text" class="short datetimepicker" name="product_date_price2" id="product_date_price2" placeholder="YYYY-MM-DD HH:MM" maxlength="16" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])[ ](0[0-9]|1[0-9]|2[0-4]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])" />
                                                                                </div>
										<div class="product_downloadable" style="display:none">
											<div class="downloadable_files">
												<label>Downloadable Files</label>
                                                                                                <div class="downloadable_files_notify">
													The Name and File URL must separated by commas.
												</div>
												<div class="downloadable_files_head">
													<span class="downloadable_files_head_name">Name</span>
													<span class="downloadable_files_head_url">File URL</span>
												</div>
												<div class="downloadable_files_con">
													<span class="downloadable_files_con_name">
														<textarea name="downloadable_files_con_name[]" class="downloadable_files_con_namevl"></textarea>
													</span>
													<span class="downloadable_files_con_url">
														<textarea name="downloadable_files_con_url[]" class="downloadable_files_con_urlvl"></textarea>
													</span>
												</div>
												
												<div class="downloadable_files_addfile">
													<span id="downloadable_files_addfile">Choose Files</span>
												</div>
											</div>
											<div class="download_limit">
												<label for="download_limit">Download Limit</label>
												<input id="download_limit" name="download_limit">
												<span>Leave blank for unlimited re-downloads.</span>
											</div>
											<div class="download_expiry">
												<label for="download_expiry">Download Expiry</label>
												<input id="download_expiry" name="download_expiry">
												<span>Enter the number of days before a download link expires, or leave blank.</span>
											</div>
											<div class="download_type">
												<label>Download Type</label>
												<select name="download_type" id="download_type">
													<option selected="selected" value="">Standard Product</option>
													<option value="application">Application/Software</option>
													<option value="music">Music</option>
												</select>
												<span>Choose a download type - this controls the schema.</span>
											</div>
										</div>
									</div>
									<div class="wapff_product_data2_right2">
										<div class="product_manage_stock">
											<label for="product_manage_stock">Manage Stock?</label>
											<input type="checkbox" id="product_manage_stock" name="product_manage_stock">
											<span>Enable stock management at product level</span>
										</div>
										<div class="product_stock_qty" style="display:none">
											<label for="product_stock_qty">Stock Qty</label>
											<input id="product_stock_qty" name="product_stock_qty">
										</div>
										<div class="product_allow_backorders" style="display:none">
											<label for="product_allow_backorders">Allow Backorders?</label>
											<select name="product_allow_backorders" id="product_allow_backorders">
												<option value="no">Do not allow</option>
												<option value="notify">Allow, but notify customer</option>
												<option value="yes">Allow</option>
											<select>
										</div>
										<div class="product_stock_status">
											<label for="product_stock_status">Stock status</label>
											<select name="product_stock_status" id="product_stock_status">
												<option value="instock">In stock</option>
												<option value="outofstock">Out of stock</option>
											<select>
										</div>
										<div class="product_sold_individually">
											<label for="product_sold_individually">Sold Individually</label>
											<input type="checkbox" id="product_sold_individually" name="product_sold_individually">
											<span>Enable this to only allow one of this item to be bought in a single order</span>
										</div>
									</div>
									<div class="wapff_product_data2_right3">
										<div class="product_weight">
											<label for="product_weight">Weight(lbs)</label>
											<input id="product_weight" name="product_weight">
										</div>
										<div class="product_dimensions">
											<label for="product_dimensions_length">Dimensions (in)</label>
											<input id="product_dimensions_length" name="product_dimensions_length" placeholder="Length">
											<input id="product_dimensions_width" name="product_dimensions_width" placeholder="Width">
											<input id="product_dimensions_height" name="product_dimensions_height" placeholder="Height">
										</div>
										<div class="product_shipping_class">
											<label for="product_shipping_class">Shipping class</label>
											<select name="product_shipping_class" id="product_shipping_class">
												<option selected="selected" value="-1">No shipping class</option>
											<select>
										</div>
									</div>
									<div class="wapff_product_data2_right4">
										<div class="product_note">
											<label for="product_note">Product Note</label>
											<textarea id="product_note" name="product_note"></textarea>
										</div>
										<div class="product_enable_review">
											<label for="product_enable_review">Enable Reviews</label>
											<input type="checkbox" id="product_enable_review" name="product_enable_review">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="product_image">
							<label for="product_image">Product Image Gallery</label>
							<div class="upload-wrapper">
								<div id="error_output"></div>
								<!-- file drop zone -->
								<div id="dropzone">
									<i>Drop files here</i>
									<!-- upload button -->
									<span class="button btn-blue input-file">
										Browse Files <input id="fileupload" type="file" name="files[]" multiple>
									</span>
								</div>
								<!-- The container for the uploaded files -->
								<div id="files" class="files"></div>
							</div>
							<span class="wpeka_plugin_url" style="display:none"><?php echo WPEKA_WAPFF_URL ?></span>
							<span class="wpeka_author_id" style="display:none"><?php echo $userId ?></span>
							<span class="wpeka_site_url" style="display:none"><?php echo site_url(); ?></span>
						</div>
                                                <input type="hidden" id="wapff_product_type" name="wapff_product_type" value="auction">
						<div class="wpeka_upload ff">Submit product</div>
					</div>
				</section>
<?php } ?>
				
</div>
<?php
