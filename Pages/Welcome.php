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
		
		$html = "<div class=section>Välkommen till Lajv-web-hotellet<br><br>";
		
		if (! Auth::Singleton()->LoggedIn())
		{
			$html .= RenderPageLink("Registrera ny användare", "CreateUser");
		}
		
		
		
		$html .= "<br><br>Det här är en väldigt tidig BETA av 'Lajv-web-hotellet' (ja, jag ska hitta på ett bättre namn)<br>Du är välkommen att skapa egna användare, lajv, grupper, artiklar, och annat bäst du vill.<br><a href=\"https://www.facebook.com/groups/551220268245764/\">Skriv gärna feedback här.</a></div>";	
			
			
		
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = "Välkommen";
		return $Data;
	}

}

?>