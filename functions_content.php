<? // prepare for nesty_page
$sql_temp = "SELECT * FROM $database.pages WHERE page_id=:page_id";
$retrieve_page = $connection_pdo->prepare($sql_temp);

// prepare for nesty_media
$sql_temp = "SELECT * FROM $database.media WHERE media_id=:media_id";
$retrieve_media = $connection_pdo->prepare($sql_temp);

// prepare for nesty_snippet
$sql_temp = "SELECT * FROM $database.snippets WHERE snippet_id=:snippet_id";
$retrieve_snippet = $connection_pdo->prepare($sql_temp);



function nesty_page($page_id_temp) {
	global $domain;
	global $publisher;
	global $login;
	
	global $connection_pdo;
	global $retrieve_page;
	global $retrieve_media;
	global $retrieve_snippet;


	if (empty($page_id_temp)): return null; endif;
	$domain_temp = $domain;
	if (strpos($page_id_temp, "|")):
		$domain_page_id_temp = explode("|", $page_id_temp);
		if (strpos($domain_page_id_temp[0], ".")): $domain_temp = $domain_page_id_temp[0]; $page_id_temp = $domain_page_id_temp[1];
		else: $domain_temp = $domain_page_id_temp[1]; $page_id_temp = $domain_page_id_temp[0]; endif; endif;
	$page_info = [];
	if (empty($domain_temp) || ($domain == $domain_temp)):
		$retrieve_page->execute(["page_id"=>(string)$page_id_temp]);
		$result = $retrieve_page->fetchAll();
		foreach ($result as $row):
			$slug_temp = null; if (!(empty($row['slug']))): $slug_temp = $row['slug']."/"; endif;
			$page_info[$row['page_id']] = [
				"page_id" => $row['page_id'],
				"slug" => $row['slug'],
				"created_time" => $row['created_time'],
				"updated_time" => $row['updated_time'],
				"header" => $row['header'],
				"domain" => $domain,
				"publisher" => $publisher,
				"link" => "https://".$domain_temp."/".$row['page_id']."/".$slug_temp ];
			endforeach;
	else:
		$page_info = file_get_contents("https://".$domain_temp."/".(string)$page_id_temp."/ping/"); // check if the page exists
		$page_info = json_decode($page_info, true); // decode the json
		endif;
	if (empty($page_info[$page_id_temp])): return null; endif;
	return $page_info; }



function nesty_media($media_id_temp, $response_temp="full") {
	global $domain;
	global $publisher;
	global $login;
	
	global $connection_pdo;
	global $retrieve_page;
	global $retrieve_media;
	global $retrieve_snippet;


	if (empty($media_id_temp)): return null; endif;
	$domain_temp = $domain;
	if (strpos($media_id_temp, "|")):
		$domain_media_id_temp = explode("|", $media_id_temp);
		if (strpos($domain_media_id_temp[0], ".")): $domain_temp = $domain_media_id_temp[0]; $media_id_temp = $domain_media_id_temp[1];
		else: $domain_temp = $domain_media_id_temp[1]; $media_id_temp = $domain_media_id_temp[0]; endif; endif;	
	if (empty($domain_temp) || ($domain == $domain_temp)):

		$retrieve_media->execute(["media_id"=>utf8_encode($media_id_temp)]);
	
		$result = $retrieve_media->fetchAll();
		
		foreach ($result as $row):
		
			$description_temp = $width_temp = $height_temp = $type_temp = $attr_temp = null;

			if ($response_temp == "full"):
				$description_temp = $row['description'];
				// convert all images to links
				preg_match_all("/(?<=\[\[\[)(.*?)(?=\]\]\])/is", $description_temp, $matches_temp);
				if (empty($matches_temp)): $matches_temp = [ [], [] ]; endif;
				foreach ($matches_temp[0] as $temp): $description_temp = str_replace("[[[".$temp."]]]", "{{{".str_replace("][", "}{", $temp)."}}}", $description_temp); endforeach;
				$description_temp = body_process($description_temp);
				endif;

			// check if file exists and height and width
			$thumb_url = "https://".$domain_temp."/media/".$row['directory']."/".$row['filename_thumb'];
			list($width_temp, $height_temp, $type_temp, $attr_temp) = getimagesize($thumb_url);
			if (empty($width_temp)): continue; endif;
	
			$media_info[$row['media_id']] = [
				"media_id"=>$row['media_id'],
				"domain"=>$domain_temp,
				"publisher"=>$publisher,
				"link"=>"https://$domain/m/".$row['media_id']."/",
				"directory"=>$row['directory'],
				"description"=>$description_temp,
				"height"=>$height_temp, // provided by list function
				"width"=>$width_temp, // provided by list function
 				"type"=>$type_temp, // provided by list function
 				"attr"=>$attr_temp, // provided by list function
				"header"=>$row['datetime_original'],
				"datetime_original"=>$row['datetime_original'] ];
			endforeach;
	else:
		$media_info = file_get_contents("https://".$domain_temp."/m/".(string)$media_id_temp."/ping/"); // check if the media exists
		$media_info = json_decode($media_info, true); // decode the json
		endif;
	if (empty($media_info[$media_id_temp])): return null; endif;
	return $media_info; }



