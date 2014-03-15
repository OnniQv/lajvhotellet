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
	die("Du känner inte till denna karaktär.");

if(Auth::Singleton()->LarpValue("approve_characters"))
{
	switch($Char->State)
	{
		case "EDIT": echo "Denna roll är redigeras fortfarande."; break;				
		case "APPROVE": echo "Denna roll är inte godkänd än."; break;				
		case "FAIL": echo "Denna roll blev inte godkänd."; break;				
		case "OK": echo "Denna roll är godkänd."; 	break;		
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