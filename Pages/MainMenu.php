<?php

require_once("Links.php");
require_once("Notifications.php");

class MainMenu
{
	private static $singleton = null;
	
	public static function Singleton ()
	{
		if (! isset(self::$singleton))
		{
			self::$singleton = new MainMenu();
		}
	
		return self::$singleton;
	}
	
	function Render()
	{	
		$html = "";
		$user_id = Auth::Singleton()->id;
		
		
	
		$html .= "<div class=main_menu>";

		if(Auth::Singleton()->LarpShortName() == "Main")
		{
			$html .= "<span class=menu_item>".RenderPageLink("Huvudsidan", "Welcome") . "</span> |";
			$html .= "<span class=menu_item>".RenderPageLink("Lajv", "ViewLarps") . "</span>";
			
			if($user_id != 0)
				$html .= " |". "<span class=menu_item>".RenderPageLink("Din sida", "ViewUser", $user_id). "</span>";
		}
		else
		{
			$html .= "<span class=menu_item>".RenderPageLink("Hem", "Home") . "</span> |";
			
			if($user_id != 0 && Auth::Singleton()->AttendingLarp())
				$html .= "<span class=menu_item>".RenderPageLink("Att göra", "TODO") . "</span> |";
			else if($user_id != 0)
				$html .= "<span class=menu_item>".RenderPageLink("Bli deltagare", "JoinLarp") . "</span> |";
			
			
			$html .= "<span class=menu_item>".RenderPageLink("Grupper", "ViewGroups") . "</span> |";
			$html .= "<span class=menu_item>".RenderPageLink("Artiklar", "ViewArticles") . "</span> |";
			$html .= "<span class=menu_item>".RenderPageLink("Roller", "ViewCharacters") . "</span> |";
			$html .= "<span class=menu_item>".RenderPageLink("Deltagare", "ViewUsers") . "</span>";
			
			if($user_id != 0 && Auth::Singleton()->AttendingLarp())
			{
				$NotificationCount = Notifications::Singleton()->GetCount();
				
				LongPoll::S()->RegisterMessageType("NotificationCount", "NotificationCount.php", "NotificationCountInit", "NotificationCountLP", "UpdateNotificationCount", $NotificationCount, true);
				
				if($NotificationCount == 0)
					$NotificationCount = "Inga";
					
				$html .= "| " . "<span class=menu_item>".RenderPageLink("<span id=NC>$NotificationCount </span> Nya meddelanden", "ViewNotifications") . "</span>";
				
				
			}
			
			if(Auth::Singleton()->OrganizingLarp())
				$html .= " | ". "<span class=menu_item>".RenderPageLink("Arrangör", "ConfigureLarp") . "</span>";
		}	
		
		
		return $html;
	}
	
	public function GetScript()
	{
		$script = "<script language='javascript'>
		
		
		function UpdateNotificationCount(newCount)
		{		
			if(newCount == 0)
				newCount = 'Inga';
				
			document.getElementById('NC').innerHTML = newCount;
		}</script>";
		
		return $script;
	}
	
}
 
?>