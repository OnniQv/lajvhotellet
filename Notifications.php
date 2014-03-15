<?php

require_once("NotificationCount.php");

class Notifications
{
	private static $singleton = null;
	
	public static function Singleton ()
	{
		if (! isset(self::$singleton))
		{
			self::$singleton = new Notifications();
		}
	
		return self::$singleton;
	}
	
	private function __construct ()
	{
	
	}
	
	function GetCount()
	{		
		NotificationCountInit(Auth::Singleton()->id, Auth::Singleton()->LarpId());
		return NotificationCount(Auth::Singleton()->id, Auth::Singleton()->LarpId());
	}
	
	function GetNotifications()
	{
		$user_id = Auth::Singleton()->id;
		$Groups = Groups::Singleton()->GetAll();
		$notifications = array();
		$larp_id = Auth::Singleton()->LarpId();
		$larp_shortname = Auth::Singleton()->LarpShortName();
		
		//Thread notifications
		$res = Q("SELECT * FROM thread_notifications WHERE reciever_id=? AND larp_id=?", array($user_id, $larp_id));		
		$threads = array();		
		while($assoc = $res->fetch_assoc())
		{
			$res2 = Q("SELECT title, article_id FROM forum_threads WHERE id=?", array($assoc['thread_id']));
			$assoc2 = $res2->fetch_assoc();
			
			$threads[$assoc['thread_id']]['thread_name'] = $assoc2['title'];
			$threads[$assoc['thread_id']]['article_id'] = $assoc2['article_id'];
			$threads[$assoc['thread_id']]['user_ids'][] = $assoc['sender_id'];
			$threads[$assoc['thread_id']]['times'][] = $assoc['time'];
			$threads[$assoc['thread_id']]['read'] = $assoc['read'];
				
			if(!isset($threads[$assoc['thread_id']]['last']))
				$threads[$assoc['thread_id']]['last'] = $assoc['time'];
			else
				$threads[$assoc['thread_id']]['last'] = max($assoc['time'], $threads[$assoc['thread_id']]['last']);
		}
		foreach($threads as $t)
		{
			$users = array();
			foreach($t['user_ids'] as $u)
				$users[] = RenderUserLink($u);
			
			$user_user = QS("SELECT COUNT(*) FROM user_user_relation WHERE article_id = ?", array($t['article_id']));
			
			if($user_user)
				$notifications[$t['last']]['html'] = implode(", ", $users) . " har skrivit i er privata tråd <i>" . $t['thread_name'] . "</i>";
			else
				$notifications[$t['last']]['html'] = implode(", ", $users) . " har skrivit i tråden <i>" . $t['thread_name'] . "</i> som tillhör artikeln [A[{$t['article_id']}]]";
				
			$notifications[$t['last']]['read'] = $t['read'];
		}
		
		//System notifications
		$res = Q("SELECT * FROM system_notifications WHERE reciever_id=? AND larp_id=?", array($user_id, $larp_id));		
		while($assoc = $res->fetch_assoc())
		{		
			$notifications[$assoc['time']]['html'] = $assoc['message'];
			$notifications[$assoc['time']]['read'] = $assoc['read'];
		}
		Q("UPDATE system_notifications SET `read`=1 WHERE reciever_id=? AND larp_id=?", array($user_id, $larp_id));
		
		
	
		//Article in Group requests				
		$AdminGroupIds = array();
		foreach($Groups[3] as $group_id => $group)
			$AdminGroupIds[] = $group_id;
		$AdminGroupIds = implode(",", $AdminGroupIds);
		if($AdminGroupIds != "")
		{
			//Readflags
			$res = Q("SELECT * FROM article_in_group WHERE group_id IN ($AdminGroupIds) AND flag_request=1", array());
			$requests = array();
			while($ar = $res->fetch_assoc())
			{
				$html = "[U[{$ar['publisher_id']}]] har begärt att få ändra läsflaggan för [A[{$ar['article_id']}]] i [G[{$ar['group_id']}]] till ";
			
				if($ar["read"] == "SHOULD")
					$html .= "<i>rekommenderad läsning</i>.";
				else
					$html .= "<i>måste läsas</i>.";
					
			
				$element_id = "aig_{$ar['article_id']}_{$ar['group_id']}";
				$html .= "<div id='$element_id'>";
				$html .= "<input type=button value='Godkänn flaggan' onclick=\"document.getElementById('$element_id').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateGroupArticle&id={$ar['article_id']}&group_id={$ar['group_id']}&larp_shortname=$larp_shortname&access={$ar['access']}&read={$ar['read']}&edit={$ar['edit']}&notification_respons=1', function(respons){document.getElementById('$element_id').innerHTML = 'Godkänd';})\">";
				$html .= "<input type=button value='Neka'            onclick=\"document.getElementById('$element_id').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateGroupArticle&id={$ar['article_id']}&group_id={$ar['group_id']}&larp_shortname=$larp_shortname&access=0&read={$ar['read']              }&edit={$ar['edit']}&notification_respons=1', function(respons){alert(respons);document.getElementById('$element_id').innerHTML = 'Nekad';})\">";
				$html .= "</div>";
					
				$notifications[$ar['time']]['html'] = $html;
				$notifications[$ar['time']]['read'] = 0;
			}
			
			//Publishing
			$res = Q("SELECT * FROM article_in_group WHERE group_id IN ($AdminGroupIds) AND request=1", array());
			$requests = array();
			while($ar = $res->fetch_assoc())
			{
				$html = "[U[{$ar['publisher_id']}]] har begärt att få publicera [A[{$ar['article_id']}]] i [G[{$ar['group_id']}]]";
				$element_id = "aig_{$ar['article_id']}_{$ar['group_id']}";
				$html .= "<div id='$element_id'>";
				$html .= "<input type=button value='Godkänn publicering' onclick=\"document.getElementById('$element_id').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateGroupArticle&id={$ar['article_id']}&group_id={$ar['group_id']}&larp_shortname=$larp_shortname&access={$ar['access']}&read={$ar['read']}&edit={$ar['edit']}&notification_respons=1', function(respons){alert(respons);document.getElementById('$element_id').innerHTML = 'Godkänd';})\">";
				$html .= "<input type=button value='Neka'                onclick=\"document.getElementById('$element_id').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateGroupArticle&id={$ar['article_id']}&group_id={$ar['group_id']}&larp_shortname=$larp_shortname&access=0&read={$ar['read']              }&edit={$ar['edit']}&notification_respons=1', function(respons){document.getElementById('$element_id').innerHTML = 'Nekad';})\">";
				$html .= "</div>";
				
				$notifications[$ar['time']]['html'] = $html;
				$notifications[$ar['time']]['read'] = 0;
			}
			
		}
		//User/Group/Character wants to JOIN my group
		$join_notifications = array();
		if($AdminGroupIds != "")
		{
			$res = Q("SELECT * FROM g_user_status_in_group WHERE group_id IN ($AdminGroupIds) AND request='JOIN'", array());
			while($assoc = $res->fetch_assoc())
			{
				$joiner = array();
				$joiner['type'] = "U";
				$joiner['id'] = $assoc['user_id'];
				$joiner['group_id'] = $assoc['group_id'];
				$joiner['status'] = $assoc['status'];
				$joiner['time'] = $assoc['time'];
				$join_notifications[] = $joiner;
			}
			$res = Q("SELECT * FROM g_group_status_in_group WHERE group_id IN ($AdminGroupIds) AND request='JOIN'", array());
			while($assoc = $res->fetch_assoc())
			{
				$joiner = array();
				$joiner['type'] = "G";
				$joiner['id'] = $assoc['viewer_id'];
				$joiner['group_id'] = $assoc['group_id'];
				$joiner['status'] = $assoc['status'];
				$joiner['time'] = $assoc['time'];
				$join_notifications[] = $joiner;
			}
			$res = Q("SELECT * FROM g_character_status_in_group WHERE group_id IN ($AdminGroupIds) AND request='JOIN'", array());
			while($assoc = $res->fetch_assoc())
			{
				$joiner = array();
				$joiner['type'] = "C";
				$joiner['id'] = $assoc['character_id'];
				$joiner['group_id'] = $assoc['group_id'];
				$joiner['status'] = $assoc['status'];
				$joiner['time'] = $assoc['time'];
				$join_notifications[] = $joiner;
			}
			
			foreach($join_notifications as $joiner)
			{
				if($joiner['type'] == "C")
					$joiner['type'] = "R";
				$html = "[{$joiner['type']}[{$joiner['id']}]] vill gå med i gruppen [G[{$joiner['group_id']}]]";
				if($joiner['status'] == "ADMIN")
					$html .= " som Administratör.";
				
				$element_id = "join_{$joiner['type']}_{$joiner['id']}_{$joiner['group_id']}";
				$html .= "<div id='$element_id'>";
				$html .= "<input type=button value='Godkänn medlemskap' onclick=\"document.getElementById('$element_id').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateGroupMember&group_id={$joiner['group_id']}&id={$joiner['id']}&type={$joiner['type']}&status={$joiner['status']}&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=1', function(respons){document.getElementById('$element_id').innerHTML = 'Godkänd';})\">";
				$html .= "<input type=button value='Neka'               onclick=\"document.getElementById('$element_id').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateGroupMember&group_id={$joiner['group_id']}&id={$joiner['id']}&type={$joiner['type']}&status=0&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=1', function(respons){document.getElementById('$element_id').innerHTML = respons;})\">";
				$html .= "</div>";
				$notifications[$joiner['time']]['html'] = $html;
				$notifications[$joiner['time']]['read'] = 0;
			}
		}
		
		
		//User/Group/Char has been INVITE'd to group
		$CharactersIOwn = array();
		$CharactersICreated = array();
		$Characters = Characters::Singleton()->GetAll();
		foreach($Characters['created'] as $ch)
		{
			$CharactersIOwn[$ch['id']] = $ch['id'];
			$CharactersICreated[$ch['id']] = $ch['id'];
		}
		foreach($Characters['playing'] as $ch)
			$CharactersIOwn[$ch['id']] = $ch['id'];
		$CharactersIOwn = implode(",", $CharactersIOwn);
		$CharactersICreated = implode(",", $CharactersICreated);
		$invite_notifications = array();
		$res = Q("SELECT * FROM g_user_status_in_group WHERE user_id=? AND request='INVITE'", array($user_id));
		while($assoc = $res->fetch_assoc())
		{
			$invite = array();
			$invite['type'] = "U";
			$invite['id'] = $assoc['user_id'];
			$invite['group_id'] = $assoc['group_id'];
			$invite['status'] = $assoc['status'];
			$invite['time'] = $assoc['time'];
			$invite_notifications[] = $invite;
		}		
		if($CharactersIOwn != "")
		{
			$res = Q("SELECT * FROM g_character_status_in_group WHERE character_id IN ($CharactersIOwn) AND request='INVITE'", array());
			while($assoc = $res->fetch_assoc())
			{
				$invite = array();
				$invite['type'] = "R";
				$invite['id'] = $assoc['character_id'];
				$invite['group_id'] = $assoc['group_id'];
				$invite['status'] = $assoc['status'];
				$invite['time'] = $assoc['time'];
				$invite_notifications[] = $invite;
			}
		}
		if($AdminGroupIds != "")
		{			
			$res = Q("SELECT * FROM g_group_status_in_group WHERE viewer_id IN ($AdminGroupIds) AND request='INVITE'", array());
			while($assoc = $res->fetch_assoc())
			{
				$invite = array();
				$invite['type'] = "G";
				$invite['id'] = $assoc['viewer_id'];
				$invite['group_id'] = $assoc['group_id'];
				$invite['status'] = $assoc['status'];
				$invite['time'] = $assoc['time'];
				$invite_notifications[] = $invite;
			}
		}
		foreach($invite_notifications as $invite)
		{
			if($invite['type'] == "C")
				$invite['type'] = "R";
			$html = "[{$invite['type']}[{$invite['id']}]] har blivit inbjuden till [G[{$invite['group_id']}]]";
			if($invite['status'] == "ADMIN")
				$html .= " som Administratör.";
			
			$element_id = "invite_{$invite['type']}_{$invite['id']}_{$invite['group_id']}";
			$html .= "<div id='$element_id'>";
			$html .= "<input type=button value='Acceptera inbjudan' onclick=\"document.getElementById('$element_id').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateGroupMember&group_id={$invite['group_id']}&id={$invite['id']}&type={$invite['type']}&status={$invite['status']}&larp_shortname=$larp_shortname&join_invite=INVITE&notification_respons=1', function(respons){document.getElementById('$element_id').innerHTML = respons;})\">";
			$html .= "<input type=button value='Neka'               onclick=\"document.getElementById('$element_id').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateGroupMember&group_id={$invite['group_id']}&id={$invite['id']}&type={$invite['type']}&status=0&larp_shortname=$larp_shortname&join_invite=INVITE&notification_respons=1', function(respons){document.getElementById('$element_id').innerHTML = respons;})\">";
			$html .= "</div>";
			$notifications[$invite['time']]['html'] = $html;
			$notifications[$invite['time']]['read'] = 0;
		}
		
		//Admin approving characters
		if(Auth::Singleton()->OrganizingLarp() && Auth::Singleton()->LarpValue('approve_characters'))
		{
			$res = Q("SELECT * FROM characters JOIN character_partof_larp ON characters.id=character_partof_larp.character_id WHERE characters.state='APPROVE' AND character_partof_larp.larp_id=?", array($larp_id));
			
			while($assoc = $res->fetch_assoc())
			{
				$html = "[R[{$assoc['id']}]] har uppdaterats och behöver godkännas.";
				$notifications[$assoc['update_time']]['html'] = $html;
				$notifications[$assoc['update_time']]['read'] = 0;
			}
		}
		
		
		//User asking to play character
		if($CharactersICreated != "")
		{
			$res = Q("SELECT * FROM user_playing_character WHERE character_id IN ($CharactersICreated) AND request='JOIN'", array());
			while($assoc = $res->fetch_assoc())
			{
				$html = "[U[{$assoc['user_id']}]] vill spela rollen [R[{$assoc['character_id']}]]";
				$element_id = "play_{$assoc['user_id']}_{$assoc['character_id']}";
				$html .= "<div id='$element_id'>";
				$html .= "<input type=button value='Tillåt' onclick=\"document.getElementById('$element_id').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateUserPlayingCharacter&user_id={$assoc['user_id']}&character_id={$assoc['character_id']}&request=NONE&larp_shortname=$larp_shortname', function(respons){document.getElementById('$element_id').innerHTML = respons;})\">";
				$html .= "<input type=button value='Neka'   onclick=\"document.getElementById('$element_id').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateUserPlayingCharacter&user_id={$assoc['user_id']}&character_id={$assoc['character_id']}&request=&larp_shortname=$larp_shortname', function(respons){document.getElementById('$element_id').innerHTML = respons;})\">";
				$html .= "</div>";
				$notifications[$assoc['time']]['html'] = $html;
				$notifications[$assoc['time']]['read'] = 0;
			}
		}
		
		krsort($notifications);
		
		
		return $notifications;
	}
	
	
}

?>