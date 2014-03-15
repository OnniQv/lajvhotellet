<?php


if(isset($_GET['command']))
{	
	require_once("../auth.php");
	
	
	$larp_shortname = $_GET['larp_shortname'];
	Auth::Singleton()->Auth($larp_shortname);
	
	if(!Auth::Singleton()->OrganizingLarp())
		die("Du �r inte arrang�r f�r detta lajv");
	
	$activate = $_GET['command']=="activate";
	
	Auth::Singleton()->SetOrganizerMode($activate);

	header("location: /$larp_shortname/ConfigureLarp");
	die();
}


require_once ("Forms.php");
require_once ("Larp.php");

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

		$larp_shortname = Auth::Singleton()->LarpShortName();
		$html = "";
		
		if(!Auth::Singleton()->OrganizingLarp())
		{
			$Data = array();
			$Data['HTML'] = "Du har inte tillg�ng till denna sida eftersom du inte �r arrang�r f�r " . Auth::Singleton()->LarpName();
			$Data['TITLE'] = Auth::Singleton()->LarpName();
			return $Data;
		}
		
		
		$html .= "<span class=section_title>Arrang�rsl�ge</span><div class=section>";
		$html .= "N�r arrang�rsl�get �r aktivt kommer du kunna se och redigera allt p� hela lajvet.";		
		if(Auth::Singleton()->OrganizerMode())
			$html .= "<form method=post action='/Pages/ConfigureLarp.php?command=deactivate&larp_shortname=$larp_shortname'><input type=submit name=activate value='Avaktivera arrang�rsl�ge'></form>";
		else
			$html .= "<form method=post action='/Pages/ConfigureLarp.php?command=activate&larp_shortname=$larp_shortname'><input type=submit name=activate value='Aktivera arrang�rsl�ge'></form>";
		$html .= "</div>";
		
		$html .= "<span class=section_title>Formul�r</span><div class=section>";
		$res = Q("SELECT * FROM larps WHERE id=?", array(Auth::Singleton()->LarpId()));
		$assoc = $res->fetch_assoc();		
		$html .= RenderPageLink("Redigera Rollformul�ret", "EditForm", $assoc['character_form_id']). "<br><br>";
		$html .= RenderPageLink("Redigera Off-formul�ret", "EditForm", $assoc['user_form_id']). "<br><br>";
		$html .= "</div>";
		
		$html .= "<div class=section>";
		$html .= RenderPageLink("Visa deltagarnas TODOs", "TODOs");
		$html .= "</div>";
		
		
		$html .= "<span class=section_title>Konfigurera</span><div class=section>";
		$html .= Larp::Singleton()->RenderEditForm();
		$html .= "</div>";
		
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = Auth::Singleton()->LarpName();
		return $Data;
		
	}
}


?>