<?php

require_once("../Debug.php");
require_once("../auth.php");
require_once("../Group.php");
require_once("../Groups.php");

echo "<meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-1' />";

echo "<style type='text/css'>img{border:none;}</style>";

Auth::Singleton()->Auth($_GET['larp']);

if($_GET['id'] == 0)
	die("Gruppen Alla �r en specialgrupp som alla spelare och roller automatiskt �r medlemmar i.");

$Group = Groups::Singleton()->GetGroup($_GET['id']);

if(!$Group->KnowOf)
	die("Du k�nner inte till denna grupp.");



	
echo $Group->Type . "<br>";	
	
if($Group->Guarded)
	echo "Det beh�vs till�telse f�r att g� med i denna grupp.<br>";

if($Group->MembersAddArticles)
	echo "Medlemmar f�r l�gga till artiklar.<br>";
	
if($Group->MembersEditReadFlags)
	echo "Medlemmar f�r �ndra l�sflaggor p� artiklar.<br>";

	
if($Group->Admin)
	echo "Du �r admin i denna grupp.";
else if($Group->Member)
	echo "Du �r medlem i denna grupp.";
	

?>