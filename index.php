<?php 
/*
PSEUDO- MAIN:

Set $alt_dom = '';
Set $meme_name = '';
Set $meme_img_url = '';
Set $meme_pop = null;
Set $meme_bias = null;
Set $meme_origin = null;
Set $meme_year = null;
Set $meme_learn_more = '';

Repeat set number of times:
	Get initial HTML DOM + number
	Find segment of DOM with images
	Repeat for each DOM segment:
		Reset $meme_name = title value of image
		Reset $meme_img_url = href value of image
		Reset $meme_learn_more = meme page detail href
		Reset $alt_DOM to meme page detail href
		Reset $meme_pop = popularity value
		Reset $meme_bias = Views value
		Reset $meme_origin = origin value
		Reset $meme_year = year value
		Run composeQuery function
		Add to database
	End Repeat
End Repeat

PSEUDO - composeQuery

function composeQuery($meme_name, $meme_img_url, $meme_origin, $meme_year, $meme_learn_more) {
	$query = 'insert into meme_list (name, img_url, popularity_value, bias_value, origin, origin_year, learn_more_url) '
	 		  . 'values ($meme_name,$meme_img_url,$meme_pop,$meme_bias,$meme_origin,$meme_year,$meme_learn_more);';
	Execute sql with query
}
*/

/*
Know Your Meme Desired Selectors

Start URL: 'http://knowyourmeme.com/memes/popular'
Pagination : .pagination_top > .pagination > a[href]
Meme Contents : .entry_list .photo img
*/

//Includes
// use PDO;
include_once "simplehtmldom_1_5/simple_html_dom.php";
// include_once "database.php";



class Mog {
	
	public $name;
	public $srcOrigin;
	public $imgUrl;
	public $srcViews;
	public $srcFaves;
	public $srcUrl;
	public $rating;
	public $rateBias;

	public function __construct($name, $imgUrl, $srcViews, $srcFaves, $srcUrl) {
		$this->name = $name;
		// $this->srcOrigin = $srcOrigin;
		$this->imgUrl = $imgUrl;
		$this->srcViews = $srcViews;
		$this->srcFaves = $srcFaves;
		$this->srcUrl = $srcUrl;
	}

	public function save() {
		try {
			$db = new PDO('mysql:host=localhost;dbname=MemeSlam;charset=utf8','root','');
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$query = $db->prepare("insert into MogMaster (name, imgUrl, srcViews, srcFaves, srcUrl) values (:name, :imgUrl, :srcViews, :srcFaves, :srcUrl)");
			$query->execute(['name'=>$this->name, 'imgUrl'=>$this->imgUrl, 'srcViews'=>$this->srcViews, 'srcFaves'=>$this->srcFaves, 'srcUrl'=>$this->srcUrl]);
		} catch (PDOException $e) {
			die($e->getMessage());	
		}
	}

}

//Executed Code
mainExecute();

/*********************************
Functions for work
**********************************/
//Main Logic Function
function mainExecute() {
	//array for offline pages testing
	$offlinePages = ['staticpages/Slender Man _ Know Your Meme.html','staticpages/Forever Alone _ Know Your Meme.html', 'staticpages/Zerg Rush _ Know Your Meme.html'];

	//Regexes for matching values from extractions
	$rgx_src = '/data-src="(.*)" src/'; //image url
	$rgx_title = '/title="(.*)"/'; //Meme title
	$rgx_pg_href = '/href="(.*)">/'; //url for meme's page
	$rgx_faves = '/>(.*)</'; //favorites count
	$rgx_views = '/>(.*)</';  //View count
	$rgx_origin = '/>(.*)</'; //Origin
	$rgx_org_year = '';
	
	//utility variables specifying desired Dom content selectors for extractContent function
	$meme_img_path = '.entry_list .photo img';
	$meme_url_path = '.entry_list h2 > a';
	$meme_faves_path = '.num';
	$meme_views_path = 'dd.views a';
	$meme_origin_path = 'dd.entry_origin_link';

	//Variables to store scraped content
	$meme_name = '';
	$meme_img_url = '';
	$meme_faves = null;
	$meme_views = null;
	$meme_origin = null;
	$meme_year = null;
	$meme_learn_more = '';
	
	//support variables for logic
	$matches = [];
	$alt_dom = '';
	$i = 0;

	//main work loop
	while ($i < 1) {

		//counter for tracking meme url index in $meme_href
		$j = 0;

		//used for offline pages array index
		// $m = 0;

		//pull and store scraped dom
		if ($i == 0) { //for first page
			$html = getDOM('http://knowyourmeme.com/memes/popular');	
			// $html = getDOM('staticpages/kym_popular_1.html');	
		} else {//for other pages up to value i is set to
			$html = getDOM('http://knowyourmeme.com/memes/popular/page/' . $i);
		}
		delay();
		//These arrays should refer to the same memes on the same indexes
		//extracts array used for meme images and titles
		$img_content = extractContent($html, $meme_img_path);
		// $img_content = $html->find($meme_img_path);
		//extracts array used for meme url to access additional content
		$meme_href = extractContent($html, $meme_url_path);
		// print_r($img_content);
		// print_r($meme_href);

		foreach ($img_content as $curr) {
			// getValue($dom, $rgx, $extract, $selector);
			
			//save meme name
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
			echo "Some other MEME URL: " . $matches[1] . "<br>";

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

			// //extract meme origin
			// $origin_segment = extractContent($alt_dom, $meme_origin_path);
			// preg_match($rgx_origin, $origin_segment[0], $matches);
			// $meme_origin = $matches[1];
			// echo "Meme Origin: " . $meme_origin . "<br>";

			echo "<br>";

			$mog = new Mog($meme_name, $meme_img_url, $meme_views, $meme_faves, $meme_learn_more);
			$mog->save();
			// $m++;
			$j++;
		}
		$i++;	
	}
}

//Support Function: get's DOM at given url
function getDOM($given) {
	//set user agent (to avoid 403 errors) and save html from set destination
	ini_set('user_agent','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.99 Safari/537.36'); 
	$html = file_get_html($given);
	return $html;
}

//Support Function: returns extraction of content given dom and selector 
function extractContent($given_html, $selector) {
	$result = $given_html->find($selector);
	return $result;
}

function getValue($dom, $rgx, $extract, $selector) {
	if ($extract) {
		$segment = extractContent($dom, $selector);
		preg_match($rgx, $segment[0], $matches);
	} else {
		$segment = $dom;
		preg_match($rgx, $segment, $matches);
	}
	$result = scrubMatch($matches[1]);
	return $result;
}

function scrubMatch($given) {
	$result = $given;
	if (substr($given,0,1) == '<') {
		$result = substr($given, (strpos($given,'>') + 1), (strrpos($given,'<')));
	}
	return $result;
}

function delay() {
	$min = 2;
	$max = 5;
	sleep(rand($min, $max));
}






// function checkMatch($matches) {
// 	if 
// }






// foreach ($pages as $link) {
// 	echo $link . "<br>";
// }


///SAMPLE CODE FOR SCRAPING
// Create DOM from URL
// $html = file_get_html('http://slashdot.org/');

// // Find all article blocks
// foreach($html->find('div.article') as $article) {
//     $item['title']     = $article->find('div.title', 0)->plaintext;
//     $item['intro']    = $article->find('div.intro', 0)->plaintext;
//     $item['details'] = $article->find('div.details', 0)->plaintext;
//     $articles[] = $item;
// }

// print_r($articles);



