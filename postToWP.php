<?php
define('ALLOW_UNFILTERED_UPLOADS', true);

class postToWP
{

	public static function post($question)
	{

		// checking if author exists and creating is it doesn't

		$user = get_user_by('login', $question['author']);
		if ($user) {
			$userId = $user->ID;
		} else {
			$password = substr(str_shuffle(strtolower(sha1(rand() . time() . "Zaporizhzhya"))), 0, 10);
			$userdata = array(
				'user_login'  => $question['author'],
				//'user_url'    =>  'http://',
				'user_pass'   =>  $password // NULL  // When creating a new user, `user_pass` is expected.
			);

			$userId = wp_insert_user($userdata);

			// On success
			// if (!is_wp_error($user_id)) {
			//    echo "User created : ". $user_id;
			// }
		}



		if (isset($question["categories"]) && count($question["categories"]) > 0) {
			$newCatIds = self::getCategoriesIDS($question["categories"]);
		}

		//post type bt default post 
		$post_type = "post";
		if (isset($question["post_type"])) {
			$post_type = $question["post_type"];
		}
		$postArr = array(
			'post_title'   => $question['title'],
			'post_content' => $question['content'],
			'post_status'  => 'publish',
			'post_author'  => $userId,
			// 'tax_input'    => array(
			//      'question-category' => 'ahmed',
			// ),
			'post_type' => $post_type
		);

		//set and get post categories 
		if (isset($question["categories"]) && count($question["categories"]) > 0) {
			$postArr['post_category'] = $newCatIds;
		}
		//post insert 
		$id = wp_insert_post($postArr);

		//adding post tags 
		if (isset($question["tags"])) {
			wp_set_post_tags($id, $question["tags"]);
		}
		//add feature image
		if (isset($question["featureImage"])) {
			self::uploadImage($question["featureImage"], $id, true);
		}
		//adding current post meta or fields
		if (isset($question["post_meta"])) {
			if (count($question["post_meta"]) > 0) {
				foreach ($question["post_meta"] as $key => $post_meta_value) {
					add_post_meta($id, $key, $post_meta_value);
				}
			}
		}

		//wp_set_object_terms($id, $question['categories'], 'question-category' );
		//wp_set_object_terms($id, ['ahmed','one more'], 'question_tags' );


		# Answers

		// if (!isset($question['answers']) or !is_array($question['answers']) or count($question['answers']) < 1)
		// 	return;

		// foreach ($question['answers'] as $answer) {
		// 	$time = current_time('m"ysql');
		// 	$user = get_user_by('login', $answer['author']);

		// 	if ($user)
		// 		$userId = $user->ID;
		// 	else
		// 		$userId = null;

		// 	$data = array(
		// 		'comment_post_ID' => $id,
		// 		//'comment_author' => 'bazookashooter',
		// 		//'comment_author_email' => 'admin@admin.com',
		// 		//'comment_author_url' => 'http://bazookashooter.boom',
		// 		'comment_content' => $answer['content'],
		// 		'comment_type' => '',
		// 		//'comment_parent' => 0,
		// 		//'user_id' => $userId,
		// 		//'comment_author_IP' => '127.0.0.1',
		// 		//'comment_agent' => 'Mozil"la/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
		// 		'comment_date' => $time,
		// 		'comment_approved' => 0,
		// 	);

		// if ($userId)
		// 	$data['user_id'] = $userId;
		// else
		// 	$data['comment_author'] = $answer['author'];


		// wp_insert_comment($data);
		//}
		return $id;
	}
	/*
	this function is used for check category is exits if exits get id of it and save in response array if not then add category and its id added in response array and return back
	@param array of categories name 
	@return array of categories ids
	*/
	public static function getCategoriesIDS($categoriesNames)
	{
		$categoriesIds = [];
		foreach ($categoriesNames as $cat_name) {
			//Check if category already exists
			$new_cat_ID = get_cat_ID($cat_name);

			//If it doesn't exist create new category
			if ($new_cat_ID == 0) {
				$cat_name = array('cat_name' => $cat_name);
				$new_cat_ID = wp_insert_category($cat_name);
			}
			$categoriesIds[] = $new_cat_ID;
		}
		return $categoriesIds;
	}
	public static function uploadImage($url, $post_id = 0, $feature_image = false)
	{
		$image = "";
		if ($url != "") {

			$file = array();
			$file['name'] = time() . rand() . "." . end(explode(".", $url));
			$file['tmp_name'] = download_url($url);


			if (is_wp_error($file['tmp_name'])) {
				@unlink($file['tmp_name']);
				var_dump($file['tmp_name']->get_error_messages());
			} else {
				$attachmentId = media_handle_sideload($file, $post_id);
				if ($feature_image) {
					set_post_thumbnail($post_id, $attachmentId);
				}
				if (is_wp_error($attachmentId)) {
					@unlink($file['tmp_name']);
					var_dump($attachmentId->get_error_messages());
				} else {
					//$image = wp_get_attachment_url($attachmentId);
				}
			}
		}
		return $attachmentId;
	}
	public static function uploadImageAsAttachment($url, $post_id = 0, $feature_image = false)
	{
		$image = "";
		if ($url != "") {

			$file = array();
			$file['name'] = $url;
			$file['tmp_name'] = download_url($url);


			if (is_wp_error($file['tmp_name'])) {
				@unlink($file['tmp_name']);
				var_dump($file['tmp_name']->get_error_messages());
			} else {
				$image = media_sideload_image($url, 0);

				if (is_wp_error($image)) {
					@unlink($file['tmp_name']);
				}
			}
		}
		return $image;
	}
}

// $question = [
// 	'title' => 'Test title',
// 	'content' => 'Test post content',
// 	'author' => 'some author',
// 	'categories' => ['Internet Explorer 8', 'Windows XP'],
// 	'answers' => [
// 		[
// 			'author' => 'some author',
// 			'content' => 'answer content'
// 		],
// 		[
// 			'author' => 'another author',
// 			'contene' => 'another content'
// 		]
// 	],
// ];

// postToWP::post($question);
//echo postToWP::uploadImage("https://s2.glbimg.com/TpregdFiSY3OfJdxNsp7SCrEU0g=/0x0:1600x900/640x0/smart/filters:strip_icc()/i.s3.glbimg.com/v1/AUTH_08fbf48bc0524877943fe86e43087e7a/internal_photos/bs/2017/x/a/O7yrkQQACfc3S5HmdkLQ/frases-de-bom-dia-capa-copy.jpg", 2);
// exit;
