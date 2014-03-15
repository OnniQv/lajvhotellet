<?php

require_once("Forms.php");

class Character
{
	
	public $id;
	public $name;
	
	public $CanEdit=0;
	public $KnowWell=0;
	public $KnowOf=0;
	
	public $CreatorId=0;
	
	public $State;
	
	function __construct($id)
	{
		$res = Q("SELECT * FROM characters WHERE id=?", array($id));
		$Character = $res->fetch_assoc();
		
		$this->id = $id;
		$this->name = $Character['name'];		
		$this->State = $Character['state'];
		$this->CreatorId = $Character['creator_id'];
		
		$user_id = Auth::Singleton()->id;
		
		//Have I created this charcter
		if($Character['creator_id'] == $user_id || Auth::Singleton()->OrganizerMode())
		{
			$this->CanEdit=1;
			$this->KnowWell=1;
			$this->KnowOf=1;
			return;
		}
		
		//Am I playing this character
		if(QS("SELECT COUNT(*) FROM user_playing_character WHERE character_id=? AND user_id=? AND request='NONE'", array($id, $user_id)))
		{
			$this->CanEdit=1;
			$this->KnowWell=1;
			$this->KnowOf=1;
			return;
		}
		
		//Does any group I am part of know this character
		$Groups = Groups::Singleton()->GetAll();		
		$res = Q("SELECT * FROM group_know_character WHERE character_id=?", array($id));
		while($assoc = $res->fetch_assoc())
		{
			$group_id = $assoc['group_id'];
								
			if(array_key_exists($group_id, $Groups[2]) || array_key_exists($group_id, $Groups[3]))			
			{
				if($assoc['know'] == "OF")
				{
					$this->KnowOf=1;
				}	
				else
				{
					$this->KnowWell=1;
					$this->KnowOf=1;
					return;
				}					
			}			
		}
				
		//Do my characters know this one
		$res = Q("SELECT k.know FROM user_playing_character AS p JOIN character_know_character AS k ON p.character_id=k.viewer_id WHERE p.user_id=? AND p.request='NONE'", array($user_id));
		list($know) = $res->fetch_array();
		if($assoc['know'] == "OF")
		{
			$this->KnowOf=1;
		}
		else
		{
			$this->KnowWell=1;
			$this->KnowOf=1;		
			return;	
		}
		
	}
	
