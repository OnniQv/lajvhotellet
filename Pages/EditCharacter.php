<?php

require_once("Characters.php");

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
		$html = "";
		
		$character_id = $Args[0];
		$Char = Characters::Singleton()->GetCharacter($character_id);
		
		if(isset($Args[1]) && $Args[1] == "DELETE")
		{
			$Char->Delete();
			SetRedirect(GetPageUrl("ViewCharacters"));
			SetSuccessMessage("Rollen är nu borttagen");
		}
		
			
		
		$html .= $Char->RenderEdit();
						
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = $Char->name;
		$Data['SCRIPT'] = null;		
		return $Data;
	}
		
}
?>