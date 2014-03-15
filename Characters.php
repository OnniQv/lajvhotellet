<?php

require_once ("Character.php");

class Characters
{
	private static $singleton = null;

	public static function Singleton ()
	{
		if (! isset(self::$singleton))
		{
			self::$singleton = new Characters();
		}

		return self::$singleton;
	}

	private function __construct ()
	{

	}
	
	function RenderCreateForm()
	{
		$html = "";
		
		$create_character_form = new Zebra_Form('create_article_form');
		$create_character_form->client_side_validation(true);
		
		$create_character_form->add('label', 'label_name', 'name', 'Rollens namn');
		$obj = $create_character_form->add('text', 'name', '', 
				array('autocomplete' => 'off'));
		$obj->set_rule(array('required' => array('error', 'Du måste skriva in ett namn.')));

 
		$create_character_form->add('label', 'label_playing', 'playing', 'Spela denna roll');
		$obj = $create_character_form->add('checkbox', 'playing', 'yes',
				array('autocomplete' => 'off'));
		
		$create_character_form->add('label', 'label_secret', 'secret', 'Hemlig roll (du måste själv tala om vilka som känner rollen)');
		$obj = $create_character_form->add('checkbox', 'secret', 'yes',
				array('autocomplete' => 'off'));
		
				
		// "submit"
		$create_character_form->add('submit', 'btnsubmit_create', 'Skapa roll');
		
		
		
		if ($create_character_form->validate())
		{
			
			$name = ($_POST['name']);					
			$user_id = Auth::Singleton()->id;
			$larp_id = Auth::Singleton()->LarpID();
						
			Q("INSERT INTO characters (name, creator_id, state, update_time) VALUES(?, ?, 'EDIT', NOW())", array($name, $user_id));			
			$character_id = SQLInsertId();
			echo ("char_id insertID:$character_id");
			
			Q("INSERT INTO character_partof_larp (character_id, larp_id) VALUES(?, ?)", array($character_id, $larp_id));
			
			if(isset($_POST['playing']))
				Q("INSERT INTO user_playing_character (user_id, character_id, larp_id, request) VALUES (?, ?, ?, 'NONE')", array($user_id, $character_id, $larp_id));

			if(!isset($_POST['secret']))
			{
				$all_group_id = Auth::Singleton()->LarpValue("all_group_id");							
				Q("INSERT INTO group_know_character (group_id, character_id, know) VALUES (?, ?, 'OF')", array($all_group_id, $character_id));
			}
						
			$larp_short_name = Auth::Singleton()->LarpShortName();
			SetRedirect("/$larp_short_name/ViewCharacter/$character_id");
			
		}
		else
		{	
			$html .= $create_character_form->render('', true);
		}
		
		
		return $html;
	}
	
	function GetAll($user_id = null, $larp_id = null)
	{
		
		$All = array();
		$All['playing'] = array();
		$All['created'] = array();
		$All['mine'] = array();
		$All['know_well'] = array();
		$All['know_of'] = array();
		$All['all_visible'] = array();
		$All['all'] = array();
		
		
		
		if($larp_id == null)
			$larp_id = Auth::Singleton()->LarpID();
		
		if($user_id == null)
			$user_id = Auth::Singleton()->id;
		
		$CharacterIdsIOwn = array();
		
		//All
		if(class_exists("Auth") && Auth::Singleton()->OrganizerMode())
		{
			$res = Q("SELECT characters.* FROM characters JOIN character_partof_larp ON characters.id=character_partof_larp.character_id AND character_partof_larp.larp_id=?", array($larp_id));
			while($assoc = $res->fetch_assoc())
			{				
				$All['all'][$assoc['id']] = $assoc;
			}
		}
		
		//Characters I have created
		$res = Q("SELECT characters.* FROM characters JOIN character_partof_larp ON characters.id=character_partof_larp.character_id AND character_partof_larp.larp_id=? WHERE characters.creator_id=?", array($larp_id, $user_id));
		while($assoc = $res->fetch_assoc())
		{
			$CharacterIdsIOwn[] = $assoc['id'];			
			$All['created'][$assoc['id']] = $assoc;
		}
		
		//Characters I am playing
		$res = Q("SELECT characters.* FROM characters JOIN character_partof_larp ON characters.id=character_partof_larp.character_id AND character_partof_larp.larp_id=? JOIN user_playing_character ON user_playing_character.character_id = characters.id AND user_playing_character.user_id=? WHERE user_playing_character.request='NONE'", array($larp_id, $user_id));
		while($assoc = $res->fetch_assoc())
		{
			$CharacterIdsIOwn[] = $assoc['id'];			
			$All['playing'][$assoc['id']] = $assoc;
		}
		
		//Groups I am member of that knows characters
		$Groups = Groups::Singleton()->GetAll();
		$MemberGroupsIds = array();
		foreach($Groups[2] as $gid => $Group)
			$MemberGroupsIds[] = $gid;
		foreach($Groups[3] as $gid => $Group)
			$MemberGroupsIds[] = $gid;
		$MemberGroupsIds = implode(",", $MemberGroupsIds);				
		$res = Q("SELECT c.*, gkc.know FROM group_know_character AS gkc JOIN characters AS c ON gkc.character_id=c.id JOIN character_partof_larp ON c.id=character_partof_larp.character_id AND character_partof_larp.larp_id=? WHERE gkc.group_id IN ($MemberGroupsIds)", array($larp_id));
		while($assoc = $res->fetch_assoc())
		{
			if($assoc['know'] == "OF")
				$pos = "know_of";
			else
				$pos = "know_well";
			
			$All[$pos][$assoc['id']] = array("name"=>$assoc["name"],  "id" => $assoc['id']);
		}
		
		//Characters i own know others
		$CharacterIdsIOwn = implode(",", $CharacterIdsIOwn);
		if($CharacterIdsIOwn != "")
		{
			$res = Q("SELECT c.*, ckc.know FROM character_know_character AS ckc JOIN characters AS c ON ckc.character_id=c.id WHERE viewer_id IN ($CharacterIdsIOwn)", array());
			while($assoc = $res->fetch_assoc())
			{
				if($assoc['know'] == "OF")
					$pos = "know_of";
				else
					$pos = "know_well";
					
				$All[$pos][$assoc['id']] = array("name"=>$assoc["name"],  "id" => $assoc['id']);
			}
		}
		
		foreach($All['playing'] as $id => $ch)
			$All['all_visible'][$id] = $ch;
		foreach($All['created'] as $id => $ch)
			$All['all_visible'][$id] = $ch;
		foreach($All['know_of'] as $id => $ch)
			$All['all_visible'][$id] = $ch;
		foreach($All['know_well'] as $id => $ch)
			$All['all_visible'][$id] = $ch;
		
		foreach($All['playing'] as $id => $ch)
			$All['mine'][$id] = $ch;
		foreach($All['created'] as $id => $ch)
			$All['mine'][$id] = $ch;
		
		
		return $All;
	}
	
	function GetCharacter($id)
	{
		return new Character($id);
	}
}
?>