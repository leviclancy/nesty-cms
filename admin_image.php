<? html_header($domain." media", $domain.$proper_uri);

$media_description = null;
if (!(empty($_POST['media_edit']))):
	$media_description = preg_replace("/\r\n/", "\n", $_POST['description']);
	$media_description = trim($media_description);
	if (ctype_space($media_description)): $media_description = null; endif;
	$values_temp = [
		"media_id"=>$_POST['media_id'], 
		"description"=>$media_description ];
	$sql_temp = sql_setup($values_temp, "$database.media");
	$update_page = $connection_pdo->prepare($sql_temp);
	$update_page->execute($values_temp);
	$result = execute_checkup($update_page->errorInfo(), "updating media description okay");

	// create statment to delete paths
	$sql_temp = "DELETE FROM $database.paths WHERE child_id=:child_id AND parent_id=:parent_id";
	$paths_delete_statement = $connection_pdo->prepare($sql_temp);

	// create statmement to add paths
	$values_temp = [
		"path_id"=>null,
		"parent_id"=>null,
		"child_id"=>null,
		"type"=>null ];
	$sql_temp = sql_setup($values_temp, "$database.paths");
	$paths_insert_statement = $connection_pdo->prepare($sql_temp);

	$fill_array = [ "parents", "parents_confirmed" ];
	foreach($fill_array as $array_temp): if (empty($_POST[$array_temp])): $_POST[$array_temp] = []; endif; endforeach;

	// parents to add
	foreach(array_diff($_POST['parents'], $_POST['parents_confirmed']) as $parent_id):
		$paths_insert_statement->execute(["path_id"=>random_code(7), "parent_id"=>$parent_id, "child_id"=>$_POST['media_id'], "type"=>"media"]);
		execute_checkup($paths_insert_statement->errorInfo(), "adding to gallery of $parent_id"); endforeach;

	// parents to remove
	foreach(array_diff($_POST['parents_confirmed'], $_POST['parents']) as $parent_id):
		$paths_delete_statement->execute(["parent_id"=>$parent_id, "child_id"=>$_POST['media_id']]);
		execute_checkup($paths_delete_statement->errorInfo(), "removing from gallery of $parent_id"); endforeach;
	endif;

$retrieve_media->execute(["media_id"=>$slug_temp]);
$result = $retrieve_media->fetchAll();
foreach ($result as $row): $media_confirmed[$row['media_id']] = $row; endforeach;

// get basic info for all pages
$pages_array = [];
$sql_temp = "SELECT page_id, header, slug FROM $database.pages ORDER BY header ASC, slug ASC";
$retrieve_pages = $connection_pdo->prepare($sql_temp);
$retrieve_pages->execute(); $result = $retrieve_pages->fetchAll();
foreach ($result as $row):
	$pages_array[$row['page_id']] = $row; endforeach;

// get parents
$media_confirmed[$slug_temp]['parents'] = [];
$sql_temp = "SELECT parent_id FROM $database.paths WHERE child_id=:media_id";
$retrieve_pages = $connection_pdo->prepare($sql_temp);
$retrieve_pages->execute(["media_id"=>$slug_temp]);
$result = $retrieve_pages->fetchAll();
foreach ($result as $row):
	$media_confirmed[$slug_temp]['parents'][] = $row['parent_id']; endforeach;


echo "<div id='edit-window'>";
echo "<div id='edit-window-create-button' class='background_1'><a href='/create/' target='_blank'><i class='material-icons'>note_add</i> Create</a></div>";
echo "<div id='edit-window-settings-button'><a href='/account/'><i class='material-icons'>settings</i></a></div>";
echo "<div id='edit-window-delete-button'><a href='/m/".$slug_temp."/delete/'>Delete</a></div>";
echo "<div id='edit-window-open-button'><a href='/m/".$slug_temp."/' target='_blank'>Open image</a></div>";
echo "</div>";

echo "<form method='post'>";

echo "<button type='submit' name='media_edit' value='save' class='floating-action-button'>save</button>";

echo "<br><br>";

echo "<center><b>media id: ".$slug_temp."</b><br>";
echo "old file name: ".$media_confirmed[$slug_temp]['filename_original']."<br><br>";

echo "<img src='/m/".$slug_temp."/thumb/'></center>"; 

echo "<br><br>";
	
echo "<input type='hidden' name='media_id' value='".$slug_temp."'>";

echo "<textarea name='description' placeholder='description' style='width: 460px; height: 200px; margin: 0 auto;'>".$media_confirmed[$slug_temp]['description']."</textarea>";

echo "<div class='footer_spacer'>&nbsp;</div>";

$parents_confirmed = array_intersect(array_keys($pages_array), $media_confirmed[$slug_temp]['parents']);
foreach ($parents_confirmed as $page_id): echo "<input type='hidden' name='parents_confirmed[]' value='$page_id'>"; endforeach;

echo "<select name='parents[]' class='parent_select' size='10' style='display: block !important; width: 500px !important; margin: 0 auto !important;' multiple>";
echo "<option disabled>parents</option>";
if (!(empty($media_confirmed[$slug_temp]['parents']))):
	echo "<optgroup label='selected'>";
	foreach($media_confirmed[$slug_temp]['parents'] as $page_id):
		if (empty($pages_array[$page_id])): continue; endif;
		echo "<option value='$page_id' selected>".$pages_array[$page_id]['header']." (".$pages_array[$page_id]['slug'].")</option>";
		endforeach;
	echo "</optgroup>"; endif;
foreach($pages_array as $page_id => $page_info): 
	if (in_array($page_id,$media_confirmed[$slug_temp]['parents'])): continue; endif;
	echo "<option value='$page_id'>".$page_info['header']." (".$page_info['slug'].")</option>";
	endforeach;
echo "</select>";

echo "</form>";

footer(); ?>
