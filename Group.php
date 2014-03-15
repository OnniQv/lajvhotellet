<?php


class Group
{

	public $id;
	public $Name;	
	public $Type;	
	public $Guarded;
	public $MembersAddArticles;
	public $MembersEditReadFlags;
	public $Creator_id;
	public $Creator;
	public $Created;
	public $KnowOf;
	public $Member;
	public $Admin;
	public $MemberJoin;
	public $AdminJoin;	
	public $MemberInvite;
	public $AdminInvite;
	public $PublicArticle;
	public $PrivateArticle;
	
	function __construct($group_id)
	{
		$this->id = $group_id;
		$user_id = Auth::Singleton()->id;
		$larp_id = Auth::Singleton()->LarpId();
		$all_id = Auth::Singleton()->LarpValue("all_group_id");
		
		
		
		$res = Q("SELECT * FROM groups WHERE id=?", array($group_id));
		
		$assoc = $res->fetch_assoc();
		$res->free();
		$this->Name = $assoc["name"];
		$this->Created = $assoc["created"];
		$this->Creator_id = $assoc["creator_id"];
		$this->Creator = $assoc["creator_id"] == $user_id;
		$this->Type = $assoc["type"];		
		$this->Guarded = $assoc["guarded"];
		$this->MembersAddArticles = $assoc["members_add_article"];
		$this->MembersEditReadFlags = $assoc["members_edit_readflags"];
		$this->PublicArticle = 0;
		$this->PrivateArticle = 0;
		
		$this->KnowOf = false;
		$this->Member = false;
		$this->Admin = false;
		$this->MemberJoin = false;
		$this->AdminJoin = false;
		$this->MemberInvite = false;
		$this->AdminInvite = false;
		
		
		if($this->Creator_id == Auth::Singleton()->id || Auth::Singleton()->OrganizerMode())
		{
			$this->KnowOf = true;
			$this->Member = true;
			$this->Admin = true;
		}
		
		if($group_id == $all_id)
		{		
			$this->KnowOf = true;
			$this->Member = true;
			$this->Admin = Auth::Singleton()->OrganizingLarp();
			return;
		}
		
		
		$res = Q("SELECT article_id, access FROM article_in_group WHERE group_id=? AND official=1", array($group_id));
		
		while(list($article_id, $access) = $res->fetch_row())
		{
			if($access == "PUBLIC")
				$this->PublicArticle = $article_id;
			else
				$this->PrivateArticle = $article_id;
		}
		
		// This User status in this group	
		$res = Q("SELECT status, request FROM g_user_status_in_group WHERE user_id=? AND group_id=?", array($user_id, $group_id));				
		while(list ($status, $request) = $res->fetch_row())
		{
			if($request == "JOIN")
			{
				if($status == "ADMIN")					
					$this->AdminJoin = true;				
				if($status == "MEMBER")									
					$this->MemberJoin = true;								
			}
			else if($request == "INVITE")
			{
				if($status == "ADMIN")
					$this->AdminInvite = true;
				if($status == "MEMBER")
					$this->MemberInvite = true;
			}
			else //request == null
			{
				if($status == "ADMIN")
				{
					$this->KnowOf = true;
					$this->Member = true;
					$this->Admin = true;
				}
				if($status == "MEMBER")
				{
					$this->KnowOf = true;
					$this->Member = true;
				}
				if($status == "KNOW")
				{
					$this->KnowOf = true;
				}
			}
		}
		
	
		//This user member of other groups
		$member_groups = array(0 => false); //Always part of group All
		$res = Q("SELECT group_id FROM g_user_status_in_group WHERE user_id=? AND (status='ADMIN' OR status='MEMBER') AND request='NONE'", array($user_id));
		while(list ($g) = $res->fetch_row())		
			$member_groups[$g] = false;
		
		//This user creastor of other groups
		$res = Q("SELECT id FROM groups WHERE creator_id = ?", array($user_id));
		while(list ($g) = $res->fetch_row())		
			$member_groups[$g] = false;
		
		
		
		// This users characters
		$larp_id = Auth::Singleton()->LarpId();
		$res = Q("SELECT character_id FROM user_playing_character WHERE user_id=? AND larp_id=? AND request='NONE'", array($user_id, $larp_id));
		while (list ($character_id) = $res->fetch_row())
		{						
			$res2 = Q("SELECT status, request FROM g_character_status_in_group WHERE character_id=? AND group_id=?", array($character_id, $group_id));
			while(list ($status, $request) = $res2->fetch_row())
			{				
				if($request == "JOIN")
				{
					if($status == "ADMIN")					
						$this->AdminJoin = true;				
					if($status == "MEMBER")									
						$this->MemberJoin = true;								
				}
				else if($request == "INVITE")
				{
					if($status == "ADMIN")
					{
						$this->KnowOf = true;//if invited, then you can see stuff
						$this->Member = true;					
						$this->AdminInvite = true;
					}
					if($status == "MEMBER")
						$this->MemberInvite = true;
				}
				else //request == null
				{
					if($status == "ADMIN")
					{
						$this->KnowOf = true;
						$this->Member = true;
						$this->Admin = true;
					}
					if($status == "MEMBER")
					{
						$this->KnowOf = true;
						$this->Member = true;
					}
					if($status == "KNOW")
					{
						$this->KnowOf = true;
					}	
				}
			}
			//This character member of group
			$res3 = Q("SELECT group_id FROM g_character_status_in_group WHERE character_id=? AND (status='ADMIN' OR status='MEMBER') AND request='NONE'", array($character_id));
			while(list ($g) = $res3->fetch_row())
				$member_groups[$g] = false;
			
		}
		
		//I am member of Alla
		$member_groups[$all_id] = false;
		
		// All those groups, recurse!
		while (true)
		{

			// Find new recursive groups
			$add = array();
			foreach ($member_groups as $g_id => $group_data)
			{

				if ($group_data)
					continue;	

				$member_groups[$g_id] = true;
				
				$res = SQL::S()->Q("SELECT group_id FROM g_group_status_in_group WHERE (status='ADMIN' OR status='MEMBER') AND viewer_id=? AND request='NONE'", array($g_id));

				while (list ($gid) = $res->fetch_row())
					$add[$gid] = false;
					
			}
			
				
			// Add new groups to list
			foreach ($add as $nid => $data)
			{
		
				if (isset($member_groups[$nid]) && $member_groups[$nid]  !== true)
				{
		
					$member_groups[$nid] = false;
				}
			}
				
			if (count($add) == 0)
				break;
		}
		$member_groups2 = array();
		foreach ($member_groups as $g_id => $group_data)
			$member_groups2[] = $g_id;

		//Now we know what groups we are a member of.
		//Any of these groups got access to $group_id?
		foreach($member_groups2 as $gid)
		{
			$res = Q("SELECT status, request FROM g_group_status_in_group WHERE viewer_id=? AND group_id=?", array($gid, $group_id));
			while(list ($status, $request) = $res->fetch_row())
			{
				if($request == "JOIN")
				{
					if($status == "ADMIN")
						$this->AdminJoin = true;
					if($status == "MEMBER")
						$this->MemberJoin = true;
				}
				else if($request == "INVITE")
				{
					if($status == "ADMIN")
						$this->AdminInvite = true;
					if($status == "MEMBER")
						$this->MemberInvite = true;
				}
				else //request == null
				{
					if($status == "ADMIN")
					{
						$this->KnowOf = true;
						$this->Member = true;
						$this->Admin = true;
					}
					if($status == "MEMBER")
					{
						$this->KnowOf = true;
						$this->Member = true;
					}
					if($status == "KNOW")
					{
						$this->KnowOf = true;
					}
				}			
			}	
		}
		
	}
	
