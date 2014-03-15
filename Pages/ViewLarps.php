<?php

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
		
		$html = "";

		
		$html .= "<span class=section_title>Alla Lajv</span><div class=section>";
		$id = Auth::Singleton()->id;
		$larps = Q("SELECT * FROM larps", array());
		while ($larp = $larps->fetch_assoc())
		{			
			$url = BasicPage::Singleton()->RootDir() . "/" . $larp["short_name"] . "/Home";
			$html .= "<a href = '$url'>" . $larp["name"] . "</a><br>";
		}
		
		$html .= "</div>";
		

		if (Auth::Singleton()->LoggedIn())		
		{
			$html .= "<span class=section_title>Lajv du är med i</span><div class=section>";
			
			$id = Auth::Singleton()->id;
			$larps = Q("SELECT larps.* FROM larps INNER JOIN user_attending_larp ON user_attending_larp.larp_id=larps.id WHERE user_attending_larp.user_id=?", array($id));
				
			while ($larp = $larps->fetch_assoc())
			{
				
				$url = BasicPage::Singleton()->RootDir() . "/" . $larp["short_name"] . "/Home";
				$html .= "<a href = '$url'>" . $larp["name"] . "</a><br>";
			}
				
			$html .= "</div>";
			
			$html .= "<div class=section>";
			$html .= RenderPageLink("Skapa nytt Lajv", "CreateLarp");
			$html .= "</div>";
		}
		else
		{
			$html .= "<div class=section>";
			$html .= "Logga in för att skapa ett nytt lajv.";
			$html .= "</div>";
		}
			
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = "Alla Lajv";
		return $Data;
	}
}
		
?>