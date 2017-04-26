<?php

if ( !defined('ABSPATH') )
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
require_once(ABSPATH . 'wp-config.php');

global $wpdb;

$productName = $_POST["productName"];
$productShortDes = $_POST["productShortDes"];
$productDes = $_POST["productDes"];
$productType = $_POST["productType"];
$productVirtual = $_POST["productVirtual"];
$productDownloadable = $_POST["productDownloadable"];
$downloadableNames = $_POST["downloadableNames"];
$downloadableURL = $_POST["downloadableURL"];
$downloadableLimit = $_POST["downloadableLimit"];
$downloadableExpiry = $_POST["downloadableExpiry"];
$downloadableType = $_POST["downloadableType"];			
$productItemCondition = $_POST["productItemCondition"];
$productAuctionType = $_POST["productAuctionType"];
$productStartPrice = $_POST["productStartPrice"];
$productBidIncrement = $_POST["productBidIncrement"];
$productReservePrice = $_POST["productReservePrice"];
$productBuyItNowPrice = $_POST["productBuyItNowPrice"];
$productPriceDateFrom = $_POST["productPriceDateFrom"];

$productPriceDateTo = $_POST["productPriceDateTo"];
$manageStock = $_POST["manageStock"];
$stockQty = $_POST["stockQty"];
$allowBackOrders = $_POST["allowBackOrders"];
$stockStatus = $_POST["stockStatus"];
$soldIndividually = $_POST["soldIndividually"];
$productWeight = $_POST["productWeight"];
$productLength = $_POST["productLength"];
$productWidth = $_POST["productWidth"];
$productHeight = $_POST["productHeight"];
$shippingClass = $_POST["shippingClass"];
$productNote = $_POST["productNote"];
$enableReviews = $_POST["enableReviews"];
$productCategories = $_POST["productCategories"];
$productTags = $_POST["productTags"];
$productImages = $_POST["productImages"];
$authorID = $_POST["authorID"];
$siteUrl = $_POST["siteUrl"];

$sql = "SELECT MAX(ID) as postid FROM ".$wpdb->prefix ."posts";
$result = $wpdb->get_results($sql, OBJECT);
$postID = $result[0]->postid + 1;

$sql2 = "SELECT MAX(meta_id) as postmtid FROM ".$wpdb->prefix ."postmeta";
$result2 = $wpdb->get_results($sql2, OBJECT);
$postMtID = $result2[0]->postmtid;

$date = date("Y-m-d h:i:s");
$postids = array();

// insert post - images
foreach($productImages as $img){
	$tach = explode("/", $img);
	$imgName = $tach[count($tach) - 1];
	$imgName2 = explode(".", $imgName);
	$imgName3 = $imgName2[0];
	
	$ext = $imgName2[1];
	if($ext = "jpg" || $ext == "JPG")
		$mime_type = "image/jpeg";
	elseif($ext == "png" || $ext == "PNG")
		$mime_type = "image/png";
	else
		$mime_type = "image/gif";
	
	$wpdb->insert( $wpdb->prefix. 'posts', 
	array( 
		'ID' => $postID,
		'post_author' => $authorID,
		'post_date' => $date,
		'post_date_gmt' => $date,
		'post_title' => $imgName3,
		'post_status' => 'inherit',
		'comment_status' => 'open',
		'ping_status' => 'closed',
		'post_name' => $imgName3,
		'post_modified' => $date,
		'post_modified_gmt' => $date,
		'post_parent' => 0,
		'guid' => $img,
		'menu_order' => 0,
		'post_type' => 'attachment',
		'post_mime_type' => $mime_type,
		'comment_count' => 0
	));
	$wpdb->insert( $wpdb->prefix. 'postmeta', 
	array( 
		'post_id' => $postID, 
		'meta_key' => '_wp_attached_file',
		'meta_value' => date("Y")."/".date("m")."/".$imgName
	));
	array_push($postids, $postID);
	$postID++;
}
// end insert post - images

// insert post meta
if($downloadableURL){
	$downloadfilesMetavalue = "a:3:{";
	$downloadfiles = explode(",", $downloadableURL);
	$downloadnames = explode(",", $downloadableNames);
	foreach($downloadfiles as $key=>$file){
		$md5 = md5(uniqid(rand(), true));
		$downloadfilesMetavalue .= 's:32:"'.$md5.'";';
			$downloadfilesMetavalue .= 'a:2:{';
				$downloadfilesMetavalue .= 's:4:"name";';
				$downloadfilesMetavalue .= 's:4:"'.$downloadnames[$key].'";';
				$downloadfilesMetavalue .= 's:4:"file";';
				$downloadfilesMetavalue .= 's:99:"'.$file.'";';
			$downloadfilesMetavalue .= '}';
	}
	$downloadfilesMetavalue .= '}';
}else{
	$downloadfilesMetavalue = '';
}