function nesty_snippet($snippet_id_temp) {
	global $domain;
	global $publisher;
	global $login;
	
	global $connection_pdo;
	global $retrieve_page;
	global $retrieve_media;
	global $retrieve_snippet;


	if (empty($snippet_id_temp)): return null; endif;
	if (strpos($snippet_id_temp, "|")):
		$domain_snippet_id_temp = explode("|", $snippet_id_temp);
		if (strpos($domain_snippet_id_temp[0], ".")): $domain_temp = $domain_snippet_id_temp[0]; $snippet_id_temp = $domain_snippet_id_temp[1];
		else: $domain_temp = $domain_snippet_id_temp[1]; $snippet_id_temp = $domain_snippet_id_temp[0]; endif; endif;
	if (empty($domain_temp) || ($domain == $domain_temp)):
		$snippet_confirmed = [];
		$retrieve_snippet->execute(["snippet_id"=>(string)$snippet_id_temp]);
		$result = $retrieve_snippet->fetchAll();
		foreach ($result as $row):

			// convert all images to links
//			preg_match_all("/(?<=\[\[\[)(.*?)(?=\]\]\])/is", $row['body'], $matches_temp);
//			if (empty($matches_temp)): $matches_temp = [ [], [] ]; endif;
//			foreach ($matches_temp[0] as $temp): $row['body'] = str_replace("[[[".$temp."]]]", "{{{".str_replace("][", "}{", $temp)."}}}", $row['body']); endforeach;

			// convert all snippets to links
			preg_match_all("/(?<=\(\(\()(.*?)(?=\)\)\))/is", $row['body'], $matches_temp);
			if (empty($matches_temp)): $matches_temp = [ [], [] ]; endif;
			foreach ($matches_temp[0] as $temp): $row['body'] = str_replace("(((".$temp.")))", "{{{".str_replace(")(", "}{", $temp)."}}}", $row['body']); endforeach;

			$snippet_confirmed[$row['snippet_id']] = [
				"snippet_id"=>$row['snippet_id'],
				"domain"=>$domain,
				"publisher"=>$publisher,
				"name"=>$row['name'],
				"year"=>$row['year'],
				"month"=>$row['month'],
				"day"=>$row['day'],
				"body"=> body_process($row['body']) ];				
			endforeach;
	else:
		$snippet_info = file_get_contents("https://$domain/s/".(string)$snippet_id_temp."/ping/"); // check if the media exists
		$snippet_confirmed = json_decode($snippet_info, true); // decode the json
		endif;
	if (empty($snippet_confirmed[$snippet_id_temp])): return null; endif;
	return $snippet_confirmed; }



