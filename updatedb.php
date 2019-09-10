<?php
set_time_limit(0);
ini_set('memory_limit', '1000M');

require '../public_html/wp-load.php';
require '../public_html/wp-admin/includes/taxonomy.php';
require '../public_html/wp-admin/includes/file.php';
require '../public_html/wp-admin/includes/image.php';
require '../public_html/wp-admin/includes/media.php';
require './postToWP.php';

$args = array(
	'posts_per_page'   => 1700,
	'orderby' => 'ID',
	'order' => 'ASC'
);
$q = get_posts($args);
//print_r($q);
// die();
foreach ($q as $post) {
	//setup_postdata($post);

	// here your code e.g.

	//print_r($post);
	// die();
	$title = strip_tags($post->post_title);
	$content = $post->post_content;

	// die();
	$title = explode(" ", $title);
	array_pop($title);
	$title = implode(" ", $title);

	$content = preg_replace('/<div class="wwp-gallery-container">(.*?)<\/div>/is', "", $content);
	preg_match_all('/<a\b[^>]*>/is', $content, $allAncherTags);

	foreach ($allAncherTags[0] as $anchor) {
		$replaceWith = '<span id="internalpage">';
		$content = str_replace($anchor, $replaceWith, $content);
	}
	$content = str_replace("</a>", "</span>", $content);

	$my_post = array(
		'ID'           => $post->ID,
		'post_title'   => $title,
		'post_content' => $content,
	);

	// Update the post into the database
	wp_update_post($my_post);
}
// query_posts('posts_per_page=10');
// $i = 1;
// if (have_posts()) : while (have_posts()) : the_post();
// 		$exp = array_pop(explode(" ", the_title()));
// 		$title = implode(" ", $exp);
// 		$content = the_content();

// 		$content = preg_replace('/<div class="wwp-gallery-container">(.*?)<\/div>/is', "", $content);
// 		preg_match_all('/<a\b[^>]*>/is', $content, $allAncherTags);
// 		foreach ($allAncherTags[0] as $anchor) {
// 			$replaceWith = '<span id="internalpage">';
// 			$doc = str_replace($anchor, $replaceWith, $doc);
// 		}
// 		$doc = str_replace("</a>", "</span>", $doc);
// 		$my_post = array(
// 			'ID'           => the_ID(),
// 			'post_title'   => $title,
// 			'post_content' => $doc,
// 		);

// 		// Update the post into the database
// 		wp_update_post($my_post);
// 	endwhile;
// endif;
// wp_reset_query();
