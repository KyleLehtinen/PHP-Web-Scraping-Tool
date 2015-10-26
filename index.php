<?php 

include_once "simplehtmldom_1_5/simple_html_dom.php";

class Mog {
	
	public $name;
	public $srcOrigin;
	public $imgUrl;
	// public $localPath;
	public $srcViews;
	public $srcFaves;
	public $srcUrl;
	public $rating;
	public $rateBias;
	public $active;

	public function __construct($name, $imgUrl, $srcViews, $srcFaves, $srcUrl, $active) {
		$this->name = $name;
		// $this->srcOrigin = $srcOrigin;
		$this->imgUrl = $imgUrl;
		// $this->localPath = $localPath;
		$this->srcViews = $srcViews;
		$this->srcFaves = $srcFaves;
		$this->rating = ($srcViews + $srcFaves);
		$this->srcUrl = $srcUrl;
		$this->active = $active;
	}

	public function saveToDB() {
		try {
			$db = new PDO('mysql:host=localhost;dbname=MemeSlamDev;charset=utf8','root','');
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$query = $db->prepare("insert into MogMaster (name, img_url, src_url, rating, active) 
				values (:name, :imgUrl, :srcUrl, :rating, :active)");
			$query->execute(['name'=>$this->name, 'imgUrl'=>$this->imgUrl, 'srcUrl'=>$this->srcUrl, 'rating'=>$this->rating, 'active'=>$this->active]);
		} catch (PDOException $e) {
			die($e->getMessage());	
		}
	}

	public function saveToCSV($filename) {
		$line = [];
		$line[] = $this->name;
		$line[] = $this->srcUrl;
		$line[] = $this->rating;
		$line[] = $this->active;
		
		$csv = fopen($filename, 'a') or die("Unable to open the file!");
		fputcsv($csv, $line);
		fclose($csv);
	}
}

mainExecute();

/*********************************
Functions for work
**********************************/
//Main Logic Function
function mainExecute() {

	//support variables for logic
	$matches = [];
	$alt_dom = '';
	$i = 1;
	$memeIDCounter = 1;
	$page_count = 2;
	$csvFileName = 'mogmaster.csv';
	$toCSV = true;
	
	//array for offline pages testing
	// $offlinePages = ['staticpages/Slender Man _ Know Your Meme.html','staticpages/Forever Alone _ Know Your Meme.html', 'staticpages/Zerg Rush _ Know Your Meme.html'];

	//Regexes for matching values from extractions
	$rgx_src = '/data-src="(.*)" src/'; //image url
	$rgx_title = '/title="(.*)"/'; //Meme title
	$rgx_pg_href = '/href="(.*)">/'; //url for meme's page
	$rgx_faves = '/>(.*)</'; //favorites count
	$rgx_views = '/>(.*)</';  //View count
	$rgx_origin = '/>(.*)</'; //Origin
	$rgx_org_year = '';
	$rgx_nsfw = '/>NSFW</';

	//utility variables specifying desired Dom content selectors for extractContent function
	$meme_img_path = '.entry_list .photo img';
	$meme_url_path = '.entry_list h2 > a';
	$meme_faves_path = '.num';
	$meme_views_path = 'dd.views a';
	$meme_origin_path = 'dd.entry_origin_link';
	$meme_nsfw_path = 'span.label-nsfw';

	//Variables to store scraped content
	$meme_name = '';
	$meme_img_url = '';
	// $meme_localPath = '';
	$meme_faves = null;
	$meme_views = null;
	$meme_origin = null;
	$meme_year = null;
	$meme_learn_more = '';

	//Recreate CSV file
	createCSV($csvFileName);
	
	//main work loop
	while ($i < $page_count) {

		//counter for tracking meme url index in $meme_href
		$j = 0;

		//counter used for offline pages array index
		// $m = 0;

		//pull and store scraped dom
		$html = getDOM('http://knowyourmeme.com/memes/popular/page/' . $i);

		delay();

		//These arrays should refer to the same memes on the same indexes
		//extracts array used for meme images and titles
		$img_content = extractContent($html, $meme_img_path);

		//extracts array used for meme url to access additional content
		$meme_href = extractContent($html, $meme_url_path);

		foreach ($img_content as $curr) {	

			//check if meme is NSFW
			$meme_nsfw = extractContent($curr, $meme_nsfw_path);

			if(!empty($meme_nsfw)) {
				$active = 1;
			} else {
				$active = 0;
			}
			echo "Meme NSFW: $active <br>";

			// //save meme name
			$meme_name = getValue($curr, $rgx_title, false, '');
			preg_match($rgx_title, $curr, $matches);
			$meme_name = $matches[1];
			echo "Meme Name: " . $meme_name . "<br>";
			
			//save meme img url
			$meme_img_url = getValue($curr, $rgx_src, false, '');
			preg_match($rgx_src, $curr, $matches);
			$meme_img_url = $matches[1];
			echo "Meme IMG URL: " . $meme_img_url . "<br>";

			//get href for current meme in $curr and set variable
			preg_match($rgx_pg_href, $meme_href[$j], $matches);
			$meme_learn_more = "http://knowyourmeme.com" . $matches[1];
			echo "Meme Main URL: " . $meme_learn_more . "<br>";

			//Get DOM for current selected meme to scrape additional content
			//for offline testing
			$alt_dom = getDOM("http://knowyourmeme.com" . $matches[1]);
			delay();
			// $alt_dom = getDOM($offlinePages[$m]);

			//extract favorite count
			$meme_faves = getValue($alt_dom, $rgx_faves, true, $meme_faves_path);
			$fave_segment = extractContent($alt_dom, $meme_faves_path);
			preg_match($rgx_faves, $fave_segment[0], $matches);
			$meme_faves = $matches[1];
			echo "Meme Favorite Count: ".$meme_faves . "<br>";

			//extract view count
			$view_segment = extractContent($alt_dom, $meme_views_path);
			preg_match($rgx_views, $view_segment[0], $matches);
			$meme_views = $matches[1];
			echo "Meme Views: " . $meme_views . "<br>";

			//Save image file to local path and collect path for saving
			saveImg($meme_img_url, $memeIDCounter);
			$memeIDCounter++;

			// //extract meme origin
			// $origin_segment = extractContent($alt_dom, $meme_origin_path);
			// preg_match($rgx_origin, $origin_segment[0], $matches);
			// $meme_origin = $matches[1];
			// echo "Meme Origin: " . $meme_origin . "<br>";

			echo "<br>";

			//create Mog object and call save method
			$mog = new Mog($meme_name, $meme_img_url, $meme_views, $meme_faves, $meme_learn_more, $active);
			if($toCSV) {
				$mog->saveToCSV($csvFileName);
			} else {
				$mog->saveToDB();	
			}

			// $m++;
			
			$j++;
		}
		echo "Page $i Done...<br>";
		$i++;	
	}
	echo "all pages done!";
}

//Get's DOM at given url
function getDOM($given) {
	//set user agent (to avoid 403 errors) and save html from set destination
	ini_set('user_agent','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.99 Safari/537.36'); 
	$html = file_get_html($given);
	return $html;
}

//Returns extraction of content given dom and selector 
function extractContent($given_html, $selector) {
	$result = $given_html->find($selector);
	return $result;
}

//Gets desired content given arguments
function getValue($dom, $rgx, $extract, $selector) {
	if ($extract) {
		$segment = extractContent($dom, $selector);
		preg_match($rgx, $segment[0], $matches);
	} else {
		$segment = $dom;
		preg_match($rgx, $segment, $matches);
	}
	
	if (!empty($matches)){
		$result = scrubMatch($matches[1]);	
	} else {
		$result = 0;
	}
	
	return $result;
}

//scrubs html entities from match
function scrubMatch($given) {
	$result = $given;
	if (substr($given,0,1) == '<') {
		$result = substr($given, (strpos($given,'>') + 1), (strrpos($given,'<')));
	}
	return $result;
}

//Sleeps scraper to avoid accidental DDOS
function delay() {
	$min = 1;
	$max = 3;
	sleep(rand($min, $max));
}

//saves scraped image file
function saveImg($given, $index) {
	$ch = curl_init($given);
	$path = "../../database/img/mogs/$index";
	$fp = fopen($path, 'wb');
	$path = "database/img/mogs/$index";
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
}

//creates csv file for storing scraped information
function createCSV($filename) {
	$csv = fopen($filename, "w") or die("Unable to open file!");
	fwrite($csv, 'name,src_url,rating,active' . PHP_EOL);
	fclose($csv);
}