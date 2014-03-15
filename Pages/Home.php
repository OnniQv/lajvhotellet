<?php

require_once ("Groups.php");
require_once ("Articles.php");


class Page   extends BasicPage
{

	public function RequireLoggedIn ()
	{
		return false;
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
		
		$html = "";
		
		/*if(isset($Args[0]))
		{			
			$short = QS("SELECT short_name FROM larps WHERE id=?", array($Args[0]));
			header("Location: /$short/Home");
			die();
		}*/
		
		if (Auth::Singleton()->AttendingLarp())
		{
					
		}
		else
		{
			$html .= "<span class=section_title>Inte Deltagare</span>";
			$html .= "<div class=section>Du kan titta runt på en del saker på denna sidan, men för att ta del av det mesta måste du logga in och bli deltagare på detta lajv.</div>";
			$html .= "<br/>";
		}
		
		$article_id = Auth::Singleton()->LarpArticleId() ; 
		$article = Articles::Singleton()->GetArticle($article_id);
		$article->UpdateContent();

		$html .= $article->Render();
		$script = $article->RenderScript();
		
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['SCRIPT'] = $script;
		$Data['TITLE'] = Auth::Singleton()->LarpName();
		return $Data;
	}
		
}

?>