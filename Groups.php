<?php

require_once ("Group.php");

class Groups
{

	private static $singleton = null;

	public static function Singleton ()
	{
		if (! isset(self::$singleton))
		{
			self::$singleton = new Groups();
		}
		
		return self::$singleton;
	}

	private function __construct ()
	{
	
	}

	
	private $all = null;
	private $group_status = array();

	private $GroupCache = array();
		
	private function InsertGroup($group_id, $status) //status= 0->4 None, Viewer, Member, Admin
	{
		if($status == "ADMIN")
			$status = 3;
		if($status == "MEMBER") 
			$status = 2;
		if($status == "KNOW")
			$status = 1;
		
		if(isset($this->group_status[$group_id]))
			$this->group_status[$group_id] = max($status, $this->group_status[$group_id]);
		else
			$this->group_status[$group_id] = $status;
	}
	
	public function GetAll ($user_id=null, $larp_id=null)
	{		
		
		if($this->all != null)
			return $this->all;
		
		if($user_id == 0)
		{
			if(!class_exists("Auth"))
				die("badness u: $user_id, l:$larp_id");
			$user_id = Auth::Singleton()->id;
		}
		if($larp_id == 0)
			$larp_id = Auth::Singleton()->LarpId();
		if(class_exists("Auth"))
			$all_id = Auth::Singleton()->LarpValue('all_group_id');
		else
			$all_id = QS("SELECT all_group_id FROM larps WHERE id=?", array($larp_id));
				
		
		
		
		if(class_exists("Auth") && Auth::Singleton()->OrganizerMode())
		{
			$res = Q("SELECT id FROM groups where larp_id=?", array($larp_id));
			while (list ($group_id) = $res->fetch_row())
			{			
				$this->InsertGroup($group_id, "ADMIN");
			}
		}
		else
			{
			//Created by me		
			$res = Q("SELECT id FROM groups WHERE creator_id=? AND larp_id=?", array($user_id, $larp_id));
			while (list ($group_id) = $res->fetch_row())
			{
				
				$this->InsertGroup($group_id, "ADMIN");
			}
			
			//Group All
			$this->InsertGroup($all_id, "MEMBER");
			
			// This User		
			$res = Q("SELECT group_id, status FROM g_user_status_in_group INNER JOIN groups ON g_user_status_in_group.group_id = groups.id WHERE g_user_status_in_group.user_id=? AND groups.larp_id=? AND g_user_status_in_group.request='NONE'", array($user_id, $larp_id));
			while (list ($group_id, $status) = $res->fetch_row())
			{			
				
				$this->InsertGroup($group_id, $status);
			}
				
			// This users characters		
			$res = Q("SELECT character_id FROM user_playing_character WHERE user_id=? AND larp_id=? AND request='NONE'", array($user_id, $larp_id));
			while (list ($character_id) = $res->fetch_row())
			{
				
				$res2 = Q("SELECT group_id, status FROM g_character_status_in_group WHERE character_id=? AND request='NONE'", array($character_id));
				while (list ($group_id, $status) = $res2->fetch_row())
				{
					
					$this->InsertGroup($group_id, $status);
				}
			
			}
			
			
			// All those groups, recurse!
			$already_recursed = array();		
			while (true)
			{
				// Find new recursive groups
				$add = 0;
				$temp_loop = $this->group_status;
				foreach ($temp_loop as $group_id => $status)
				{
					//Dont recurse if only viewer
					if($status <= 1)
						continue;
					
					//Don't do groups several time
					if(isset($already_recursed[$group_id]))
						continue;
					$already_recursed[$group_id] = true;
					
					
					$res = Q("SELECT group_id, status FROM g_group_status_in_group WHERE viewer_id=?", array($group_id));
				
					
					while (list ($gid, $gst) = $res->fetch_row())
					{
						
						$this->InsertGroup($gid, $gst);
						$add++;
					}
				
				}				
				
				if ($add == 0)
					break;
			}
		}
		
		$this->all[1] = array();
		$this->all[2] = array();
		$this->all[3] = array();
		if(count($this->group_status) > 0)
		{
			$in = implode(",", array_keys($this->group_status));
			$res = Q("SELECT * FROM groups WHERE id IN ($in)", array());
			
			while($assoc = $res->fetch_assoc())
				$this->all[$this->group_status[$assoc['id']]][$assoc['id']] = $assoc; 
		}
				
		return $this->all;
	}

	public function GetGroup ($group_id)
	{
		if(!isset($this->GroupCache[$group_id]))
			$this->GroupCache[$group_id] = new Group($group_id);
		
		return $this->GroupCache[$group_id];
	}

	public function RenderCreateForm ()
	{
		return $this->RenderForm(0);		
	}

	public function RenderEditForm ($group_id)
	{		
		return $this->RenderForm($group_id);
	}
	
