<? amp_header($domain);

echo "<h1>".$publisher."</h1>";

if (!(empty($description))):
	echo "<div class='home-description'>";
	echo body_process($description);
	echo "</div>";
	endif;

$count_temp = 0;
$sql_temp = "SELECT page_id, created_time, header FROM $database.pages ORDER BY created_time DESC LIMIT 5";
foreach($connection_pdo->query($sql_temp) as $row):
	if ($count_temp == 0): echo "<span class='home-header'>Recent posts</span>"; $count_temp++; endif;
	echo body_process("{{{".$row['page_id']."}{".$row['header']."}{tile}}}");
	endforeach;

$count_temp = 0;
$sql_temp = "SELECT media_id FROM $database.media ORDER BY datetime_original DESC LIMIT 5"; // datetime_process is alternative method of sorting
foreach($connection_pdo->query($sql_temp) as $row):
	if ($count_temp == 0): echo "<span class='home-header'>Latest images</span>"; $count_temp++; endif;
	echo body_process("[[[".$row['media_id']."][thumb]]]");
	endforeach;

// echo "<span class='home-header'><a href='/schedule/'>View archive</a></span>";

footer(); ?>