	function RenderView()
	{
		$html = "";
		
		
		if(!$this->KnowOf)
		{
			return "<div class=section>Du känner inte denna Roll</div>";
		}
		
		$html .= "<h2>$this->name</h2>";
		
		$larp_shortname = Auth::Singleton()->LarpShortName();
		$larp_id = Auth::Singleton()->LarpId();
		
		if(Auth::Singleton()->LarpValue("approve_characters"))
		{
			$html .= "<span class=section_title>Godkännande</span><div class=section>";
			switch($this->State)
			{
				case "EDIT": $html .= "Denna roll är redigeras fortfarande."; 
						if($this->CanEdit)
						{
							$html .= "\r\n<br><div id=edit_state>";
							$html .= "<input type=button value='Skicka rollen till godkännande' 
							onclick=\"document.getElementById('edit_state').innerHTML = '<img src=/img/wait.gif>'; 
							$.get('/AjaxApi.php?command=UpdateCharacterState&character_id={$this->id}&state=APPROVE&update_time=1&larp_shortname=$larp_shortname', 
							function(respons){document.getElementById('edit_state').innerHTML = respons;});\">";
							$html .= "</div>\r\n";
						}
						break;
						
				case "APPROVE": $html .= "Denna roll är inte godkänd än."; 
						if($this->CanEdit)
						{
							$html .= "<br><span id=edit_state>";
							$html .= "<input type=button value='Godkänn' onclick=\"document.getElementById('edit_state').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateCharacterState&character_id={$this->id}&state=OK&update_time=0&larp_shortname=$larp_shortname', function(respons){document.getElementById('edit_state').innerHTML = 'Godkänd';})\">";
							$html .= "<input type=button value='Neka'    onclick=\"document.getElementById('edit_state').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateCharacterState&character_id={$this->id}&state=FAIL&update_time=0&larp_shortname=$larp_shortname', function(respons){document.getElementById('edit_state').innerHTML = 'Nekad';})\">";
							$html .= "</span>";
						}
						break;
						
				case "FAIL": $html .= "Denna roll blev inte godkänd."; 
						break;
						
				case "OK": $html .= "Denna roll är godkänd."; 
						break;
			
				
			}
			$html .= "</div>";
		}
		
		
		$html .= "<span class=section_title>Spelas Av</span><div class=section>";
		$res = Q("SELECT user_id FROM user_playing_character WHERE character_id=? AND request='NONE'", array($this->id));
		$i_am_playing = false;
		while(list($user_id) = $res->fetch_array())
		{
			if($user_id == Auth::Singleton()->id)
				$i_am_playing = true;
			$html .= RenderUserLink($user_id) . "<br>";
		}		
		$html .= "<br>";
		
		if(Auth::Singleton()->LoggedIn())
		{
			$user_id = Auth::Singleton()->id;
			$request = QS("SELECT request FROM user_playing_character WHERE character_id=? AND user_id=?", array($this->id, $user_id));
			switch($request)
			{
				case "JOIN": $html .= "Du har ansökt om att spela denna roll."; break;
				case "INVITE": $html .= "Du har blivit tillfrågad att spela denna roll."; break;
				case "NONE":  
							$html .= "<br><span id=play_stop>";
							$html .= "<input type=button value='Sluta spela denna roll' onclick=\"document.getElementById('play_stop').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateUserPlayingCharacter&character_id={$this->id}&user_id=$user_id&request=&larp_shortname=$larp_shortname', function(respons){ if(respons!=''){ alert(respons); } document.getElementById('play_stop').innerHTML = 'Du spelar inte längre denna roll';})\">";
							$html .= "</span>";
							break;
				case null:
							$html .= "<br><span id=play_join>";
							$html .= "<input type=button value='Ansök om att spela denna roll' onclick=\"document.getElementById('play_join').innerHTML = '<img src=/img/wait.gif>'; $.get('/AjaxApi.php?command=UpdateUserPlayingCharacter&character_id={$this->id}&user_id=$user_id&request=JOIN&larp_shortname=$larp_shortname', function(respons){ if(respons!=''){ alert(respons); } document.getElementById('play_join').innerHTML = 'Ansökt';})\">";
							$html .= "</span>";
							break;
			}
		}
		$html .= "</div>";
		
		$form_id = Auth::Singleton()->CharacterForm();	
		$html .= Forms::Singleton()->RenderFillData($form_id, $this->id);
		
		
		if($this->CanEdit)
			$html .= "<div class=section>" . RenderPageLink("Redigera Roll", "EditCharacter", $this->id) . "</div>";
		
		
		return $html;
	}
	
	function RenderEdit()
	{
		$html = "";
		
		if(!$this->CanEdit)
		{
			return "<div class=section>Du får inte redigera denna Roll</div>";
		}
			
		
		$html .= PermissionsForm::RenderCharacterForm($this->id);
				
		$form_id = Auth::Singleton()->CharacterForm();		
		$html .= Forms::Singleton()->RenderFillForm($form_id, $this->id);
	
		$this->name = QS("SELECT name FROM characters WHERE id=?", array($this->id));
		
		$delete_url = GetPageUrl("EditCharacter", $this->id, "DELETE");
		$html .= "<div class=section><input type=submit value='Ta bort Rollen' onclick=\"if(confirm('Är du säker på att du vill ta bort rollen {$this->name}?')) {window.location.href='$delete_url'}\"></div>";
		
		return $html;
	}
	
