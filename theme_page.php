<? $retrieve_page->execute(["page_id"=>$page_temp]);
$result = $retrieve_page->fetchAll();
foreach ($result as $row):

	$page_confirmed[$page_temp]['body'] = body_process($row['body']);
	$page_confirmed[$page_temp]['password'] = $row['password'];
//	$page_confirmed[$page_temp]['studies'] = body_process($row['studies']);

	endforeach;


amp_header($page_confirmed[$page_temp]['header'], $domain.$proper_uri);


$parents = $siblings_temp = $children = $gallery = $snippets = $pages_array = [];
$pages_order_temp = $pages_order_list_marker_temp = $pages_order_header_temp = [];

$sql_temp = "SELECT * FROM $database.paths";
$result = fetchall($sql_temp);
foreach ($result as $row):
	if ($row['type'] == "page"):
		if ($row['parent_id'] == $page_temp): $children[] = $row['child_id']; endif;
		if ($row['child_id'] == $page_temp): $parents[] = $row['parent_id']; endif;
		if (empty($siblings_temp[$row['parent_id']])): $siblings_temp[$row['parent_id']] = []; endif;
		$siblings_temp[$row['parent_id']][] = $row['child_id'];
		endif;
	if ($row['parent_id'] !== $page_temp): continue; endif;
	if ($row['type'] == "media"): $gallery[] = $row['child_id']; endif;
	if ($row['type'] == "entry"): $snippets[] = $row['child_id']; endif;
	if ($row['type'] == "snippet"): $snippets[] = $row['child_id']; endif; endforeach;

$sql_temp = "SELECT page_id, list_marker, header FROM $database.pages";
$result = fetchall($sql_temp);
foreach ($result as $row):
	$pages_array[$row['page_id']] = $row;
	if (empty($row['list_marker'])): $pages_order_header_temp[$row['page_id']] = $row['header'];
	else: $pages_order_list_marker_temp[$row['page_id']] = $row['list_marker']; endif;
	endforeach;
natsort($pages_order_list_marker_temp);
natsort($pages_order_header_temp);
$pages_order_temp = array_merge($pages_order_list_marker_temp, $pages_order_header_temp);
$pages_array = array_merge($pages_order_temp, $pages_array);

if (!(empty($snippets))):

	$snippets = array_unique($snippets);	

	if (count($snippets) > 1):

		$snippets_order = [];
		$sql_temp = "SELECT snippet_id FROM $database.snippets ORDER BY year ASC, month ASC, day ASC, name ASC";
		$order_snippet = $connection_pdo->prepare($sql_temp);
		$order_snippet->execute();
		$result = $order_snippet->fetchAll();
		foreach ($result as $row): $snippets_order[] = $row['snippet_id']; endforeach;

		$snippets = array_intersect($snippets_order, $snippets);

		endif;

	$page_confirmed[$page_temp]['body'] .= "\n\n<hr>\n\n";
	foreach($snippets as $snippet_id):
		$page_confirmed[$page_temp]['body'] .= body_process("(((".$snippet_id.")))");
		endforeach;
	endif;


echo "<article><div vocab='http://schema.org/' typeof='Article'>";

echo "<header>";
echo "<h1 property='name' amp-fx='parallax' data-parallax-factor='1.3'>";
echo $page_confirmed[$page_temp]['header']."</h1></header>";

if (!(empty($page_confirmed[$page_temp]['body'])) || !(empty($gallery))):
	echo "<p amp-fx='parallax' data-parallax-factor='1.3' class='nesting-or-popover'>";

	if (!(empty($snippet_confirmed['password'])) && !(empty($_SESSION[$snippet_confirmed['page_id']]))):
		echo "<a href='/".$snippet_confirmed['page_id']."/*/'><i class='material-icons button'>lock</i> Lock post</a><br><br>";
		endif;

	echo "By <span property='author'>Levi Clancy</span> for <span property='publisher'>$publisher</span>";
	echo " on <time datetime='".$page_confirmed[$page_temp]['created_time']."' property='datePublished'>".date("l jS F, o", strtotime($page_confirmed[$page_temp]['created_time']))."</time>";
	if ($page_confirmed[$page_temp]['created_time'] !== $page_confirmed[$page_temp]['updated_time']):
		echo "<br><i>updated <time datetime='".$page_confirmed[$page_temp]['updated_time']."' property='dateModified'>".date("jS F, o", strtotime($page_confirmed[$page_temp]['updated_time']))."</time></i>";
		endif;
	echo "</p>";
	endif;

