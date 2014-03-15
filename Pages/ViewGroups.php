<?php

class Page   extends BasicPage
{

	public function RequireLoggedIn ()
	{
		return false;
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
		$html = "";
		
		//$html .= "<span class=section_title>Grupper i ".Auth::Singleton()->LarpName()." </span>";

		$create_form = Groups::Singleton()->RenderCreateForm();
		
		$all_groups = Groups::Singleton()->GetAll();
			
			
		$html .= "<span class=section_title>Grupper du kan se</span><div class=section>";
		foreach ($all_groups[1] as $group_id => $group)
			$html .= RenderGroupLink($group_id, $group['name']);
		$html .= "</div>";	
		
		if (Auth::Singleton()->AttendingLarp())
		{
			$html .= "<span class=section_title>Grupper du är med i</span><div class=section>";
			foreach ($all_groups[2] as $group_id => $group)
				$html .= RenderGroupLink($group_id, $group['name']);
			$html .= "</div>";
			
			$html .= "<span class=section_title>Grupper du är admin för</span><div class=section>";
			foreach ($all_groups[3] as $group_id => $group)
				$html .= RenderGroupLink($group_id, $group['name']);
			$html .= "</div>";
			
			$html .= "<span class=section_title>Skapa Ny Grupp</span><div class=section>";
			$html .= $create_form;
			$html .= "</div>";
		}
		
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = "Grupper i " . Auth::Singleton()->LarpName();
		return $Data;
	}

}
?>