	private function RenderForm ($group_id)
	{
		$html = "";
		$create_form = new Zebra_Form('new_group_form');
		$create_form->client_side_validation(true);
	
		$larp_id = Auth::Singleton()->LarpId();
		$user_id = Auth::Singleton()->id;
		$all_id = Auth::Singleton()->LarpValue("all_group_id");
	
		$group = null;
		if($group_id != 0)
			$group = Q("SELECT * FROM groups WHERE id=?", array($group_id))->fetch_assoc();
		
		// title
		if($group_id != $all_id)
		{
			$title = "";
			if($group != null)
				$title = $group['name'];
			$create_form->add('label', 'label_title', 'title', 'Titel');
			$obj = $create_form->add('text', 'title', $title,
					array('autocomplete' => 'off'));
			$obj->set_rule(
					array(
							// error messages will be sent to a variable called
							// "error", usable in custom templates
							'required' => array('error',
									'Du måste skriva in en titel.')));
		}
		
		if($group_id == 0 && $group_id!=$all_id)
		{
			$create_form->add('label', 'label_type', 'type', 'In eller Off');
			$obj = $create_form->add('radios', 'type', array("IN" => "In (Endast Roller kan bli medlemmar)", "OFF" => "Off (Endast Användare kan bli medlemmar)"),
					array('autocomplete' => 'off', 0 => "IN"));
		}
		
		if($group_id == 0 && $group_id!=$all_id)
		{			
			$create_form->add('label', 'label_secret', 'secret', 'Hemlig grupp (Denna grupp kommer inte synas för icke medlemmar)');
			$obj = $create_form->add('checkbox', 'secret', 'yes',
					array('autocomplete' => 'off'));
		}
		
		if($group_id != $all_id)
		{
			$guarded = "";
			if(isset($group))
				$guarded = $group['guarded']?"checked":"";
			$create_form->add('label', 'label_guarded', 'guarded', 'Kräver godkännande av gruppadmin för att gå med i');
			$obj = $create_form->add('checkbox', 'guarded', 'yes',
					array($guarded => $guarded, 'autocomplete' => 'off'));
		}
	
		$addarticles = "";
		if(isset($group))
			$addarticles = $group['members_add_article']?"checked":"";
		$create_form->add('label', 'label_addarticles', 'addarticles', 'Medlemmar får lägga till artiklar');
		$obj = $create_form->add('checkbox', 'addarticles', 'yes',
				array($addarticles => $addarticles, 'autocomplete' => 'off'));
	
		$editflags = "";
		if(isset($group))
			$editflags = $group['members_edit_readflags']?"checked":"";
		$create_form->add('label', 'label_read', 'readflags', 'Medlemmar får ändra läsflaggorna');
		$obj = $create_form->add('checkbox', 'readflags', 'yes',
				array($editflags => $editflags, 'autocomplete' => 'off'));
	
		if($group_id == 0)
		{
			$create_form->add('submit', 'btnsubmit_create_group', 'Skapa Grupp');
		}
		else
		{
			$create_form->add('submit', 'btnsubmit_create_group', 'Uppdatera Grupp');
		}
		if ($create_form->validate())
		{
			$title = isset($_POST['title'])?$_POST['title']:"Alla";			
			$secret = isset($_POST['secret']);
			$guarded = (isset($_POST['guarded'])?"1":"0");
			$addarticles = (isset($_POST['addarticles'])?"1":"0");
			$readflags = (isset($_POST['readflags'])?"1":"0");
				
			$id = Auth::Singleton()->id;
				
			if($group_id != 0)
			{
				Q("UPDATE groups SET name=?, guarded=?, members_add_article=?, members_edit_readflags=? WHERE id=?", array($title, $guarded, $addarticles, $readflags, $group_id));
				SetSuccessMessage("Gruppen är uppdaterad");
			}
			else
			{	
				$type = $_POST['type'];
				Q("INSERT INTO groups (name, created, creator_id, larp_id, type, guarded, members_add_article, members_edit_readflags)
				VALUES(?, NOW(), ?, ?, ?, ?, ?, ?)", array($title, $id, $larp_id, $type, $guarded, $addarticles, $readflags));
					
				$group_id = SQL::S()->InsertId();
				if(!$secret)
					Q("INSERT INTO g_group_status_in_group (viewer_id, status, group_id)	VALUES('0', 'KNOW', ?)", array($group_id));
										
						
				Q("INSERT INTO articles (creator_id, larp_id)	VALUES(?, ?)", array($user_id, $larp_id));
				$private_article_id = SQL::S()->InsertId();
				Q("INSERT INTO articles (creator_id, larp_id)	VALUES(?, ?)", array($user_id, $larp_id));
				$public_article_id = SQL::S()->InsertId();
	
				Q("INSERT INTO article_content (article_id, author_id, created, content, title, significant_change, version)
				VALUES(?, ?, NOW(), 'Att göra: Beskriv gruppen för gruppens medlemmar här.', ?, 1, '0.0')", array($private_article_id, $user_id, $title." (Privat)"));
				Q("INSERT INTO article_content (article_id, author_id, created, content, title, significant_change, version)
				VALUES(?, ?, NOW(), 'Att göra: Beskriv gruppen för gruppens besökare här.', ?, 1, '0.0')", array($public_article_id, $user_id, $title." (Publik)"));
				Q("INSERT INTO article_in_group (article_id, group_id, official, `access`, `read`, `edit`)
				VALUES(?, ?, 1, 'PRIVATE', 'NONE', 'NONE')", array($private_article_id, $group_id));
				Q("INSERT INTO article_in_group (article_id, group_id, official, `access`, `read`, `edit`)
				VALUES(?, ?, 1, 'PUBLIC', 'NONE', 'NONE')", array($public_article_id, $group_id));
	
				SetSuccessMessage("Din grupp är nu skapad!");
			}			
			
		}
	
		$html .= $create_form->render('', true);
				
		return $html;
	
	}
}
		
?>