<?php

require_once("Article.php");
require_once("Debug.php");

class Articles
{
	private static $singleton = null;
	
	public static function Singleton ()
	{
		if (! isset(self::$singleton))
		{
			self::$singleton = new Articles();
		}
	
		return self::$singleton;
	}
	
	private function __construct ()
	{
	
	}

	public function GetArticle ($id, $user_id=0)
	{	
		if($user_id != 0)
			return new Article($id, $user_id);
		
		if(!isset($this->ArticleCache[$id]))
			$this->ArticleCache[$id] = new Article($id);
		
		return $this->ArticleCache[$id];
	}

	private $Articles = array();
	private $AllGroups = array();
	private $ArticleCache = array();
		
	private $AllViewGroups = array();
	private $AllMemberGroups = array();
	private $AllAdminGroups = array();
	
	private function InsertArticle($assoc_in_group)
	{		
		
		
		if(!isset($assoc_in_group['own']))
			$assoc_in_group['own'] = 0;
		if(!isset($assoc_in_group['can_edit']))
			$assoc_in_group['can_edit'] = 0;
		
				
		if(isset($this->Articles[$assoc_in_group['article_id']]))
		{
			
			if($assoc_in_group["access"] == "PUBLIC")
				$this->Articles[$assoc_in_group['article_id']]["access"] = "PUBLIC";
			
			if($assoc_in_group["read"] == "MUST")
				$this->Articles[$assoc_in_group['article_id']]["read"] = "MUST";
			
			if($assoc_in_group["read"] == "SHOULD" && $this->Articles[$assoc_in_group['article_id']]["read"] != "MUST")
				$this->Articles[$assoc_in_group['article_id']]["read"] = "SHOULD";
			
		}
		else
		{	
			$this->Articles[$assoc_in_group['article_id']] = $assoc_in_group;			
		}
		
		/*$this->Articles[$assoc_in_group['article_id']]["groups"][$assoc_in_group['group_id']] = "Group Name";
		
		if($this->Articles[$assoc_in_group['article_id']]["can_edit"] == 0)
		{
			if($assoc_in_group['own'] == 1)
				$this->Articles[$assoc_in_group['article_id']]["can_edit"] = 1;
				
			if($assoc_in_group['edit'] == "MEMBERS")
			{
				if(array_key_exists($assoc_in_group['group_id'],$this->AllGroups[2])//member
				|| array_key_exists($assoc_in_group['group_id'],$this->AllGroups[3]))//admin
					$this->Articles[$assoc_in_group['article_id']]["can_edit"] = 1;
			} 
			else // edit == ADMINS
			{
				if(array_key_exists($assoc_in_group['group_id'],$this->AllGroups[3]))
					$this->Articles[$assoc_in_group['article_id']]["can_edit"] = 1;
			} 
			/*
			Sätt om jag får redigera eller inte
			
			$this->AllMemberGroups
			$this->AllAdminGroups
			*//*
		}*/
	}
	
