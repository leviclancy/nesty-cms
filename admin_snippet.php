<? if ($page_temp == "add"): html_header("new", "+ snippet");
else: html_header($snippet_confirmed['name'], $domain."/edit/"); endif;

$sql_temp = "SELECT page_id, header, slug FROM $database.pages ORDER BY header ASC, slug ASC";
$retrieve_pages = $connection_pdo->prepare($sql_temp);
$retrieve_pages->execute();
$result = $retrieve_pages->fetchAll();
foreach ($result as $row):
	$pages_array[$row['page_id']] = $row; endforeach;

if (empty($pages_array)):
	echo "<span class='warning'>must have at least one page for parentage</span>";
	footer(); endif;

$snippet_temp = $slug_temp;

if (isset($_POST['snippet_edit'])):

	$snippet_temp = $_POST['snippet_id'];

	$body_text = $_POST['body'];
	$body_text = str_replace("[[[", "\n\n[[[", $body_text);
	$body_text = str_replace("]]]", "]]]\n\n", $body_text);
	$body_text = preg_replace("/\r\n/", "\n", $body_text);
	$body_text = preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n\n", $body_text);
	$body_text = trim($body_text);
	if (ctype_space($body_text)): $body_text = null; endif;

	// convert all images to links
//	preg_match_all("/(?<=\[\[\[)(.*?)(?=\]\]\])/is", $body_text, $matches_temp);
//	if (empty($matches_temp)): $matches_temp = [ [], [] ]; endif;
//	foreach ($matches_temp[0] as $temp): $body_text = str_replace("[[[".$temp."]]]", "{{{".str_replace("][", "}{", $temp)."}}}", $body_text); endforeach;

	// convert all citations to links
	preg_match_all("/(?<=\(\(\()(.*?)(?=\)\)\))/is", $body_text, $matches_temp);
	if (empty($matches_temp)): $matches_temp = [ [], [] ]; endif;
	foreach ($matches_temp[0] as $temp): $body_text = str_replace("(((".$temp.")))", "{{{".str_replace(")(", "}{", $temp)."}}}", $body_text); endforeach;

	$values_temp = [
		"snippet_id"=>$_POST['snippet_id'],
		"updated_time"=>date("Y-m-d"), 
		"name"=>$_POST['name'], 
		"year"=>$_POST['year'], 
		"month"=>$_POST['month'], 
		"day"=>$_POST['day'], 
		"created_time"=>$_POST['created_time'], 
		"body"=>$body_text ];
	$sql_temp = sql_setup($values_temp, "$database.snippets");
	$update_snippet = $connection_pdo->prepare($sql_temp);
	$update_snippet->execute($values_temp);
	execute_checkup($update_snippet->errorInfo(), "updating snippet content okay");
	if (empty($_POST['parents'])): $_POST['parents'] = []; endif;

	// delete from where child_id = snippet_id
	$sql_temp = "DELETE FROM $database.paths WHERE child_id=:snippet_id";
	$paths_delete_statement = $connection_pdo->prepare($sql_temp);
	$paths_delete_statement->execute(["snippet_id"=>$_POST['snippet_id']]);
	execute_checkup($paths_delete_statement->errorInfo(), "clearing old paths");
	$values_temp = [
		"path_id"=>null,
		"parent_id"=>null,
		"child_id"=>null,
		"type"=>null ];
	$sql_temp = sql_setup($values_temp, "$database.paths");
	$paths_insert_statement = $connection_pdo->prepare($sql_temp);
	foreach($_POST['parents'] as $page_id_temp):
		$values_temp = [
			"path_id"=>random_code(7),
			"parent_id"=>$page_id_temp,
			"child_id"=>$_POST['snippet_id'],
			"type"=>"snippet" ];
		$paths_insert_statement->execute($values_temp);
		execute_checkup($paths_insert_statement->errorInfo(), "inserting parent paths");
		endforeach;
	endif;

$retrieve_snippet->execute(["snippet_id"=>$slug_temp]);
$result = $retrieve_snippet->fetchAll();
foreach ($result as $row): $snippet_confirmed = $row; endforeach;

if (empty($snippet_confirmed)):
	$snippet_confirmed = ["snippet_id"=>random_code(10), "name"=>null, "created_time"=>date("Y-m-d"), "body"=>null]; endif;


$snippet_confirmed['parents'] = [];
$sql_temp = "SELECT * FROM $database.paths WHERE child_id=:snippet_id";
$retrieve_paths = $connection_pdo->prepare($sql_temp);
$retrieve_paths->execute(["snippet_id"=>$snippet_confirmed['snippet_id']]);
$result = $retrieve_paths->fetchAll();
foreach ($result as $row):
	$snippet_confirmed['parents'][]= $row['parent_id']; endforeach;

$snippet_confirmed['citations'] = [];
$sql_temp = "SELECT page_id FROM $database.pages WHERE body LIKE :snippet_id";
$retrieve_page_citations = $connection_pdo->prepare($sql_temp);
$retrieve_page_citations->execute(["snippet_id"=>"$".$snippet_confirmed['snippet_id']."%"]);
$result = $retrieve_page_citations->fetchAll();
foreach ($result as $row):
	$snippet_confirmed['citations'][] = $row['page_id']; endforeach;

