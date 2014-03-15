<?PHP

require_once("SQL.php");
require_once("Debug.php");
require_once("zebra_form/Zebra_Form.php");
//require_once("FB/facebook.php");

function check_user ($value)
{
	global $validate_email;
	$validate_email = $value;
	
	$res = QS("SELECT COUNT(*) FROM users WHERE email = ?", array($value));
		
	return $res != 0;
}

function check_psw ($psw)
{	
	$hash = crypt($psw, "Blubbeliblubb");
	global $validate_email;
	$res = QS("SELECT COUNT(*) FROM users WHERE password_hash = ? AND email=?", array($hash, $validate_email));
	
	return $res != 0;
}

class Auth
{

	public $name = "";

	public $email = "";

	public $id = 0;

	public $larps = array();

	public $current_shortname;

	private $login_form;

	private $logout_form;
	
	public $fb_access_token;

	private static $singleton = null;

	public static function Singleton ()
	{
		if (! isset(self::$singleton))
		{
			self::$singleton = new Auth();
		}
		
		return self::$singleton;
	}

	private function __construct ()
	{
		
	}

	public function LarpName ()
	{
		return $this->larps[$this->current_shortname]["name"];
	}
	
	public function LarpId ()
	{
		if($this->current_shortname == "Main")
			return 0;
		return $this->larps[$this->current_shortname]["id"];
	}

	public function LarpShortName ()
	{
		return $this->current_shortname;
	}

	public function LoggedIn ()
	{
		return $this->id != 0;
	}

	public function AttendingLarp ()
	{
		if($this->current_shortname == "Main")
			return true;
		
		return $this->larps[$this->current_shortname]['attending'];
	}
	public function OrganizingLarp ()
	{
		if($this->current_shortname == "Main")
			return false;
		return $this->larps[$this->current_shortname]['organizer'];
	}
	
	public function CharacterForm ()
	{
		return $this->larps[$this->current_shortname]["character_form_id"];
	}
	
	public function UserForm ()
	{
		return $this->larps[$this->current_shortname]["user_form_id"];
	}
	
	public function LarpArticleId ()
	{
		return $this->larps[$this->current_shortname]["article_id"];
	}
	
	public function LarpValue ($value)
	{
		return $this->larps[$this->current_shortname][$value];
	}

	public function Auth ($larp_shortname)
	{	
		if(session_id() == '')	
			session_start();
		
		$this->current_shortname = $larp_shortname;
		
		AddDebug("current_shortname: $larp_shortname");
		
		//Facebookstuff
		
		if(!isset($_GET["error"]))
		{
		
			if(isset($_GET["code"]))
			{
				$code = $_GET["code"];
								
				$url = 'https://graph.facebook.com/oauth/access_token?client_id=297695873663373&redirect_uri=http://lajvhotellet.se'.$_SERVER['REQUEST_URI'].'&client_secret=9b03913c887773c63af483739adbd47f&code='.$code;
		
				$curl_handle=curl_init();
				curl_setopt($curl_handle,CURLOPT_URL,$url);
				curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,6);
				curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
				$buffer = curl_exec($curl_handle);
				curl_close($curl_handle);
				if(strpos($buffer, 'access_token=') === 0)
				{
					//if you requested offline acces save this token to db
					//for use later
					$this->fb_access_token = $token = str_replace('access_token=', '', $buffer);
		
					//this is just to demo how to use the token and
					//retrieves the users facebook_id
					$url = 'https://graph.facebook.com/me/?access_token='.$token;
					$curl_handle=curl_init();
					curl_setopt($curl_handle,CURLOPT_URL,$url);
					curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
					curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
					$buffer = curl_exec($curl_handle);
					curl_close($curl_handle);
					$jobj = json_decode($buffer);
					$res = Q("SELECT * FROM users WHERE facebook_id=?", array($jobj->id))->fetch_assoc();					
					
					
					if(!isset($res['facebook_id']))
					{
						$jobj->name = utf8_decode($jobj->name);
						
						Q("INSERT INTO users (full_name, email, facebook_id) VALUES (?,?,?)", array($jobj->name, $jobj->email, $jobj->id));
						
						$res = Q("SELECT * FROM users WHERE facebook_id=?", array($jobj->id))->fetch_assoc();
					}
							
					
					$this->email = $_SESSION['user_email'] = $res['email'];
					$this->name = $_SESSION['user_name'] = $res['full_name'];
					$this->id = $_SESSION['user_id'] = $res['id'];
					$this->PerformLogin();		
					
					$e = explode('?', 'http://xn--hjltesng-1zao.se'.$_SERVER['REQUEST_URI']);
					Header("location: " . $e[0]);
					
				}
				else
				{
					die("Facebook error: " . $buffer);
				}
			}
		}
		else
		{
			die("Facebook error: " . $_GET["error"]);
		}
				
		
		$this->logout_form = new Zebra_Form('logout_form');
		$this->logout_form->form_properties['attributes']['class'] = "Zebra_Tight";
		$this->logout_form->add('submit', 'btnsubmit', 'Logga Ut');
		
