<?php
require_once("../Debug.php");
require_once("../SQL.php");
require_once("../auth.php");
require_once("../Character.php");
require_once("../Characters.php");
require_once("../Links.php");

echo "<meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-1' />";
echo "<style type='text/css'>img{border:none;}</style>";

Auth::Singleton()->Auth($_GET['larp']);

$Char = Characters::Singleton()->GetCharacter($_GET["id"]);

if(!$Char->KnowOf)
	die("Du k�nner inte till denna karakt�r.");

if(Auth::Singleton()->LarpValue("approve_characters"))
{
	switch($Char->State)
	{
		case "EDIT": echo "Denna roll �r redigeras fortfarande."; break;				
		case "APPROVE": echo "Denna roll �r inte godk�nd �n."; break;				
		case "FAIL": echo "Denna roll blev inte godk�nd."; break;				
		case "OK": echo "Denna roll �r godk�nd."; 	break;		
	}
}

echo "<br>Spelas av:";

$res = Q("SELECT user_id FROM user_playing_character WHERE character_id=? AND request='NONE'", array($_GET["id"]));
while(list($user_id) = $res->fetch_array())
{
	echo RenderUserLink($user_id);
	echo "<br>";
}


?>