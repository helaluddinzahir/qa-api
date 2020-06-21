<?php
	require_once '../qa-include/qa-base.php';
	require_once '../qa-include/qa-db-users.php';
	require_once '../qa-include/qa-db-selects.php';
	require_once '../qa-include/qa-app-format.php';
	require_once '../qa-include/qa-app-users.php';
	require_once '../qa-include/qa-app-cookies.php';

	$categoryslugs = qa_request_parts(1);
	$countslugs = count($categoryslugs);

	$by = ($countslugs && !QA_ALLOW_UNINDEXED_QUERIES) ? null : qa_get('by');
	$start = min(max(0, (int)qa_get('start')), QA_MAX_LIMIT_START);
	$userid = qa_get_logged_in_userid();
	$cookieid = qa_cookie_get();

	list($questions, $categories, $categoryid) = qa_db_select_with_pending(
		qa_db_unanswered_qs_selectspec($userid, $selectby, $start, $categoryslugs, false, false, qa_opt_if_loaded('page_size_una_qs')),
		QA_ALLOW_UNINDEXED_QUERIES ? qa_db_category_nav_selectspec($categoryslugs, false, false, true) : null,
		$countslugs ? qa_db_slugs_to_category_id_selectspec($categoryslugs) : null
	);
	
	$total = count($questions);
	
	$data = array();
	$usershtml = qa_userids_handles_html($questions, true);
	foreach( $questions as $question ){
		$questionid = $question['postid'];
		$htmloptions = qa_post_html_options($question, null, true);
		$htmloptions['answersview'] = false; // answer count is displayed separately so don't show it here
		$htmloptions['avatarsize'] = qa_opt('avatar_q_page_q_size');
		$htmloptions['q_request'] = qa_q_request($questionid, $question['title']);
		
		$qa_content = qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, $htmloptions);
		
		//$who = $qa_content['who'];
		
		$when = '<b>'.@$qa_content['when']['data'].' '.@$qa_content['when']['suffix'].'</b>';
		$where = @$qa_content['where']['prefix'].' <b>'.@$qa_content['where']['data'].'</b>';
		if (array_key_exists('points', @$qa_content['who']))
		$points = ' ('. @$qa_content['who']['points']['data'].' '. $qa_content['who']['points']['suffix'].')';
		else $points = '';
		$who = @$qa_content['who']['prefix'].' <b>'.@$qa_content['who']['data'].'</b>'. $points;

		array_push($data, array(
			'postid' 			=> $questionid,			
			'type' 				=> $question['type'],
			'basetype' 			=> $question['basetype'],
			'hidden' 			=> $question['hidden'],
			'queued' 			=> $question['queued'],
			'acount' 			=> $question['acount'],
			'selchildid' 		=> $question['selchildid'],
			'closedbyid' 		=> $question['closedbyid'],
			'upvotes' 			=> $question['upvotes'],
			'downvotes' 		=> $question['downvotes'],
			'netvotes' 			=> $question['netvotes'],
			'views' 			=> $question['views'],
			'hotness' 			=> $question['hotness'],
			'flagcount' 		=> $question['flagcount'],
			'title' 			=> $question['title'],
			'tags' 				=> $question['tags'],
			'created' 			=> $question['created'],
			'categoryid' 		=> $question['categoryid'],
			'name' 				=> $question['name'],
			'categoryname' 		=> $question['categoryname'],
			'categorybackpath' 	=> $question['categorybackpath'],
			'categoryids' 		=> $question['categoryids'],
			'userid' 			=> $question['userid'],
			'cookieid' 			=> $question['cookieid'],
			'createip' 			=> $question['createip'],
			'points' 			=> $question['points'],
			'flags' 			=> $question['flags'],
			'level' 			=> $question['level'],
			'email' 			=> $question['email'],
			'handle' 			=> $question['handle'],
			'avatarblobid' 		=> $question['avatarblobid'],
			'avatarwidth' 		=> $question['avatarwidth'],
			'avatarheight' 		=> $question['avatarheight'],
			'avatar' 			=> $qa_content['avatar'],
			'vote_state' 		=> $qa_content['vote_state'],
			'meta_order' 		=> $qa_content['meta_order'],
			'meta_what' 		=> $qa_content['what'],
			'meta_when'			=> strip_tags($when),
			'meta_where' 		=> strip_tags($where),
			'meta_who' 			=> strip_tags($who))
		);
	}
	$output = json_encode(array('total' => $total, 'data' => $data));
	
	echo $output;