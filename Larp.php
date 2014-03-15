<?php

require_once("auth.php");
require_once("Debug.php");

class Larp
{
	
	private $id;
	
	
	private static $singleton = null;
	
	public static function Singleton ()
	{
		if (! isset(self::$singleton))
		{
			self::$singleton = new Larp();
		}
	
		return self::$singleton;
	}
	
	private function __construct ()
	{
		$this->id = Auth::Singleton()->LarpId();
	}
	
	
	
	public function RenderEditForm()
	{
		$larp = Q("SELECT * FROM larps WHERE id = ?", array($this->id))->fetch_assoc();
		
		$html = "";
		$config_form = new Zebra_Form('config_larp_form');
		$config_form->client_side_validation(true);
		
		// larpname
		$config_form->add('label', 'label_larpname', 'larpname', 'Lajvets namn');
		$obj = $config_form->add('text', 'larpname', $larp['name'],
				array('autocomplete' => 'off'));
		$obj->set_rule(
				array('required' => array('error',
								'Du måste skriva in ett namn.'),
						'alphanumeric' => array(' åäöÅÄÖ', 'error',
								'Får endast innehålla bokstäver, siffror och mellanrum.')));
		
		// shortname
		$config_form->add('label', 'label_short_name', 'short_name',
				'Kort namn. (OBS! Om du ändrar detta ändras URLen till lajvet.)');
		$obj = $config_form->add('text', 'short_name', $larp['short_name'],
				array('autocomplete' => 'off'));
		$obj->set_rule(
				array('required' => array('error',
								'Du måste skriva in ett kort namn.'),
						'alphanumeric' => array('_', 'error',
								'Får endast innehålla bokstäver, siffror och understreck.')));
		
		//Tagline
		$config_form->add('label', 'label_tagline', 'tagline',
				'Undertitel (Kommer visas tillsammans med lajvnamnet, där utrymme finns.)');
		$obj = $config_form->add('text', 'tagline', $larp['tagline'],
				array('autocomplete' => 'off'));
		$obj->set_rule(array('length' => array(0, 50, 'error',
								'Undertiteln får inte vara mer än 50 tecken.')));
		
		//Området
		$config_form->add('label', 'label_area_short', 'area_short',
				'Område (Kort beskrivning. Tex "Smålands skogar", "Ramhälls gruva", "Innom tunnelbanan i Stockholm")');
		$obj = $config_form->add('text', 'area_short', $larp['area_short'],
				array('autocomplete' => 'off'));
		$obj->set_rule(array('length' => array(0, 50, 'error',
								'Områdesbeskrivningen får inte vara mer än 50 tecken.')));
		
		//Datum-tid på området
		$config_form->add('label', 'label_date_onsite', 'date_onsite',
				'Datum då spelarna ska vara på området');
		$obj = $config_form->add('date', 'date_onsite', substr($larp['date_onsite'],0,10),
				array('autocomplete' => 'off'));		
		$obj->set_rule(	array('date' => array(0)));
		
		
		
		//Datum-tid game on
		$config_form->add('label', 'label_date_gameon', 'date_gameon',
				'Datum då lajvet börjar');
		$obj = $config_form->add('date', 'date_gameon', substr($larp['date_gameon'],0,10),
				array('autocomplete' => 'off'));
		$obj->set_rule(	array('date' => array(0)));
		
		
		//Datum-tid game off		
		$config_form->add('label', 'label_date_gameoff', 'date_gameoff',
				'Datum då lajvet slutar');
		$obj = $config_form->add('date', 'date_gameoff',substr($larp['date_gameoff'],0,10),
				array('autocomplete' => 'off'));
		$obj->set_rule(	array('date' => array(0)));
		
		//roller behöver godkännas
		$checked = (($larp['approve_characters']?"checked":0));		
		$config_form->add('label', 'label_approve_characters', 'approve_characters',
				'Roller behöver godkännas av en arrangör');
		$obj = $config_form->add('checkbox', 'approve_characters', 'yes',
				array($checked => $checked ,'autocomplete' => 'off'));
				
		
	
		
		
			
		
		// "submit"
		$config_form->add('submit', 'btnsubmit_create',	'Uppdatera Lajv');
		
		if ($config_form->validate())
		{
			$approve_characters = isset($_POST['approve_characters'])?"1":"0";
			
			Q("UPDATE larps SET name=?, 
								short_name=?, 
								area_short=?, 
								tagline=?, 
								date_onsite=?, 
								date_gameon=?,
								date_gameoff=?,
								approve_characters=?
								
									WHERE id=?", 
			array($_POST['larpname'], $_POST['short_name'], $_POST['area_short'], $_POST['tagline'], $_POST['date_onsite'], $_POST['date_gameon'], $_POST['date_gameoff'], $approve_characters, $this->id));
			
			SetSuccessMessage("Lajvet uppdaterat");		
			//TODO: reload this page
		}
		else
		{
			$html .= $config_form->render('', true);
		}
		return $html;
	}
	
