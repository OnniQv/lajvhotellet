<?PHP

require_once("MainMenu.php");
require_once ("Links.php");
require_once ("Debug.php");
require_once ("LongPoll.php");

class BasicPage
{

	private $Args;

	private static $singleton;
	
	public $SuccessMessage;
	public $FailMessage;
	public $Redirect;

	private $Debug = Array();

	private $Error = Array();

	function __contruct ()
	{
		self::$singleton = $this;
	}

	public function RootDir ()
	{
		return "";
	}

	public static function Singleton ()
	{		
		if(self::$singleton == null)
			self::$singleton = new BasicPage();
		return self::$singleton;
	}

	public function SetArgs ($a)
	{
		$this->Args = $a;
	}

	public function Render ($Args)
	{
		die("Error, Render() not overridden!");
	}

	public function RequireLoggedIn ()
	{
		return false;
	}

	public function RequireLarp ()
	{
		return false;
	}
	
	function SetSuccessMessage($mess)
	{
		
	}

	public function AddDebug ($text)
	{
		$entry = Array("text" => $text, "trace" => StackTrace());
		$this->Debug[] = $entry;
	}

	public function AddError ($text)
	{
		$entry = Array("text" => $text, "trace" => StackTrace());
		$this->Error[] = $entry;
	}