	public function GetAllVisible($user_id = 0)
	{
		
		if($user_id == 0)
			$user_id = Auth::Singleton()->id;
		$larp_id = Auth::Singleton()->LarpId();
		
		if(Auth::Singleton()->OrganizerMode())
		{			
			$res = Q("SELECT id FROM articles WHERE larp_id=? AND id NOT IN (SELECT article_id FROM user_user_relation)", array($larp_id));
			while(list($id) = $res->fetch_array())
				$this->Articles[] = Articles::GetArticle($id);
			return $this->Articles;
		}
		$this->AllGroups = Groups::Singleton()->GetAll();
		
		 
		$this->AllViewGroups = $this->AllGroups[1];
		$this->AllMemberGroups = $this->AllGroups[2];
		$this->AllAdminGroups = $this->AllGroups[3];
		foreach ($this->AllGroups[3] as $i => $d)
			$this->AllMemberGroups[$i] = $d;
		
				
		//All groups I can view
		if(count($this->AllViewGroups) > 0)
		{
			$ids = implode(",", array_keys( $this->AllViewGroups));
			
			$res = Q("SELECT * FROM article_in_group JOIN articles ON articles.larp_id=? AND articles.id = article_in_group.article_id WHERE group_id IN ($ids) AND access = 'PUBLIC' AND article_in_group.request=0", array($larp_id));
			while($assoc = $res->fetch_assoc())
				$this->InsertArticle($assoc);
		}
		
		
		//All groups I am member of
		if(count($this->AllMemberGroups) > 0)
		{		
			$ids = implode(",", array_keys($this->AllMemberGroups));	
			$res = Q("SELECT * FROM article_in_group JOIN articles ON articles.larp_id=? AND articles.id = article_in_group.article_id WHERE group_id IN ($ids) AND article_in_group.request=0", array($larp_id));
			while($assoc = $res->fetch_assoc())
				$this->InsertArticle($assoc);
		}
		
		$res = Q("SELECT * FROM articles WHERE creator_id = ? AND larp_id=?", array($user_id, $larp_id));
		while($assoc = $res->fetch_assoc())
		{
			if(QS("SELECT COUNT(*) FROM user_user_relation WHERE article_id =?", array($assoc['id'])))
				continue;
			
			$assoc['article_id'] = $assoc['id'];
			$assoc['group_id'] = -1;
			$assoc['own'] = 1;
			$assoc['read'] = "NONE";
			$assoc['access'] = "PRIVATE";
			$assoc['edit'] = "NONE";
			$this->InsertArticle($assoc);
		}
		
		if(count($this->Articles) > 0)
		{
			/*$article_ids = implode(",", array_keys($this->Articles));
			$res = SQL::S()->Q("SELECT * FROM articles WHERE id IN ($article_ids)");
			while($assoc = $res->fetch_assoc())
			{
				$this->Articles[$assoc['id']]["creator_id"] = $assoc['creator_id'];
				$this->Articles[$assoc['id']]["created"] = $assoc['created'];
				
				if($assoc['creator_id'] == $user_id)
					$this->Articles[$assoc['id']]["own"] = 1;
			}
					
			$sql = "";
			foreach($this->Articles as $id => $art)
			{
				if($sql != "")
					$sql .= " UNION ";
				$sql .= "(SELECT * FROM article_content WHERE article_id = $id ORDER BY created DESC LIMIT 1)";
			} 
			$res = SQL::S()->Q($sql
			/*$res = SQL::S()->Q("SELECT article_content.* FROM article_content JOIN (SELECT MAX(article_content.created) AS max_date FROM article_content
	                                                       GROUP BY article_id) AS dates ON article_content.created = dates.max_date 
															ORDER BY article_content.created DESC");*//*
			while($assoc = $res->fetch_assoc())
			{
				$this->Articles[$assoc['article_id']]["title"] = $assoc['title'];
				$this->Articles[$assoc['article_id']]["author_id"] = $assoc['author_id'];
				$this->Articles[$assoc['article_id']]["last_change"] = $assoc['created'];
			}*/
			
			//TODO: Opta! Det finns redan en massa info om access, read, edit som inte behöver fixas i Article::__cons
			
			$temp = array();
			foreach($this->Articles as $key => $data)
			{
				//if($data['group_id'] >= 0)
					$temp[] = Articles::Singleton()->GetArticle($key, $user_id);
			}
			
			$this->Articles = $temp;
						
		}
		
		
		return $this->Articles;
		
	}
	
	public function GetAllInGroup($group_id)
	{
		$Group = Groups::Singleton()->GetGroup($group_id);
		$AllInGroup = array();
		$larp_id = Auth::Singleton()->LarpID();
		
		
		$res = Q("SELECT * FROM article_in_group JOIN articles ON articles.larp_id=? AND articles.id = article_in_group.article_id WHERE article_in_group.group_id=? AND article_in_group.official=0  AND article_in_group.request=0", array($larp_id, $group_id));
		while($assoc = $res->fetch_assoc())
		{			
			if($assoc['access'] == 'PUBLIC' && $Group->KnowOf)
				$access = 1;
			else if($assoc['access'] == 'PRIVATE' && ($Group->Member || $Group->Admin))
				$access = 2;
			else
				continue;
									
			$Data = array();
			$Data["article_id"] = $assoc["article_id"];
			
			$Data["Read"] = "NONE";
			$Data["Access"] = $access;
			if($Group->Member)
				$Data["Read"] = $assoc['read'];
			
			$Data["Edit"] = 0;
			if(($Group->KnowOf && $assoc['edit']=="VIEWERS") || ($Group->Member && $assoc['edit']=="MEMBERS") || ($Group->Admin && $assoc['edit']=="ADMINS"))
				$Data["Edit"] = 1;
			
			$AllInGroup[] = $Data;				
		}
		$Res = array();		
		$Res[1] = array();
		$Res[2] = array();
		
		foreach($AllInGroup as $a)
		{
			$Article = Articles::GetArticle($a["article_id"]);
			$Res[$a["Access"]][] = $Article;
		}
		return $Res;		
	}
	
