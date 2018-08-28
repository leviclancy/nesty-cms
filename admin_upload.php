<? // session_start();
ob_start();
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
include_once('config.php');

$connection_pdo = new PDO("mysql:host=$server;dbname=$database;charset=utf8mb4", $username, $password);

include_once('functions.php');

// ini_set('display_errors',1);

// get the files and generate duplicate directories
$originals_directories = glob('originals/*' , GLOB_ONLYDIR);
foreach ($originals_directories as $directory_temp):
	$directory_name = explode("/", $directory_temp); $directory_name = $directory_name[1];
	if (!(is_dir("media/".$directory_name))): mkdir("media/".$directory_name, 0755, true); endif;
	if (empty($files[$directory_name])): $files[$directory_name] = null; endif;
	$files_temp = array_diff(scandir($directory_temp),[".",".."]);
	foreach((array)$files_temp as $file_temp):
		if ( (strpos($file_temp, ".jpeg") == FALSE) && (strpos($file_temp, ".jpg") == FALSE)): continue; endif;
		$exif_raw = exif_read_data("$directory_temp/$file_temp", 'IFD0');
		$exif_process = ["Model"=>null, "ExposureTime"=>null, "FNumber"=>null, "ISOSpeedRatings"=>null, "FocalLength"=>null, "FileDateTime"=>null, "DateTime"=>null];
		foreach ($exif_process as $temp => $discard):
			if (empty($exif_raw[$temp])): $exif_process[$temp] = null; continue; endif;
			$exif_process[$temp] = $exif_raw[$temp];
			if ($temp == "FileDateTime"): $exif_process[$temp] = date("Y-m-d G:i", $exif_process[$temp]); endif; endforeach;
		$files[$directory_name][$file_temp] = $exif_process; endforeach; endforeach;

$count_max = 0;

echo "<h2>Running</h2>";

foreach((array)$files as $directory => $files_temp):
	foreach ((array)$files_temp as $file_name => $file_info):

		// only do ten images at a time for server resource conservation
		if ($count_max >= 50): echo "<h2>Finished</h2>"; exit; endif;

		$photo_location = $photo = null;

		$file_name_new = time($file_info['DateTime'])."_".random_code(10);
		$values = [
			"media_id"	=>	$file_name_new,
			"directory"	=>	$directory,
			"filename_original"	=>	$file_name,
			"filename_full"		=>	$file_name_new."_full.jpg",
			"filename_large"		=>	$file_name_new."_large.jpg",
			"filename_thumb"		=>	$file_name_new."_thumb.jpg",
			"datetime_original"	=>	$file_info['DateTime'],
			"datetime_file"		=>	$file_info['FileDateTime'],
			"datetime_process"	=>	date("Y-m-d h:i:s"),
			"model"			=>	$file_info['Model'],
			"exposure"		=>	$file_info['ExposureTime'],
			"fnumber"		=>	$file_info['FNumber'],
			"iso"			=>	$file_info['ISOSpeedRatings'],
			"focallength"	=>	$file_info['FocalLength'] ];

		echo "starting $file_name<br>";

		// check for duplicates and confirm whether to replace them or not, search for duplicates by matching datetime_original, filename_original
		if (empty($check_image_statement)):		
			$sql_temp = "SELECT * FROM $database.media WHERE `datetime_original`=:datetime_original AND `filename_original`=:filename_original";
			$check_image_statement = $connection_pdo->prepare($sql_temp); endif;
		$check_image_statement->execute(["datetime_original"=>$values['datetime_original'], "filename_original"=>$values['filename_original']]);
		$possible_duplicates = $check_image_statement->fetchAll();
		foreach ($possible_duplicates as $row):
		$values['media_id'] = $row['media_id'];
    unlink("media/".$directory."/".$values['filename_thumb']);   
    unlink("media/".$directory."/".$values['filename_large']);   
    unlink("media/".$directory."/".$values['filename_full']);   

