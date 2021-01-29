<? if ($page_temp == "new"): amp_header("new", "+ page");
else: amp_header($page_confirmed['header'], $domain."/edit/"); endif;


if (isset($_POST['page_edit'])):
	$page_temp = $_POST['page_id'];

	$body_text = $_POST['body'];
	$body_text = str_replace("[[[", "\n\n[[[", $body_text);
	$body_text = str_replace("]]]", "]]]\n\n", $body_text);
	$body_text = preg_replace("/\r\n/", "\n", $body_text);
	$body_text = preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n\n", $body_text);
	$body_text = trim($body_text);
	if (ctype_space($body_text)): $body_text = null; endif;

	$popover_text = preg_replace("/\r\n/", "\n", $_POST['popover']);
	$popover_text = trim($popover_text);
	if (ctype_space($popover_text)): $popover_text = null; endif;

	// sanitise the list marker
	$_POST['list_marker'] = trim($_POST['list_marker']);
	if (empty($_POST['list_marker']) || ctype_space($_POST['list_marker'])): $_POST['list_marker'] = null; endif;

	// sanitise the slug
	$_POST['slug'] = trim($_POST['slug']);
	if (empty($_POST['slug']) || ctype_space($_POST['slug'])): $_POST['slug'] = null; endif;
	if (in_array($_POST['slug'], ["delete", "ping", "edit", "account", "settings", "security", "users", "*"])): $_POST['slug'] = $_POST['slug']."_".random_code(3); endif;
	$_POST['slug'] = str_replace("/", "_", $_POST['slug']);

	$values_temp = [
		"page_id"=>$_POST['page_id'], 
		"list_marker"=>$_POST['list_marker'], 
		"updated_time"=>$_POST['updated_time'], 
		"header"=>$_POST['header'], 
		"slug"=>$_POST['slug'], 
		"password"=>$_POST['password'], 
		"created_time"=>$_POST['created_time'], 
		"body"=>$body_text, 
		"popover"=>$popover_text ];
	$sql_temp = sql_setup($values_temp, "$database.pages");
	$update_page = $connection_pdo->prepare($sql_temp);
	$update_page->execute($values_temp);
	execute_checkup($update_page->errorInfo(), "updating post content okay");
	if (empty($_POST['parents'])): $_POST['parents'] = []; endif;
	if (empty($_POST['children'])): $_POST['children'] = []; endif;

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

	$fill_array = ["parents", "parents_confirmed", "children", "children_confirmed", "snippets", "snippets_confirmed"];
	foreach($fill_array as $array_temp): if (empty($_POST[$array_temp])): $_POST[$array_temp] = []; endif; endforeach;

	// parents to add
	foreach(array_diff($_POST['parents'], $_POST['parents_confirmed']) as $parent_id):
		$paths_insert_statement->execute(["path_id"=>random_code(7), "parent_id"=>$parent_id, "child_id"=>$_POST['page_id'], "type"=>"page"]);
		execute_checkup($paths_insert_statement->errorInfo(), "adding parent_id $parent_id"); endforeach;

	// parents to remove
	foreach(array_diff($_POST['parents_confirmed'], $_POST['parents']) as $parent_id):
		$paths_delete_statement->execute(["parent_id"=>$parent_id, "child_id"=>$_POST['page_id']]);
		execute_checkup($paths_delete_statement->errorInfo(), "removing parent_id $parent_id"); endforeach;

	// children to add
	foreach(array_diff($_POST['children'], $_POST['children_confirmed']) as $child_id):
		$paths_insert_statement->execute(["path_id"=>random_code(7), "parent_id"=>$_POST['page_id'], "child_id"=>$child_id, "type"=>"page"]);
		execute_checkup($paths_insert_statement->errorInfo(), "adding child_id $child_id (page)"); endforeach;

	// children to remove
	foreach(array_diff($_POST['children_confirmed'], $_POST['children']) as $child_id):
		$paths_delete_statement->execute(["parent_id"=>$_POST['page_id'], "child_id"=>$child_id]);
		execute_checkup($paths_delete_statement->errorInfo(), "removing child_id $child_id (page)"); endforeach;

