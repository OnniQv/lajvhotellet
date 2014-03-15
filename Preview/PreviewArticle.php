<?php

require_once("../Debug.php");
require_once("../auth.php");
require_once("../Article.php");
require_once("../Articles.php");

echo "<meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-1' />";

echo "<style type='text/css'>img{border:none;}</style>";

Auth::Singleton()->Auth($_GET['larp']);


$Article = Articles::Singleton()->GetArticle($_GET['id']);

$Article->UpdateContent();

if($Article->CanRead == 0)
	die("Du f�r inte l�sa denna artikel.");


echo "Version: {$Article->Version}<br>";

if($Article->ShouldRead == 2)
	echo "<img src =\"/img/exclamation_red.png\"'>Du m�ste l�sa denna artikel.<br>";
else if($Article->ShouldRead == 2)
	echo "<img src =\"/img/exclamation_green.png\">Du rekommenderas att l�sa denna artikel.<br>";

$time = RenderTime($Article->LastReadTime);
switch($Article->HaveRead)
{	
	case 0: echo "Du har inte l�st denna artikel.<br>"; break;
	case 1: echo "Du l�ste denna artikel $time.<br>"; break;
	case 2: echo "Du l�ste denna artikel $time och en liten f�r�ndring har skett sedan dess.<br>"; break;
	case 3: echo "Du l�ste denna artikel $time men en stor f�r�ndring har skett sedan dess.<br>"; break;
}

echo "<script src='/TimeFormat.js'></script>";
echo "<script language='javascript'>TimeStart();</script>";

if($Article->Edit == 1)
	echo "Du f�r redigera denna artikel.<br>";
	
if($Article->Own == 0)
	echo "Du �r skapare av denna artikel.<br>";

echo "<br><br>";
echo substr($Article->RawContent, 0, 150);
echo "...";

?>