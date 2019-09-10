<?php
set_time_limit(0);
ini_set('memory_limit', '1000M');

require 'vendor/autoload.php';
require '../public_html/wp-load.php';
require '../public_html/wp-admin/includes/taxonomy.php';
require '../public_html/wp-admin/includes/file.php';
require '../public_html/wp-admin/includes/image.php';
require '../public_html/wp-admin/includes/media.php';
require './postToWP.php';


use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$listinguRLS = file('./single.txt', FILE_IGNORE_NEW_LINES);

$requestOptions = setRequestOptions();
$client = new Client($requestOptions);
$wp = new postToWP;
$requests = function () use ($listinguRLS) {
	foreach ($listinguRLS as $listItem) {
		yield new Request('GET', $listItem);
	}
};

$pool = new Pool($client, $requests(), [
	'concurrency' => 1,
	'fulfilled' => function ($response, $index) use ($listinguRLS, $wp) {

		$post = [];
		$post_meta = [];
		$html = (string) $response->getBody();
		$doc = hQuery::fromHTML($html);
		//title
		preg_match_all('/<div class="col-xs-3 col-md-2 nopm icon_container"> <img src="(.*?)" alt="" \/><\/div>/', $html,  $icon_image);
		$featureImage = $icon_image[1][0];
		preg_match_all('/<div class="col-xs-9 col-md-5 nopm title_container">(.*?)<\/div>/', $html,  $titleRawData);
		preg_match_all('/<h1>(.*?)<\/h1>/', $titleRawData[1][0],  $titlearray);
		//get title
		$title = $titlearray[1][0];
		$title = explode(" ", $title);
		array_pop($title);
		$title = implode(" ", $title);

		$extVersionRaw = explode("-", strip_tags($titleRawData[1][0]));
		//get version
		$version = $extVersionRaw[1];
		$post_meta["version"] = $version;

		//download link
		$html = str_replace("'", '"', $html);
		preg_match_all('/<div class="col-xs-12 col-md-5 nopm dld_now_container"> <a href="(.*?)" class="dld_now cookie_start_download" data-postId="(.*?)"><span class="meta_1">free download<\/span><span class="size">(.*?)<br \/> MB<\/span> <\/a><\/div>/', $html,  $downloadRawData);

		$downloadLink = $downloadRawData[1][0];
		$post_meta["download_link"] = $downloadLink;

		//ratings
		$rating = $doc->find(".post-ratings")->text;

		$raw_rating = str_replace("(", "", $rating);
		$raw_rating = str_replace(")", "", $raw_rating);
		$exp_rating = explode(",", $raw_rating);

		$totalRating = explode(" ", trim($exp_rating[0]));

		$total_votes = $totalRating[0];
		$post_meta["total_votes"] = $total_votes;

		$avgRating = explode(":", $exp_rating[1]);
		$avgRating = explode("out of", $avgRating[1]);
		$avgRating = $avgRating[0];
		$post_meta["avg_rating"] = $avgRating;
		//images 
		// $banners = $doc->find('<div class="wwp-gallery-container">(.*?)</div>');
		preg_match_all('/<div class="wwp-gallery-container">(.*?)<\/div>/', $html,  $allimageshtml);

		//preg_match_all('/<img src="(.*?)" \/>/', $allimageshtml[1][0],  $allimages);
		preg_match_all('/<a href="(.*?)" class="wwp-item-link(.*?)">(.*?)<\/a>/', $allimageshtml[1][0],  $allimages);

		//echo "<pre>";
		$productImages = [];
		foreach ($allimages[1] as $key => $image) {
			# code...
			$productImages[] = postToWP::uploadImage($image, 0);
		}
		$post_meta["_gallery"] = serialize($productImages);
		$post_meta["_gallery_format"] = 'default';
		$post_meta["_gallery_format_data"] = serialize(['default' => []]);

		$namesList = ['Emma', 'Lukas', 'Jakub', 'Adrian', 'Francesco', 'Hugo', 'Oscar', 'Noah'];
		$author = $namesList[array_rand($namesList)];

		// $post_tags = $tags[3];
		// $categories = explode(" ", $doc->find('.breadcrumbs')->text);
		// if (is_string($categories[1])) {
		// 	$categories[] = $categories[1];
		// }
		//categories
		preg_match_all('/<div class="breadcrumbs clearfix">(.*?)<\/div>/', $html,  $allcategoriesRaw);
		preg_match_all('/<li> <a href="(.*?)">(.*?)<\/a><\/li>/', $allcategoriesRaw[1][0],  $allcategoriesRaw);
		//print_r($allcategoriesRaw);
		$categories[] = $allcategoriesRaw[2][1];
		//die();

		//get technical detail
		preg_match_all('/<div class="flex"><div class="flex-item first"> (.*?)<\/div><div class="flex-item second">(.*?)<\/div><\/div>/', $html,  $allTechnicalDetailRaw);


		$technicalDetail = [];
		for ($i = 0; $i < count($allTechnicalDetailRaw[1]); $i++) {
			$key = str_replace(":", "", $allTechnicalDetailRaw[1][$i]);
			if ($key != "Author") {
				$technicalDetail[$allTechnicalDetailRaw[1][$i]] = $allTechnicalDetailRaw[2][$i];
			}
		}
		$post_meta["technical_detail"] = json_encode($technicalDetail);
		$doc = $doc->find('.the_content');

		$doc = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $doc);
		$doc = preg_replace('/<div class="wwp-gallery-container" \b[^>]*>(.*?)<\/div>/is', "", $doc);
		$doc = preg_replace('/<div class="divider" \b[^>]*>(.*?)<\/div>/is', "", $doc);
		$doc = preg_replace('/<div class="wwp-gallery-container">(.*?)<\/div>/is', "", $doc);
		preg_match_all('/<a\b[^>]*>/is', $doc, $allAncherTags);
		// print_r($allAncherTags);
		// die();
		foreach ($allAncherTags[0] as $anchor) {
			$replaceWith = '<span id="internalpage">';
			$doc = str_replace($anchor, $replaceWith, $doc);
		}
		$doc = str_replace("</a>", "</span>", $doc);
		$contentClean = $doc;
		// echo $contentClean;
		// die();
		$post["title"] 		= $title;
		$post["author"] 	= $author;
		$post["content"] 	= $contentClean;
		$post["featureImage"] 	= $featureImage;
		//$post["tags"] 		= $post_tags;
		$post["post_meta"] 	= $post_meta;
		$post["categories"] = $categories;

		$post_id = $wp::post($post);
		file_put_contents('./log.txt', $listinguRLS[$index] . "\n", FILE_APPEND);
		file_put_contents('./downloadUrls.txt', $listinguRLS[$index] . "|" . $post_id . "\n", FILE_APPEND);

		//print $uriList[$index].PHP_EOL;

	},
	'rejected' => function ($reason, $index) {
		// this is delivered each failed request
	},
]);

