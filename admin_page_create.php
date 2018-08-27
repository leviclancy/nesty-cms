<? html_header("Create");

$sql_temp = "SELECT COUNT(page_id) AS count FROM $database.pages";
$count_pages = $connection_pdo->prepare($sql_temp);
$count_pages->execute();
$result = $count_pages->fetchAll();
foreach ($result as $row):
	$count_pages = $row['count'];
	endforeach;

$sql_temp = "SELECT COUNT(snippet_id) AS count FROM $database.snippets";
$count_snippets = $connection_pdo->prepare($sql_temp);
$count_snippets->execute();
$result = $count_snippets->fetchAll();
foreach ($result as $row):
	$count_snippets = $row['count'];
	endforeach;

echo "<div id='create-window'>";

echo "<a href='/'><div id='create-window-home-button'>Home</div></a>";

echo "<a href='/new/'><div id='create-window-new-page-button' class='background_1'>new page</div></a>";
if (empty($count_pages)): echo "<span>There are no pages.</span>";
elseif ($count_pages == 1): echo "<span>There is one page.</span>";
else: echo "<span>There are ".number_format($count_pages)." pages.<span>"; endif;

echo "<a href='/add/'><div id='create-window-add-snippet-button' class='background_2'>add snippet</div></a>";
if (empty($count_snippets)): echo "<span>There are no snippets.</span>";
elseif ($count_snippets == 1): echo "<span>There is one snippet.</span>";
else: echo "<span>There are ".number_format($count_snippets)." snippets.<span>"; endif;

echo "</div>";

footer(); ?>