		if (isset($_SESSION['user_name']))
		{
			$this->name = $_SESSION['user_name'];
			$this->id = $_SESSION['user_id'];
			$this->email = $_SESSION['user_email'];
			$this->PerformLogin();
			
			if ($this->logout_form->validate())
			{
				if ($_POST['btnsubmit'] == "Logga Ut")
				{
					session_destroy();
					$_SESSION = array();
					$this->name = "";
					$this->id = 0;
					$this->email = "";
				}
			}
		}
		
		if (! isset($_SESSION['user_name']))
		{
			
			$this->login_form = new Zebra_Form('login_form');
			$this->login_form->client_side_validation(true);
			$this->login_form->form_properties['attributes']['class'] = "Zebra_Tight";
			
			// the label for the "email" element
			$this->login_form->add('label', 'label_email', 'email', 'Email');
			$obj = $this->login_form->add('text', 'email', '', 
					array('autocomplete' => 'on'));
			$obj->set_rule(
					array(
							
							// error messages will be sent to a variable called
							// "error", usable in custom templates
							'required' => array('error', 
									'Du måste skriva in en email-adress!'), 
							'email' => array('error', 
									'Du måste skriva in en korrekt email-adress!'), 
							'custom' => array("check_user", null, 'error', 
									'Hittar inte den användaren.'))

					);
			
			// "password"
			$this->login_form->add('label', 'label_password', 'password', 
					'Lösenord');
			$obj = $this->login_form->add('password', 'password', '', 
					array('autocomplete' => 'off'));
			$obj->set_rule(
					array(
							'required' => array('error', 
									'Du måste skriva in ett lösenord!'), 
							'length' => array(3, 100, 'error', 
									'Lösenordet måste vara minst tre tecken långt!'), 
							'custom' => array("check_psw", null, 'error', 
									'Fel lösenord!')));
			
			// "remember me"
			/*$this->login_form->add('checkbox', 'remember_me', 'yes');
			$this->login_form->add('label', 'label_remember_me_yes', 
					'remember_me_yes', 'Kom ihåg mig', 
					array('style' => 'font-weight:normal'));*/
			
			// "submit"
			$this->login_form->add('submit', 'btnsubmit', 'Logga In');
			
			if ($this->login_form->validate())
			{
				if (isset($_POST['email']))
				{
					$email = $_POST['email'];
					
					$res = Q("SELECT * FROM users WHERE email =?", array($email))->fetch_assoc();
					
					if ($res['password_hash'] == crypt($_POST['password'], "Blubbeliblubb"))
					{
						
						$this->email = $_SESSION['user_email'] = $res['email'];
						$this->name = $_SESSION['user_name'] = $res['full_name'];
						$this->id = $_SESSION['user_id'] = $res['id'];
					}
					else
					{
						// $html .= "Fel lösenord!";
					}
				}
			}
		}