echo "<div id='edit-window'>";
if ($page_temp !== "new"):
	echo "<div id='edit-window-create-button' class='background_1'><a href='/create/' target='_blank'><i class='material-icons'>note_add</i> Create</a></div>";
	echo "<div id='edit-window-settings-button'><a href='/account/'><i class='material-icons'>settings</i></a></div>";
//	echo "<div id='edit-window-delete-button' style='right: 20px;'><a href='/e/".$snippet_confirmed['snippet_id']."/delete/'>Delete</a></div>";
	echo "<div id='edit-window-delete-button' style='right: 160px;'><a href='/".$snippet_confirmed['snippet_id']."/delete/'>Delete</a></div>";
	echo "<div id='edit-window-open-button'><a href='/e/".$snippet_confirmed['snippet_id']."/' target='_blank'>Open snippet</a></div>";
else:
	echo "<div id='edit-window-create-button' style='background: #555;'><i class='material-icons'>note_add</i> Create</div>";
	echo "<div id='edit-window-settings-button'><a href='/account/'><i class='material-icons'>settings</i></a></div>";
	echo "<div id='edit-window-home-button'><a href='/' target='_blank'>Home</a></div>";
	endif;
echo "</div>";

echo "<form action='/e/".$snippet_confirmed['snippet_id']."/edit/' method='post'>";

echo "<button type='submit' name='snippet_edit' value='save' class='floating-action-button'>save</button>";

echo "<input type='hidden' name='snippet_id' value='".$snippet_confirmed['snippet_id']."'>"; ?>

	<style>
		option { width: 360px;  }
		option:checked {  }
		optgroup { font-weight: 400; padding: 10px 10px 0 10px; font-style: italic !important; color: #bbb !important; }
		optgroup option { font-style: normal; color: #333; } optgroup option:first-child { margin-top: 5px; }

		#input-name { display: block; width: 90%; max-width: 900px; margin: 20px auto; border-radius: 4px; }
		#input-date { display: block; width: 90%; max-width: 900px; margin: 20px auto; text-align: center; }
		#input-date input { margin: 10px; padding: 5px; border: 0; border-bottom: 1px solid #bbb; text-align: center; display: inline-block; border-radius: 0; }
		#input-date-year { width: 50px; }
		#input-date-month { width: 40px; }
		#input-date-day { width: 35px; }
		
		.parent_select { margin: 20px auto; display: block; border: 0; width: 90%; max-width: 400px; }

		#textarea-body { display: block; width: 90%; max-width: 900px; margin: 20px auto; }
	</style>

<? echo "<input type='text' name='name' id='input-name' value='".htmlspecialchars($snippet_confirmed['name'], ENT_QUOTES)."' placeholder='name'>";

echo "<div id='input-date'>";
echo "<input type='number' name='year' id='input-date-year' value='".$snippet_confirmed['year']."' placeholder='yyyy'>";
echo "<input type='number' name='month' id='input-date-month' value='".$snippet_confirmed['month']."' min='1' max='12' placeholder='mm'>";
echo "<input type='number' name='day' id='input-date-day' value='".$snippet_confirmed['day']."' placeholder='dd' min='1' max='31'>";
echo "</div>";

echo "<select name='parents[]' class='parent_select' multiple required>";
$parents_confirmed = $citations_confirmed = [];
foreach($pages_array as $page_id => $page_info): 
	if (in_array($page_id,$snippet_confirmed['parents'])): $parents_confirmed[] = $page_id; endif;
	if (in_array($page_id,$snippet_confirmed['citations'])): $citations_confirmed[] = $page_id; endif;
	endforeach;
if (!(empty($citations_confirmed))):
	echo "<optgroup label='citations'>";
	foreach($parents_confirmed as $page_id):
		echo "<option disabled>".$pages_array[$page_id]['header']." (".$pages_array[$page_id]['slug'].")</option>";
		endforeach;
	echo "</optgroup>"; endif;
if (!(empty($parents_confirmed))):
	echo "<optgroup label='selected'>";
	foreach($parents_confirmed as $page_id):
		echo "<option value='$page_id' selected>".$pages_array[$page_id]['header']." (".$pages_array[$page_id]['slug'].")</option>";
		endforeach;
	echo "</optgroup>"; endif;
foreach($pages_array as $page_id => $page_info):
	if (in_array($page_id,$parents_confirmed)): continue; endif;
	echo "<option value='$page_id'>".$page_info['header']." (".$page_info['slug'].")</option>";
	endforeach;
echo "</select>";

echo "<textarea id='textarea-body' name='body' required>".$snippet_confirmed['body']."</textarea>";

if (empty($snippet_confirmed['created_time'])): $snippet_confirmed['created_time'] = date("Y-m-d"); endif;
echo "<input type='hidden' name='created_time' value='".$snippet_confirmed['created_time']."'>";

echo "<script>"; ?>
	$('#textarea-body').height($(window).height() - 50);
	window.onresize = function(event) {
		$('#textarea-body').height($(window).height() - 50); }
<? echo "</script>";

echo "</form>";
footer(); ?>