	private function StatusNumber($status_text)
	{
		switch($status_text)
		{
			case "KNOW": return 0;
			case "MEMBER": return 1;
			case "ADMIN": return 2;
		}
	}
	
	private function InsertMember($member)
	{
		$id = $member['type'] . $member['id'];
		
		if(isset($this->Members[$id]))
		{
			if($this->StatusNumber($member['status']) > $this->StatusNumber($this->Members[$id]['status']))
				$this->Members[$id]['status'] = $member['status'];
		}
		else		
			$this->Members[$id] = $member;
	}
	
	public function GetMembers()
	{
		$this->Members = array();
		
		
		$member = array();
		$member['id'] = $this->Creator_id;
		$member['type'] = "user";
		$member['status'] = "ADMIN"; //0->2 know_of, member, admin
		$this->InsertMember($member);
		
		
		
		$res = Q("SELECT * FROM g_user_status_in_group WHERE group_id = ? AND request='NONE'", array($this->id));
		while($assoc = $res->fetch_assoc())
		{
			$member = array();
			$member['id'] = $assoc["user_id"];
			$member['type'] = "user";			
			$member['status'] = $assoc["status"]; //0->2 know_of, member, admin
			$this->InsertMember($member);
		}	
		
		$res = Q("SELECT * FROM g_character_status_in_group WHERE group_id = ? AND request='NONE'", array($this->id));
		while($assoc = $res->fetch_assoc())
		{
			$member = array();
			$member['id'] = $assoc["character_id"];
			$member['type'] = "character";
			$member['status'] = $assoc["status"]; //0->2 know_of, member, admin
			$this->InsertMember($member);
		}
		
		$res = Q("SELECT * FROM g_group_status_in_group WHERE group_id = ? AND request='NONE'", array($this->id));
		while($assoc = $res->fetch_assoc())
		{
			$member = array();
			$member['id'] = $assoc["viewer_id"];
			$member['type'] = "group";
			$member['status'] = $assoc["status"]; //0->2 know_of, member, admin
			$this->InsertMember($member);
		}
		//AddDebug("Group[{$this->id}]]->GetMembers:" . print_r($this->Members, true));
		return $this->Members;
	}
	
