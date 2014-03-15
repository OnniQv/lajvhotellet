<?php

class Page   extends BasicPage
{

	public function RequireLoggedIn ()
	{
		return true;
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
		
		$html = "Skapa artikel i " . Auth::Singleton()->LarpName();
		$redirect="";
		
		$create_form = new Zebra_Form('new_article_form');
		$create_form->client_side_validation(true);
		
		// title
		$create_form->add('label', 'label_title', 'title', 'Titel');
		$obj = $create_form->add('text', 'title', '', 
				array('autocomplete' => 'off'));
		$obj->set_rule(
				array(
						// error messages will be sent to a variable called
						// "error", usable in custom templates
						'required' => array('error', 
								'Du måste skriva in en titel.')));
		
			
		// content
		//$create_form->add('label', 'label_shortname', 'content', '');
		$obj = $create_form->add('textarea', 'content', '', 
				array('autocomplete' => 'off'));
		
		// "submit"
		$create_form->add('submit', 'btnsubmit_write', 'Spara Artikel');
		
		if ($create_form->validate())
		{
			$title = ($_POST['title']);
			$content = ($_POST['content']);			
			$id = Auth::Singleton()->id;
			$larp_id = Auth::Singleton()->LarpId();
				
			
			Q("INSERT INTO articles (creator_id, larp_id) VALUES (?,?)", array($id, $larp_id));
			$article_id = SQL::S()->InsertId();
			Q("INSERT INTO article_content (article_id, author_id, created, content, title, significant_change, version)
											VALUES(?, ?,     NOW(), ?, ?, true, '0.0')",
											array($article_id, $id, $content, $title)	);											
			
			setrawcookie(GetPageUrl("ViewArticle", $article_id));
			SetSuccessMessage("Din artikel är nu skapad!");
		}
		else
		{
			$html .= $create_form->render('', true);
		}
		
		$Script = "<script type='text/javascript' src='/nicEdit/nicEdit.js'></script>
					<script type='text/javascript'>
					bkLib.onDomLoaded(function() {
						nicEditors.allTextAreas() });
						</script>";
		
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = "Skapa Artikel";
		$Data['SCRIPT'] = $Script;
		return $Data;
	}

}

?>