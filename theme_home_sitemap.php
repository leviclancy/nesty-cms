<? amp_header($domain);

$dates_array = [];
$sql_temp = "SELECT page_id, slug, header, created_time FROM $database.pages";
$result = fetchall($sql_temp);
foreach ($result as $row):
	$pages[$row['page_id']] = $row; endforeach;

$skip_array = [];
$sql_temp = "SELECT child_id, parent_id FROM $database.paths WHERE type='page'";
$result = fetchall($sql_temp);
foreach ($result as $row):
	if (empty($pages[$row['parent_id']]) || empty($pages[$row['child_id']])): continue; endif;
	if (empty($relationships[$row['parent_id']])): $relationships[$row['parent_id']] = []; endif;
	$relationships[$row['parent_id']][] = $row['child_id'];
	$skip_array[] = $row['child_id']; endforeach;

function print_relationships($page_id, $completed_array=[]) {
	global $domain;
	global $pages;
	global $relationships;
	if (empty($relationships[$page_id])): return null; endif;
	echo "<ul>";
	foreach($relationships[$page_id] as $child_id):
		$slug_temp = null; if (!(empty($pages[$child_id]['slug']))): $slug_temp = $pages[$child_id]['slug']."/"; endif;
		echo "<li><a href='/".$child_id."/".$slug_temp."'>".$pages[$child_id]['header']."</a>";
		if (!(empty($relationships[$child_id])) && !(in_array($child_id, $completed_array))):
			$completed_array[] = $child_id;
			$completed_array = print_relationships($child_id, $completed_array);
			endif;
		echo "</li>";
		endforeach;
	echo "</ul>";
	return $completed_array; }

$completed_array = [];
foreach ($pages as $page_id => $page_info):
	if (in_array($page_id, $skip_array)): continue; endif;
	$slug_temp = null; if (!(empty($pages[$page_id]['slug']))): $slug_temp = $pages[$page_id]['slug']."/"; endif;
	echo "<h2><a href='/".$page_id."/".$slug_temp."'>".$pages[$page_id]['header']."</a></h2>";
	if (empty($relationships[$page_id]) || in_array($page_id, $completed_array)):
		continue; endif;
	$completed_array[] = $page_id;
	$completed_array = print_relationships($page_id, $completed_array);
	endforeach;

footer(); ?>
