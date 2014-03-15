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
	die("Du får inte läsa denna artikel.");


echo "Version: {$Article->Version}<br>";

if($Article->ShouldRead == 2)
	echo "<img src =\"/img/exclamation_red.png\"'>Du måste läsa denna artikel.<br>";
else if($Article->ShouldRead == 2)
	echo "<img src =\"/img/exclamation_green.png\">Du rekommenderas att läsa denna artikel.<br>";

$time = RenderTime($Article->LastReadTime);
switch($Article->HaveRead)
{	
	case 0: echo "Du har inte läst denna artikel.<br>"; break;
	case 1: echo "Du läste denna artikel $time.<br>"; break;
	case 2: echo "Du läste denna artikel $time och en liten förändring har skett sedan dess.<br>"; break;
	case 3: echo "Du läste denna artikel $time men en stor förändring har skett sedan dess.<br>"; break;
}

echo "<script src='/TimeFormat.js'></script>";
echo "<script language='javascript'>TimeStart();</script>";

if($Article->Edit == 1)
	echo "Du får redigera denna artikel.<br>";
	
if($Article->Own == 0)
	echo "Du är skapare av denna artikel.<br>";

echo "<br><br>";
echo substr($Article->RawContent, 0, 150);
echo "...";

?>