	public function GetAllReadingUsers()
	{
		$readers = array();
		$Members = $this->GetMembers();
		$groups = array();
		$all_done = false;
		
		
		
		$x = 0;
		while(!$all_done)
		{
			if($x++ > 100)
				break;
			$all_done = true;
			foreach($Members as $i => $m)
			{
				$Members[$i];
				if(isset($m['read']))
					continue;
				$Members[$i]['read'] = true;
				$all_done = false;
				
				if($m['status'] == 0)
					continue;
				
				switch($m['type'])
				{
					case "user": $reader[$m['id']] = $m['id']; break;
					case "character": $res = Q("SELECT user_id FROM user_playing_character WHERE character_id= {$m['id']} AND request='NONE'");
										while(list($uid) = $res->fetch_array())
											$readers[$uid] = $uid;
										break;
					case "group" : $groups[$m['id']] = $m['id']; break;
				}			
				
			}
			
			
			foreach($groups as $g)
			{
				$res = Q("SELECT * FROM g_user_status_in_group WHERE group_id = ? AND request='NONE'", array($g));
				while($assoc = $res->fetch_assoc())
				{
					$member = array();
					$member['id'] = $assoc["user_id"];
					$member['type'] = "user";
					$member['status'] = $assoc["status"]; //0->2 know_of, member, admin
					$this->InsertMember($member);
				}
				
				$res = Q("SELECT * FROM g_character_status_in_group WHERE group_id = ? AND request='NONE'", array($g));
				while($assoc = $res->fetch_assoc())
				{
					$member = array();
					$member['id'] = $assoc["character_id"];
					$member['type'] = "character";
					$member['status'] = $assoc["status"]; //0->2 know_of, member, admin
					$this->InsertMember($member);
				}
				
				$res = Q("SELECT * FROM g_group_status_in_group WHERE group_id = ? AND request='NONE'", array($g));
				while($assoc = $res->fetch_assoc())
				{
					$member = array();
					$member['id'] = $assoc["viewer_id"];
					$member['type'] = "group";
					$member['status'] = $assoc["status"]; //0->2 know_of, member, admin
					$this->InsertMember($member);
				}
			}
		}
		
		
		return $readers;			
		
	} 
	
