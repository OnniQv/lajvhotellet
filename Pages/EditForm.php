<?php

//require_once ("Groups.php");
require_once ("Forms.php");


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

		$html = Forms::Singleton()->RenderEditForm($Args[0]);
		
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = Auth::Singleton()->LarpName();
		return $Data;
	}
	
}

?>