<?php

error_reporting(E_ALL | E_STRICT);
require('UploadHandler.php');

$upload_dir = '../../../uploads/'.date("Y")."/".date("m")."/"; //specify path to your upload folder

$upload_handler = new UploadHandler(array(
	'max_file_size' => 1048576, //1MB file size
	'image_file_types' => '/\.(gif|jpe?g|png)$/i',
	'upload_dir' => $upload_dir,
	'upload_url' => date("Y")."/".date("m")."/"
));
