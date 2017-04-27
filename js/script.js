jQuery(document).ready(function(){
	var pluginURL = jQuery(".wpeka_plugin_url").text();
	var authorID = jQuery(".wpeka_author_id").text();
	var siteUrl = jQuery(".wpeka_site_url").text();
        
        //var calendar_image = 'http://localhost/test_woo_auction/wp-content/plugins/woocommerce/assets/images/calendar.png';
        var calendar_image = pluginURL+"images/calendar.png";
        jQuery('.datetimepicker').datetimepicker(
            {defaultDate: "",
                dateFormat: "yy-mm-dd",
                numberOfMonths: 1,
                showButtonPanel: true,
                showOn: "button",
                buttonImage: calendar_image,
                buttonImageOnly: true
            }
        );
	
	jQuery("#wapff_product_des").Editor();
	jQuery(".wapff_product_data2_left > div").each(function(key, val){
		var elthis = jQuery(this);
		var checkey = key+1;
		elthis.click(function(){
			elthis.addClass("active");
			jQuery(".wapff_product_data2_left > div").not(this).removeClass("active");
			jQuery(".wapff_product_data2_right > div").removeClass("active");
			jQuery(".wapff_product_data2_right"+checkey).addClass("active");
		});
	});
	jQuery( "#product_date_price1" ).datepicker({dateFormat: "yy-mm-dd"});
	jQuery( "#product_date_price2" ).datepicker({dateFormat: "yy-mm-dd"});
	jQuery("#wapff_product_virtual").on('change', function(){
		jQuery(this).val(this.checked ? "1" : "0");
                if(jQuery(this).val() == 1)
			jQuery(".shipping_options").hide();
		else
			jQuery(".shipping_options").show();
	});
	jQuery("#product_enable_review").on('change', function(){
		jQuery(this).val(this.checked ? "1" : "0");
	});
	jQuery("#product_manage_stock").on('change', function(){
		jQuery(this).val(this.checked ? "1" : "0");
		if(jQuery(this).val() == 1){
			jQuery(".product_stock_qty").show();
			jQuery(".product_allow_backorders").show();
		}
		else{
			jQuery(".product_stock_qty").hide();
			jQuery(".product_allow_backorders").hide();
		}
	});
	jQuery("#wapff_product_downloadable").on('change', function(){
		jQuery(this).val(this.checked ? "1" : "0");
		
		if(jQuery(this).val() == 1)
			jQuery(".product_downloadable").show();
		else
			jQuery(".product_downloadable").hide();
	});
	
	/*/////////////////
	jQuery("#wapff_product_type").change(function(){
		var value = jQuery(this).val();
		if(value == "simple"){
			
		}else if(value == "grouped"){
			
		}else if(value == "external"){
			
		}else if(value == "variable"){
			
		}
	});
	////////////*/
	
	var file_frame; // variable for the wp.media file_frame
	// attach a click event (or whatever you want) to some element on your page
	jQuery( '#downloadable_files_addfile' ).on( 'click', function( event ) {
		event.preventDefault();
		// if the file_frame has already been created, just reuse it
		if ( file_frame ) {
			file_frame.open();
			return;
		}
		file_frame = wp.media.frames.file_frame = wp.media({
			title: jQuery( this ).data( 'uploader_title' ),
			button: {
				text: jQuery( this ).data( 'uploader_button_text' ),
			},
			multiple: true // set this to true for multiple file selection
		});

		file_frame.on( 'select', function() {
			attachment = file_frame.state().get('selection').first().toJSON();
			var check = jQuery( '.downloadable_files_con_urlvl' ).val();
			if(check)
				jQuery( '.downloadable_files_con_urlvl' ).val(check+","+attachment.url);
			else
				jQuery( '.downloadable_files_con_urlvl' ).val(attachment.url);
		});

		file_frame.open();
	});
	
	jQuery(".wpeka_upload").click(function(){
		var productName = jQuery("#wapff_product_name").val(),
			productShortDes = jQuery("#wapff_product_sdes").val(),
			productDes = jQuery(".Editor-editor").html(),
			productType = jQuery("#wapff_product_type").val(),
			productVirtual = jQuery("#wapff_product_virtual").val(),
			productDownloadable = jQuery("#wapff_product_downloadable").val(),
			downloadableNames = jQuery(".downloadable_files_con_namevl").val(),
			downloadableURL = jQuery(".downloadable_files_con_urlvl").val(),
			downloadableLimit = jQuery("#download_limit").val(),
			downloadableExpiry = jQuery("#download_expiry").val(),
			downloadableType = jQuery("#download_type").val(),
			
                        productItemCondition = jQuery("#product_item_condition").val(),
			productAuctionType = jQuery("#product_auction_type").val(),
			productStartPrice = jQuery("#product_start_price").val(),
                        productBidIncrement = jQuery("#product_bid_increment").val(),
                        productReservePrice = jQuery("#product_reserve_price").val(),
                        productBuyItNowPrice = jQuery("#product_buy_it_now_price").val(),
                        
                        productPriceDateFrom = jQuery("#product_date_price1").val(),
			productPriceDateTo = jQuery("#product_date_price2").val(),
			
			manageStock = jQuery("#product_manage_stock").val(),
			stockQty = jQuery("#product_stock_qty").val(),
			allowBackOrders = jQuery("#product_allow_backorders").val(),
			stockStatus = jQuery("#product_stock_status").val(),
			soldIndividually = jQuery("#product_sold_individually").val(),
			
			productWeight = jQuery("#product_weight").val(),
			productLength = jQuery("#product_dimensions_length").val(),
			productWidth = jQuery("#product_dimensions_width").val(),
			productHeight = jQuery("#product_dimensions_height").val(),
			shippingClass = jQuery("#product_shipping_class").val(),
			
                        manageStock = jQuery("#product_manage_stock").val(),
			stockQty = jQuery("#product_stock_qty").val(),
			allowBackOrders = jQuery("#product_allow_backorders").val(),
			stockStatus = jQuery("#product_stock_status").val(),
			soldIndividually = jQuery("#product_sold_individually").val(),
                        
			productNote = jQuery("#product_note").val(),
			enableReviews = jQuery("#product_enable_review").val(),
			
			productCategories = [];
			jQuery("input[name='product_categories[]']:checked").each(function(){
				productCategories.push(jQuery(this).val());
			});
                        var productTags = jQuery("#product_tags").val(),
			productImages = [];
			
			jQuery(".product_image #files .file-wrapper").each(function(){
				var url = jQuery(this).find("a").attr("href");
				productImages.push(url);
			});
		
		/////////////////////////
		jQuery.ajax({
			url: pluginURL+"helper.php",
			data: {
				productName: productName,
				productShortDes: productShortDes,
				productDes: productDes,
				productType: productType,
				productVirtual: productVirtual,
				productDownloadable: productDownloadable,
				downloadableNames: downloadableNames,
				downloadableURL: downloadableURL,
				downloadableLimit: downloadableLimit,
				downloadableExpiry: downloadableExpiry,
				downloadableType: downloadableType,
                                productItemCondition:productItemCondition,
                                productAuctionType:productAuctionType,
                                productStartPrice:productStartPrice,
                                productBidIncrement:productBidIncrement,
                                productReservePrice:productReservePrice,
                                productBuyItNowPrice:productBuyItNowPrice,
				productPriceDateFrom: productPriceDateFrom,
				productPriceDateTo: productPriceDateTo,
				manageStock: manageStock,
				stockQty: stockQty,
				allowBackOrders: allowBackOrders,
				stockStatus: stockStatus,
				soldIndividually: soldIndividually,
				productWeight: productWeight,
				productLength: productLength,
				productWidth: productWidth,
				productHeight: productHeight,
				shippingClass: shippingClass,
				productNote: productNote,
				enableReviews: enableReviews,
				productCategories: productCategories,
				productTags: productTags,
				productImages: productImages,
				authorID: authorID,
				siteUrl: siteUrl
			},
			type: 'POST',
			success: function(data){
				if(data)
					alert("Product Uploaded !");
				else
					alert("Error !");
			}
		});
	});
});
