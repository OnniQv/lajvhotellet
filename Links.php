<?php

require_once("Characters.php");
require_once("Character.php");
require_once("Articles.php");
require_once("Article.php");
require_once("Groups.php");
require_once("Group.php");

function GetPageUrl($Page, $Var1=null, $Var2=null, $Var3=null)
{
	if(class_exists('Auth'))
		$larp_shortname =  Auth::Singleton()->LarpShortName();
	else	
		global $larp_shortname;
		
	$link =  "/$larp_shortname/$Page";

	if(isset($Var1))
		$link .= "/" . $Var1;
		$link .= "/" . $Var1;

	if(isset($Var2))
		$link .= "/" . $Var2;

	if(isset($Var3))
		$link .= "/" . $Var3;

	return $link;
}

function RenderPageLink($Text, $Page, $Var1=null, $Var2=null, $Var3=null, $PreviewUrl=null, $PreviewText=null)
{
	global $LinkId;
	$LinkId = rand(0, 4000000000);
	$extra = "";
	if($PreviewUrl != null)
	{//$PreviewUrl
		$extra = "class=\"jTip\" name=\"$PreviewUrl\" title=\"$PreviewText\" id=\"$LinkId\" ";
	}
	
	return "<nobr><a href=\"" . GetPageUrl($Page, $Var1, $Var2, $Var3) . "\" $extra >$Text</a></nobr> ";
}

function RenderUserLink($Id, $Name=null)
{
	if($Name == null)
	{		
		$Name = QS("SELECT full_name FROM users WHERE id=?", array($Id));
	}
	$Text = RenderItemImage("user") . $Name;
	$short = Auth::Singleton()->LarpShortName();
	return RenderPageLink($Text, "ViewUser", $Id, null, null, "/Preview/PreviewUser.php?id=$Id&larp=$short", $Name);
}

function RenderGroupLink($Id)
{		
	if($Id == 0)
		$Name = "Alla";
	else
	{			
		$Group = Groups::Singleton()->GetGroup($Id);
		if($Group->KnowOf)
			$Name = $Group->Name;
		else
			$Name = "Okänd grupp";
	}
	
	$Text = RenderItemImage("group") . $Name;
	$short = Auth::Singleton()->LarpShortName();
	return RenderPageLink($Text, "ViewGroup", $Id, null, null, "/Preview/PreviewGroup.php?id=$Id&larp=$short", $Name);
}

function RenderCharacterLink($Id)
{
	$Character = Characters::Singleton()->GetCharacter($Id);
	
	if($Character->KnowOf)
		$Name = $Character->name;
	else
		$Name = "Okänd roll";
		
	$Text = RenderItemImage("character") . $Name;
	
	if(Auth::Singleton()->LarpValue("approve_characters"))
	{
		if($Character->State == "OK")
			$Text .= "<img src =\"/img/check_green.png\" title='Denna roll är godkänd.'>";
		else
			$Text .= "<img src =\"/img/exclamation_red.png\" title='Denna roll är inte godkänd än.'>";
	}
	
	$short = Auth::Singleton()->LarpShortName();
	return RenderPageLink($Text, "ViewCharacter", $Id, null, null, "/Preview/PreviewCharacter.php?id=$Id&larp=$short", $Name);
}

function RenderArticleLink($Id) 
{
	return RenderArticleLinkObject(Articles::Singleton()->GetArticle($Id));
}

function RenderArticleLinkObject($Article)
{
	
	
	$Id = $Article->id;
	
	if($Article->CanRead || Auth::Singleton()->OrganizerMode())
		$Text = RenderItemImage("article") . $Article->Title;
	else
		$Text = RenderItemImage("article") . "Okänd artikel";
	
	if($Article->ShouldRead > 0 && ($Article->HaveRead == 0 || $Article->HaveRead == 3))
	{
		switch($Article->ShouldRead)
		{
			case 1: $Text .= "<img src =\"/img/exclamation_green.png\" title='Du rekommenderas att läsa denna artickel.'>";break;
			case 2: $Text .= "<img src =\"/img/exclamation_red.png\" title='Du måste läsa denna artickel.'>";break;
		}
	}
	
	switch($Article->HaveRead)
	{
		case 0: break;
		case 1: $Text .= "<img src =\"/img/check_green.png\" title='Du läste artickeln {$Article->LastReadTime}'>"; break;
		case 2: $Text .= "<img src =\"/img/check_yellow.png\" title='Du läste artickeln {$Article->LastReadTime}, men den skedde en liten förändring i den {$Article->LastChangeTime}'>"; break;
		case 3: $Text .= "<img src =\"/img/check_red.png\" title='Du läste artickeln {$Article->LastReadTime}, men den skedde en förändring i den {$Article->LastChangeTime}'>"; break;
	}
	
	$short = Auth::Singleton()->LarpShortName();
	return RenderPageLink($Text, "ViewArticle", $Id, null, null, "/Preview/PreviewArticle.php?id=$Id&larp=$short", $Article->Title);
		
}

function RenderItemImage($ItemType)
{
	switch($ItemType)
	{
		case "user": $src = "user.png"; break;
		case "character": $src = "character.png"; break;
		case "group": $src = "group.png"; break;
		case "article": $src = "article.png"; break;
		default: die("501 wrong ItemType in RenderItemImage()");
	}
	
	return '<img src="/img/' . $src . '">';	
}

function RenderTime($timetext)
{
	$timestamp = strtotime($timetext);
	if(!isset($_SESSION['counter']))
		$_SESSION['counter'] = 0;
	$_SESSION['counter']++;
	return "<a name='timer' id='timer_{$_SESSION['counter']}' class=meta_data>$timestamp</a>";
}




?>