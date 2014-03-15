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
		
		$html = "<div class=section>V�lkommen till Lajv-web-hotellet<br><br>";
		
		if (! Auth::Singleton()->LoggedIn())
		{
			$html .= RenderPageLink("Registrera ny anv�ndare", "CreateUser");
		}
		
		
		
		$html .= "<br><br>Det h�r �r en v�ldigt tidig BETA av 'Lajv-web-hotellet' (ja, jag ska hitta p� ett b�ttre namn)<br>Du �r v�lkommen att skapa egna anv�ndare, lajv, grupper, artiklar, och annat b�st du vill.<br><a href=\"https://www.facebook.com/groups/551220268245764/\">Skriv g�rna feedback h�r.</a></div>";	
			
			
		
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = "V�lkommen";
		return $Data;
	}

}

?>