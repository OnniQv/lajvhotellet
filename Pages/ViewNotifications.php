<?php

require_once ("Articles.php");

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
		$user_id = Auth::Singleton()->id;
		
		
		
		$notifications = Notifications::Singleton()->GetNotifications();
				
		foreach($notifications as $time => $n)
		{
			if($n['read'])
				$html .= "<div class=read_notification>";
			else
				$html .= "<div class=unread_notification>";
			
			$html .= $n['html'];
			
			$html .= "<br><span class=meta_data>".RenderTime($time)."</span>";
			
			$html .= "</div>";
		}
		
		//$script = "<script language='javascript'></script>";
	
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = Auth::Singleton()->LarpName();
		//$Data['SCRIPT'] = $script;
		return $Data;
		
	}
	
}

?>