function body_process($body_incoming) {
	global $domain;
	global $publisher;
	global $login;
	
	global $connection_pdo;
	global $retrieve_page;
	global $retrieve_media;
	global $retrieve_snippet;
	
	
	$body_incoming = str_replace("\r", "\n", $body_incoming);
	
	$delimiter = "\n\n";

	$body_incoming = $delimiter.$body_incoming.$delimiter;
	
	$line_break_placeholder = random_code(5);
	
	$body_incoming = str_replace($delimiter."|||***", $delimiter."<table><thead><tr><th>", $body_incoming);
	$body_incoming = str_replace("\n|||***", "</th><th>", $body_incoming);
	$body_incoming = str_replace("|||***", $delimiter."<table><thead><tr><th>", $body_incoming);
	$body_incoming = str_replace($delimiter."---\n---".$delimiter."***", "</th></tr></thead><tbody>\n<tr><td>".$delimiter, $body_incoming);
	$body_incoming = str_replace($delimiter."---\n---", $delimiter."</td></tr></tbody></table>".$delimiter, $body_incoming);
	$body_incoming = str_replace($delimiter."---".$delimiter."***", $delimiter."</td></tr>\n<tr><td>".$delimiter, $body_incoming);
	$body_incoming = str_replace("\n***", $delimiter."</td><td>".$delimiter, $body_incoming);
	$body_incoming = str_replace("<blockquote>", $delimiter."<blockquote>".$delimiter, $body_incoming);
	$body_incoming = str_replace("</blockquote>", $delimiter."</blockquote>".$delimiter, $body_incoming);

	$image_lightbox_array = [];
	
	// process <samp>  text
	preg_match_all("/(?<=\<samp)(.*?)(?=\<\/samp\>)/is", $body_incoming, $matches);
	if (empty($matches)): $matches = [ [], [] ]; endif;
	$matches = array_unique($matches[0]);
	foreach ($matches as $match_temp):
		$samp_string = $match_temp;
		$samp_string = str_replace("<", "&lt;", $samp_string);
		$samp_string = str_replace("\n", $line_break_placeholder, $samp_string);
		$body_incoming = str_replace("<samp".$match_temp."</samp>", "<samp".$samp_string."</samp>", $body_incoming);
		endforeach;
	
	// process <kbd>  text
	preg_match_all("/(?<=\<kbd)(.*?)(?=\<\/kbd\>)/is", $body_incoming, $matches);
	if (empty($matches)): $matches = [ [], [] ]; endif;
	$matches = array_unique($matches[0]);
	foreach ($matches as $match_temp):
		$kbd_string = $match_temp;
		$kbd_string = str_replace("<", "&lt;", $kbd_string);
		$kbd_string = str_replace("\n", $line_break_placeholder, $kbd_string);
		$body_incoming = str_replace("<kbd".$match_temp."</kbd>", "<kbd".$kbd_string."</kbd>", $body_incoming);
		endforeach;

	
	// process links first
	$matches = [];
	preg_match_all("/(?<=\{\{\{)(.*?)(?=\}\}\})/is", $body_incoming, $matches);
//	preg_match_all("/(?<=\{\{\{)(.+)(?=\}\}\})/is", $body_incoming, $matches); // too greedy
	if (empty($matches)): $matches = [ [], [] ]; endif;
	$matches = array_unique($matches[0]);
	foreach ($matches as $match_temp):

		$link_string = $link_type = null;
	
		$temp_array = explode("}{", $match_temp."}{");

		$anchor_temp = null;
		if (strpos($temp_array[0], "#") !== FALSE):
			$temp_array[0] = explode("#", $temp_array[0]);
			if (!(empty($temp_array[0][1]))): $anchor_temp = "#".$temp_array[0][1]; endif;
			$temp_array[0] = $temp_array[0][0];
			endif;

		if (strpos($temp_array[0], "_") !== FALSE): $link_info = nesty_media($temp_array[0], "short");
		else: $link_info = nesty_page($temp_array[0]); endif; // check if the page exists
	
		$link_id_temp = $temp_array[0];
		if (strpos($temp_array[0], "|")):
			$domain_id_temp = explode("|", $temp_array[0]);
			if (strpos($domain_id_temp[0], ".")): $link_id_temp = $domain_id_temp[1];
			else: $link_id_temp = $domain_id_temp[0]; endif;
			endif;

		if (in_array($temp_array[1], ["button", "tile", "link"])): $link_type = $temp_array[1]; unset($temp_array[1]);
		elseif (in_array($temp_array[2], ["button", "tile", "link"])): $link_type = $temp_array[2]; unset($temp_array[2]); endif;

		if (!(empty($temp_array[1]))): $link_string = $temp_array[1];
		elseif (!(empty($temp_array[2]))): $link_string = $temp_array[2];
		elseif (!(empty($link_info[$link_id_temp]['header']))): $link_string = $link_info[$link_id_temp]['header']; endif;

		if (empty($link_info[$link_id_temp])):
			$body_incoming = str_replace("{{{".$match_temp."}}}", $link_string, $body_incoming);
			continue; endif; // page id does not exist so skip it

		if (empty($link_string)): $link_string = "<i class='material-icons'>link</i>"; endif;
	
		if ($link_type == "button"): $link_type = "tile"; endif;
	
		// remove all images inside links
		preg_match_all("/(?<=\[\[\[)(.*?)(?=\]\]\])/is", $link_string, $matches_temp);
		if (empty($matches_temp)): $matches_temp = [ [], [] ]; endif;
		foreach ($matches_temp[0] as $temp): $link_string = str_replace("[[[".$temp."]]]", null, $link_string); endforeach;

		// remove all snippets inside links
		preg_match_all("/(?<=\(\(\()(.*?)(?=\)\)\))/is", $link_string, $matches_temp);
		if (empty($matches_temp)): $matches_temp = [ [], [] ]; endif;
		foreach ($matches_temp[0] as $temp): $link_string = str_replace("(((".$temp.")))", null, $link_string); endforeach;
	
		if ($link_type == "tile"):
			$link_string = "<div class='tile'><a href='".$link_info[$link_id_temp]['link'].$anchor_temp."'>".$link_string;
			$link_string .= "<div class='tile-read-more background_".rand(1,10)."'>Read more</div></a>";
			if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && ($link_info[$link_id_temp]['domain'] == $domain)):
				$link_string .= "<a href='".$link_info[$link_id_temp]['link']."edit/'><div class='tile-edit'>Edit</div></a>";
				endif;
			$link_string .= "</div>";
		else:
			$link_string = "<a href='".$link_info[$link_id_temp]['link'].$anchor_temp."'>".$link_string."</a>";
			endif;
	
		$body_incoming = str_replace("{{{".$match_temp."}}}", $link_string, $body_incoming);
	
		endforeach;
	
	// process media next
	$matches = [];
	preg_match_all("/(?<=\[\[\[)(.*?)(?=\]\]\])/is", $body_incoming, $matches);
	if (empty($matches)): $matches = [ [], [] ]; endif;
	$matches = array_unique($matches[0]);	
	foreach ($matches as $match_temp):

		$image_string = $filename_size = $file_description = null;

		$temp_array = explode("][", $match_temp."][");
		if (empty(temp_array[1])): $temp_array[1] = null; endif;
		if (empty(temp_array[2])): $temp_array[2] = null; endif;

		$media_info = nesty_media($temp_array[0]);

		$media_id_temp = $temp_array[0];
		if (strpos($temp_array[0], "|")):
			$domain_id_temp = explode("|", $temp_array[0]);
			if (strpos($domain_id_temp[0], ".")): $media_id_temp = $domain_id_temp[1];
			else: $media_id_temp = $domain_id_temp[0]; endif;
			endif;

		if (empty($media_info[$media_id_temp])):
			$body_incoming = str_replace("[[[".$match_temp."]]]", null, $body_incoming);
			continue; endif; // media id does not exist so skip it
		
		if (in_array($temp_array[1], ["full", "large", "thumb"])): $filename_size = $temp_array[1]; unset($temp_array[1]);
		elseif (in_array($temp_array[2], ["full", "large", "thumb"])): $filename_size = $temp_array[2]; unset($temp_array[2]); endif;

		if (!(empty($temp_array[1]))): $file_description = $temp_array[1];
		elseif (!(empty($temp_array[2]))): $file_description = $temp_array[2];
		elseif (!(empty($media_info[$media_id_temp]['description']))): $file_description = $media_info[$media_id_temp]['description']; endif;
	
		// convert all images to links
		preg_match_all("/(?<=\[\[\[)(.*?)(?=\]\]\])/is", $file_description, $matches_temp);
		if (empty($matches_temp)): $matches_temp = [ [], [] ]; endif;
		foreach ($matches_temp[0] as $temp): $file_description = str_replace("[[[".$temp."]]]", "{{{".str_replace("][", "}{", $temp)."}}}", $file_description); endforeach;

		// remove all snippets inside images
		preg_match_all("/(?<=\(\(\()(.*?)(?=\)\)\))/is", $file_description, $matches_temp);
		if (empty($matches_temp)): $matches_temp = [ [], [] ]; endif;
		foreach ($matches_temp[0] as $temp): $file_description = str_replace("(((".$temp.")))", null, $file_description); endforeach;
	
		$file_description = body_process($file_description);
	
		$img_height = 240;
		$img_width = round(240*$media_info[$media_id_temp]['width']/$media_info[$media_id_temp]['height']);
		$img_height_large = round(2.5*$img_height);
		$img_width_large = round(2.5*$img_width);

		if ($filename_size == "full"):
			$image_string = "<a href='".$media_info[$media_id_temp]['link']."' on='tap:lightbox".$media_id_temp."' role='button' tabindex='1'>view image</a>";
	
		elseif ($filename_size == "large"):
			$image_string = "<div class='image_large'>";
			$image_string .= "<figure><amp-img on='tap:lightbox".$media_id_temp."' src='".$media_info[$media_id_temp]['link']."large/' width='".$img_width_large."px' height='".$img_height_large."px' role='button' tabindex='1' sizes='(min-width: 1100px) 1000px, (min-width: 500px) 90vw, 90vw'></amp-img>";
			if (!(empty($file_description))):
				$image_string .= "<amp-fit-text width='".($img_width_large)."px' height='30px' min-font-size='14px' max-font-size='14px'>".mb_substr(strip_tags(str_replace(["</th>", "</td>", "</div>", "</p>", "<br>", "<br />"], ' ',$file_description)),0,200)."</amp-fit-text>";
				endif;
			$image_string .= "</figure>";
			$image_string .= "<a href='".$media_info[$media_id_temp]['link']."' target='_blank'><div class='image-div-link-button material-icons'>link</div></a>";
			$image_string .= "<div on='tap:lightbox".$media_id_temp."' role='button' tabindex='1' class='image-div-open-button'>Tap to open</div>";
			$image_string .= "</div>";

		else:
			$image_string = "<div class='image_thumbnail'>";
			$image_string .= "<figure><amp-img on='tap:lightbox".$media_id_temp."' src='".$media_info[$media_id_temp]['link']."thumb/' width='".$img_width."px' height='".$img_height."px' role='button' tabindex='1' sizes='(min-width: ".($img_width+100)."px) ".$img_width."px, 70vw'></amp-img>";
			$image_string .= "<amp-fit-text width='".($img_width)."px' height='30px' min-font-size='14px' max-font-size='14px' sizes='(min-width: ".($img_width+100)."px) ".($img_width)."px, 70vw'>".mb_substr(strip_tags(str_replace(["</th>", "</td>", "</div>", "</p>", "<br>", "<br />"], ' ', $file_description)),0,200)."</amp-fit-text>";
			$image_string .= "</figure>";
			$image_string .= "<a href='".$media_info[$media_id_temp]['link']."' target='_blank'><div class='image-div-link-button material-icons'>link</div></a>";
			$image_string .= "<div on='tap:lightbox".$media_id_temp."' role='button' tabindex='1' class='image-div-open-button'>Tap to open</div>";
			$image_string .= "</div>"; endif;

		$body_incoming = str_replace("[[[".$match_temp."]]]", $image_string, $body_incoming);
	
		$lightbox_temp = "<amp-lightbox scrollable id='lightbox".$media_id_temp."' layout='nodisplay'>";
		if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && ($media_info[$media_id_temp]['domain'] == $domain)):
			$lightbox_temp .= "<div class='floating-action-button'><a href='/m/".$media_id_temp."/edit/'>edit</a></div>";
			endif;
		$lightbox_temp .= "<figure><div class='image_large' on='tap:lightbox".$media_id_temp.".close' tabindex='1' role='button'><amp-img src='".$media_info[$media_id_temp]['link']."large/' width='".$img_width_large."px' height='".$img_height_large."px' sizes='(min-width: 1100px) 1000px, (min-width: 500px) 90vw, 90vw'></amp-img></div>";
		$lightbox_temp .= "<a href='".$media_info[$media_id_temp]['link']."' target='_blank'><div class='amp-lightbox-image-link-button'>new window</div></a>";
		$lightbox_temp .= "<div class='amp-lightbox-media-id'>".$media_info[$media_id_temp]['domain']."|".$media_id_temp."</div>";
		$lightbox_temp .= "<figcaption>".$file_description."</figcaption></figure>";
		$lightbox_temp .= "<div class='amp-lightbox-close background_2' on='tap:lightbox".$media_id_temp.".close' tabindex='1' role='button'>close</div>";
		$lightbox_temp .= "</amp-lightbox>";
		$image_lightbox_array[] = $lightbox_temp;
	
		endforeach;
	
	// process snippets
	$matches = [];
	preg_match_all("/(?<=\(\(\()(.*?)(?=\)\)\))/is", $body_incoming, $matches);	
	if (empty($matches)): $matches = [ [], [] ]; endif;
	$matches = array_unique($matches[0]);
	$binding_defaults = [];
	foreach ($matches as $match_temp):

		$snippet_string = null;
	
		$temp_array = explode(")(", $match_temp.")(");
	
		$snippet_id_temp = $temp_array[0];
		if (strpos($temp_array[0], "|")):
			$domain_id_temp = explode("|", $temp_array[0]);
			if (strpos($domain_id_temp[0], ".")): $snippet_id_temp = $domain_id_temp[1];
			else: $snippet_id_temp = $domain_id_temp[0]; endif;
			endif;
	
		// nothing so skip it
		if (empty($snippet_id_temp)):
			$body_incoming = str_replace("(((".$match_temp.")))", null, $body_incoming);
			endif;
	
		// if it is a bound snippet
		if (in_array($temp_array[1], ["input", "default", "result"])):

			$binding_string = null;
	
			$binding_name = $temp_array[0];

			if (empty($binding_defaults[$binding_name])):
				$binding_defaults[$binding_name] = [
					"key" => random_code(15),
					"random" => random_code(15),
					"default" => $binding_name
					];
				endif;
	
			if ($temp_array[1] == "input"):
				if (!(empty($temp_array[2]))): $binding_string = "\n\n<span class='input-description'>".$temp_array[2]."</span>";
				else: $binding_string = "<span class='input-description'>".$binding_name."</span>"; endif;
				$binding_string .= "<input type='text' on='input-debounced:AMP.setState({ pageValues: { ".$binding_defaults[$binding_name]['key'].": event.value } })' value='".$binding_defaults[$binding_name]['random']."'>\n\n";			
			elseif ($temp_array[1] == "default"):
				$binding_defaults[$binding_name]['default'] = $temp_array[2];
				$binding_string = null;
			else:
				$binding_string = "<span [text]='pageValues.".$binding_defaults[$binding_name]['key']."'>".$binding_defaults[$binding_name]['random']."</span>";
				endif;	

			$body_incoming = str_replace("(((".$match_temp.")))", $binding_string, $body_incoming);
	
			continue;
	
			endif;
	
		// if it is a link snippet
		$snippet_info = nesty_snippet($temp_array[0]); // check if the snippet exists
	
		// snippet id does not exist so it must be user-defined
		if (empty($snippet_info[$snippet_id_temp])):
			$body_incoming = str_replace("(((".$match_temp.")))", null, $body_incoming);
			continue; endif; 

		$snippet_string = [];
		if (!(empty($snippet_info[$snippet_id_temp]['name']))):
			$snippet_string[] = "<div class='snippet-name'>".$snippet_info[$snippet_id_temp]['name']."</div>"; endif;
		$snippet_string[] = "<a href='https://".$snippet_info[$snippet_id_temp]['domain']."/s/".$snippet_id_temp."/'><div class='snippet-credit background_".rand(1,10)."'>".$snippet_info[$snippet_id_temp]['publisher']." &nbsp;|&nbsp; ".$snippet_id_temp."</div></a>";

		$snippet_date_string = [];
		if (!(empty($snippet_info[$snippet_id_temp]['year']))):
			$snippet_date_string[] = $snippet_info[$snippet_id_temp]['year'];
			endif;
		if (!(empty($snippet_info[$snippet_id_temp]['month']))):
			$snippet_date_string[] = date("F", strtotime("2000-".$snippet_info[$snippet_id_temp]['month']."-01"));
			if (!(empty($snippet_info[$snippet_id_temp]['day']))):
				$snippet_date_string[] = date("jS", strtotime("2000-01-".$snippet_info[$snippet_id_temp]['day']));
				endif; endif;
		if (!(empty($snippet_date_string))):
			$snippet_string[] = "<div class='snippet-date'>".implode(" ", $snippet_date_string)."</div>";
			endif;

		if (!(empty(login)) && in_array($login['status'], ["contributor", "admin"]) && ($domain == $snippet_info[$snippet_id_temp]['domain'])):
			$snippet_string[] = "<a href='/s/".$snippet_id_temp."/edit/'><div class='snippet-edit'>Edit</div></a>";
			endif;
	
		$snippet_string = $delimiter.implode(null,$snippet_string).$snippet_info[$snippet_id_temp]['body'].$delimiter;

		$body_incoming = str_replace("(((".$match_temp.")))", $snippet_string, $body_incoming);

		endforeach;

	// if there were any bound snippets
	$amp_state = [];
	foreach ($binding_defaults as $binding_name => $binding_info):
		$body_incoming = str_replace($binding_info['random'], htmlspecialchars($binding_info['default']), $body_incoming);
		$amp_state[] = '"'.$binding_info['key'].'": "'.htmlspecialchars($binding_info['default']).'"';
		endforeach;
	if (!(empty($amp_state))):
		$body_incoming .= "<amp-state id='pageValues'><script type='application/json'> { ";
		$body_incoming .= implode(", ", $amp_state); 
		$body_incoming .= " } </script></amp-state>";
		endif;
		
	$skip_array = [
		"<blockquote", "blockquote>", "<iframe", "iframe>", "<div", "div>", "<hr", "<aside", "aside>", 
		"<table", "table>", "<thead", "thead>", "<tbody", "tbody>", "<tr", "tr>", "<td", "td>", "<th", "th>", 
		"<h1", "h1>", "<h2", "h2>", "<h3", "h3>", "<h4", "h4>", "<h5", "h5>", "<h6", "h6>",
		"<ul", "ul>", "<ol", "ol>", "<li", "li>",
		"<section", "section>", // "<samp", "samp>", "<kbd", "kbd>",
		"<amp-state", "amp-state>",
		"<script", "script>",
		"<amp-img", "amp-img>",
		"<amp-fit-text", "amp-fit-text>", "<amp-accordion", "amp-accordion>" ];
	
	$body_incoming = preg_replace('/<li(.*?)>/', $delimiter.'<li$1>'.$delimiter, $body_incoming);
	$body_incoming = preg_replace('/<ul(.*?)>/', $delimiter.'<ul$1>'.$delimiter, $body_incoming);
	$body_incoming = preg_replace('/<ol(.*?)>/', $delimiter.'<ol$1>'.$delimiter, $body_incoming);
	$body_incoming = str_replace("</li>", $delimiter."</li>".$delimiter, $body_incoming);
	$body_incoming = str_replace("</ul>", $delimiter."</ul>".$delimiter, $body_incoming);
	$body_incoming = str_replace("</ol>", $delimiter."</ol>".$delimiter, $body_incoming);

	$body_temp = explode($delimiter, $body_incoming);
	$body_incoming = $body_final = null;

	foreach($body_temp as $content_temp):
		$content_temp = trim($content_temp);
		if (ctype_space($content_temp)): continue; endif;
		if (empty($content_temp) && ($content_temp !== "0")): continue; endif;
		if (strpos("*".$content_temp, "///") == 1): continue; endif;
		foreach ($skip_array as $skip_temp):
			if (strpos("*".$content_temp, $skip_temp)):
				$body_final .= $content_temp;
				continue 2; endif;
			endforeach;
		$body_final .= "<p>".$content_temp."</p>";
		endforeach;
	$body_final .= implode(null, $image_lightbox_array);
	$body_final = str_replace("\n", "<br>", $body_final);
	
	// for handling samp and kbd
	$body_final = str_replace($line_break_placeholder, "\n", $body_final);
	$body_final = str_replace("<p><samp", "<p class='samp'><samp", $body_final);
	$body_final = str_replace("<p><kbd", "<p class='kbd'><kbd", $body_final);

	foreach($skip_array as $skip_temp):
		$body_final = str_replace($skip_temp."<br>", $skip_temp, $body_final);
		endforeach;
	
	return $body_final; }




