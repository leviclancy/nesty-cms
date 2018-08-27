<? $sql_temp = "SELECT * FROM $database.pages";
$retrieve_entries = $connection_pdo->prepare($sql_temp);
$retrieve_entries->execute();
$result = $retrieve_entries->fetchAll();
foreach ($result as $row):
	$slug_temp = null; if (!(empty($row['slug']))): $slug_temp = $row['slug']."/"; endif;
	$information_array[$row['page_id']] = [
		"entry_id" => $row['entry_id'],
		"page_id" => $row['page_id'],
		"link" => "https://".$domain."/".$row['entry_id']."/".$slug_temp,
		"slug" => $row['slug'],
		"name" => $row['header'],
		"header" => $row['header'],
		"summary" => null ];
	endforeach;

echo json_encode($information_array); ?>
