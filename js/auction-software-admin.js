jQuery(document).ready(
    function($){
        var calendar_image = '';
        if (typeof woocommerce_writepanel_params != 'undefined') {
            calendar_image = woocommerce_writepanel_params.calendar_image;
        } else if (typeof woocommerce_admin_meta_boxes != 'undefined') {
            calendar_image = woocommerce_admin_meta_boxes.calendar_image;
        }
    
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
    
        var productType = jQuery('#product-type').val();
        if (productType=='auction') {
            jQuery('.show_if_simple').show();
            jQuery('.inventory_options').hide();
        } else{
            jQuery('#Auction.postbox').hide();
            jQuery('#Automatic_relist_auction.postbox').hide();
        }
        jQuery('#product-type').live(
            'change', function(){
                if  (jQuery(this).val() =='auction') {
                    jQuery('.show_if_simple').show();
                    jQuery('.inventory_options').hide();
                    jQuery('#Auction.postbox').show();
                    jQuery('#Automatic_relist_auction.postbox').show();
                } else{
                    jQuery('#Auction.postbox').hide();
                    jQuery('#Automatic_relist_auction.postbox').hide();
                }
            }
        );
        jQuery('label[for="_virtual"]').addClass('show_if_auction');
        jQuery('label[for="_downloadable"]').addClass('show_if_auction');

        var disabledclick = false;
    
        jQuery('.auction-table .action a:not(.disabled)').on(
            'click',function(event){

        
                if(disabledclick) {
                    return; }

                jQuery('.auction-table .action a').addClass('disabled');
                disabledclick = true;
                var logid = $(this).data('id');
                var postid = $(this).data('postid');
                var curent = $(this);
        
                jQuery.ajax(
                    {
                        type : "post",
                        url : SA_Ajax.ajaxurl,
                        data : {action: "delete_bid", logid : logid, postid: postid, SA_nonce : SA_Ajax.SA_nonce },
                        success: function(response) {
                            if (response.action == 'deleted') {
                                curent.parent().parent().addClass('deleted').fadeOut('slow');
                            }
                     
                            if (response.auction_current_bid ) {
                        
                                $('.postbox#Auction span.higestbid').html(response.auction_current_bid)
                            }

                            if (response.auction_current_bider ) {
                                $('.postbox#Auction span.higestbider').html(response.auction_current_bider)
                            }

                             disabledclick = false;
                             jQuery('.auction-table .action a').removeClass('disabled');


                        }
                    }
                );
                event.preventDefault();
          
            }
        );


        jQuery('#Auction .removereserve').on(
            'click',function(event){
                var postid = $(this).data('postid');
                var curent = $(this);
        
                jQuery.ajax(
                    {
                        type : "post",
                        url : SA_Ajax.ajaxurl,
                        data : {action: "remove_reserve_price", postid: postid, SA_nonce : SA_Ajax.SA_nonce },
                        success: function(response) {
                            if (response.error) {
                                curent.after(response.error)
                            } else{
                                if (response.succes) {
                                    $('.postbox#Auction .reservefail').html(response.succes)
                                }
                            }
                        }    
                     
                    }
                );
                event.preventDefault();
          
            }
        );
        jQuery('#general_product_data #_regular_price').on(
            'keyup',function(){
                console.log(jQuery(this).val());
                jQuery('#auction_tab #_regular_price').val(jQuery(this).val());
            }
        );
        
        jQuery('#relistauction').on(
            'click',function(event){
                event.preventDefault();
                jQuery('.relist_auction_dates_fields').toggle();
            
          
            }
        );

        if(jQuery('#_auction_proxy:checkbox:checked').length > 0) {
            $('.form-field._auction_sealed_field ').hide();

        }
        if(jQuery('#_auction_sealed:checkbox:checked').length > 0) {
            $('.form-field._auction_proxy_field ').hide();

        }
        
        $("#_auction_proxy").on(
            'change' ,function() {
                if(this.checked) {
                    $('.form-field._auction_sealed_field ').slideUp('fast');
                    $('#_auction_sealed').prop('checked', false);

                } else{
                    $('.form-field._auction_sealed_field ').slideDown('fast');
                }
            }
        );

        $("#_auction_sealed").on(
            'change' ,function() {
                if(this.checked) {
                    $('.form-field._auction_proxy_field ').slideUp('fast');
                    $('#_auction_proxy').prop('checked', false);

                } else{
                    $('.form-field._auction_proxy_field ').slideDown('fast');
                }
            }
        );
    
 
    }
);
