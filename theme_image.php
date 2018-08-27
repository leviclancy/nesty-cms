<? amp_header($domain." media", $domain.$proper_uri);

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

echo "<div class='image_description'><p>".$media_confirmed[$slug_temp]['filename_original']."</p></div>";

$img_width = 600;
$img_height = round($img_width*$media_confirmed[$slug_temp]['height']/$media_confirmed[$slug_temp]['width']);

echo "<div class='image_large'><figure>";
echo "<amp-img src='/m/".$slug_temp."/large/' width='".$img_width."px' height='".$img_height."px' sizes='(min-width: 700px) 90vw, 70vw'></amp-img>";
echo "</figure></div>";

if (!(empty($media_confirmed[$slug_temp]['description']))): echo $media_confirmed[$slug_temp]['description']; endif;

// show connections
$media_confirmed[$slug_temp]['connections'] = [];
$sql_temp = "SELECT page_id from $database.pages WHERE body LIKE :media_id ORDER BY header ASC";
$retrieve_pages = $connection_pdo->prepare($sql_temp);
$retrieve_pages->execute(["media_id"=>"%".$slug_temp."%"]);
$result= $retrieve_pages->fetchAll();
foreach ($result as $row):
	$media_confirmed[$slug_temp]['connections'][] = $row['page_id'];
	endforeach;

echo "<hr>";

if (!(empty($media_confirmed[$slug_temp]['parents']))):
	foreach ($media_confirmed[$slug_temp]['parents'] as $page_id):
		echo "<div class='tile'><amp-fit-text width='180' height='150' max-font-size='30'><a href='/".$page_id."/'>".$pages_array[$page_id]['header']."</a></amp-fit-text></div>";
		endforeach;
	endif;
if (!(empty($media_confirmed[$slug_temp]['connections']))):
	foreach ($media_confirmed[$slug_temp]['connections'] as $page_id):
		echo "<div class='tile'><amp-fit-text width='180' height='150' max-font-size='30'><a href='/".$page_id."/'>".$pages_array[$page_id]['header']."</a></amp-fit-text></div>";
		endforeach;
	endif;

if (!(empty($media_confirmed[$slug_temp]['parents'])) || !(empty($media_confirmed[$slug_temp]['connections']))):
	echo "<hr>"; endif;

echo "<div class='tile'><amp-fit-text width='180' height='150' max-font-size='30'><a href='/m/".$slug_temp."/thumb/' target='_blank'><i class='material-icons'>link</i><br>thumbnail</a></amp-fit-text></div>";
echo "<div class='tile'><amp-fit-text width='180' height='150' max-font-size='30'><a href='/m/".$slug_temp."/large/' target='_blank'><i class='material-icons'>link</i><br>large file</a></amp-fit-text></div>";
echo "<div class='tile'><amp-fit-text width='180' height='150' max-font-size='30'><a href='/m/".$slug_temp."/full/' target='_blank'><i class='material-icons'>link</i><br>full file</a></amp-fit-text></div>";

echo "<hr>";

echo "<table><thead><tr><th>descriptor</th><th>information</th></tr></thead><tbody>";
echo "<tr><td>filename_original</td><td>".$media_confirmed[$slug_temp]['filename_original']."</td></tr>";
echo "<tr><td>directory</td><td>".$media_confirmed[$slug_temp]['directory']."</td></tr>";
echo "<tr><td>datetime_original</td><td>".$media_confirmed[$slug_temp]['datetime_original']."</td></tr>";
echo "<tr><td>datetime_processed</td><td>".$media_confirmed[$slug_temp]['datetime_processed']."</td></tr>";
echo "</tbody></table>";

echo "<br><br><br>";

footer(); ?>