		$this->PerformLogin();
		
		
	}

	private function PerformLogin()
	{
		// Logged in correct, now check Larp short names and so on
		
		if(isset($this->logged_in))
			return;
		
		$this->logged_in = true;
		
		$RLI = false;
		$RL = false;
		
		/*$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "graph.facebook.com/641919300/notifications");
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_POST, 3);
		curl_setopt($ch,CURLOPT_POSTFIELDS, "access_token={$this->fb_access_token}&template=Logged into localhost&href=localhost/SL/Home");
		
		AddDebug(curl_exec ($ch));
		
		curl_close ($ch);*/
		
		
		if(class_exists('BasicPage'))
		{
			$P = BasicPage::Singleton();
			$RLI = $P->RequireLoggedIn();
			$RL = $P->RequireLarp();
			
			if ($RLI && ! Auth::Singleton()->LoggedIn())
				die("403 Du är inte inloggad");
			
			if ($RL && $this->current_shortname == "Main")
				die("404 Denna sida finns bara i existerande lajv");
			
			/*if (! $RL && $this->current_shortname != "Main")
				die("404 Denna sida existerar inte i specifika lajv, använd mappen Main");*/
			
			
			
		}
		if ($this->current_shortname == "Main")
			return;
		
		//TODO: bort med false och opta
		if (false && isset($_SESSION["larps"][$this->current_shortname]))
		{
			$this->larps = $_SESSION["larps"];	
		}
		else
		{
				
			$res = Q("SELECT * FROM larps WHERE short_name = ?", 
						array($this->current_shortname));
			$assoc = $res->fetch_assoc(); 	
			if (! isset($assoc['name']))
				die("404 Lajvet existerar inte. Short:" . $this->current_shortname);

			$larp = $assoc;
			$larp['name'] = $assoc['name'];
			$larp['short_name'] = $assoc['short_name'];
			$larp['id'] = $assoc['id'];
			$larp['attending'] = 0;			
			$larp['organizer'] = 0;
			
			
				
			$res = Q("SELECT organizer FROM user_attending_larp WHERE user_id=? AND larp_id=?", array($this->id, $larp['id']));
			
			if($assoc = $res->fetch_assoc())
			{
				$larp['attending'] = 1;
				$larp['organizer'] = $assoc['organizer'];
			}						
				
			if(!isset($_SESSION['larps'][$larp['short_name']]['organizer_mode']))
				$organizer_mode = false;
			else
				$organizer_mode = $_SESSION['larps'][$larp['short_name']]['organizer_mode'];
			
			$_SESSION['larps'][$larp['short_name']] = $larp;
			
			
			$_SESSION['larps'][$this->current_shortname]['organizer_mode'] = $organizer_mode;
			
			$this->larps = $_SESSION['larps'];
												
		}
		
		if ($RL && $RLI)
		{
			if ($RL && $this->larps[$this->current_shortname]['attending'] != "1")
			{
				die("403 Du är inte med i detta lajv");
			}
		}
		
		if(isset($P))
			$P->AddDebug("Session: " . print_r($_SESSION, true));
	}
	
	public function RenderLogin ()
	{
		$html = "<div class=login>";
				
		
		if ($this->id != 0)
		{
			$html .= "Inloggad som: " . RenderPageLink($this->name, "ViewUser", $this->id);
			$html .= $this->logout_form->Render('*vertical', true);
		}
		else
		{
			$html .= $this->login_form->Render('*horizontal', true);
			
			$html .= "<div class=facebook_button><a href='https://www.facebook.com/dialog/oauth?client_id=297695873663373&redirect_uri=http://xn--hjltesng-1zao.se{$_SERVER['REQUEST_URI']}'><img src='/img/fb_login.png'></a></div>";
			$html .= "<div class=register_user>".RenderPageLink("Registrera ny användare", "CreateUser") . "</div>";
						
		}
		$html .= "</div>";
		
		return $html;
	}
	
	public function SetOrganizerMode($activate)
	{
		if(!$this->OrganizingLarp())
			return;
	
		
		$_SESSION['larps'][$this->current_shortname]['organizer_mode'] = $activate;
		$this->larps[$this->current_shortname]['organizer_mode'] = $activate;
		
	}
	public function OrganizerMode()
	{
		if($this->current_shortname == "Main")
			return false;
		
		//$this->larps[$this->current_shortname]['organizer_mode'] = $activate;
		return $this->larps[$this->current_shortname]['organizer_mode'];
	}

}

?>