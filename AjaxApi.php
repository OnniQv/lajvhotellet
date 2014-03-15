<?php


require_once("auth.php");
Auth::Singleton()->Auth($_GET['larp_shortname']);

if($_GET['command'] == "UpdateGroupMember")
{	
	$joiner_id = $_GET['id'];
	$joiner_type = $_GET['type'];
	$group_id = $_GET['group_id'];
	$status = $_GET['status'];	
	$notification_respons = $_GET['notification_respons'];
	$join_invite = $_GET['join_invite'];
	
	if(isset($_GET['return_url']))
		$return_url = $_GET['return_url'];
	
	unset($_GET);

	require_once("Groups.php");
	
	
	$Group = Groups::Singleton()->GetGroup($group_id);
	$result = $Group->UpdateMember($joiner_type, $joiner_id, $status, $join_invite, $notification_respons);

	if(isset($return_url))
		header("location: $return_url");
	
	die($result);
}

if($_GET['command'] == "UpdateGroupArticle")
{
	$article_id = $_GET['id'];	
	$group_id = $_GET['group_id'];
	$access = $_GET['access'];
	$read = $_GET['read'];
	$edit = $_GET['edit'];	
	$notification_respons = $_GET['notification_respons'];
	
	unset($_GET);

	require_once("Groups.php");
	require_once("Articles.php");
	
	
	$Group = Groups::Singleton()->GetGroup($group_id);
	
	$result = $Group->UpdateArticle($article_id, $access, $read, $edit, $notification_respons);
	

	die($result);
	
}

if($_GET['command'] == "UpdateCharacterKnower")
{
	$knower_id = $_GET['knower_id'];
	$character_id = $_GET['character_id'];
	$knower_type = $_GET['knower_type'];
	$know = $_GET['know'];
	
	unset($_GET);

	require_once("Characters.php");
	
	switch($know)
	{
		case "0": $know = ""; break;
		case "1": $know = "OF"; break;
		case "2": $know = "WELL"; break;
	}

	$Character = Characters::Singleton()->GetCharacter($character_id);

	$result = $Character->UpdateKnower($knower_type, $knower_id, $know);

	die($result);

}

if($_GET['command'] == "UpdateCharacterState")
{
	$character_id = $_GET['character_id'];
	$state = $_GET['state'];
	$update_time = $_GET['update_time'];
		
	unset($_GET);
	
	
	
	$c = Characters::Singleton()->GetCharacter($character_id);
	$c->UpdateState($state, $update_time);
	
	die();
}

if($_GET['command'] == "UpdateUserPlayingCharacter")
{
	$character_id = $_GET['character_id'];
	$user_id = $_GET['user_id'];
	$request = $_GET['request'];
	$larp_id = Auth::Singleton()->LarpId();
	
	unset($_GET);
	
	require_once("Characters.php");
	
	$c = Characters::Singleton()->GetCharacter($character_id);
	$c->UpdatePlaying($user_id, $request);
	
	
	die();
}


die("MissedMe");
?>
