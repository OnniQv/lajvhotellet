<?php

require_once ("Articles.php");
require_once ("Groups.php");

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
		if (! isset($Args[0]))
			die("404 need to know what group to look at");
		
		$html = "";
		$group_id = $Args[0];
		$larp_shortname = Auth::Singleton()->LarpShortName();
		$user_id = Auth::Singleton()->id;
		$larp_id = Auth::Singleton()->LarpId();
		
		$all_id = Auth::Singleton()->LarpValue('all_group_id');
					
		$Group = Groups::Singleton()->GetGroup($group_id);

		if (isset($Args[1]) && $Args[1]=="DELETE")
		{
			$Name = $Group->Name;
			$Group->Delete();
			SetRedirect(GetPageUrl("ViewGroups"));
			SetSuccessMessage("Gruppen '$Name' är nu raderad");
			return;
		}
		
		if(!$Group->KnowOf)
		{
			$Data = array();
			$Data['HTML'] = "<div class=section>Du har inte tillgång till denna grupp.</div>";
			$Data['TITLE'] = Auth::Singleton()->LarpName();
			return $Data;
		}
		
		//Rendera tidigt, så uppdateringarna syns nedan
		if($Group->Admin)
		{
			$form = "<span class=section_title>Ändra inställningar</span><div class=section>";
			$form .= Groups::Singleton()->RenderEditForm($group_id);
			$form .= "</div>";
		}
		
			
				
		$html .= "<span class=section_title>{$Group->Name}</span><div class=section>";
		
		if($group_id == $all_id)
		{
			$html .= "<br>Gruppen 'Alla' finns automatiskt med i varje lajv. Lajvets alla Deltagare och Roller är automatiskt medlemmar i denna grupp.<br>";
			$html .= "</div>";
		}
		else
		{
			$html .= "({$Group->Type})<br>";
			
			if($Group->Creator)
				$html .= "(Skapare)<br>";
			else if($Group->Admin)
					$html .= "(Administratör)<br>";			
			else if($Group->Member)
				$html .= "(Medlem)<br>";
			else if($Group->KnowOf)
					$html .= "(Besökare)<br>";
			
			if(Auth::Singleton()->LoggedIn())
			{
			
				if($Group->Type == "IN")
				{
					$Characters = Characters::Singleton()->GetAll();
					$Characters = $Characters["mine"];
					
					$CharacterIds = array();
					foreach($Characters as $c)
						$CharacterIds[] = $c['id'];
					$CharacterIdString = implode(",", $CharacterIds);
					$statuses = array();
					if($CharacterIdString != "")
					{			
						$res = Q("SELECT character_id, status, request FROM g_character_status_in_group WHERE character_id IN ($CharacterIdString) AND group_id = ?", array($group_id));
						while($assoc = $res->fetch_assoc())
							$statuses[$assoc['character_id']] = $assoc;
					}	
					if(count($CharacterIds) == 0)
						$html .= "Du måste ha minst en Roll för att gå med i en Inlajv grupp.";	
					foreach($CharacterIds as $cid)
					{			
						$html .= "<nobr>[R[$cid]] ";
						if(isset($statuses[$cid]))  //This character have some sort of status
						{
							switch($statuses[$cid]['request'])
							{
								case "NONE":	if($statuses[$cid]['status'] == "ADMIN")
													$html .= "Denna Roll är Admin i denna grupp.";
												else if($statuses[$cid]['status'] == "MEMBER")
												{
													$html .= "Denna Roll är medlem i denna grupp.";
													$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$cid&type=C&group_id=$group_id&status=ADMIN&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Ansök om att bli Admin'></form>";
												}
												else if($statuses[$cid]['status'] == "KNOW")
												{
													$html .= "Denna Roll är är besökare i denna grupp.";
													if($Group->Guarded)
														$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$cid&type=C&group_id=$group_id&status=MEMBER&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Ansök om att bli Medlem'></form>";
													else
														$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$cid&type=C&group_id=$group_id&status=MEMBER&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Bli Medlem'></form>";
													$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$cid&type=C&group_id=$group_id&status=ADMIN&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Ansök om att bli Admin'></form>";
												}
												break; 
									break;
								case "JOIN": 
												if($statuses[$cid]['status'] == "ADMIN")
													$html .= "Denna Roll har ansökt om att bli Admin i denna grupp.";
												else
												{
													$html .= "Denna Roll har ansökt om medlemskap i denna grupp.";
													$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$cid&type=C&group_id=$group_id&status=ADMIN&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Ansök om att bli Admin'></form>";
												}
												break;
									
								case "INVITE":
												if($statuses[$cid]['status'] == "ADMIN")
													$html .= "Denna Roll är inbjuden som Admin i denna grupp. Titta på dina meddelanden för att svara.";
												else
												{
													$html .= "Denna Roll är inbjuden som Medlem i denna grupp. Titta på dina meddelanden för att svara.";
													$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$cid&type=C&group_id=$group_id&status=ADMIN&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Ansök om att bli Admin'></form>";
												}
												
												break;
													
							}
							
							$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$cid&type=C&group_id=$group_id&status=0&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Lämna gruppen'></form>";
						}
						else  //This character has no status
						{
							if($Group->Guarded)
									$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$cid&type=C&group_id=$group_id&status=MEMBER&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Ansök om att bli Medlem'></form>";
								else
									$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$cid&type=C&group_id=$group_id&status=MEMBER&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Bli Medlem'></form>";
								$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$cid&type=C&group_id=$group_id&status=ADMIN&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Ansök om att bli Admin'></form>";
						}
						$html .= "</nobr><br>";
					}
					
					
				}
				else
				{
					if(!$Group->Member)
					{
						if($Group->Guarded)						
							$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$user_id&type=U&group_id=$group_id&status=MEMBER&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Ansök om att bli Medlem'></form>";
						else							
							$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$user_id&type=U&group_id=$group_id&status=MEMBER&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Bli Medlem'></form>";
					}
					else
					{
						if($Group->Creator)
							$html .= "Du skapade denna grupp och kan inte gå ur den.";
						else
							$html .= "<form method=post action='/AjaxApi.php?command=UpdateGroupMember&id=$user_id&type=U&group_id=$group_id&status=0&larp_shortname=$larp_shortname&join_invite=JOIN&notification_respons=0&return_url=/$larp_shortname/ViewGroup/$group_id'><input type=submit value='Lämna gruppen'></form>";
					}
				}
			}
			
				
			
				
			$html .= "</div>";
			
			$PrivateArticle = Articles::Singleton()->GetArticle($Group->PrivateArticle);
			$PublicArticle = Articles::Singleton()->GetArticle($Group->PublicArticle);
						
					
			$html .= "<span class=section_title>Publik artikel</span><div class=section>" . RenderArticleLinkObject($PublicArticle) . "</div>";		
			if($Group->Member)
			{
				$html .= "<span class=section_title>Privat artikel</span><div class=section>" . RenderArticleLinkObject($PrivateArticle) . "</div>";
			}
		}
				
		
		//Articles
		$Articles = Articles::Singleton()->GetAllInGroup($group_id);		
		$html .= "<span class=section_title>Artiklar tillgängliga för besökare</span><div class=section>";
		foreach($Articles[1] as $Article)
			$html .= RenderArticleLinkObject($Article) . "<br>";
		$html .= "</div>";
		
		if($Group->Member)
		{
			$html .= "<span class=section_title>Privata Artiklar</span><div class=section>";
			foreach($Articles[2] as $Article)
				$html .= RenderArticleLinkObject($Article) . "<br>";	
			$html .= "</div>";
		}
		
		//Members
		
		if($group_id == $all_id)
		{
			
		}
		else
		{
			
			$Members = $Group->GetMembers();		
			$html .= "<span class=section_title>De som känner till denna grupp</span><div class=section>";
			foreach($Members as $Member)
				if($Member['status'] == "KNOW")
				{
					switch($Member['type'])
					{
						case "user": $html .= RenderUserLink($Member['id']);break;
						case "character": $html .= RenderCharacterLink($Member['id']);break;
						case "group": $html .= RenderGroupLink($Member['id']);break;
					}
					$html .= " ";
				}
				$html .= "</div>";
			$html .= "<span class=section_title>Medlemmar</span><div class=section>";
			foreach($Members as $Member)
				if($Member['status'] != "KNOW")
				{
					switch($Member['type'])
					{
						case "user": $html .= RenderUserLink($Member['id']);break;
						case "character": $html .= RenderCharacterLink($Member['id']);break;
						case "group": $html .= RenderGroupLink($Member['id']);break;
					}
					if($Member['status'] == "ADMIN")
					{
						if($Member['type'] == "user" && $Member['id'] == $Group->Creator_id)
							$html .= "(Skapare)";
						else
							$html .= "(Admin)";
					}					
					$html .= " ";	
				}
			if($Group->Admin)
				$html .= "<br><br>".PermissionsForm::RenderGroupForm($group_id);
			$html .= "</div>";
		}
			
		if($Group->Admin)
		{
			//Edit Group form
			$html .= $form;
			$delete_url = GetPageUrl("ViewGroup", $group_id, "DELETE");
			$html .= "<div class=section><input type=submit value='Ta bort Gruppen' onclick=\"if(confirm('Är du säker på att du vill ta bort gruppen {$Group->Name}?')) {window.location.href='$delete_url'}\"></div>";
		}
		
			
		$Data = array();
		$Data['HTML'] = $html;
		$Data['TITLE'] = $Group->Name;
		return $Data;
	}
}

?>