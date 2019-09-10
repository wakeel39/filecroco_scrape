<?php
set_time_limit(0);
ini_set('memory_limit', '1000M');

require 'vendor/autoload.php';
require '../../wordpress/wp-load.php';
require '../../wordpress//wp-admin/includes/taxonomy.php';
require '../../wordpress//wp-admin/includes/file.php';
require '../../wordpress//wp-admin/includes/image.php';
require '../../wordpress//wp-admin/includes/media.php';
require './postToWP.php';


use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$listinguRLS = file('./listpage.txt', FILE_IGNORE_NEW_LINES);

$requestOptions = setRequestOptions();
$client = new Client($requestOptions);
$wp = new postToWP;
$requests = function () use ($listinguRLS) {
	foreach ($listinguRLS as $listItem) {
		$exp = explode("|", $listItem);


		for ($i = 0; $i <= $exp[1]; $i++) {
			$url = $exp[0];
			if ($i > 0) {
				$url = $exp[0] . "page/" . $i;
			}
			//echo $url;
			// die();
			yield new Request('GET', $url);
		}
	}
};

$pool = new Pool($client, $requests(), [
	'concurrency' => 1,
	'fulfilled' => function ($response, $index) use ($listinguRLS, $wp) {

		$post = [];

		$html = (string) $response->getBody();
		$doc = hQuery::fromHTML($html);
		preg_match_all('/<div class="flex-item two"><div class="title"> <a href="(.*?)">(.*?)<\/a><\/div>/', $html,  $itemsUrls);

		//echo "<pre>";
		//print_r($itemsUrls[1]);
		foreach ($itemsUrls[1] as $saveUrl) {
			file_put_contents('./single.txt', $saveUrl . "\n", FILE_APPEND);
		}
		//die();
		// $html = preg_replace('/itemscope=".*?"/', '', $doc);
		// $html = preg_replace('/itemtype=".*?"/', '', $doc);
		// //$html = str_replace('TechTudo', 'CropTech', $doc);
		// preg_match_all('/<li class="entities__list-item"> <a href="(.*?)" class="entities__list-itemLink" data-track-click="(.*?)"> (.*?) <\/a> <\/li>/', $html,  $tags);

		// $namesList = ['Emma', 'Lukas', 'Jakub', 'Adrian', 'Francesco', 'Hugo', 'Oscar', 'Noah'];
		// $author = $namesList[array_rand($namesList)];

		// $post_tags = $tags[3];
		// $doc = $doc->find('.mc-article-body');

		// $doc = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $doc);
		// $doc = preg_replace('/<ul\b[^>]*>(.*?)<\/ul>/is', "", $doc);
		// $doc = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $doc);
		// $doc = preg_replace('/<div class="saibamais componente-conteudo expandido" \b[^>]*>(.*?)<\/div>/is', "", $doc);
		// $doc = preg_replace('/<img class="progressive-draft" \b[^>]*>/is', "", $doc);
		// $doc = preg_replace('/<h3 class="content-related-articles__title">(.*?)<\/h3>/is', "", $doc);
		// $doc = preg_replace('/<ul class="content-related-articles__list">(.*?)<\/ul>/is', "", $doc);
		// $doc = str_replace("data-src", "src", $doc);
		// $doc = strip_tags($doc, '<a><p><br><img><ul><li><ol><h1><h2><h3><h4><h5><h6><strong><table><td><tr><tbody><thead></th>');
		// preg_match_all('/<a\b[^>]*>/is', $doc, $allAncherTags);
		// foreach ($allAncherTags[0] as $anchor) {
		// 	$replaceWith = '<span id="internalpage">';
		// 	$doc = str_replace($anchor, $replaceWith, $doc);
		// }
		// $doc = str_replace("</a>", "</span>", $doc);

		// // $doc = preg_replace('/Pensando em comprar(.*)Opine no Fórum do TechTudo!<br>/', '', $doc);
		// // $doc = preg_replace('/<p>([^<]*)Fórum do TechTudo([^<]*)<\/p>/', "", $doc);
		// // $doc = preg_replace('/Comment on Fórum do TechTudo!/', '', $doc);
		// // $doc = preg_replace('/Fórum do TechTudo!/', '', $doc);
		// // $doc = preg_replace('/Fórum do TechTudo/', '', $doc);
		// $doc = str_replace('/saiba mais/', '', $doc);
		// $doc = str_replace('TechTudo', 'CropTech', $doc);
		// $doc = str_replace('<span id="internalpage"></span>', '', $doc);
		// $doc =  preg_replace('/<p>(.*?)<span id="internalpage">[^><]+?<\/span>[^><]+?Fórum do CropTech!<br><\/p>/', "", $doc);
		// $doc =  preg_replace('/<p>(.*?)<span id="internalpage">[^><]+?<\/span>[^><]+?Fórum do CropTech!<\/p>/', "", $doc);

		// //echo $doc;
		// //die();
		// $contentClean = $doc;
		// $post["title"] 		= $title;
		// $post["author"] 	= $author;
		// $post["content"] 	= $contentClean;
		// $post["tags"] 		= $post_tags;
		// $post["categories"] = ["Blog"];

		// $wp::post($post);
		//file_put_contents('./log.txt', $ . "\n", FILE_APPEND);

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