//	// media to add
//	foreach(array_diff($_POST['media'], $_POST['media_confirmed']) as $media_id):
//		$paths_insert_statement->execute(["path_id"=>random_code(7), "parent_id"=>$_POST['page_id'], "child_id"=>$child_id], "type"=>"media");
//		execute_checkup($paths_insert_statement->errorInfo(), "adding child_id $child_id (page)"); endforeach;

//	// media to remove
//	foreach(array_diff($_POST['media_confirmed'], $_POST['media']) as $media_id):
//		$paths_delete_statement->execute(["parent_id"=>$_POST['page_id'], "child_id"=>$child_id]);
//		execute_checkup($paths_delete_statement->errorInfo(), "removing child_id $child_id (page)"); endforeach;

	// snippets to add
	foreach(array_diff($_POST['snippets'], $_POST['snippets_confirmed']) as $child_id):
		$paths_insert_statement->execute(["path_id"=>random_code(7), "parent_id"=>$_POST['page_id'], "child_id"=>$child_id, "type"=>"snippet"]);
		execute_checkup($paths_insert_statement->errorInfo(), "adding child_id $child_id (snippet)"); endforeach;

//	// snippets to remove
//	foreach(array_diff($_POST['snippets_confirmed'], $_POST['snippets']) as $child_id):
//		$paths_delete_statement->execute(["parent_id"=>$_POST['page_id'], "child_id"=>$child_id]);
//		execute_checkup($paths_delete_statement->errorInfo(), "removing child_id $child_id  (snippet)"); endforeach;

	endif;

$retrieve_page->execute(["page_id"=>$page_temp]);
$result = $retrieve_page->fetchAll();
foreach ($result as $row):
	$page_confirmed = $row;
	endforeach;

$sql_temp = "SELECT page_id, header, slug FROM $database.pages ORDER BY header ASC, slug ASC";
$retrieve_pages = $connection_pdo->prepare($sql_temp);
$retrieve_pages->execute();
$result = $retrieve_pages->fetchAll();
foreach ($result as $row):
	$pages_array[$row['page_id']] = $row; endforeach;

$sql_temp = "SELECT snippet_id, name, year, month, day FROM $database.snippets ORDER BY name ASC, year ASC, month ASC, day ASC";
$retrieve_snippets = $connection_pdo->prepare($sql_temp);
$retrieve_snippets->execute();
$result = $retrieve_snippets->fetchAll();
foreach ($result as $row):
	$snippets_array[$row['snippet_id']] = $row; endforeach;

$sql_temp = "SELECT * FROM `paths` WHERE `parent_id`=:page_id OR `child_id`=:page_id";
$retrieve_pages = $connection_pdo->prepare($sql_temp);
$retrieve_pages->execute(["page_id"=>$page_confirmed['page_id']]);
$result = $retrieve_pages->fetchAll();
foreach ($result as $row):
	if ($row['parent_id'] == $page_confirmed['page_id']): $page_confirmed['children'][]= $row['child_id']; endif;
	if ($row['child_id'] == $page_confirmed['page_id']): $page_confirmed['parents'][]= $row['parent_id']; endif; endforeach;

if (empty($page_confirmed)):
	$page_confirmed = ["page_id"=>random_code(5), "header"=>null, "slug"=>null,  "password"=>null, "created_time"=>date("Y-m-d"), "body"=>null, "menu"=>null]; endif;

echo "<div id='edit-window'>";
if ($page_temp !== "new"):
	echo "<div id='edit-window-create-button' class='background_1'><a href='/create/' target='_blank'><i class='material-icons'>note_add</i> Create</a></div>";
	echo "<div id='edit-window-settings-button'><a href='/account/'><i class='material-icons'>settings</i></a></div>";
	echo "<div id='edit-window-delete-button'><a href='/".$page_confirmed['page_id']."/delete/'>Delete</a></div>";
	echo "<div id='edit-window-open-button'><a href='/".$page_confirmed['page_id']."/' target='_blank'>Open post</a></div>";
else:
	echo "<div id='edit-window-create-button' style='background: #555;'><i class='material-icons'>note_add</i> Create</div>";
	echo "<div id='edit-window-settings-button'><a href='/account/'><i class='material-icons'>settings</i></a></div>";
	echo "<div id='edit-window-open-button'><a href='/' target='_blank'>Home</a></div>";
	endif;