	public function RenderAll ()
	{
		$this->AddDebug("POST: " . print_r($_POST, true));
		$this->AddDebug("GET: " . print_r($_GET, true));
		
		
		$Data = $this->Render($this->Args);
		
		if ((isset($Data['REDIRECT']) && $Data['REDIRECT'] != "") || (isset($this->Redirect) && $this->Redirect))
		{
			if( $Data['REDIRECT'] != "")
				$header = "Location: " . $Data['REDIRECT'];
			else 
				$header = "Location: " . $this->Redirect;
			
			if(isset($this->SuccessMessage))
				$_SESSION['SUCCESS_MESSAGE'] = $this->SuccessMessage; 	
			if(isset($this->FailMessage))
				$_SESSION['FAIL_MESSAGE'] = $this->FailMessage;
					
			header($header);
			die();
		}
		
		if(isset($_SESSION['SUCCESS_MESSAGE']))
			$this->SuccessMessage = $_SESSION['SUCCESS_MESSAGE'];
		if(isset($_SESSION['FAIL_MESSAGE']))
			$this->FailMessage = $_SESSION['FAIL_MESSAGE'];
		unset($_SESSION['SUCCESS_MESSAGE']);
		unset($_SESSION['FAIL_MESSAGE']);
		
		
		
		$html = "<html xmlns:fb='http://www.facebook.com/2008/fbml'><head><title>{$Data['TITLE']}</title><meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-1' />";
		header('Content-Type: text/html; charset=ISO-8859-1');
		
		$html .= "<link rel='stylesheet' type='text/css' href='/zebra_form/public/css/zebra_form.css' />";
				
		if (Auth::Singleton()->LarpShortName() != "Main" &&
		 Auth::Singleton()->LarpShortName() != "")
		{
			$css = "/css/" . Auth::Singleton()->LarpShortName() . ".css";
			if(!file_exists($css))
			{
				$css = "/css/default.css";				
			}
		}
		else
			$css = "/css/default.css";
		
		$this->AddDebug("css: " . $css);
		
		$html .= "<link rel='stylesheet' type='text/css' href='$css' />";

		if(isset($Data["SCRIPT"]))
			$html .= $Data["SCRIPT"];
				
		$style = "";
		if(Auth::Singleton()->OrganizerMode())
			$style = "style='background: red;'";
		$html .= "</head><body $style>";
		
	
			
		//$html .= "<script src=\"http://code.jquery.com/jquery-latest.js\"></script>";		
		$html .= "<script src=\"/jquery.js\"></script>";
		$html .= "<script src='/TimeFormat.js'></script>";
		$html .= "<script src='/jtip.js'></script>";
		$html .= "<script src='/zebra_form/public/javascript/zebra_form.js'></script>";
		
		
		// Header
		$html .= "<div class=top_frame>";
		$html .= "<div class=main_menu_info>";
		if(Auth::Singleton()->LarpId())
		{
			$html .= "<span class=main_menu_title>" . Auth::Singleton()->LarpName() . "</span><br>";
			$html .= "<span class=main_menu_tagline>" . Auth::Singleton()->LarpValue('tagline') ."</span><br>";
			$html .= "<span class=main_menu_dates>" . date("d/M", strtotime(Auth::Singleton()->LarpValue('date_onsite'))) . " - " . date("d/M", strtotime(Auth::Singleton()->LarpValue('date_gameoff'))) . "</span>";
			$html .= " <span class=main_menu_area>" . Auth::Singleton()->LarpValue('area_short') . "</span>";
		}
		else
		{
			$html .= "<span class=main_menu_title>Lajv-web-hotellet</span><br>";
			$html .= "<span class=main_menu_tagline>Webhotellet för lajvarrangörer</span><br>";
		}
		$html .= "</div>";
		$html .= "<div class=main_menu_login>";
		$html .= Auth::Singleton()->RenderLogin();
		$html .= "</div>";		
		
		$html .= "<div class=main_page_link><a href='/'>Tillbaka till<br>huvudsidan</a></div>";
		
		
		$html .= "</div>";
		$html .= MainMenu::Singleton()->Render();
		$html .= MainMenu::Singleton()->GetScript();
		
		if (isset($this->SuccessMessage) && $this->SuccessMessage != "")
		{						
			$html .= "<div id=message_frame class=success_frame>";
			$html .= $this->SuccessMessage;
			$html .= "</div>";				
		}
		if (isset($this->FailMessage) && $this->FailMessage != "")
		{
			$html .= "<div id=message_frame  class=fail_frame>";
			$html .= $this->FailMessage;
			$html .= "</div>";			
		}
		$html .= "<script language='javascript'>$(\"#message_frame\").delay(2000).fadeOut(2000);";
		$html .= "</script>";
		
		
		// Main data
		$html .= "<div class=sword></div><div class=shield></div><div class=main_top></div><div class=main_center id=main>";		
		$Content = $Data['HTML'];	
		if(!isset($Data['DONT_REPLACE_LINKS']))
		{
			$Content = $this->ReplaceLinks($Content, "U");
			$Content = $this->ReplaceLinks($Content, "G");
			$Content = $this->ReplaceLinks($Content, "R");
			$Content = $this->ReplaceLinks($Content, "A");
		}		
		$html .= $Content;		
		$html .= "</div><div class=main_bottom></div>";
				
		// Footer
		$html .= "<div class=debug_frame>";
		if (count($this->Error) > 0)
		{
			$html .= "<h2>Errors:</h2><table class=debug_table border=3>";
			foreach ($this->Error as $E)
			{
				$html .= "<tr><td>{$E['text']}</td><td>{$E['trace']}</td></tr>";
			}
			$html .= "</table>";
		}
		if (count($this->Debug) > 0)
		{
			$html .= "<br><h2>Debug:</h2><table class=debug_table border=3>";
			foreach ($this->Debug as $D)
			{
				$html .= "<tr><td>{$D['text']}<br></td><td>{$D['trace']}</td></tr>";
			}
			$html .= "</table>";
		}
		if (count(SQL::S()->Querys) > 0)
		{
			$sql_time = 0;
			$html .= "<br><h2>SQL:</h2>";
			
			$html .= "Connect time: " . SQL::S()->ConnectTime;
			
			$html .= "<br><table class=debug_table border=3>";
			foreach (SQL::S()->Querys as $Q)
			{
				$html .= "<tr><td>{$Q['query']}</td><td>{$Q['rows']}</td><td>{$Q['time']}</td><td>{$Q['trace']}</td><td>{$Q['error']}</td></tr>";
			
				$sql_time += $Q['time'];
			}
			$html .= "</table>";
			$html .= "<br>Total SQL time: $sql_time";
		}
		
		$html .= "</div>";
		
		LongPoll::S()->SetUserId(Auth::Singleton()->id, Auth::Singleton()->LarpId());
		$html .= LongPoll::S()->GetScript();
		
		
		return $html;
	}

	function ReplaceLinks($Content, $type)
	{
		if($Content == "")
			return "";
		
		$pos = 0;
		while(($pos = strpos($Content, "[{$type}[", $pos+3)) !== false)
		{
			$endpos = strpos($Content, "]]", $pos);
			$id_string = substr($Content, $pos+3, ($endpos - 3) - $pos);
			
			if($id_string == 0)
				continue;
			
			$NewContent = substr($Content, 0, $pos);
			switch($type)
			{
				case "U" : $NewContent .= RenderUserLink($id_string); break;
				case "G" : $NewContent .= RenderGroupLink($id_string); break;
				case "R" : $NewContent .= RenderCharacterLink($id_string); break;
				case "A" : $NewContent .= RenderArticleLink($id_string); break;
			}
			$NewContent .= substr($Content, $endpos+2);
				
			$Content = $NewContent;
		}
		return $Content;
	}
}



function SetSuccessMessage($mess)
{
	BasicPage::Singleton()->SuccessMessage = $mess;
}
function SetFailMessage($mess)
{
	BasicPage::Singleton()->FailMessage = $mess;
}
function SetRedirect($url)
{
	BasicPage::Singleton()->Redirect = $url;
}




?>