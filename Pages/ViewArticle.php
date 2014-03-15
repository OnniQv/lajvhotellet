<?php

require_once("Articles.php");



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
		$script = "";
		$article_id = $Args[0];
		
		$Article = Articles::Singleton()->GetArticle($article_id);
		
		if(!$Article->CanRead)
		{
			$html .= "Du har inte tillgång till denna Artikel";			
		}
		else
		{	
			$Article->UpdateContent();
			
			$html .= $Article->Render();
			$script = $Article->RenderScript();
		}
		$Data = array();
		$Data['HTML'] = $html;
		$Data['SCRIPT'] = $script;
		$Data['TITLE'] = $Article->Title;
		return $Data;
	}

}

?>