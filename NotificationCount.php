<?php

require_once("SQL.php");
require_once("Groups.php");
require_once("Characters.php");
require_once("Debug.php");


$AdminGroupIds = "";
$CharactersIOwn = "";
$CharactersICreated = "";


function NotificationCountInit($user_id, $larp_id)
{
	global $id;
	global $AdminGroupIds;
	global $CharactersIOwn;
	global $CharactersICreated;
	
	
	$id = $user_id;
	
	$Groups = Groups::Singleton()->GetAll($user_id, $larp_id);
	$AdminGroupIds = array();
	foreach($Groups[3] as $group_id => $group)
		$AdminGroupIds[] = $group_id;
	$AdminGroupIds = implode(",", $AdminGroupIds);
	
	$Characters = Characters::Singleton()->GetAll($user_id, $larp_id);
	$CharactersIOwn = array();
	foreach($Characters['mine'] as $ch)
		$CharactersIOwn[$ch['id']] = $ch['id'];	
	
	$CharactersICreated = array();
	foreach($Characters['created'] as $ch)
		$CharactersICreated[$ch['id']] = $ch['id'];
	
	
	$CharactersIOwn = implode(",", $CharactersIOwn);
	$CharactersICreated = implode(",", $CharactersICreated);
	
	
}

function NotificationCountLP($user_id, $larp_id, $count)
{
	$newCount = NotificationCount($user_id, $larp_id);
	if($newCount == $count)
		return false;
	
	return $newCount;
}

function NotificationCount($user_id, $larp_id)
{

	global $AdminGroupIds;
	global $CharactersIOwn;
	global $CharactersICreated;
	
	
	//Thread notifications
	$thread_notifications = QS("SELECT COUNT(DISTINCT(thread_id)) FROM thread_notifications WHERE reciever_id=? AND `read`=0 AND larp_id=?", array($user_id, $larp_id));
	if(!isset($thread_notifications))
		$thread_notifications = 0;
	
	//System notifications	
	$system_notifications = QS("SELECT COUNT(*) FROM system_notifications WHERE reciever_id=? AND `read`=0 AND larp_id=?", array($user_id, $larp_id));
	if(!isset($system_notifications))
		$system_notifications = 0;
	
	//Article in Group requests
	if($AdminGroupIds != "")
		$article_notifications = QS("SELECT count(*) FROM article_in_group WHERE group_id IN ($AdminGroupIds) AND (request=1 OR flag_request=1)", array());
	if(!isset($article_notifications))
		$article_notifications = 0;
	
	//User/Group/Character wants to JOIN my group
	$join_notifications = 0;
	if($AdminGroupIds != "")
	{
		$join_notifications += QS("SELECT count(*) FROM g_user_status_in_group WHERE group_id IN ($AdminGroupIds) AND request='JOIN'", array());
		$join_notifications += QS("SELECT count(*) FROM g_character_status_in_group WHERE group_id IN ($AdminGroupIds) AND request='JOIN'", array());
		$join_notifications += QS("SELECT count(*) FROM g_group_status_in_group WHERE group_id IN ($AdminGroupIds) AND request='JOIN'", array());
	}
	
	//User/Group/Char has been INVITE'd to group
	$invite_notifications = 0;
	$invite_notifications += QS("SELECT count(*) FROM g_user_status_in_group WHERE user_id=? AND request='INVITE'", array($user_id));
	if($CharactersIOwn != "")
		$invite_notifications += QS("SELECT count(*) FROM g_character_status_in_group WHERE character_id IN ($CharactersIOwn) AND request='INVITE'", array());
	if($AdminGroupIds != "")
		$invite_notifications += QS("SELECT count(*) FROM g_group_status_in_group WHERE viewer_id IN ($AdminGroupIds) AND request='INVITE'", array());
	
	
	//Characters need approval
	$approve_character_notifications = 0;
	if(class_exists("Auth") && Auth::Singleton()->OrganizingLarp() && Auth::Singleton()->LarpValue('approve_characters'))
	{
		$larp_id = Auth::Singleton()->LarpId();
		$approve_character_notifications = QS("SELECT COUNT(*) FROM characters JOIN character_partof_larp ON characters.id=character_partof_larp.character_id WHERE characters.state='APPROVE' AND character_partof_larp.larp_id=?", array($larp_id));
	}
	
	//Users asking to play Characters
	$characters_play_notifications = 0;
	if($CharactersICreated != "")
		$characters_play_notifications = QS("SELECT COUNT(*) FROM user_playing_character WHERE character_id IN ($CharactersICreated) AND request='JOIN'", array());
	
	$total = $thread_notifications + $system_notifications + $article_notifications + $join_notifications + $invite_notifications + $approve_character_notifications + $characters_play_notifications;
		
	return $total;
}
?>
