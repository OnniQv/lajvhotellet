<?php

require_once("Larp.php");

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
		
		if(!Auth::Singleton()->LoggedIn())
		{
			$html .= "Logga in för att kunna skapa ett nytt Lajv.<br>";
			
		}
		else
		{
			$html .= "<span class=section_title>Skapa nytt lajv:</span>";
			$html .= "<div class=section>";
				
			$html .= Larp::Singleton()->RenderCreateForm();
			
			$html .= "</div>";
			
		}
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = "Skapa nytt Lajv";
		return $Data;
	}
}

?>