if (!(empty($children)) || !(empty($parents))):

	if (!(empty($page_confirmed[$page_temp]['body'])) || !(empty($gallery))):
		echo "<amp-accordion amp-fx='parallax' data-parallax-factor='1.3' class='nesting-or-popover'>";
		echo "<section><header><span class='header-outline show-more'>▶&#xFE0E; View related</span>";
		echo "<span class='header-outline show-less'>▼&#xFE0E; Tap to hide</span></header>";
		endif;

	$parents = array_intersect(array_keys($pages_array), $parents);
	$children = array_intersect(array_keys($pages_array), $children);

	echo "<ul>";

	if (!(empty($parents))):
		foreach ($parents as $parent_id):
			if ($parent_id == $page_temp): continue; endif;
			echo "<li><a href='/".$parent_id."/'>".$pages_array[$parent_id]['header']."</a>";
			if (!(empty($siblings_temp[$parent_id]))):
				$siblings_temp[$parent_id] = array_intersect(array_keys($pages_array), $siblings_temp[$parent_id]);
				echo "<ul>";
				foreach ($siblings_temp[$parent_id] as $sibling_id ):
					echo "<li>";
					if ($sibling_id == $page_temp): echo "<b>"; endif;
					if (!(empty($pages_array[$sibling_id]['list_marker']))): echo "<span class='list-marker'>".$pages_array[$sibling_id]['list_marker']."</span>"; endif;
					echo "<a href='/".$sibling_id."/'>".$pages_array[$sibling_id]['header']."</a>";
					if ($sibling_id == $page_temp): echo "</b>"; endif;
					echo "</li>";
					endforeach;
				echo "</ul>";
				endif;
			echo "</li>";
			endforeach;
		endif;

	if (!(empty($children))):
		$plural_temp = null; if (count($children) > 1): $plural_temp = "s"; endif;
		echo "<li>Subpage".$plural_temp."<ul>";
		foreach ($children as $child_id):
			if ($child_id == $page_temp): continue; endif;
			echo "<li>";
			if (!(empty($pages_array[$child_id]['list_marker']))): echo "<span class='list-marker'>".$pages_array[$child_id]['list_marker']."</span>"; endif;
			echo "<a href='/$child_id/'>".$pages_array[$child_id]['header']."</a></li>";
			endforeach;
		echo "</ul></li>";
		$genealogy_map = array_merge($genealogy_map, $children);
		endif;

	echo "</ul>";

	if (!(empty($page_confirmed[$page_temp]['body'])) || !(empty($gallery))):
		echo "</section></amp-accordion>";
		endif;

	endif;

// if there is a password, it requires the user form which cannot be an amp page
if (!(empty($page_confirmed[$page_temp]['password'])) && ( empty($_SESSION[$page_temp]) || ($_SESSION[$page_temp] !== $page_confirmed[$page_temp]['password']) ) ):
	echo "<div id='lock-window'>";
	echo "<form target='_top' action-xhr='https://".$domain."/unlock/' method='post'>";
	echo "<span>Post locked. Please enter post password.</span>";
	echo "<input type='password' name='password' placeholder='Password' autocomplete='off' id='lock-window-password' required>";
	echo "<input type='hidden' name='page' value='".$page_temp."'>";
	echo "<button type='submit' name='unlock' value='unlock' class='background_2' id='lock-window-submit-button'>Unlock page</button>";
	echo "<div submit-success><template type='amp-mustache'>Success!</template></div>";
	echo "<div submit-error><template type='amp-mustache'>{{{message}}}</template></div>";
	echo "</form></div>";
	echo "<br><br><br>";
	footer();
elseif (!(empty($page_confirmed[$page_temp]['password']))):
	echo "<div id='lock-window'>";
	echo "<form method='post' action-xhr='https://".$domain."/relock/'>";
	echo "<input type='hidden' name='page' value='".$page_temp."'>";
	echo "<button type='submit' name='relock' value='relock' class='background_3' id='lock-window-submit-button'>Relock page</button>";
	echo "</form></div>";	
	endif;


if (!(empty($page_confirmed[$page_temp]['body'])) || !(empty($gallery))):

	if (!(empty($page_confirmed['popover']))):
		echo "<amp-accordion amp-fx='parallax' data-parallax-factor='1.3' class='nesting-or-popover'>";
		echo "<section><header><span class='header-outline show-more'>▶&#xFE0E; Show table of contents</span>";
		echo "<span class='header-outline show-less'>▼&#xFE0E; Tap to hide</span></header>";
		echo $page_confirmed['popover'];
		echo "</section></amp-accordion>";
		endif;

	echo "<span property='articleBody'>";
	if (!(empty($page_confirmed[$page_temp]['body']))):
		echo $page_confirmed[$page_temp]['body'];
		endif;
	if (!(empty($gallery))):
		echo "<hr>";
		$gallery_array = [];
		foreach ($gallery as $media_id):
			$media_info = nesty_media($media_id);
			$key_temp = strtotime($media_info[$media_id]['datetime_original'])."_".random_code(5);
			$gallery_array[$key_temp] = $media_id;
			endforeach;
		ksort($gallery_array);
		foreach($gallery_array as $media_id):
			echo body_process("[[[".$media_id."]]]");
			endforeach;
		endif;
	echo "</span>";

	endif;

echo "</div></article>";

footer(); ?>
