<?php

require_once("SQL.php");
$last_read_time = 0;

function ForumRefreshInit($user_id, $larp_id, $article_id)
{
	global $last_read_time;
	
	$last_read_time = QS("SELECT time FROM user_read_article_threads WHERE article_id=? AND user_id=?", array($article_id, $user_id));
}

function ForumRefresh($user_id, $larp_id, $article_id)
{
	
	global $last_read_time;
		
	$new_threads = QS("SELECT COUNT(*) FROM forum_threads WHERE article_id=? AND created>?", array($article_id, $last_read_time));	
	if($new_threads > 0)
		return "NewThread";
	
	$new_threads = QS("SELECT thread_id FROM forum_messages WHERE article_id=? AND created>?", array($article_id, $last_read_time));
	if($new_threads > 0)
		return "$new_threads";	
	
	
	return false;
}


?>