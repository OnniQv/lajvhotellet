<?php

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
		if (! isset($Args[0]))
			die("404 need to know what user to look at");
		
		$html = "";
		$user_id = $Args[0];
		
		$larp_id = Auth::Singleton()->LarpId();
		$me = Auth::Singleton()->id;
		
		$User = Q("SELECT * FROM users WHERE id=?", array($user_id))->fetch_assoc();
			
		
		$html .= "<h2> {$User['full_name']} </h2>";
		
		$html .= "<span class=section_title>Roller</span><div class=section>";
		$res = Q("SELECT character_id FROM user_playing_character WHERE user_id=? AND larp_id=? AND request = 'NONE'", array($user_id, $larp_id));
		while(list($character_id) = $res->fetch_array())
		{
			$html .= RenderCharacterLink($character_id);						
			$html .=  "<br>";
		}
		$html .= "</div>";
			
		$html .= "<span class=section_title>Lajv</span><div class=section>";		
			
			
		$larps = Q("SELECT larps.* FROM larps INNER JOIN user_attending_larp ON user_attending_larp.larp_id=larps.id WHERE user_attending_larp.user_id=?", array($user_id));
		while ($larp = $larps->fetch_assoc())
		{
				
			$url = BasicPage::Singleton()->RootDir() . "/" . $larp["short_name"] . "/Home";
			$html .= "<a href = '$url'>" . $larp["name"] . "</a><br>";
		}
		$html .= "</div>";
		
		if(Auth::Singleton()->LoggedIn())
		{
			if($user_id != $me)
			{
				$article_id = QS("SELECT article_id FROM user_user_relation WHERE (user_id_1=? AND user_id_2=?) OR (user_id_1=? AND user_id_2=?)", array($me, $user_id, $user_id, $me));
				if($article_id == 0)
				{
					Q("INSERT INTO articles (creator_id, larp_id) VALUES (?,?)", array($me, $larp_id));
					$article_id = SQLInsertId();
					Q("INSERT INTO user_user_relation (user_id_1, user_id_2, article_id) VALUES (?,?,?)", array($me, $user_id, $article_id));
				}
				
				//AddDebug("UserUserArticle: $article_id");
				$html .= "<br><br><br>";
				$html .= Forum::Singleton()->RenderForum($article_id, true);
			}
			else
			{
				$html .= "<br><br><br><div class=section>";
				$html .= RenderPageLink("Redigera Användare", "EditUser", $user_id);
				$html .= "</div>";
			}
			
			if((Auth::Singleton()->OrganizingLarp() || $user_id == $me) && Auth::Singleton()->LarpShortName() != "Main")
			{
				//$html .= "<span class=section_title>Off-formulär</span><div class=section>";
				
				$form_id = Auth::Singleton()->UserForm();
				$html .= Forms::Singleton()->RenderFillData($form_id, $user_id);
				
				//$html .= "</div>";
			}
		}
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = $User['full_name'];
		return $Data;
	}
}

?>