	/*
	 * $member_type: "U", "R"/"C", "G"
	 * $status: "","KNOW", "MEMBER", "ADMIN"	  
	 * $join_invite: "JOIN", "INVITE"
	 * $notification_respons: true/false
	 */
	public function UpdateMember($joiner_type, $joiner_id, $status, $join_invite, $notification_respons)
	{
		if($joiner_type == "C")
			$joiner_type = "R";

		if($status == "0")
			$status = "";


		$group_id = $this->id;
		$actor_listeners = array();
		$my_joiner = false;
		$larp_id = Auth::Singleton()->LarpId();
		switch($joiner_type)
		{
			case "U": 	$table = "g_user_status_in_group"; $viewer = "user_id";		
								$actor_listeners[$member_id] = $member_id; 
								if($member_id == Auth::Singleton()->id)
									$my_joiner = true;
								break;		
										
			case "R": 	$table = "g_character_status_in_group"; $viewer = "character_id";	
								$res = Q("SELECT user_id FROM user_playing_character WHERE character_id=? AND larp_id=? AND request='NONE'", array($joiner_id, $larp_id));
								while(list($uid) = $res->fetch_array())
								{
									$actor_listeners[$uid] = $uid;
									if($uid  == Auth::Singleton()->id)
										$my_joiner = true;
								}								
								Q("INSERT INTO group_know_character (group_id, character_id, know) VALUES (?, ?, 'OF') ON DUPLICATE KEY UPDATE group_id=group_id", array($group_id, $joiner_id));								
								break;
								
			case "G": 	$table = "g_group_status_in_group"; $viewer = "viewer_id";	
								$Group2 = Groups::Singleton()->GetGroup($joiner_id);
								$actor_listeners = $Group2->GetAllReadingUsers();
								if($Group2->Admin)
									$my_joiner = true;
								break;
		}
		
		
		$message = "";		
		$request = "NONE";
		
		//New in the group
		if($join_invite == "JOIN")
		{
			if($notification_respons)
			{
				//Accept JOIN-request (or NOT)
				if($status == "")
					$message = "[{$joiner_type}[{$joiner_id}]] blev nekad tillträde till [G[$group_id]]";
				else
				{
					$message = "[{$joiner_type}[{$joiner_id}]] har fått tillträde till [G[$group_id]]";
					if($status == "ADMIN")
						$message .= " som Administratör.";	
				}
			}
			else
			{
				//Can I JOIN you?
				if($this->Guarded || $status=="ADMIN")
				{
					//JOIN request, no need for other notification
					$request = "JOIN";					
				}
				else 
				{
					//Direct member/leave
					if($status != "")
						$message = "[{$joiner_type}[{$joiner_id}]] har gått med i [G[$group_id]]";
					else					
						$message = "[{$joiner_type}[{$joiner_id}]] har lämnat [G[$group_id]]";					
				}				
			}			
		}
		else
		{
			if($notification_respons)
			{				
				//Accept INVITE (or NOT)
				$message = "[{$joiner_type}[{$joiner_id}]] har accepterat inbjudan till [G[$group_id]]";
				if($status == "ADMIN")
					$message .= " som Administratör.";
				$message = "[{$joiner_type}[{$joiner_id}]] nekade inbjudan till [G[$group_id]]";
				
			}
			else
			{
				//Inviting myself
				if($my_joiner)
				{
					if($status != "")
						$message = "[{$joiner_type}[{$joiner_id}]] har gått med i [G[$group_id]]";
					else
						$message = "[{$joiner_type}[{$joiner_id}]] har lämnat [G[$group_id]]";
				}	
				else//INVITEing someone else
				{				
					$request = "INVITE";
				}
			}
		}
			
		if($status != "")
		{			
			//TODO: if upgrading from member->user we loose status while waiting
			Q("DELETE FROM $table WHERE $viewer=? AND group_id=?", array($joiner_id, $group_id));
			
			Q("INSERT INTO $table (group_id, $viewer, status, request, time) VALUES (?, ?, ?, ?, NOW())", array($group_id, $joiner_id, $status, $request));
			
			
		}
		else
		{			
			if($notification_respons && $status!="")
				Q("DELETE FROM $table WHERE $viewer=? AND group_id=? AND status=?", array($joiner_id, $group_id, $status));
			else
				Q("DELETE FROM $table WHERE $viewer=? AND group_id=?", array($joiner_id, $group_id));
			
			$request = "";
		}
		
		
		if($message != "")
		{		
			$affected_user = $this->GetAllReadingUsers();
			
			foreach($actor_listeners as $u)
				$affected_user[$u] = $u;
			
			foreach($affected_user as $user_id)
			{
				if($user_id == Auth::Singleton()->id)
					continue;
				Q("INSERT INTO system_notifications (reciever_id, message, time, larp_id) VALUES (?,?, NOW(), ?)", array($user_id, $message, $larp_id));
			}
		}
		return $request;
	}
	
