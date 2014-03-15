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
		$user_id = Auth::Singleton()->id;
		
		$html = "<span class=section_title>Att göra</span>";

		$html .= "<div class=section>";
		
		
		$html .= "<img src=/img/check_green.png> Anmäl dig till lajvet.<br><br>";


		$form_id = Auth::Singleton()->UserForm();
		$fields = array();
		$res = Q("SELECT id FROM form_fields WHERE form_id = ?", array($form_id));
		while(list($id) = $res->fetch_array())
			$fields[$id] = $id;
		$fields = implode(",", $fields);
		if($fields != "" && QS("SELECT COUNT(*) FROM form_filling WHERE field_id IN ($fields) AND filler_id=?", array($user_id)) > 0)
			$html .= "<img src=/img/check_green.png>";
		else
			$html .= "<img src=/img/exclamation_red.png>";		
		$html .= "Fyll i Off-formuläret<br><br>";
		
		//Approved character
		$Chars = Characters::Singleton()->GetAll();
		$one_approved = false;
		foreach($Chars['mine'] as $C)
			if($C['state'] == "OK" || !Auth::Singleton()->LarpValue('approve_characters'))
				$one_approved = true;
		if($one_approved)
			$html .= "<img src=/img/check_green.png>";
		else
			$html .= "<img src=/img/exclamation_red.png>";		
		$html .= "Ha minst en (godkänd) roll<br><br>";
		

		
		//Articles
		$Arts = Articles::Singleton()->GetAllVisible();
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
		$html .= "Läs alla nödvändiga artiklar ($read/$total)<br><br>";
		
		
		$user_id = Auth::Singleton()->id;
		$larp_id = Auth::Singleton()->LarpId();
		$groups_with_members = 0;
		$res = Q("SELECT * FROM groups WHERE creator_id=? AND larp_id=?", array($user_id, $larp_id));
		while($assoc = $res->fetch_assoc())
		{
			if($assoc['id'] == Auth::Singleton()->LarpValue('all_group_id'))
				continue;
			
			$G = Groups::Singleton()->GetGroup($assoc['id']);
			if($G->GetAllReadingUsers() > 1)
				$groups_with_members++;
		}
		if($groups_with_members >= 3)
			$html .= "<img src=/img/check_green.png>";
		else
			$html .= "<img src=/img/exclamation_red.png>";
		$html .= "Skapa minst 3 grupper (som har fler medlemmar än bara dig) ($groups_with_members/3)<br><br>";
		
		
		
		$html .= "</ul>";
		$html .= "</div>";

		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = Auth::Singleton()->LarpName();
		return $Data;
	}

}


?>