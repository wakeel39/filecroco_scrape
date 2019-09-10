<?php
set_time_limit(0);
ini_set('memory_limit', '-1');

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


$listinguRLS = file('./downloadUrls.txt', FILE_IGNORE_NEW_LINES);

$requestOptions = setRequestOptions();
$client = new Client($requestOptions);
$wp = new postToWP;
$requests = function () use ($listinguRLS) {
	foreach ($listinguRLS as $listItem) {
		$exp = explode("|", $listItem);

		yield new Request('GET', $exp[0] . "download");
	}
};

$pool = new Pool($client, $requests(), [
	'concurrency' => 1,
	'fulfilled' => function ($response, $index) use ($listinguRLS, $wp) {

		$html = (string) $response->getBody();
		//$doc = hQuery::fromHTML($html);
		print_r($html);
		// die();
		//title
		preg_match_all('/<div class="mirrors_container flex vertically-centered"><div class="flex-item one"><div> <a href="(.*?)" class="btn_dld_2" rel="nofollow">Download now<\/a><\/div><\/div><div class="flex-item two flag"> <img src="(.*?)" title="(.*?)" \/>(.*?)<\/div><div class="flex-item three mirror_type">(.*?)<\/div><div class="flex-item four mentions">(.*?)<\/div><div class="flex-item five">(.*?)<\/div><\/div> <\/div><\/div>/', $html,  $downloadRawDiv);
		print_r($downloadRawDiv);
		$downloadUrlData = [];
		$i = 0;
		foreach ($downloadRawDiv[2] as $downloadurl) {
			echo "url---";
			ob_get_clean();
			// $exp = explode("/", $downloadRawDiv[1][$i]);
			// $filename = end($exp);
			// 
			$ext = explode("|", $listinguRLS[$index]);
			// echo "<pre>";
			// print_r($ext);
			// die();
			$ext = explode("/", $ext[0]);
			// echo "<pre>";
			// print_r($ext);
			// die();
			$ext = $ext[count($ext) - 2];

			// print_r($ext);
			// die();
			$name = str_replace("download-", "", $ext);
			// print_r($name);
			// die();
			try {
				$filename = saveToFolder($downloadRawDiv[1][$i], $name, $listinguRLS[$index]);
				//file_put_contents("../public_html/downloads/" . $filename, fopen($downloadRawDiv[1][$i], 'r'));
			} catch (Exception $e) {
				file_put_contents("error_links.log", $downloadRawDiv[1][$i] . "\n", FILE_APPEND);
			}
			$downloadUrlData[$i]["download_url"] = "https://filesilo.me/downloads/" . $filename;
			$downloadUrlData[$i]["country"] = $downloadRawDiv[3][$i];
			$downloadUrlData[$i]["mirror"] = $downloadRawDiv[5][$i];
			$downloadUrlData[$i]["os"] = strip_tags($downloadRawDiv[6][$i]);
			$i++;
		}

		//print $uriList[$index].PHP_EOL;
		$id = explode("|", $listinguRLS[$index]);

		add_post_meta($id[1], "downloadUrlData", json_encode($downloadUrlData));
		//die();
	},
	'rejected' => function ($reason, $index) {
		// this is delivered each failed request
		file_put_contents("error.txt", $reason, FILE_APPEND);
	},
]);

// Initiate the transfers and create a promise
$promise = $pool->promise();

// Force the pool of requests to complete.
$promise->wait();

// ====================

function saveToFolder($url, $name, $originalurl)
{

	set_time_limit(0);
	$mimeTypes = ["application/x-dosexec" => "exe", "application/octet-stream" => "exe", "application/zip" => "zip"];
	$file_info = new finfo(FILEINFO_MIME_TYPE);
	$arrContextOptions = array(
		"ssl" => array(
			"verify_peer" => false,
			"verify_peer_name" => false,
		),
	);
	try {
		//$content = ;
		$file = get_headers($url, 1);
		$extension = $mimeTypes[$file["Content-Type"][1]];
		$destination_folder = "../public_html/downloads/";
		$filename = $name . "." . $extension;
		$newfname = $destination_folder . $filename;
		file_put_contents($newfname, file_get_contents($url, false, stream_context_create($arrContextOptions)));
		ob_clean();
	} catch (Exception $ex) {
		$url =  $originalurl . "\n";
		file_put_contents("error_links.log", $url, FILE_APPEND);
	}

	// $ch = curl_init($url);
	// $fp = fopen($newfname, 'wb');
	// curl_setopt($ch, CURLOPT_FILE, $fp);
	// curl_setopt($ch, CURLOPT_HEADER, 0);
	// curl_exec($ch);
	// curl_close($ch);
	// fclose($fp);

	// $file = fopen($url, "rb");
	// if ($file) {
	// 	$newf = fopen($newfname, "wb");

	// 	if ($newf)
	// 		while (!feof($file)) {
	// 			fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
	// 		}
	// }

	// if ($file) {
	// 	fclose($file);
	// }

	// if ($newf) {
	// 	fclose($newf);
	// }
	return $filename;
}


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
			'authority' => 'www.filecroco.com'
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