echo "</div>";

echo "<form action='/".$page_confirmed['page_id']."/edit/' method='post'>";

echo "<button type='submit' name='page_edit' value='save' class='floating-action-button'>save</button>";

echo "<input type='hidden' name='page_id' value='".$page_confirmed['page_id']."'>";

echo "<input type='hidden' name='updated_time' value='".date("Y-m-d")."'>";

$parents_confirmed = $children_confirmed = $snippets_confirmed = [];
$parents_confirmed = array_intersect(array_keys($pages_array), $page_confirmed['parents']);
$children_confirmed = array_intersect(array_keys($pages_array), $page_confirmed['children']);
$snippets_confirmed = array_intersect(array_keys($snippets_array), $page_confirmed['children']);
foreach ($parents_confirmed as $page_id): echo "<input type='hidden' name='parents_confirmed[]' value='$page_id'>"; endforeach;
foreach ($children_confirmed as $page_id): echo "<input type='hidden' name='children_confirmed[]' value='$page_id'>"; endforeach;
foreach ($snippets_confirmed as $snippet_id): echo "<input type='hidden' name='snippets_confirmed[]' value='$snippet_id'>"; endforeach;

echo "<style> #input-header { margin: 0 auto 25px; display: block; width: 95%; max-width: 1000px; padding: 5px 15px; height: 50px; text-align: center; font-size: 23px; font-weight: 300; letter-spacing: 1px; } </style>";
echo "<input type='text' name='header' id='input-header' value='".htmlspecialchars($page_confirmed['header'], ENT_QUOTES)."' placeholder='header' required>";

echo "<div style='display: block; text-align: center;'>";
echo "<input type='text' name='list_marker' value='".htmlspecialchars($page_confirmed['list_marker'], ENT_QUOTES)."' placeholder='list marker' style='margin: 10px 12px; text-align: center; display: inline-block; width: 20%; max-width: 200px; '>";
echo "<input type='text' name='slug' value='".htmlspecialchars($page_confirmed['slug'], ENT_QUOTES)."' pattern='[a-zA-Z0-9-]+' placeholder='slug' style='margin: 10px 12px; text-align: center; display: inline-block; width: 30%; max-width: 300px; '>";
echo "<input type='text' name='password' value='".$page_confirmed['password']."' placeholder='password' style='margin: 10px 12px; text-align: center; display: inline-block; width: 22%; max-width: 220px; '>";
echo "<input type='date' name='created_time' value='".$page_confirmed['created_time']."' style='margin: 10px 12px; text-align: center; display: inline-block; width: 15%; max-width: 150px; '></div>";
echo "</div>";

// echo "<tr><td><input type='text' name='toggle_1' value='".$page_confirmed['toggle_1']."' placeholder='toggle'></td>";
// echo "<td><input type='text' name='toggle_2' value='".$page_confirmed['toggle_2']."' placeholder='toggle'></td></tr></tbody></table>";

// echo "<textarea name='menu' value='".$page_confirmed['menu']."' placeholder='menu'/></textarea>";

echo "<style> #input-textarea { width: 95%; max-width: 1000px; margin: 25px auto 120px; padding: 15px; border: 2px solid rgba(255,255,255,0.3); box-shadow: 0 0 30px -2px rgba(150,150,150,0.45); background: #fff; } </style>";
echo "<textarea name='body' id='input-textarea'>".$page_confirmed['body']."</textarea>";

echo "<style> .edit-subheaders { width: 600px; margin: 60px auto 20px; display: block; color: rgba(0,0,0,0.7); text-align: left; } </style>";

// here you can manage parent and child hierarchy
echo "<div class='edit-subheaders'>Parents</div>";
echo "<select name='parents[]' size='9' style='width: 600px; margin: 10px auto 40px; display: block; border-radius: 4px;' multiple>";
// echo "<option disabled>parents</option>";
if (!(empty($parents_confirmed))):
	echo "<optgroup label='selected'>";
	foreach($parents_confirmed as $page_id):
		if ($page_id == $page_confirmed['page_id']): continue; endif; // cannot select itself 
		echo "<option value='$page_id' selected>".$pages_array[$page_id]['header']." (".$pages_array[$page_id]['slug'].")</option>";
		endforeach;
	echo "</optgroup>"; endif;
