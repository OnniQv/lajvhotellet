<?php

require_once("Characters.php");

class PermissionsForm
{
	public static function RenderArticleForm($article_id)
	{
		return PermissionsForm::RenderForm($article_id, null, null);
	}

	public static function RenderGroupForm($group_id)
	{
		return PermissionsForm::RenderForm(null, $group_id, null);
	}
	
	public static function RenderCharacterForm($character_id)
	{
		return PermissionsForm::RenderForm(null, null, $character_id);
	}
	
	
	
	private static function RenderForm($article_id, $group_id, $character_id)
	{
		$html = "";
		
		
		
		$render_article = $article_id !== null;
		$render_group = $group_id !== null;
		$render_character = $character_id !== null;
		
		
		
		$larp_shortname = Auth::Singleton()->LarpShortName();
		$larp_id = Auth::Singleton()->LarpId();
		
		$all_id = Auth::Singleton()->LarpValue("all_group_id");
		
		if($render_article)
		{
			$html .= "<div id=Permissions class=a>";
			$html .= "<div id=groups></div>";
		}
		else if($render_group)
		{
			$html .= "<div id=Permissions class=Permissions>";		
			$html .= "<div><a OnClick=\"document.all.Permissions.style.visibility='hidden'\">[X]</a></div>";
			$html .= "<div id=viewers></div>";
			$html .= "<div id=members></div>";
			$html .= "<div id=admins></div>";
		}
		else// $render_character
		{
			$html .= "<div id=Permissions class=Permissions>";
			$html .= "<div><a OnClick=\"document.all.Permissions.style.visibility='hidden'\">[X]</a></div>";
			$html .= "<div id=know_of></div>";
			$html .= "<div id=know_well></div>";
			
		}

		
		$html .= "<br>Filtrera: <input id='search' name='search' oninput='Update()'>";
		
		$html .= "<div id=search_results style=\"overflow-y:scroll\"></div>";
		
		$html .= "</div>";
		
		if($render_group)
			$html .= "<input  type='button'  value=' Redigera Medlemslistan '  id='Button1'  name='Button1'  onclick=\"document.all.Permissions.style.visibility='visible'\">";
		else if($render_character)
			$html .= "<input  type='button'  value=' Redigera Bekantskaper '  id='Button1'  name='Button1'  onclick=\"document.all.Permissions.style.visibility='visible'\">";

		
		
		$Data = array();
		

		if($render_article)
		{
			$GroupsAll = Groups::Singleton()->GetAll();
			$res = Q("SELECT g.id, g.name, a.official, a.access, a.read, a.edit, a.request FROM article_in_group as a LEFT JOIN groups as g ON a.group_id = g.id WHERE article_id=?", array($article_id));
			
			$AIG = array();
			while($assoc = $res->fetch_assoc())			
				$AIG[$assoc["id"]] = $assoc;			
				
			$Groups = array();
			foreach($GroupsAll[1] as $gid => $Group)
				$Groups[$gid] = $Group;
			foreach($GroupsAll[2] as $gid => $Group)
				$Groups[$gid] = $Group;
			foreach($GroupsAll[3] as $gid => $Group)
				$Groups[$gid] = $Group;
			
			
			
			foreach($Groups as $gid => $assoc)
			{
				if(isset($AIG[$gid]))
				{
					$assoc = $AIG[$gid];					
					if($assoc["request"] == 0)
						$assoc["request"] = "";
					else
						$assoc["request"] = "Förfrågan";
				}
				else
				{
					$assoc = array("access" => 0, "id" => $gid, "name" => $assoc["name"], "official" => 0, "read" => 0, "edit" => 0, "request" => "");
				}	
				
				if($assoc["official"] == 1)
					$Data = array();
				
				$Data[] = array("Type" => "G", "Name" => $assoc["name"], "Html" => RenderGroupLink($assoc["id"], $assoc["name"]), "Id" => $assoc["id"], "Official" => $assoc["official"], "Read" => $assoc["read"], "Edit" => $assoc["edit"], "Access" => $assoc["access"], "Request" => $assoc['request']);
				
				if($assoc["official"] == 1)
					break;
			}
		}
		else if($render_group)
		{	
			list($group_type, $creator_id) = Q("SELECT type, creator_id FROM groups WHERE id=?", array($group_id))->fetch_array();
			
			if($group_type == "OFF")
			{
				
				//Left JOIN, cause we want all users
				$res = Q("SELECT u.full_name, u.id, g.status, g.request FROM users as u LEFT JOIN g_user_status_in_group as g ON u.id = g.user_id AND g.group_id=?", array($group_id));
						
				while($assoc = $res->fetch_assoc())
				{			
					if($assoc['id'] == $creator_id)
						continue;
					
					switch($assoc["status"])
					{
						case "": $status = 0; break;
						case "KNOW": $status = 1; break;
						case "MEMBER": $status = 2; break;
						case "ADMIN": $status = 3; break;			
					}				
					switch($assoc["request"])
					{
						case "": $request = ""; break;
						case "NONE": $request = ""; break;
						case "JOIN": $request = "Begärd"; break;
						case "INVITE": $request = "Inbjuden"; break;
					}
					$Data[] = array("Type" => "U", "Name" => $assoc["full_name"], "Status" => $status, "Id" => $assoc["id"], "Html" => RenderUserLink($assoc["id"], $assoc["full_name"]), "Request" => $request);
				}
			}
			else 
			{
				$Characters = array();
				$res = Q("SELECT characters.* FROM characters JOIN character_partof_larp ON characters.id=character_partof_larp.character_id AND character_partof_larp.larp_id=?", array($larp_id));
				while($assoc = $res->fetch_assoc())
					$Characters[$assoc['id']] = Characters::Singleton()->GetCharacter($assoc['id']);
				
	
				$UsedChars = array();			
				//NOT Left JOIN, cause we want only users with permission in group	
				$res = Q("SELECT c.name, c.id, g.status, g.request FROM characters as c JOIN g_character_status_in_group as g ON c.id = g.character_id AND g.group_id=?", array($group_id));
				while($assoc = $res->fetch_assoc())
				{
					if(!$Characters[$assoc['id']]->KnowOf)
					{
						continue;
					}
					switch($assoc["status"])
					{
						case "": $status = 0; break;
						case "KNOW": $status = 1; break;
						case "MEMBER": $status = 2; break;
						case "ADMIN": $status = 3; break;
					}
					switch($assoc["request"])
					{
						case "": $request = ""; break;
						case "NONE": $request = ""; break;
						case "JOIN": $request = "Begärd"; break;
						case "INVITE": $request = "Inbjuden"; break;
					}
					$UsedChars[$assoc['id']] = true;  
					$Data[] = array("Type" => "C", "Name" => $assoc["name"], "Status" => $status, "Id" => $assoc["id"], "Html" => RenderCharacterLink($assoc["id"], $assoc["name"]), "Request" => $request);
				}
				foreach($Characters as $c)
					if(!isset($UsedChars[$c->id]))
						$Data[] = array("Type" => "C", "Name" => $c->name, "Status" => 0, "Id" => $c->id, "Html" => RenderCharacterLink($c->id), "Request" => "");
				
			}
			$GroupsAll = Groups::Singleton()->GetAll();			
			$res = Q("SELECT gr.name, gr.id, g.status, g.request, gr.type FROM groups AS gr RIGHT JOIN g_group_status_in_group AS g ON gr.id = g.viewer_id AND g.group_id=?", array($group_id));
			$GIG = array();
			while($assoc = $res->fetch_assoc())
			{				
				$GIG[$assoc["id"]] = $assoc;				
			}
					
			$Groups = array();
			foreach($GroupsAll[1] as $gid => $Group)
				if($group_type == $Group["type"] || $Group["id"] == $all_id)
					$Groups[$gid] = $Group;
			foreach($GroupsAll[2] as $gid => $Group)
				if($group_type == $Group["type"] || $Group["id"] == $all_id)
					$Groups[$gid] = $Group;
			foreach($GroupsAll[3] as $gid => $Group)
				if($group_type == $Group["type"] || $Group["id"] == $all_id)
					$Groups[$gid] = $Group;
				
			foreach($Groups as $gid => $Group)
			{
				
				
				if(isset($GIG[$gid]))
				{
					switch($GIG[$gid]["status"])					
					{	
						case "KNOW": $status = 1; break;
						case "MEMBER": $status = 2; break;
						case "ADMIN": $status = 3; break;
					}
					switch($GIG[$gid]["request"])
					{
						case "NONE": $request = ""; break;
						case "JOIN": $request = "Begärd"; break;
						case "INVITE": $request = "Inbjuden"; break;
					}
						
				}
				else
				{
					$status = 0;
					$request = "";
				}
							
				if($gid == $group_id)
					continue;
				$Data[] = array("Type" => "G", "Name" => $Group["name"], "Status" => $status, "Id" => $gid, "Html" => RenderGroupLink($gid, $Group["name"]), "Request" => $request);
			}
			AddDebug(print_r($Data, true));

		}
		else//$render_character
		{			
			$res = Q("SELECT c.name, c.id, k.know
						FROM character_know_character AS k
						RIGHT JOIN characters AS c ON c.id = k.viewer_id
						AND k.character_id = ?
						JOIN character_partof_larp ON character_partof_larp.character_id = c.id
						AND character_partof_larp.larp_id =?", array($character_id, $larp_id));
			
			while($assoc = $res->fetch_assoc())
			{
				if($assoc["id"] == $character_id)
					continue;
				$status = 0;
				switch($assoc["know"])
				{					
					case "OF": $status = 1; break;
					case "WELL": $status = 2; break;					
				}
			
				$Data[] = array("Type" => "C", "Name" => $assoc["name"], "Status" => $status, "Id" => $assoc["id"], "Html" => RenderCharacterLink($assoc["id"], $assoc["name"]));
			}
			
			
			
			$GKC = array();		
			$GroupsAll = Groups::Singleton()->GetAll();
			$res = Q("SELECT gr.name, gr.id, k.know, gr.type FROM groups AS gr LEFT JOIN group_know_character AS k ON gr.id = k.group_id AND k.character_id=?", array($character_id));
			while($assoc = $res->fetch_assoc())
				$GKC[$assoc["id"]] = $assoc;				
			$Groups = array();
			foreach($GroupsAll[1] as $gid => $Group)
				if("IN" == $Group["type"] || $Group['id'] == $all_id)
					$Groups[$gid] = $Group;
			foreach($GroupsAll[2] as $gid => $Group)
				if("IN" == $Group["type"] || $Group['id'] == $all_id)
					$Groups[$gid] = $Group;
			foreach($GroupsAll[3] as $gid => $Group)
				if("IN" == $Group["type"] || $Group['id'] == $all_id)
					$Groups[$gid] = $Group;
			
			foreach($Groups as $gid => $Group)
			{
				$status = 0;
				if(isset($GKC[$gid]))
				{
					switch($GKC[$gid]["know"])
					{
						case "OF": $status = 1; break;
						case "WELL": $status = 2; break;
					}
				}
				
					
				$Data[] = array("Type" => "G", "Name" => $Group["name"], "Status" => $status, "Id" => $gid, "Html" => RenderGroupLink($gid, $Group["name"]));
			}
			
		}
		
		$html .= "<script  language='javascript'>";
		
		$html .= "var Data=new Array();";					
					
					$i = 0;
					$official = 0;
					foreach($Data as $D)
					{
						if(!isset($D['Status']))
							$D['Status'] = 0;
						if(!isset($D['Official']))
							$D['Official'] = 0;						
						if(!isset($D['Read']))
							$D['Read'] = 0;
						if(!isset($D['Edit']))
							$D['Edit'] = 0;
						if(!isset($D['Access']))
							$D['Access'] = 0;	
						if(!isset($D['Request']))
							$D['Request'] = 0;
						
						$D['Html'] = str_replace("'", "\\'", $D['Html']);
						
						$html .= "\r\n Data[$i]= {name:'{$D['Name']}', status:'{$D['Status']}', type:'{$D['Type']}' , id:'{$D['Id']}', html:'{$D['Html']}', official:'{$D['Official']}', read:'{$D['Read']}', edit:'{$D['Edit']}', access:'{$D['Access']}', saved:'1', request:'{$D['Request']}' };";
						if($D['Official'] == 1)
							$official = 1;
						$i++;
					}
		
					$html .= "var Official = $official;";
					
		if($render_article)
		{
			$html .= "
				function Edit(key, access, read, edit)
				{
					switch(access)
					{
						case 0: Data[key].access = '0' ; break;
						case 1: Data[key].access = 'PUBLIC'; break;
						case 2: Data[key].access = 'PRIVATE'; break; 
					}
					switch(read)
					{
						case 0: Data[key].read = 'NONE'; break;
						case 1: Data[key].read = 'SHOULD'; break;
						case 2: Data[key].read = 'MUST'; break;
					}
					switch(edit)
					{
						case 0: Data[key].edit = 'NONE'; break;
						case 1: Data[key].edit = 'VIEWERS'; break;
						case 2: Data[key].edit = 'MEMBERS'; break;
						case 3: Data[key].edit = 'ADMINS'; break;
					}
					";
		}
		else if($render_group ||  $render_character)
		{
			$html .= "	
				function Edit(key, status)
				{
					Data[key].status = status;";
		}
		
		$html .= "
										
					Data[key].saved = '0';
					Update();
					";
					
		
		if($render_article)		
			$html .= "$.get('/AjaxApi.php?command=UpdateGroupArticle&id=$article_id&access=' + Data[key].access + '&read=' + Data[key].read + '&edit=' + Data[key].edit  + '&group_id=' + Data[key].id + '&larp_shortname=$larp_shortname&notification_respons=0', function(respons){";
		else if($render_group)
			$html .= "$.get('/AjaxApi.php?command=UpdateGroupMember&type=' + Data[key].type + '&id=' + Data[key].id + '&status=' + Data[key].status + '&group_id=$group_id&larp_shortname=$larp_shortname&join_invite=INVITE&notification_respons=0', function(respons){";
		else
			$html .= "$.get('/AjaxApi.php?command=UpdateCharacterKnower&character_id=$character_id&knower_id=' + Data[key].id + '&knower_type=' + Data[key].type + '&know=' + Data[key].status + '&larp_shortname=$larp_shortname', function(respons){";
		
		$html .= "
					  if(respons == 'JOIN')
						  respons = 'Förfrågan';			
					  else if(respons == 'NONE')
						  respons = '';				
					  else if(respons == 'INVITE')
						  respons = 'Inbjuden';				
				      else if(respons == 'Flagg-forfragan')
						  respons = 'Flagg-förfrågan';
				      else if(respons == '')
					  {
					  }
					  else
						  alert(respons);
						
				
					  Data[key].saved='1';
					  Data[key].request=respons;
					  Update();
					}).error(function(respons) { alert('Error:'+respons); });
					
				}
		
				function Render(key)
				{
					var item = Data[key];
						
					var html = '<span style=\"overflow:visible\">' + item.html;
										
					if(Data[key].saved == '0')
					{
						html += '<img src=\"/img/wait.gif\">';
					}
					else
					{";
				
		if($render_article)
			$html .= "		
						if(Data[key].access != '0')
						{
							if(Data[key].request != '')
								html += '<a title=\"Denna grupp tillåter inte medlemmar att lägga till artiklar, därför måste en administratör godkänna den först.\">('+Data[key].request+')</a>';
				
							var Access = 0;
							var AccessText = '';
							var AccessAlt = '';
							if(Data[key].access == 'PUBLIC')
							{
								Access = 1;
								AccessText = 'Publik';
								AccessAlt = 'Denna artickel är synlig även för gruppens besökare.';
							}
							if(Data[key].access == 'PRIVATE')
							{
								Access = 2;
								AccessText = 'Privat';	
								AccessAlt = 'Denna artickel är endast synlig för gruppens medlemar.';
							}
							var Read = 0;
							var ReadText = 'Inget läskrav';
							var ReadAlt = 'Det finns inget krav på att läsa denna artickel.';
							if(Data[key].read == 'SHOULD')
							{
								Read = 1;
								var ReadText = 'Borde Läsa';
								var ReadAlt = 'Alla medlemmar i denna grupp borde läsa artickeln.';
							}
							if(Data[key].read == 'MUST')
							{
								Read = 2;
								var ReadText = 'Måste Läsa';
								var ReadAlt = 'Alla medlemmar i denna grupp måste läsa artickeln';
							}
							var Edit = 0;
							var EditText = 'Ingen';
							var EditAlt = 'Ingen, utom articklens ägare och arrangörerna, kan redigera denna artickel.';
							if(Data[key].edit == 'VIEWERS')
							{
								Edit = 1;
								EditText = 'Alla';
								EditAlt = 'Alla som kan se denna artickel kan redigera den';
							}		
							if(Data[key].edit == 'MEMBERS')
							{
								Edit = 2;
								EditText = 'Medlemmar';
								EditAlt = 'Medlemmar i denna grupp kan redigera denna artickel.';
							}
							if(Data[key].edit == 'ADMINS')
							{
								Edit = 3;
								EditText = 'Administratörer';
								EditAlt = 'Administratörer i denna grupp kan redigera denna artickel.';
							}
							
							var NextAccess = Access==1?2:1;
							var NextRead = (Read+1)%3;
							var NextEdit = (Edit+1)%4;
				
							if(Data[key].official == 0)
							{
								html +=  '<input type=submit value=\" - \" title=\"Ta bort denna artikel från gruppen.\" onclick=\"Edit('+key+',0,0,0)\">';
								if(Data[key].id != 0)
									html +=  '<a title=\"'+AccessAlt+'\">'+AccessText+'</a>' + '<input type=submit value=\"X\" title=\"Ändra vem som får se artikeln.\" onclick=\"Edit('+key+','+(NextAccess)+','+(Read)+','+(Edit)+')\">';
							}
							else
							{
								html +=  ' <a title=\"'+AccessAlt+'\">('+AccessText+' Gruppartikel)</a> '
							}
					
							
							html +=  '<a title=\"'+ReadAlt+'\">'+ReadText+'</a>'     + '<input type=submit value=\"X\" title=\"Ändra läskraven\" onclick=\"Edit('+key+','+(Access)+','+(NextRead)+','+(Edit)+')\">';
							html +=  '<a title=\"'+EditAlt+'\">'+EditText+'</a>'     + '<input type=submit value=\"X\" title=\"Ändra redigerarättigheterna\" onclick=\"Edit('+key+','+(Access)+','+(Read)+','+(NextEdit)+')\">';		
				
						}  	
						else
						{
							html +=  '<input type=submit value=\"+\" title=\"Lägg till denna artikel till denna grupp.\" onclick=\"Edit('+key+',1,0,0)\">';
						}			
				";		
		else if($render_group)		
			$html .= "
						if(Data[key].request != '' && Data[key].status != '0')
								html += '<a title=\"Detta är ej godkänt än\">('+Data[key].request+')</a>';
				
						if(Data[key].status != '1')
							html +=  '<input type=submit value=\"B\" title=\"Tillåt att besöka gruppen och läsa dess publika artiklar.\" onclick=\"Edit('+key+',1)\">';
						else
							html +=  '<input type=submit value=\"B\" title=\"Tillåt att besöka gruppen och läsa dess publika artiklar.\" disabled>';
						if(Data[key].status != '2')
							html +=  '<input type=submit value=\"M\" title=\"Gör till medlem i gruppen.\" onclick=\"Edit('+key+',2)\">';
						else
							html += '<input type=submit value=\"M\" title=\"Gör till medlem i gruppen.\" disabled>';
						if(Data[key].status != '3')
							html +=  '<input type=submit value=\"A\" title=\"Gör till administratör i gruppen.\" onclick=\"Edit('+key+',3)\">';
						else
							html += '<input type=submit value=\"A\" title=\"Gör till administratör i gruppen.\" disabled>';
						if(Data[key].status != '0')
							html +=  '<input type=submit value=\"X\" title=\"Ta bort rättighet.\" onclick=\"Edit('+key+',0)\">';
						else
							html += '<input type=submit value=\"X\" title=\"Ta bort rättighet.\" disabled>'";
		else //$render_character
			$html .= "
						if(Data[key].status != '1')
							html +=  '<input type=submit value=\"K\" title=\"Tillåt denna roll eller grupp att känna till rollen.\" onclick=\"Edit('+key+',1)\">';
						else
							html += '<input type=submit value=\"K\" title=\"Tillåt denna roll eller grupp att känna till rollen.\" disabled>';
						if(Data[key].status != '2')
							html +=  '<input type=submit value=\"V\" title=\"Tillåt denna roll eller grupp att känna rollen väl.\" onclick=\"Edit('+key+',2)\">';
						else
							html += '<input type=submit value=\"V\" title=\"Tillåt denna roll eller grupp att känna rollen väl.\" disabled>';
						if(Data[key].status != '0')
							html +=  '<input type=submit value=\"X\" title=\"Ta bort rättighet.\" onclick=\"Edit('+key+',0)\">';
						else
							html += '<input type=submit value=\"X\" title=\"Ta bort rättighet.\" disabled>';";
		
		$html .= "
					
					}
						
					
					html +=  '</span> ';
											
					return html;
				}
		
				function Update()
				{
					
					searchString = document.getElementById('search').value;
				
					searchString = searchString.toLowerCase();";
		
		if($render_article)			
			$html .= "
					  if(Official == 0)
					  {
						document.getElementById('groups').innerHTML = 'Grupper denna artikel finns med i:<br>';
					
					  }
					  else
					  {
					     document.getElementById('search').hidden='hidden';
						 document.getElementById('groups').innerHTML = '';
						
					  }
					";
		
		else if($render_group)
			$html .= "		
					document.getElementById('viewers').innerHTML = 'Besökare:<br>';
					document.getElementById('members').innerHTML = '<br>Medlemmar:<br>';
					document.getElementById('admins').innerHTML = '<br>Administratörer:<br>';
					";
		else
			$html .= "			
					document.getElementById('know_of').innerHTML = 'Känner till rollen:<br>';
					document.getElementById('know_well').innerHTML = '<br>Känner rollen väl:<br>';
					";
		
					
		$html .= 	"document.getElementById('search_results').innerHTML = '';									
					
					for(var key in Data)
					{	 
						
						
				";
		
		if($render_article)
			$html .= "if(Data[key].access == '0')
						{
							if(searchString == '' || Data[key].name.toLowerCase().search(searchString) >= 0	)				
								document.getElementById('search_results').innerHTML += Render(key);	
						}
						else
							document.getElementById('groups').innerHTML += Render(key);
					";
		else if($render_group)
			$html .= " 
						if(Data[key].status == '0')
						{
							if(searchString == '' || Data[key].name.toLowerCase().search(searchString) >= 0	)				
								document.getElementById('search_results').innerHTML += Render(key);	
						}
						if(Data[key].status == '1')
							document.getElementById('viewers').innerHTML += Render(key);	
							
						if(Data[key].status == '2')
							document.getElementById('members').innerHTML +=Render(key);					
							
						if(Data[key].status == '3')
							document.getElementById('admins').innerHTML += Render(key);
					";
		else 
			$html .=  " 
						if(Data[key].status == '0')
						{
							if(searchString == '' || Data[key].name.toLowerCase().search(searchString) >= 0	)				
								document.getElementById('search_results').innerHTML += Render(key);	
						}
						if(Data[key].status == '1')
							document.getElementById('know_of').innerHTML += Render(key);	
							
						if(Data[key].status == '2')
							document.getElementById('know_well').innerHTML +=Render(key);				
						";						
		
		$html .= "	}	
					
				}
				Update();		
				JT_init();		
		</script>";
		
		
		return $html;
	}
}

?>