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
		
		$form_id = Auth::Singleton()->UserForm();
		
		$html .= "<span class=section_title>Lajvspecifik data</span><div class=section>";		
		$html .= Forms::Singleton()->RenderFillForm($form_id, Auth::Singleton()->id);
		$html .= "</div>";				
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = Auth::Singleton()->name;
		$Data['SCRIPT'] = null;		
		return $Data;
	}
		
}
?>