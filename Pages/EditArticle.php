<?php

require_once("Articles.php");

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
		$html = "";
		$redirect = "";
				
		$article_id = $Args[0];
		$Article = Articles::Singleton()->GetArticle($article_id);
		$Article->UpdateContent();
		
		if(isset($Args[1]) && $Args[1] == "DELETE")
		{
			$Article->Delete();
			SetSuccessMessage("Artikeln är nu raderad");
			SetRedirect(GetPageUrl("ViewArticles"));
			return;
		}
		
		if(!$Article->Edit)
		{
			SetRedirect(GetPageUrl("ViewArticle", $article_id));
			SetFailMessage("Du har inte tillåtelse att redigera denna artickel.");
			
			$Data = array();
			$Data['HTML'] = $html;
			$Data['TITLE'] = "Skapa Artikel";
			return $Data;
		}
		
		$html = "";
		
		$edit_form = new Zebra_Form('create_article_form');
		$edit_form->client_side_validation(true);
		//$edit_form->form_properties['attributes']['class'] = "";
		
		// title
		$edit_form->add('label', 'label_title', 'title', 'Titel');
		$obj = $edit_form->add('text', 'title', $Article->Title, 
				array('autocomplete' => 'off'));
		$obj->set_rule(
				array(
						// error messages will be sent to a variable called
						// "error", usable in custom templates
						'required' => array('error', 
								'Du måste skriva in en titel.')));
		
		
		// content
		$edit_form->add('label', 'label_content', '', 'Länkar:<br> [R[0]] skapar en länk till rollen med id 0 <br> [U[0]] skapar en länk till användaren med id 0 <br>  [G[0]] skapar en länk till gruppen med id 0<br>  [A[0]] skapar en länk till artikeln med id 0');
		$obj = $edit_form->add('textarea', 'content', $Article->RawContent, 
				array('autocomplete' => 'off', 'class' => ''));
		$obj->set_attributes(array("class" => "nicEditTextArea"));
		
		// Open
		$edit_form->add('label', 'label_significant', 'significant', 'Stor förändring (de som läst artikeln måste läsa om den)');
		$obj = $edit_form->add('checkbox', 'significant', 'yes',
				array('autocomplete' => 'off'));
				
		// "submit"
		$edit_form->add('submit', 'btnsubmit_edit', 'Spara Artikel');
		
		if ($edit_form->validate())
		{
			$title = ($_POST['title']);
			$content = html_entity_decode($_POST['content']);			
			$id = Auth::Singleton()->id;
			$larp_id = Auth::Singleton()->LarpId();
			$significant = ($_POST['significant']=="yes"?"1":"0");
			
			
			$split = explode(".", $Article->Version);
			if($significant)
			{
				$split[0]++;
				$split[1] = 0;
			}
			else
				$split[1]++;
			$version = $split[0] . "." . $split[1];
			
			Q("INSERT INTO article_content (article_id, author_id, created, content, title, significant_change, version) VALUES(?, ?, NOW(), ?, ?, ?, ?)",
			array($article_id, $id,$content, $title, $significant, $version));
			
			Q("INSERT INTO user_read_article (user_id, article_id, time) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE time=NOW()",
			array($id, $article_id));
								
			
			SetRedirect(GetPageUrl("ViewArticle", $article_id));
			SetSuccessMessage("Din artikel är nu uppdaterad!");
		}
		else
		{
			$html .= PermissionsForm::RenderArticleForm($article_id);
			$html .= $edit_form->render('', true);
		}

		
			
		$delete_url = GetPageUrl("EditArticle", $article_id, "DELETE");
		$html .= "<div class=section><input type=submit value='Ta bort Artikeln' onclick=\"if(confirm('Är du säker på att du vill ta bort artikeln {$Article->Title}?')) {window.location.href='$delete_url'}\"></div>";
	
			
		
		$Script = "<script type='text/javascript' src='/nicEdit/nicEdit.js'></script>
		<script type='text/javascript'>
		bkLib.onDomLoaded(function() {
			nicEditors.allTextAreas() });
			</script>";
				
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = "Redigera Artikel";
		$Data['SCRIPT'] = $Script;		
		$Data['DONT_REPLACE_LINKS'] = true;
		
		return $Data;
	}
		
}

?>