//			echo "skip $file_name<br>";
//			echo "<img src='media/".$directory."/".$row['filename_thumb']."' alt='img' />";
//			skip it for now .... will deal with duplicates later, needs interface to decide which ones to keep or to skip
//			if (file_exists("originals/".$directory."/".$values['filename_original'])): unlink("originals/".$directory."/".$values['filename_original']); endif;
			continue 2;
			endforeach;

		// make image
		$photo_location = "originals/".$directory."/".$values['filename_original'];
		$photo = imagecreatefromjpeg($photo_location); usleep(500);
		list($photo_width, $photo_height) = getimagesize($photo_location);

		// make large version
		$max_large = 1800;
		$max_thumb = 300;
		if ($photo_width > $photo_height):
			$large_width = $max_large; $large_height = round($photo_height*($max_large/$photo_width));
			$thumb_height = $max_thumb; $thumb_width = round($photo_width*($max_thumb/$photo_height));
		else:
			$large_height = $max_large; $large_width = round($photo_width*($max_large/$photo_height));
			$thumb_width = $max_thumb; $thumb_height = round($photo_height*($max_thumb/$photo_width)); endif;

		$large = imagecreatetruecolor($large_width, $large_height);
		imagecopyresampled($large, $photo, 0, 0, 0, 0, $large_width, $large_height, $photo_width, $photo_height);

		// make thumbnail version
		$thumb = imagecreatetruecolor($thumb_width, $thumb_height);
		imagecopyresampled($thumb, $photo, 0, 0, 0, 0, $thumb_width, $thumb_height, $photo_width, $photo_height);

		// copy original version
		imagejpeg($thumb, "media/".$directory."/".$values['filename_thumb'], 85); usleep(500);
		imagejpeg($large, "media/".$directory."/".$values['filename_large'], 85); usleep(500);
//		imagejpeg($photo, "media/".$directory."/".$values['filename_full'], 100); usleep(500);
		copy($photo_location, "media/".$directory."/".$values['filename_full']); usleep(500);

		// check if the images are okay
		$error_temp = 0;
		if (!(file_exists("media/".$directory."/".$values['filename_thumb'])) || !(filesize("media/".$directory."/".$values['filename_thumb']))): $error_temp = 1; endif;
		if (!(file_exists("media/".$directory."/".$values['filename_large'])) || !(filesize("media/".$directory."/".$values['filename_large']))): $error_temp = 1; endif;
		if (!(file_exists("media/".$directory."/".$values['filename_full'])) || !(filesize("media/".$directory."/".$values['filename_full']))): $error_temp = 1; endif;
		if ($error_temp !== 0):
			if (file_exists("media/".$directory."/".$values['filename_thumb'])): unlink("media/".$directory."/".$values['filename_thumb']); endif;
			if (file_exists("media/".$directory."/".$values['filename_large'])): unlink("media/".$directory."/".$values['filename_large']); endif;
			if (file_exists("media/".$directory."/".$values['filename_full'])): unlink("media/".$directory."/".$values['filename_full']); endif;
			continue; endif;

		// if the images are okay then we can proceed to add the image to the database
		if (empty($insert_image_statement)):
			$values['datetime_process'] = date("Y-m-d h:i:s");
			foreach ($values as $key => $value): $keys_array[] = "`$key`"; $inputs_array[] = ":$key"; endforeach;
			$sql_temp = "INSERT INTO $database.media (".implode(", ", $keys_array).") VALUES (".implode(", ", $inputs_array).") ";
//			$sql_temp .= "ON DUPLICATE KEY UPDATE `datetime_original`=VALUES(`datetime_original`), `datetime_process`=VALUES(`datetime_process`)";
			$insert_image_statement = $connection_pdo->prepare($sql_temp); endif;
		$insert_image_statement->execute($values);

		// check if insert_image_statement has an error
		$error_temp = execute_checkup($insert_image_statement->errorInfo(), "inserting image into database");		
		if ($error_temp !== "success"):
			if (file_exists("media/".$directory."/".$values['filename_thumb'])): unlink("media/".$directory."/".$values['filename_thumb']); endif;
			if (file_exists("media/".$directory."/".$values['filename_large'])): unlink("media/".$directory."/".$values['filename_large']); endif;
			if (file_exists("media/".$directory."/".$values['filename_full'])): unlink("media/".$directory."/".$values['filename_full']); endif;
			continue; endif;

		// if no errors so far then it means the images are okay and they were inserted okay, so delete original file
		if (file_exists("originals/".$directory."/".$values['filename_original'])): unlink("originals/".$directory."/".$values['filename_original']); endif;

		$count_max++;

		echo "finished $file_name<br>";

		ob_flush(); flush(); set_time_limit(0); // exit;

		endforeach; endforeach;

echo "<h2>Complete</h2>";

exit; ?>
