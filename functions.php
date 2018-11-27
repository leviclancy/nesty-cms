<? include_once('functions_content.php');
include_once('functions_layout.php');
include_once('functions_sql.php');


function permanent_redirect ($url) {
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');
	header("HTTP/1.1 301 Moved Permanently"); 
	header("Location: $url");
	exit; }


function random_code($length=16) {
	$characters = [
		"2", "3", "4", "5", "6", "7", // base 32 compliant numbers
		"B", "C", "D", "F", "G", "H", // remove vowels
		"J", "K", "L", "M", "N", "P", // remove vowels
		"Q", "R", "S", "T", "V", "W", // remove vowels
		"Y", "Z" // remove x for vulgar use
		];
	if (!(is_int($length))): $length = 16; endif;
	if ($length < 1): $length = 16; endif;
	$length_temp = 0; $key_temp = null;
	while ($length_temp < $length): $key_temp .= $characters[rand(0,25)]; $length_temp++; endwhile;
	return $key_temp; }

function encode_thirtytwo($string) {
	
	// with help from https://code.tutsplus.com/tutorials/base-what-a-practical-introduction-to-base-encoding--net-27590
	
	$alphabet_map = [
		"0" => "A",
		"1" => "B",
		"2" => "C",
		"3" => "D",
		"4" => "E",
		"5" => "F",
		"6" => "G",
		"7" => "H",
		"8" => "I",
		"9" => "J",
		"10" => "K",
		"11" => "L",
		"12" => "M",
		"13" => "N",
		"14" => "O",
		"15" => "P",
		"16" => "Q",
		"17" => "R",
		"18" => "S",
		"19" => "T",
		"20" => "U",
		"21" => "V",
		"22" => "W",
		"23" => "X",
		"24" => "Y",
		"25" => "Z",
		"26" => "2",
		"27" => "3",
		"28" => "4",
		"29" => "5",
		"30" => "6",
		"31" => "7" ];
	
	$binary_blob = $encoded_blob = null;
	
	// we want to go through each character one at a time
	$string_array = str_split($string, 1);
	
	// make each character of the string into its decimal number, then convert the decimal to an eight-digit binary
	foreach ($string_array as $string_character):
//		$binary_blob .= sprintf( "%08d", decbin(ord($string_character))); // condensed code
		$string_character = ord($string_character); // convert the character to its decimal value
		$string_character = decbin($string_character); // convert the decimal value to eight-digit binary
		$string_character = sprintf( "%08d", $string_character); // restores leading zeroes, so 00000001 does not become 1
		$binary_blob .= $string_character; // add this to a long string with all the binary values put together
		endforeach;

	// split the long binary blob into five-digit chunks;
	// because 2^5 is less than or equal to 32, we can convert ...
	// ... the five-digit binary value to a decimal that will be between 0 and 31,
	// and map each number from 0 to 31 to a character in the alphabet map
	$binary_array = str_split($binary_blob, 5);
	foreach ($binary_array as $binary_temp):
		$binary_temp = str_pad($binary_temp, 5, "0"); // if it is the end, maybe it less than five digits, so paid it e.g. 100 -> 10000
		$decimal_temp = bindec($binary_temp); // convert the five-digit binary value to decimal
		$encoded_blob .= $alphabet_map[$decimal_temp]; // map the decimal between 0 and 31 to the correct character
		endforeach;
	return $encoded_blob; }

// this function accepts an unencoded code
function code_generator ($authenticator_secret) {
	// with help from https://github.com/PHPGangsta/GoogleAuthenticator/blob/master/PHPGangsta/GoogleAuthenticator.php
	$time_temp = floor(gmmktime() / 30); // first turn the UTC time into a thirty-second chunk
	$time_temp = chr(0).chr(0).chr(0).chr(0).pack('N*', $time_temp); // pack time into a binary string
	$hash_temp = hash_hmac('SHA1', $time_temp, $authenticator_secret, true); // hash it with the authenticator key
	$offset = ord(substr($hash_temp, -1)) & 0x0F; // use last nipple of result as index/offset
	$code_temp = substr($hash_temp, $offset, 4); // grab 4 bytes of the result
	$code_temp = unpack('N', $code_temp);  // unpack binary value
	$code_temp = $code_temp[1]; 
	$code_temp = $code_temp & 0x7FFFFFFF; // only 32 bits
	return str_pad($code_temp % 1000000, 6, '0', STR_PAD_LEFT); // make sure it is six characters
	}

// this function generates an xhr
function xhr_output ($result, $message, $redirect=null) {
	
	global $domain;
	
	header("Content-type: application/json");
	header("Access-Control-Allow-Credentials: true");
	header("Access-Control-Allow-Origin: https://".$domain);
	header("AMP-Access-Control-Allow-Source-Origin: https://".$domain);
	
	if ($result == "success"): header("Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin");
	elseif ($result !== "success): header("HTTP/1.0 412 Precondition Failed", true, 412); endif;
	
	if ( ($outcome == "success") && !(empty($destination_url))):
		header("AMP-Redirect-To: https://".$domain.$redirect);
		header("Access-Control-Expose-Headers: AMP-Redirect-To, AMP-Access-Control-Allow-Source-Origin");
		endif;
	
	echo json_encode(["result"=>$result, "message"=>$message]);
	
	exit; } ?>
