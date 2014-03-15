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
			$html .= "Logga ut f�r att kunna skapa en ny anv�ndare.<br>";
			
		}
		else
		{
			$html .= "<div>Registera ny anv�ndare:</div>";
						
			$html .= Users::RenderCreateForm();
		}
		

		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = "Skapa ny anv�ndare";
		return $Data;
	}
}