	/*
	 * $access: "0", "PUBLIC", "PRIVATE"
	 * $read: "NONE", "SHOULD", "MUST"
	 * $edit: "NONE", "MEMBERS", "ADMINS"
	 */
	public function UpdateArticle($article_id, $access, $read, $edit, $notification_respons)	
	{

		$request = "JOIN"; //åäö blir null i JSONifieringen
		$r = 1;
		$fr = 1;
		$group_id = $this->id;
		
		$larp_id = Auth::Singleton()->LarpId();
		
		if($access != "0")
		{
			if(($this->MembersAddArticles && $this->Member) || $this->Admin)
			{
				$r = 0;
				$request = "";
			}
		}
		
		//I can set readflag, or readflag not significant
		if(($this->MembersEditReadFlags && $this->Member) || $this->Admin || $read == "NONE")
		{
			$fr = 0;
		}
		else if($request == "")
			$request = "Flagg-forfragan";
		
		
		//if answering notification, there is no request
		if($notification_respons)
		{
			$r = 0;
			$fr = 0;
		}
			
		
		
		if($access == "0")
		{
			Q("DELETE FROM article_in_group WHERE article_id=? AND group_id=?", array($article_id, $group_id));
			
			if($notification_respons)
				$message = "[A[$article_id]] blev nekad publicering i [G[$group_id]].";
			else
				$message = "[A[$article_id]] har tagits bort ur [G[$group_id]].";
				
			$users = $this->GetAllReadingUsers();
			foreach($users as $u)
			{
				if($u == Auth::Singleton()->id)
					continue;
				Q("INSERT INTO system_notifications (reciever_id, message, time, larp_id) VALUES (?,?, NOW(), ?)", array($u, $message, $larp_id));
			}
		}
		else
		{
			$old_request = QS("SELECT COUNT(*) FROM article_in_group WHERE article_id=? AND group_id=? AND request=1", array($article_id, $group_id));
				
			$published = ($r == 0 && $old_request > 0);
				
			if($published)
			{
				$users_before = Articles::Singleton()->GetAllReadingUsers($article_id);
			}
				
			$publisher_id = Auth::Singleton()->id;
			Q("INSERT INTO article_in_group (article_id, group_id, official, `access`, `read`, `edit`, request, flag_request, publisher_id, time) VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE `access`=?, `read`=?, `edit`=?, request=?, flag_request=?, time=NOW()", array($article_id, $group_id, $access, $read, $edit, $r, $fr, $publisher_id, $access, $read, $edit, $r, $fr));
				
				
				
			if($published || ($fr == 0 && $read != "NONE"))
			{
				$users_after = Articles::Singleton()->GetAllReadingUsers($article_id);
		
				if($published)
				{
					foreach($users_before as $ub)
						unset($users_after[$ub]);
				}
												
				if($published)
					$message = "Artikeln [A[$article_id]] har publicerats i [G[$group_id]].";
				else if($read == "SHOULD")
					$message = "Du är nu rekommenderad att läsa [A[$article_id]] i [G[$group_id]].";
				else
					$message = "Du måste nu läsa [A[$article_id]] i [G[$group_id]].";
			
				
				foreach($users_after as $u)
				{
					if($u == Auth::Singleton()->id)
						continue;
					Q("INSERT INTO system_notifications (reciever_id, message, time, larp_id) VALUES (?,?, NOW(), '$larp_id')", array($u, $message));
				}
			}
		}
		
		return $request;
	}
	
	function Delete()
	{
		
		Q("DELETE FROM groups WHERE id=?", array($this->id));
		$res = Q("SELECT article_id FROM article_in_group WHERE group_id=? AND official=1", array($this->id));
		while(list($article_id) = $res->fetch_array())
		{
			Q("DELETE FROM articles WHERE id=?", array($article_id));
			Q("DELETE FROM article_content WHERE article_id=?", array($article_id));
			
			Q("DELETE FROM forum_threads WHERE article_id=?", array($article_id));
			Q("DELETE FROM forum_messages WHERE article_id=?", array($article_id));
			
			Q("DELETE FROM user_read_article_threads WHERE article_id=?", array($article_id));
			
			$res2 = Q("SELECT id FROM forum_threads WHERE article_id=?", array($article_id));
			while(list($thread_id) = $res2->fetch_array())
			{
				Q("DELETE FROM user_follows_thread WHERE thread_id=?", array($thread_id));				
			}
		}
		Q("DELETE FROM article_in_group WHERE group_id=? AND official=1", array($this->id));
		
		Q("DELETE FROM group_know_character WHERE group_id=?", array($this->id));
		Q("DELETE FROM g_character_status_in_group WHERE group_id=?", array($this->id));
		Q("DELETE FROM g_user_status_in_group WHERE group_id=?", array($this->id));
		Q("DELETE FROM g_group_status_in_group WHERE group_id=? OR viewer_id=?", array($this->id, $this->id));
		
	}
}

?>