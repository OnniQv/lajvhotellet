<?php

require_once("Characters.php");

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
		
		$character_id = $Args[0];
		
		$Char = Characters::Singleton()->GetCharacter($character_id);
		$html .= $Char->RenderView();
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = $Char->name;
		$Data['SCRIPT'] = null;		
		return $Data;
	}
	
}
?>