	public function UpdateKnower($knower_type, $knower_id, $know)
	{
		
		
		if($knower_type == "C")
			$knower_type = "R";
		
		$larp_id = Auth::Singleton()->LarpId();
		$character_id = $this->id;
		
		$listeners = array();
		
		switch($knower_type)
		{
			case "R": $table = "character_know_character"; $viewer = "viewer_id";
				$res = Q("SELECT user_id FROM user_playing_character WHERE character_id=? AND larp_id=? AND request='NONE'", array($knower_id, $larp_id));
				while(list($uid) = $res->fetch_array())
					$listeners[$uid] = $uid;
			break;
			case "G": $table = "group_know_character"; $viewer = "group_id";
					$Group = Groups::Singleton()->GetGroup($knower_id);
					$listeners = $Group->GetAllReadingUsers();		
			break;
		}
		
		$message = "";
		if($know == "")
		{
			Q("DELETE FROM $table WHERE $viewer=? AND character_id=?", array($knower_id, $character_id));
			$message = "[{$knower_type}[$knower_id]] känner inte längre [R[$character_id]]";
		}
		else
		{
			if($know == "OF")
				$message = "[{$knower_type}[$knower_id]] känner nu till [R[$character_id]]";
			else
				$message = "[{$knower_type}[$knower_id]] känner nu [R[$character_id]] väl.";
			
			Q("INSERT INTO $table ($viewer, know, character_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE know=?", array($knower_id, $know, $character_id, $know));
		}
		
		foreach($listeners as $uid)
		{
			if($uid == Auth::Singleton()->id)
				continue;
			
			Q("INSERT INTO system_notifications (reciever_id, message, time, larp_id) VALUES (?,?, NOW(), ?)", array($uid, $message, $larp_id));
		}
	}
	
	
	public function UpdateState($new_state, $update_time)
	{
		switch ($new_state)
		{
			case "FAIL":
			case "OK": 
				if(!Auth::Singleton()->OrganizingLarp())
					return;
		}
		
		if($update_time)
			$update_time = ", update_time=NOW() ";
		else 
			$update_time = "";
		
		Q("UPDATE characters SET state=? $update_time WHERE id=?", array($new_state, $this->id));
	}
	
	function UpdatePlaying($user_id, $request)
	{
		$larp_id = Auth::Singleton()->LarpId();
		
		$old_request = QS("SELECT request FROM user_playing_character WHERE character_id=? AND user_id=?", array($this->id, $user_id));
		
		if($request == "")
			Q("DELETE FROM user_playing_character WHERE character_id=? AND user_id=?", array($this->id, $user_id));
		else
			Q("INSERT INTO user_playing_character (user_id, character_id, larp_id, request, time) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE request=?, time=NOW()", array($user_id, $this->id, $larp_id, $request, $request));
		
		$mess = "";
		switch($old_request)
		{
			case "": 
				switch($request)
				{
					case "JOIN": break; //A notification will be generated automatically
					case "INVITE": break; //TODO: generate INVITE notification and make GUI for this
					case "NONE": $mess = "[U[$user_id]] spelar nu [R[{$this->id}]]"; break;
				}
				break;
		
			case "JOIN":
				switch($request)
				{
					case "": $mess = "[U[$user_id]] blev nekad att spela [R[{$this->id}]]"; break;					
					case "NONE": $mess = "[U[$user_id]] spelar nu [R[{$this->id}]]"; break;
				}
				break;
				
			case "INVITE":
				switch($request)
				{
					case "": $mess = "[U[$user_id]] tackade nej till att spela [R[{$this->id}]]"; break;
					case "NONE": $mess = "[U[$user_id]] spelar nu [R[{$this->id}]]"; break;
				}
				break;
				
			case "NONE":
				switch($request)
				{
					case "": $mess = "[U[$user_id]] spelar inte längre [R[{$this->id}]]"; break;					
				}
				break;
			
		}
		
		if($mess != "")
		{
			$listeners = array();
			$listeners[$this->CreatorId] = $this->CreatorId;
			$res = Q("SELECT user_id FROM user_playing_character WHERE character_id=? AND larp_id=?", array($this->id, $larp_id));
			while(list($uid) = $res->fetch_array())
				$listeners[$uid] = $uid;
			
			foreach($listeners as $uid)
			{
				if($uid == Auth::Singleton()->id)
					continue;
					
				Q("INSERT INTO system_notifications (reciever_id, message, time, larp_id) VALUES (?,?, NOW(), ?)", array($uid, $mess, $larp_id));
			}
			
		}
		
	}
	
	function Delete()
	{
		Q("DELETE FROM characters WHERE id=?", array($this->id));
		Q("DELETE FROM character_know_character WHERE character_id=? OR viewer_id=?", array($this->id, $this->id));
		Q("DELETE FROM character_partof_larp WHERE character_id=?", array($this->id));
		Q("DELETE FROM group_know_character WHERE character_id=?", array($this->id));
		Q("DELETE FROM g_character_status_in_group WHERE character_id=?", array($this->id));		
		Q("DELETE FROM user_playing_character WHERE character_id=?", array($this->id));
		
		$form_id = Auth::Singleton()->LarpValue("character_form_id");
		$res = Q("SELECT id FROM form_fields WHERE form_id=?", array($form_id));
		while(list($field_id) = $res->fetch_array())
			Q("DELETE FROM form_filling WHERE field_id=? AND filler_id=?", array($field_id, $this->id));
		
	}
}


?>