<?php


class Page   extends BasicPage
{

	public function RequireLoggedIn ()
	{
		return true;
	}

	public function RequireLarp ()
	{
		return true;
	}

	function __construct ()
	{
		parent::__contruct();
	}

	public function Render ($Args)
	{
		
		if(!Auth::Singleton()->OrganizingLarp())
			die("403: du är inte arrangör");

		$larp_id = Auth::Singleton()->LarpId();
		
		$html = "<span class=section_title>Alla deltagare</span>";

		$html .= "<div class=section>";
		
		$html .= "<table><tr><td>Deltagare</td><td width=55> Off-formulär </td><td width=55> Roll </td><td width=75> Artiklar </td><td width=75> Grupper </td></tr>";
		
		$form_id = Auth::Singleton()->UserForm();
		$fields = array();
		$res = Q("SELECT id FROM form_fields WHERE form_id = ?", array($form_id));
		while(list($id) = $res->fetch_array())
			$fields[$id] = $id;
		$fields = implode(",", $fields);
		
		
		$res2 = Q("SELECT users.*, user_attending_larp.organizer FROM users JOIN user_attending_larp ON user_attending_larp.user_id=users.id WHERE user_attending_larp.larp_id=?", array($larp_id));		
		while($u = $res2->fetch_assoc())	
			$Users[$u['id']] = $u;		

		
			
		foreach($Users as $user_id => $user)
		{	
			$html .= "<tr><td>";
			$html .= RenderUserLink($user_id);
			

			//Off-form
			$html .= "</td><td>";
			if(QS("SELECT COUNT(*) FROM form_filling WHERE field_id IN ($fields) AND filler_id=?", array($user_id)) > 0)
				$html .= "<img src=/img/check_green.png>";
			else
				$html .= "<img src=/img/exclamation_red.png>";
			
			//Characters
			$html .= "</td><td>";
			$CharOk = false;
			$res = Q("SELECT characters.* FROM characters JOIN character_partof_larp ON characters.id=character_partof_larp.character_id AND character_partof_larp.larp_id=? WHERE characters.creator_id=?", array($larp_id, $user_id));
			while($assoc = $res->fetch_assoc())
			{
				if($assoc['state'] == "OK")
				{
					$CharOk = true;
					break;
				}
			}
			if(!$CharOk)
			{
				$res = Q("SELECT characters.* FROM characters JOIN character_partof_larp ON characters.id=character_partof_larp.character_id AND character_partof_larp.larp_id=? JOIN user_playing_character ON user_playing_character.character_id = characters.id AND user_playing_character.user_id=? WHERE user_playing_character.request='NONE'", array($larp_id, $user_id));
				while($assoc = $res->fetch_assoc())
				{
					if($assoc['state'] == "OK")
					{
						$CharOk = true;
						break;
					}
				}
			}
			if($CharOk)
				$html .= "<img src=/img/check_green.png>";
			else
				$html .= "<img src=/img/exclamation_red.png>";
			
			
			//Articles
			$html .= "</td><td>";
			$Arts = Articles::Singleton()->GetAllVisible($user_id);
			$read = 0;
			$total = 0;
			foreach($Arts as $A)
			{
				if($A->ShouldRead == 2)
				{
					$total++;
					if($A->HaveRead == 1 || $A->HaveRead == 2)
						$read++;
				}
			}
			
			if($read == $total)
				$html .= "<img src=/img/check_green.png>";
			else
				$html .= "<img src=/img/exclamation_red.png>";
			$html .= "($read/$total)";
			
			//Create groups
			$html .= "</td><td>";
			$larp_id = Auth::Singleton()->LarpId();
			$groups_with_members = 0;
			$res = Q("SELECT * FROM groups WHERE creator_id=? AND larp_id=?", array($user_id, $larp_id));
			while($assoc = $res->fetch_assoc())
			{
				if($assoc['id'] == Auth::Singleton()->LarpValue('all_group_id'))
					continue;
					
				$G = Groups::Singleton()->GetGroup($assoc['id']);
				if(count($G->GetMembers()) > 1)
					$groups_with_members++;
			}
			if($groups_with_members >= 3)
				$html .= "<img src=/img/check_green.png>";
			else
				$html .= "<img src=/img/exclamation_red.png>";
			
			
			
			$html .= "</td></tr>";
			
		}
		$html .= "</table>";
		
			
		
		
		
		
		$html .= "</div>";

		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = Auth::Singleton()->LarpName();
		return $Data;
	}

}


?>