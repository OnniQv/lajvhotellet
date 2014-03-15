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
		$larp_name = Auth::Singleton()->LarpName();
		$larp_id = Auth::Singleton()->LarpId();
		$user_id = Auth::Singleton()->id;
		$larp_short_name = Auth::Singleton()->LarpShortName();
		
		if(isset($_POST['join']))
		{
			Q("INSERT INTO user_attending_larp (joined, user_id, larp_id, organizer) VALUES (NOW(), ?, ?, 0)", array($user_id, $larp_id));
			
			SetSuccessMessage("Välkommen som deltagare på $larp_name");
			SetRedirect("/$larp_short_name/Home");
			
			
			$Data = array();
			$Data['HTML'] = $html;
			$Data['TITLE'] = "Bli deltagare i $larp_name";
						
			return $Data;
		}
		
		$html .= "<form action='/$larp_short_name/JoinLarp' method=post><input type=submit name=join value=' Bli deltagare i $larp_name '></form>";
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = "Bli deltagare i $larp_name";		
		return $Data;
		
	}
	
}