	public function GetAllReadingUsers($article_id)
	{
		$user_ids = array();
		$larp_id = Auth::Singleton()->LarpId();
		
		
		
		//if this is a user_user_relation, just return the two users
		$res = Q("SELECT * FROM user_user_relation WHERE article_id=?", array($article_id));
		if($assoc = $res->fetch_assoc())
		{
			$user_ids[$assoc['user_id_1']] = $assoc['user_id_1'];
			$user_ids[$assoc['user_id_2']] = $assoc['user_id_2'];
			return $user_ids;
		}		
		
		$creator_id = QS("SELECT creator_id FROM articles WHERE id=?", array($article_id));
		$user_ids[$creator_id] = $creator_id;
		
		AddDebug("1 " . print_r($user_ids, true));
		
		//If part of group ALL: return everybody and be done with it
		////////////////////////////////////////////////////////////////////////////////
		if(QS("SELECT COUNT(*) FROM article_in_group WHERE article_id=? AND group_id=0 AND request=0", array($article_id)))
		{
			$res = Q("SELECT user_id FROM user_attending_larp WHERE larp_id=?", array($larp_id));
			while(list($id) = $res->fetch_array())
				$user_ids[$id] = $id;
			
			return $user_ids;
		}
		
		AddDebug("2 " . print_r($user_ids, true));
		
		//Part of Groups
		////////////////////////////////////////////////////////////////////////////////
		$groups = array();
		$res = Q("SELECT group_id, access FROM article_in_group WHERE article_id=? AND request=0", array($article_id));
		while(list($id, $access) = $res->fetch_array())
		{
				$groups[$id]['recursed'] = 0;
				$groups[$id]['access'] = $access;
		}			
		
		//Recurse Groups
		////////////////////////////////////////////////////////////////////////////////
		while(true)
		{
			$all_recursed = true;
			$new_groups = array();
			foreach ($groups as $id => $group)			
			{				
				if($group['recursed'])
					continue;
				
				$groups[$id]['recursed'] = true;
				
				$all_recursed = false;
				
				if($group['access'] == 'PRIVATE')
					$search = "(status='MEMBER' OR status='ADMIN')";
				else
					$search = "(status='MEMBER' OR status='ADMIN' OR status='KNOW')";
				
				
				$res = Q("SELECT viewer_id, status FROM g_group_status_in_group WHERE group_id=? AND request='NONE' AND $search", array($id));
				while(list($viewer_id, $status) = $res->fetch_array())
				{
					//If group All, just return everyone
					if($viewer_id == 0)
					{
						$user_ids = array();
						$res = Q("SELECT user_id FROM user_attending_larp WHERE larp_id=?", array($larp_id));
						while(list($id) = $res->fetch_array())
							$user_ids[$id] = $id;							
						return $user_ids;
					}
					if($status == 'KNOW')
						$new_groups[$viewer_id]['recursed'] = true; //If the viewing group only KNOWs this one, don't recurse any longer. 
					else
						$new_groups[$viewer_id]['recursed'] = false;
					
					$new_groups[$viewer_id]['access'] = "PRIVATE"; //Always make private in watching group
				}			
			}
			
			foreach($new_groups as $nid => $ng)
			{	
				$groups[$nid]['recursed'] = $ng['recursed'];
				$groups[$nid]['access'] = $ng['access'];
			}
			
			if($all_recursed)
				break;
		}
		$private_groups = array();
		$public_groups = array();
		foreach($groups as $id => $group)
		{
			if($group['access'] == "PRIVATE")
				$private_groups[] = $id;
			else
				$public_groups[] = $id;
		}
		
		
		$private_groups = implode(",", $private_groups);
		$public_groups = implode(",", $public_groups);
		
		$all_groups = $private_groups . $public_groups;		
		if($private_groups != "" && $public_groups != "")
			$all_groups = $private_groups . "," . $public_groups;
		
		//Get Users From Groups
		////////////////////////////////////////////////////////////////////////////////
		if($private_groups != "")
		{
			$res = Q("SELECT user_id FROM g_user_status_in_group WHERE request='NONE' AND (status='MEMBER' OR status='ADMIN') AND group_id IN ($private_groups)", array());
			while(list($user_id) = $res->fetch_array())
				$user_ids[$user_id] = $user_id;
		}
		
		AddDebug("3 " . print_r($user_ids, true));
		
		if($public_groups != "")
		{
			$res = Q("SELECT user_id FROM g_user_status_in_group WHERE request='NONE' AND group_id IN ($public_groups)", array());
			while(list($user_id) = $res->fetch_array())
				$user_ids[$user_id] = $user_id;
		}
		AddDebug("3.5 " . print_r($user_ids, true));
		if($all_groups != "")
		{
			$res = Q("SELECT creator_id FROM groups WHERE id IN ($all_groups)", array());
			while(list($user_id) = $res->fetch_array())
				$user_ids[$user_id] = $user_id;
		}
		
		AddDebug("4 " . print_r($user_ids, true));
		
		//Get characters form groups and their players/owners
		////////////////////////////////////////////////////////////////////////////////
		$character_ids = array();
		if($private_groups != "")
		{
			$res = Q("SELECT character_id FROM g_character_status_in_group WHERE request='NONE' AND (status='MEMBER' OR status='ADMIN') AND group_id IN ($private_groups)", array());
			while(list($character_id) = $res->fetch_array())
				$character_ids[$character_id] = $character_id;
		}
		if($public_groups != "")
		{
			$res = Q("SELECT character_id FROM g_character_status_in_group WHERE request='NONE' AND group_id IN ($public_groups)", array());
			while(list($character_id) = $res->fetch_array())
				$character_ids[$character_id] = $character_id;
		}

		$character_ids = implode(",", $character_ids);
		if($character_ids != "")
		{
			//Owners
			$res = Q("SELECT creator_id FROM characters WHERE id IN ($character_ids)", array());
			while(list($user_id) = $res->fetch_array())
				$user_ids[$user_id] = $user_id;
			
			//Players
			$res = Q("SELECT user_id FROM user_playing_character WHERE character_id IN ($character_ids) AND request='NONE'", array());
			while(list($user_id) = $res->fetch_array())
				$user_ids[$user_id] = $user_id;
		}		
		
		AddDebug("5 " . print_r($user_ids, true));
		
		return $user_ids;
	}
}

?>