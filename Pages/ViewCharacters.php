<?php

require_once ("Characters.php");


class Page   extends BasicPage
{

	public function RequireLoggedIn ()
	{
		return false;
	}

	public function RequireLarp ()
	{
		return false;
	}

	function __construct ()
	{
		parent::__contruct();
	}

	public function Render ($Args)
	{
		
		$html = "";
		
		if(Auth::Singleton()->LoggedIn())
			$create_form_html = Characters::Singleton()->RenderCreateForm();
		
		$Characters = Characters::Singleton()->GetAll();
		
		if(Auth::Singleton()->LoggedIn())
		{
			$html .= "<span class=section_title>Roller du har skapat</span><div class=section>";
			foreach($Characters['created'] as $Char)
				$html .= RenderCharacterLink($Char['id'], $Char['name'])."<br>";
			$html .= "</div>";
			
			$html .= "<span class=section_title>Roller du spelar</span><div class=section>";
			foreach($Characters['playing'] as $Char)
				$html .= RenderCharacterLink($Char['id'], $Char['name'])."<br>";
			$html .= "</div>";
			
			$html .= "<span class=section_title>Roller du känner väl</span><div class=section>";
			foreach($Characters['know_well'] as $Char)
				$html .= RenderCharacterLink($Char['id'], $Char['name'])."<br>";
			$html .= "</div>";
		}
		$html .= "<span class=section_title>Roller du känner till</span><div class=section>";
		foreach($Characters['know_of'] as $Char)
			$html .= RenderCharacterLink($Char['id'], $Char['name'])."<br>";
		$html .= "</div>";
		
		if(Auth::Singleton()->OrganizerMode())
		{	
			$html .= "<span class=section_title>Alla roller (Arrangörsläge)</span><div class=section>";
			foreach($Characters['all'] as $Char)
				$html .= RenderCharacterLink($Char['id'], $Char['name'])."<br>";
			$html .= "</div>";
			
		}
		
		if(Auth::Singleton()->LoggedIn())
		{
			$html .= "<span class=section_title>Skapa ny roll</span><div class=section>";
			$html .= $create_form_html;
			$html .= "</div>";
		}
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = Auth::Singleton()->LarpName();
		return $Data;
	}
}
?>