function clip_length($content=null,$length=140,$ellipsis=null,$breaks=null) {
	if ($breaks == null): $content = str_replace(array("\r", "\n", "\r\n", "\v", "\t", "\0","\x"), " ", $content);
	else: $content = str_replace(array("\r\r", "\n\n", "\r\n"), "\r", $content); endif;
	$clip_length = mb_substr($content,0,$length,"utf-8");	
	if (strlen($clip_length) >= ($length-1) && (strrpos($clip_length, ' ') !== FALSE)): $clip_length = mb_substr($clip_length,0,strrpos($clip_length, ' ')); endif;
	if ( ($ellipsis == "ellipsis") && (strlen($content) >= ($length-1)) ): $clip_length .= "…"; endif;
	return $clip_length; }


function number_condense($n, $decimals=1) {
	$negative = null;
	if ($n < 0): $n = abs($n); $negative = "-"; endif;
	if (($n == 0) || ($n == null)): $n_format = "0"; $suffix_temp = null;
	elseif ($n < 1): $n_format = "0"; $suffix_temp = null;
	elseif ($n < 1000): $n_format = number_format($n); $suffix_temp = null;
	elseif ($n < 1000000): $n_format = number_format($n / 1000, $decimals); $suffix_temp = "k";
	elseif ($n < 1000000000): $n_format = number_format($n / 1000000, $decimals); $suffix_temp = "m";
	else: $n_format = number_format($n / 1000000000, $decimals); $suffix_temp = "b"; endif;
	if (strlen($n_format) - strripos($n_format,".0") == 2): $n_format = str_replace(".0", null, $n_format); endif;
	return $negative.$n_format.$suffix_temp; } ?>