$metakeys = array("_visibility", "_stock_status", "_downloadable", "_virtual", "_purchase_note", "_featured",
	"_weight", "_length", "_width", "_height", "_auction_item_condition", "_auction_type", "_auction_start_price", "_auction_bid_increment", "_auction_reserved_price","", "_sale_price", "_auction_dates_to", "_auction_dates_from", "_regular_price", "_sold_individually", "_manage_stock", "_backorders", "_stock" , "_downloadable_files", "_download_limit", "_download_expiry", "_download_type");

foreach($metakeys as $metakey){
    
	if($metakey == "_visibility")
		$value = "visible";
	elseif($metakey == "_stock_status")
		$value = $stockStatus;
	elseif($metakey == "_downloadable")	
		$value = $productDownloadable ? "yes":"no";
	elseif($metakey == "_virtual")	
		$value = $productVirtual ? "yes":"no";
	elseif($metakey == "_purchase_note")
		$value = $productNote;
	elseif($metakey == "_featured")	
		$value = "no";
	elseif($metakey == "_weight")
		$value = $productWeight;
	elseif($metakey == "_length")
		$value = $productLength;
	elseif($metakey == "_width")
		$value = $productWidth;
	elseif($metakey == "_height")
		$value = $productHeight;
	elseif($metakey == "_auction_item_condition")
		$value = $productItemCondition;
	elseif($metakey == "_auction_type")
		$value = $productItemCondition;
	elseif($metakey == "_auction_start_price")
		$value = $productStartPrice;
	elseif($metakey == "_auction_bid_increment")
		$value = $productBidIncrement;
	elseif($metakey == "_auction_reserved_price")
		$value = $productReservePrice;
	elseif($metakey == "_regular_price")
		$value = $productBuyItNowPrice;
	elseif($metakey == "_auction_dates_from")	
                $value = $productPriceDateFrom;
	elseif($metakey == "_auction_dates_to")	
                $value = $productPriceDateTo;
	elseif($metakey == "_sold_individually")
		$value = $soldIndividually ? "yes":"no";
	elseif($metakey == "_manage_stock")	
		$value = $manageStock ? "yes":"no";
	elseif($metakey == "_backorders")
		$value = $allowBackOrders ? "yes":"no";
	elseif($metakey == "_stock")
		$value = $stockQty;
	elseif($metakey == "_downloadable_files")
		$value = $downloadfilesMetavalue;
	elseif($metakey == "_download_limit")
		$value = $downloadableLimit;
	elseif($metakey == "_download_expiry")
		$value = $downloadableExpiry;
	elseif($metakey == "_download_type")
		$value = $downloadableType;
		
	$wpdb->insert( $wpdb->prefix. 'postmeta', 
	array( 
		'post_id' => $postID, 
		'meta_key' => $metakey,
		'meta_value' => $value
	));
}	

foreach($postids as $key=>$postid){
	if($key == 0)
	{
		$wpdb->insert( $wpdb->prefix. 'postmeta', 
		array( 
			'post_id' => $postID, 
			'meta_key' => '_thumbnail_id',
			'meta_value' => $postid
		));
		unset($postids[$key]);
	}	
}
$productGallery = implode(",", $postids);
$wpdb->insert( $wpdb->prefix. 'postmeta', 
array( 
	'post_id' => $postID, 
	'meta_key' => '_product_image_gallery',
	'meta_value' => $productGallery
));
// end insert post meta

// insert post
	
	$wpdb->insert( $wpdb->prefix. 'posts', 
	array( 
		'ID' => $postID,
		'post_author' => $authorID,
		'post_date' => $date,
		'post_date_gmt' => $date,
		'post_content' => $productDes,
		'post_title' => $productName,
		'post_excerpt' => $productShortDes,
		'post_status' => 'pending',
		'comment_status' => 'open',
		'ping_status' => 'closed',
		'post_name' => $productName,
		'post_modified' => $date,
		'post_modified_gmt' => $date,
		'post_parent' => 0,
		'guid' => $siteUrl.'/?post_type=product&#038;p='.$postID,
		'menu_order' => 0,
		'post_type' => 'product',
		'comment_count' => 0
	));
// end insert post

if($productType){
		$termIdValue = 6;
	$wpdb->insert( $wpdb->prefix. 'term_relationships', 
	array( 
		'object_id' => $postID, 
		'term_taxonomy_id' => $termIdValue,
		'term_order' => 0
	));
}


echo 1;
