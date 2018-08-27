<? amp_header("search", $domain."/search/");

if (empty($_SESSION['term']) && (empty($_SESSION['since']) || empty($_SESSION['through'])) ):
	echo "<p>No search inputs.</p>";
	footer(); endif;

if (empty($slug_temp)): $slug_temp = 1; endif;

$count_divider_temp = 20;

$before_since = [];

if (!(empty($_SESSION['since']))):
	$before_since[] = "after ".date("l jS F, o", strtotime($_SESSION['since'])); endif;
if (!(empty($_SESSION['through']))):
	$before_since[] = "before ".date("l jS F, o", strtotime($_SESSION['through'])); endif;

if (!(empty($before_since))):
	echo "<p>Searching ".implode(" and ", $before_since)." &nbsp;&nbsp; <a href='/search/?clear_date=yes'><b>reset</b></a></p>";
	endif;

if (!(empty($_SESSION['term']))):
	echo "<p>Search term: <i>".htmlspecialchars($_SESSION['term'])."</i> &nbsp;&nbsp; <a href='/search/?clear_term=yes'><b>reset</b></a></p>";
	endif;


if ($slug_temp == "listing"): echo "<table><tbody>"; endif;

$count_media_temp = 0;

$sql_temp = "SELECT media_id, datetime_original, filename_original FROM $database.media ORDER BY datetime_original DESC";
foreach($connection_pdo->query($sql_temp) as $row):

	if (!(empty($_SESSION['since'])) && (strtotime($row['datetime_original']) < strtotime($_SESSION['since']))): continue; endif;
	if (!(empty($_SESSION['through'])) && (strtotime($row['datetime_original']) > strtotime($_SESSION['through']))): continue; endif;
	if (!(empty($_SESSION['term'])) && !(strpos("*".strtolower($row['media_id']).strtolower($row['description']).strtolower($row['filename_original']), strtolower($_SESSION['term'])))): continue; endif;

	if ($slug_temp == "listing"):
		echo "<tr><td><a href='/m/".$row['media_id']."/'><i class='material-icons gray'>open_in_new</i></a> &nbsp;&nbsp;";
		echo $row['filename_original']."</td>";
		echo "<td>".date("l jS F, o", strtotime($row['datetime_original']))."</td></tr>";
		continue;
		endif;

	if ($count_media_temp >= ($slug_temp*$count_divider_temp)): $count_media_temp++; continue; endif;

	if ($count_media_temp < (($slug_temp-1)*$count_divider_temp)): $count_media_temp++; continue; endif;

	echo body_process("[[[".$row['media_id']."]]]");

	$count_media_temp++;

	endforeach;

$count_page_temp = 0;

$sql_temp = "SELECT page_id, created_time, header, body FROM $database.pages ORDER BY created_time DESC";
foreach($connection_pdo->query($sql_temp) as $row):

	if (!(empty($_SESSION['since'])) && (strtotime($row['created_time']) < strtotime($_SESSION['since']))): continue; endif;
	if (!(empty($_SESSION['through'])) && (strtotime($row['created_time']) > strtotime($_SESSION['through']))): continue; endif;
	if (!(empty($_SESSION['term'])) && !(strpos("*".strtolower($row['page_id']).strtolower($row['header']).strtolower($row['body']), strtolower($_SESSION['term'])))): continue; endif;

	if ($slug_temp == "listing"):
		echo "<tr><td><a href='/m/".$row['page_id']."/'><i class='material-icons gray'>open_in_new</i></a> &nbsp;&nbsp;";
		echo $row['header']."</td>";
		echo "<td>".date("l jS F, o", strtotime($row['created_time']))."</td></tr>";
		continue;
		endif;

	if ($count_page_temp >= ($slug_temp*$count_divider_temp)): $count_page_temp++; continue; endif;

	if ($count_page_temp < (($slug_temp-1)*$count_divider_temp)): $count_page_temp++; continue; endif;

	if ($count_page_temp == (($slug_temp-1)*$count_divider_temp)): echo "<hr>"; endif;

	echo body_process("{{{".$row['page_id']."}{tile}}}");

	$count_page_temp++;

	endforeach;

$count_snippet_temp = 0;

$sql_temp = "SELECT snippet_id, created_time, body FROM $database.pages ORDER BY created_time DESC";
foreach($connection_pdo->query($sql_temp) as $row):

	if (!(empty($_SESSION['since'])) && (strtotime($row['created_time']) < strtotime($_SESSION['since']))): continue; endif;
	if (!(empty($_SESSION['through'])) && (strtotime($row['created_time']) > strtotime($_SESSION['through']))): continue; endif;
	if (!(empty($_SESSION['term'])) && !(strpos("*".strtolower($row['snippet_id']).strtolower($row['body']), strtolower($_SESSION['term'])))): continue; endif;

	if ($slug_temp == "listing"):
		echo "<tr><td><a href='/m/".$row['page_id']."/'><i class='material-icons gray'>open_in_new</i></a> &nbsp;&nbsp;";
		echo $row['header']."</td>";
		echo "<td>".date("l jS F, o", strtotime($row['created_time']))."</td></tr>";
		continue;
		endif;

	if ($count_snippet_temp >= ($slug_temp*$count_divider_temp)): $count_snippet_temp++; continue; endif;

	if ($count_snippet_temp < (($slug_temp-1)*$count_divider_temp)): $count_snippet_temp++; continue; endif;

	if ($count_snippet_temp == (($slug_temp-1)*$count_divider_temp)): echo "<hr>"; endif;

	echo body_process("(((".$row['page_id'].")))");

	$count_snippet_temp++;

	endforeach;

if ($slug_temp == "listing"):
	echo "</tbody></table>";
	footer(); endif;

$count_temp = max([$count_media_temp, $count_page_temp, $count_snippet_temp]);
if ($count_temp > $count_divider_temp):
	echo "<hr>";
	echo "<div class='paginator'>";
	$pages_temp = ceil($count_temp/$count_divider_temp);
	$count_pages_temp = 1;
	while ($count_pages_temp <= $pages_temp):
		$chosen_temp = null; if ($count_pages_temp == $slug_temp): $chosen_temp = "class='bold'"; endif; 
		echo "<span ".$chosen_temp."><a href='/search/".$count_pages_temp."/'>".$count_pages_temp."</a></span>";
		$count_pages_temp++; endwhile;
	echo "</div>";
	endif;

footer();?>