	public function RenderCreateForm()
	{
		$html = "";
		$create_form = new Zebra_Form('new_larp_form');
		$create_form->client_side_validation(true);
		
		// larpname
		$create_form->add('label', 'label_larpname', 'larpname',
				'Lajvets namn');
		$obj = $create_form->add('text', 'larpname', '',
				array('autocomplete' => 'off'));
		$obj->set_rule(
				array(
						// error messages will be sent to a variable
						// called "error", usable in custom
						// templates
						'required' => array('error',
								'Du måste skriva in ett namn.'),
						'alphanumeric' => array(' åäöÅÄÖ', 'error',
								'Får endast inehålla bokstäver, siffror och mellanrum.')));
		
		// shortname
		$create_form->add('label', 'label_shortname', 'shortname',
				'Kort namn.');
		$obj = $create_form->add('text', 'shortname', '',
				array('autocomplete' => 'off'));
		$obj->set_rule(
				array(
						// error messages will be sent to a variable
						// called "error", usable in custom
						// templates
						'required' => array('error',
								'Du måste skriva in ett kort namn.'),
						'alphanumeric' => array('_', 'error',
								'Får endast inehålla bokstäver, siffror och understreck.')));
		
		// "submit"
		$create_form->add('submit', 'btnsubmit_create',
				'Skapa Lajv');
		
		if ($create_form->validate())
		{
			$larpname = ($_POST['larpname']);
			$shortname = ($_POST['shortname']);
			$id = Auth::Singleton()->id;
				
			Q("INSERT INTO forms () VALUES ()", array());
			$in_form_id = SQLInsertId();
			Q("INSERT INTO forms () VALUES ()", array());
			$off_form_id = SQLInsertId();
				
			Q("INSERT INTO larps (name, short_name, created, creator_id, article_id, character_form_id, user_form_id) VALUES (?, ?, NOW(), ?, 0, ?, ?)", array($larpname, $shortname, $id, $in_form_id, $off_form_id));
			$larp_id = SQL::S()->InsertId();
				
			Q("INSERT INTO articles (creator_id, larp_id) VALUES (?, ?)", array($id, $larp_id));
			$article_id = SQL::S()->InsertId();
				
			Q("INSERT INTO groups (name, larp_id, creator_id, created, type, guarded, members_add_article, members_edit_readflags) VALUES ('Alla', ?, ?, NOW(), 'OFF', 0, 0, 0)", array($larp_id, $id));
			$all_id = SQL::S()->InsertId();			
			
			Q("UPDATE larps SET article_id=?, all_group_id=? WHERE id=?", array($article_id, $all_id, $larp_id));
				
			$content = "<h3>Välkommen till lajvet $larpname.</h3><br/>för att ändra detta välkomstmeddelande: klicka på Redigera Artikel.";
			
			Q("INSERT INTO article_content (article_id, author_id, created, content, title, significant_change, version) VALUES (?, ?, NOW(), ?, ?, 1, '0.0')", array($article_id, $id, $content, $larpname));
				
				
			Q("INSERT INTO article_in_group (article_id, group_id, official, access, `read`, edit, request, publisher_id, time) VALUES (?, 0, 0, 'PRIVATE', 'NONE', 'NONE', 0, ?, NOW())", array($article_id, $id));
				
				
			Q("INSERT INTO user_attending_larp (user_id, larp_id, joined, organizer) VALUES (?, ?, NOW(), 1)", array($id, $larp_id));
				
				
			$html .= "<div>Ditt lajv är nu skapat!</div>";
				
		}
		else
		{
			$html .= $create_form->render('', true);
		}
		return $html;
	}
	
	
	
}


?>