// Initiate the transfers and create a promise
$promise = $pool->promise();

// Force the pool of requests to complete.
$promise->wait();

// ====================


function setRequestOptions($useProxy = false)
{

	if ($useProxy) {
		$proxyList = file_get_contents('http://proxyrack.net/rotating/megaproxy/');
		$proxyArray = explode("\n", trim($proxyList));
		$randomProxy = array_rand(array_flip($proxyArray));
	}

	$chromeVersions = ['44.0.2403.157', '60.0.3112.101', '62.0.3202.94', '51.0.2704.106', '64.0.3282.39', '68.0.3440.84'];
	$randomChromeVersion = $chromeVersions[rand(0, count($chromeVersions) - 1)];

	$requestOptions = [
		'cookies' => new \GuzzleHttp\Cookie\FileCookieJar('./guzzleCookes.txt', true),
		'headers' => [
			'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/' . $randomChromeVersion . ' Safari/537.36',
			'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Language' => 'en-us,en;q=0.5',
			'Accept-Charset' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
			'Accept-Encoding' => 'gzip,deflate',
			'Keep-Alive' => '115',
			'Connection' => 'keep-alive',
		]
	];

	if ($useProxy)
		$requestOptions['proxy'] = 'http://' . $randomProxy;

	return $requestOptions;
}

function cleanDescription($s)
{
	//return $s;
	$s = preg_replace('#<blockquote>.+?</blockquote>#s', '', $s);
	$s = preg_replace('#<a.+?http.+?answers.microsoft.com/.+?/profile/.+?>(.+?)</a>#s', "$1", $s);
	$s = str_replace("&nbsp;", ' ', $s);
	$s = str_replace(['<li>', '</li>'], ['- ', '<br>'], $s);
	//$s = str_replace('<li>', ' - ', $s);
	//$s = str_replace('</li>', '<br>', $s);
	$s = preg_replace('#<[\s/]+div>#', '', $s);
	$s = strip_tags($s, '<p><br><img><a><table><tbody><tr><td><th><strong><b>');
	$s = preg_replace('#<[a-z0-9\s]+>[\s]+</[a-z0-9\s]+>#', '', $s);
	return $s;
}
