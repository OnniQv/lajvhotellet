<?php

require_once("../Debug.php");
require_once("../auth.php");
require_once("../Group.php");
require_once("../Groups.php");

echo "<meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-1' />";

echo "<style type='text/css'>img{border:none;}</style>";

Auth::Singleton()->Auth($_GET['larp']);

if($_GET['id'] == 0)
	die("Gruppen Alla är en specialgrupp som alla spelare och roller automatiskt är medlemmar i.");

$Group = Groups::Singleton()->GetGroup($_GET['id']);

if(!$Group->KnowOf)
	die("Du känner inte till denna grupp.");



	
echo $Group->Type . "<br>";	
	
if($Group->Guarded)
	echo "Det behövs tillåtelse för att gå med i denna grupp.<br>";

if($Group->MembersAddArticles)
	echo "Medlemmar får lägga till artiklar.<br>";
	
if($Group->MembersEditReadFlags)
	echo "Medlemmar får ändra läsflaggor på artiklar.<br>";

	
if($Group->Admin)
	echo "Du är admin i denna grupp.";
else if($Group->Member)
	echo "Du är medlem i denna grupp.";
	

?>