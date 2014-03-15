<?php

require_once("Users.php");




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
		{
			$html .= "Logga ut för att kunna skapa en ny användare.<br>";
			
		}
		else
		{
			$html .= "<div>Registera ny användare:</div>";
						
			$html .= Users::RenderCreateForm();
		}
		

		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = "Skapa ny användare";
		return $Data;
	}
}

