<?php 



function saveImg($given, $index) {
	$ch = curl_init($given);
	$fp = fopen("../../database/img/mogs/$index", 'wb');
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
}

saveImg("http://i3.kym-cdn.com/entries/icons/medium/000/002/994/1277081081470.jpg", 1);
echo "Done";