foreach($pages_array as $page_id => $page_info): 
	if ($page_id == $page_confirmed['page_id']): continue; endif; // cannot select itself 
	if (in_array($page_id,$parents_confirmed)): continue; endif;
	echo "<option value='$page_id'>".$page_info['header']." (".$page_info['slug'].")</option>";
	endforeach;
echo "</select>";

echo "<div class='edit-subheaders'>Children</div>";
echo "<select name='children[]' size='9' style='width: 600px; margin: 10px auto 40px; display: block; border-radius: 4px;' multiple>";
// echo "<option disabled>children</option>";
if (!(empty($children_confirmed))):
	echo "<optgroup label='selected'>";
	foreach($children_confirmed as $page_id):
		if ($page_id == $page_confirmed['page_id']): continue; endif; // cannot select itself 
		echo "<option value='$page_id' selected>".$pages_array[$page_id]['header']." (".$pages_array[$page_id]['slug'].")</option>";
		endforeach;
	echo "</optgroup>"; endif;
foreach($pages_array as $page_id => $page_info): 
	if ($page_id == $page_confirmed['page_id']): continue; endif; // cannot select itself 
	if (in_array($page_id,$children_confirmed)): continue; endif;
	echo "<option value='$page_id'>".$page_info['header']." (".$page_info['slug'].")</option>";
	endforeach;
echo "</select>";

// here you can manage lists of media and snippets
// show image gallery at top, middle, or bottom
// show list snippets at top, middle, or bottom
// show article at top, middle, or bottom
// organise list snippets alphabetically or by date
// organise list snippets as table or blockquote
if (!(empty($snippets_confirmed))):
	echo "<div style='display: inline-block; width: 600px; margin: 20px;'><span style='text-align: left; display: block; padding-bottom: 10px;'>snippets</span>";
	foreach($snippets_confirmed as $snippet_id):
		echo "<a href='/s/".$snippet_id."/edit/'>".$snippets_array[$snippet_id]['name']." (".$snippet_id.")</a><br>";
		endforeach;
	echo "</div>";
	endif;

echo "<div class='edit-subheaders'>Snippets (add only)</div>";
echo "<select name='snippets[]' size='9' style='width: 600px; margin: 10px auto 40px; display: block; border-radius: 4px;' multiple>";
// echo "<option disabled>Snippets (add only)</option>";
foreach($snippets_array as $snippet_id => $snippet_info): 
	if (in_array($snippet_id,$snippets_confirmed)): continue; endif;
	echo "<option value='$snippet_id'>".$snippet_info['name']." ($snippet_id)</option>";
	endforeach;
echo "</select>";

echo "<div class='edit-subheaders'>Popover</div>";
echo "<textarea name='popover' placeholder='popover' style='width: 560px !important; height: 400px !important; margin: 10px auto 40px; display: block;'>".$page_confirmed['popover']."</textarea>";

echo "<script>"; ?>
	$('#input-textarea').height($(window).height() - 80);
	window.onresize = function(event) { $('#input-textarea').height($(window).height() - 80) }
	$('#input-textarea').click(function(){ window.scrollTo(0, 310); });
<? echo "</script>";

//	$('#popover_button').click(function() { $('.lightbox-close').show(); $('#path_window').hide(); $('#list_window').hide(); $('#edit_window').hide(); $('#popover_window').show(); });
//	$('#list_button').click(function() { $('.lightbox-close').show(); $('#path_window').hide(); $('#popover_window').hide(); $('#edit_window').hide(); $('#list_window').show(); });
//	$('.lightbox-close').click(function() { $('.lightbox-close').hide(); $('#path_window').hide(); $('#popover_window').hide(); $('#list_window').hide(); $('#edit_window').show(); window.scrollTo(0, 270); });
//	$(document).keyup(function(e) {
//		if (e.keyCode === 27) { $('.lightbox-close').hide(); $('#path_window').hide(); $('#popover_window').hide(); $('#list_window').hide(); $('#edit_window').show(); window.scrollTo(0, 270); }
//		});

echo "</form>";

footer(); ?>
