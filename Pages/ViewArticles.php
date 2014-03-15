<?PHP
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
		
		$html .= "<span class=section_title>Alla tillgängliga Artiklar i " . Auth::Singleton()->LarpName() . "</span>";
		$html .= "<div class=section>";
		
		$Articles = Articles::Singleton()->GetAllVisible();
			
		AddDebug("GetAllVisibleArticles: " . print_r($Articles, true));
		
		foreach($Articles as $Article)
		{
			$html .= RenderArticleLinkObject($Article) . "<br>";
		}
			
		if(Auth::Singleton()->AttendingLarp())			
			$html .= "<br><br>". RenderPageLink("Skapa Artikel", "CreateArticle");
		$html .= "</div>";
		
		$Data['HTML'] = $html;		
		$Data['TITLE'] = Auth::Singleton()->LarpName();
		
		return $Data;
	}
}

?>