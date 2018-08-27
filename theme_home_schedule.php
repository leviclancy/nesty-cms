<? amp_header($domain);


$dates_array = [];
$sql_temp = "SELECT page_id, slug, header, created_time FROM $database.pages";
$result = fetchall($sql_temp);
foreach ($result as $row):
	$year_temp = date("Y", strtotime($row['created_time']));
	$month_temp = date("Y-m-01", strtotime($row['created_time']));
	if (empty($dates_array[$year_temp])):
		$dates_array[$year_temp] = [
			"$year_temp-01-01"=>0, "$year_temp-02-01"=>0, "$year_temp-03-01"=>0, 
			"$year_temp-04-01"=>0, "$year_temp-05-01"=>0, "$year_temp-06-01"=>0, 
			"$year_temp-07-01"=>0, "$year_temp-08-01"=>0, "$year_temp-09-01"=>0, 
			"$year_temp-10-01"=>0, "$year_temp-11-01"=>0, "$year_temp-12-01"=>0 ];
		endif;
	$dates_array[$year_temp][$month_temp]++;
	$pages[$row['page_id']] = $row; endforeach;

$sql_temp = "SELECT media_id, datetime_original FROM $database.media";
$result = fetchall($sql_temp);
foreach ($result as $row):
	$year_temp = date("Y", strtotime($row['datetime_original']));
	$month_temp = date("Y-m-01", strtotime($row['datetime_original']));
	if (empty($dates_array[$year_temp])):
		$dates_array[$year_temp] = [
			"$year_temp-01-01"=>0, "$year_temp-02-01"=>0, "$year_temp-03-01"=>0, 
			"$year_temp-04-01"=>0, "$year_temp-05-01"=>0, "$year_temp-06-01"=>0, 
			"$year_temp-07-01"=>0, "$year_temp-08-01"=>0, "$year_temp-09-01"=>0, 
			"$year_temp-10-01"=>0, "$year_temp-11-01"=>0, "$year_temp-12-01"=>0 ];
		endif;
	$dates_array[$year_temp][$month_temp]++;
	endforeach;

krsort($dates_array);

foreach($dates_array as $year_temp => $months_temp):
	echo "<div id='schedule'>";
	ksort($months_temp);
	echo "<h2><a href='/search/?term=&since=".$year_temp."-01-01&through=".($year_temp+1)."-01-01'>$year_temp</a> <sup>".number_format(array_sum($months_temp))."</sup></h2>";
	$count_temp = 0;
	foreach ($months_temp as $month_temp => $tally_temp):
		if ($count_temp >= 3): echo "<br>"; $count_temp = 0; endif; $count_temp++;
		if (empty($tally_temp)): echo "<div>".date("F", strtotime($month_temp))."</div>"; continue; endif;
		echo "<div><a href='/search/?term=&since=".date("Y-m-01", strtotime($month_temp))."&through=".date("Y-m-01", strtotime($month_temp." +1 months"))."'>";
		echo date("F", strtotime($month_temp))."</a> <sup>".number_format($tally_temp)."</sup></div>";
		endforeach;
	echo "</div>";
	endforeach;

footer(); ?>
