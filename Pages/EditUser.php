<?php

require_once ("Articles.php");
require_once ("Groups.php");
require_once ("Users.php");

class Page   extends BasicPage
{

	public function RequireLoggedIn ()
	{
		return true;
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
		if (! isset($Args[0]))
			die("404 need to know what user to look at");
		
		$html = "";
		$user_id = $Args[0];
		
		$larp_id = Auth::Singleton()->LarpId();
		$me = Auth::Singleton()->id;
		
		$User = Q("SELECT * FROM users WHERE id=?", array($user_id))->fetch_assoc();
		
		if($me != $user_id)
		{
			SetRedirect(GetPageUrl("Welcome"));
			SetFailMessage("Du är inte inloggad som " . $User['full_name']);
			$Data = array();
			$Data['HTML'] = "";
			$Data['TITLE'] = $User['full_name'];
			return $Data;			
		}
		
			
		
		$html .= "<span class=section_title> {$User['full_name']} </span> <div class=section>";
		$html .= RenderPageLink("Redigera Lajv-specifik data", "OffForm");
		
		$html .= Users::RenderEditForm($me);
		$html .= "</div>";
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = $User['full_name'];
		return $Data;
	}
}

?>