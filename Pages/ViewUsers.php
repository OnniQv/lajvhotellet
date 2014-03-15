<?php

if(isset($_GET['id']))
{
	require_once("..\\auth.php");
	$id = $_GET['id'];
	$activate = $_GET['activate'];
	$larp_shortname = $_GET['larp_shortname'];
	
	
	Auth::Singleton()->Auth($larp_shortname);
	
	$larp_id = Auth::Singleton()->LarpId();
	
	if(Auth::Singleton()->LarpValue("creator_id") !=Auth::Singleton()->id)
		die("Du är inte huvudarrangör");

	Q("UPDATE user_attending_larp SET organizer=? WHERE larp_id=? AND user_id=?", array($activate, Auth::Singleton()->LarpId(), $id));
	
	
	if($activate == "1")
		$message = "[U[$id]] har blivit Arrangör";
	else
		$message = "[U[$id]] är inte längre Arrangör";
	
	$res = Q("SELECT id FROM users JOIN user_attending_larp ON users.id = user_attending_larp.user_id WHERE  user_attending_larp.organizer=1 AND users.id<>?", array(Auth::Singleton()->id));
	
	while(list($user_id) = $res->fetch_array())
		Q("INSERT INTO system_notifications (reciever_id, message, time, larp_id) VALUES (?,?, NOW(), ?)", array($user_id, $message, $larp_id));
	
	if($activate == "1")
		die("Arrangor");
	else
		die("");
}

require_once ("Articles.php");
require_once ("Groups.php");

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
		
		$larp_id = Auth::Singleton()->LarpId();
		$larp_shortname = Auth::Singleton()->LarpShortName();
		
		$main_organizer = Auth::Singleton()->LarpValue("creator_id") == Auth::Singleton()->id;
		
		$html .= "<span class=section_title>Deltagare</span><div class=section><table>";
		
		$res = Q("SELECT users.*, user_attending_larp.organizer FROM users JOIN user_attending_larp ON user_attending_larp.user_id=users.id WHERE user_attending_larp.larp_id=?", array($larp_id));		
		while($user = $res->fetch_assoc())			
		{
			$organizer = "";
			if($main_organizer)
				$organizer = "<span id=row_{$user['id']}><input type=submit value=' Uppgradera till Arrangör ' onclick=\"Organizer({$user['id']}, 1)\"></span>";
			
			if($user['organizer'])
			{
				$organizer = "Arrangör";
				
				if($main_organizer)
					$organizer = " <span id=row_{$user['id']}>Arrangör <input type=submit value=' Ta bort status ' onclick=\"Organizer({$user['id']}, 0)\"></span>";
			}
			
			if(Auth::Singleton()->LarpValue("creator_id") == $user['id'])
				$organizer = "Huvudarrangör (Kan inte ändras)";
			
			$html .= "<tr><td>" . RenderUserLink($user['id']) . "</td><td>$organizer</tr></tr><br>";
		}
		$html .= "</table></div>";
	
		$script = "";
		if($main_organizer)
			$script = "<script language='javascript'>
		
						function Organizer(id, activate)
						{
							document.getElementById('row_'+id).innerHTML = '<img src=\"/img/wait.gif\">';
		
							$.get('/Pages/ViewUsers.php?id='+id+'&activate='+activate+'&larp_shortname=$larp_shortname', function(respons)
							{							
								if(respons == 'Arrangor')
									respons = 'Arrangör';
								document.getElementById('row_'+id).innerHTML = respons;
							});
						
					}
					</script>";
		
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['SCRIPT'] = $script;
		$Data['TITLE'] = "Alla Deltagare";
		return $Data;

	}	
	
}

?>