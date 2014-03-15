<?php

require_once("../Debug.php");
require_once("../auth.php");
require_once("../Links.php");

echo "<meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-1' />";

echo "<style type='text/css'>img{border:none;}</style>"; 

Auth::Singleton()->Auth($_GET['larp']);

$larp_id = Auth::Singleton()->LarpId();
$user_id = $_GET['id'];

$User = Q("SELECT * FROM users WHERE id=?", array($user_id))->fetch_assoc();

echo "Roller:<br>";
$res = Q("SELECT character_id FROM user_playing_character WHERE user_id=? AND larp_id=? AND request='NONE'", array($user_id, $larp_id));
while(list($character_id) = $res->fetch_array())
{
	echo RenderCharacterLink($character_id) . "<br>";
}
	
echo "Lajv:<br>";
$res = Q("SELECT larps.* FROM larps JOIN user_attending_larp ON larps.id=user_attending_larp.larp_id WHERE user_attending_larp.user_id=?", array($user_id));
while($larp = $res->fetch_assoc())
{
	echo $larp['name'] . "<br>";
}
