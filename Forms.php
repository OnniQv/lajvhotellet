<?php

require_once("Groups.php");

class Forms
{
	private static $singleton = null;
	
	public static function Singleton ()
	{
		if (! isset(self::$singleton))
		{
			self::$singleton = new Forms();
		}
	
		return self::$singleton;
	}
	
	private function __construct ()
	{
	
	}
	
	function RenderEditForm($form_id)
	{		
		$html = "";
		
		if(QS("SELECT count(*) FROM larps WHERE character_form_id=?", array($form_id)))
			$character_form = true;		
		else if(QS("SELECT count(*) FROM larps WHERE user_form_id=?", array($form_id)))
			$character_form = false;
		else	
			return "Kan inte hitta formuläret med id: $form_id";
		
		
		if($character_form)
			$html .= "<h3>Redigera Rollforumläret i " . Auth::Singleton()->LarpName() . "</h3>Alla roller skapade för detta lajv måste fylla i detta formulär.<br>Observera att du inte behöver lägga till rollnamn i detta formulär, det finns alltid med ändå.";
		else
			$html .= "<h3>Redigera Off-forumläret i " . Auth::Singleton()->LarpName() . "</h3>Alla deltagare på detta lajv måste fylla i detta formulär.";
		
		$html .= "<br>Lämna ett fältnamnnamn tomt så kommer det inte sparas.<br><br>";
		
		//Save POST data
		if(isset($_POST['save_form']))
		{
			
			
			
			for($i=0; $i<30; $i++)
			{
				if($_POST['name_'.$i] != "")
				{
					
					$name = $_POST['name_'.$i];
					$desc = $_POST['desc_'.$i];
					if($character_form)
						$visible = $_POST['visible_'.$i];
					else
						$visible = "OWNER";
					$type = $_POST['type_'.$i];
					
					if(isset($_POST['old_id_'.$i]))
					{
						$field_id = $_POST['old_id_'.$i];						
						//Don't update type
						Q("UPDATE form_fields SET name=?, description=?, visible=? WHERE id=?", array($name, $desc, $visible, $field_id));						
					}
					else
					{	
						Q("INSERT INTO form_fields (form_id, name, description, type, visible) VALUES (?,?,?,?,?)", array($form_id, $name, $desc, $type, $visible));
						$field_id = SQLInsertId();
					}
									
					
					
					
					$value_names = array();
					$value_groups = array();
					if($type == 'RADIO' || $type == 'CHECKBOX')
					{	
						for($j =0; $j<12; $j++)
						{	
							if($_POST['value_name_'.$i.'_'.$j] != "")
							{	
								$value_names[] = $_POST['value_name_'.$i.'_'.$j];
								$value_groups[] = $_POST['value_group_'.$i.'_'.$j];
							}
						}
						
						Q("DELETE FROM form_field_options WHERE form_id=? AND field_id=?", array($form_id, $field_id));
						for($j =0; $j<count($value_names); $j++)
						{							
							Q("INSERT INTO form_field_options (form_id, field_id, name, group_id) VALUES (?, ?, ?, ?)", array($form_id, $field_id, $value_names[$j], $value_groups[$j]));
						}
						
					}
					
				}
				
				
			}
			
			SetSuccessMessage("Ditt formulär är sparat.");
		}
		
		$AllGroups = Groups::Singleton()->GetAll();		
		$GroupsJoined = array();
		foreach($AllGroups[1] as $g)
			$GroupsJoined[] = $g;
		foreach($AllGroups[2] as $g)			
			$GroupsJoined[] = $g;
		foreach($AllGroups[3] as $g)
			$GroupsJoined[] = $g;		
		$GroupOptions = "<option value=-1></option>";
		foreach($GroupsJoined as $g)
		{
			if($g['id'] == 0)
				continue;
			$GroupOptions .= "<option value={$g['id']} selected_{$g['id']}>{$g['name']}</option>"; 
		}
		
		$html .= "<form action='".$_SERVER['REQUEST_URI'] ."' method=POST>";
		
		
		$html .= "<div id=form_content>";
		
		$res = Q("SELECT * FROM form_fields WHERE form_id=? ORDER BY id ASC", array($form_id));
		
		$fields = array();
		while($field = $res->fetch_assoc())
		{
			$fields[] = $field;
		}
		
		
		$Count = 0;
		$ValueCount = array();
		//30fields * 12values goes under the limit of 512 post-fields
		for($i=0; $i<30; $i++)
		{
			$old_field = null;
			if(isset($fields[$i]))
				$old_field = $fields[$i];
			
			$know_of_selected = "";
			$know_well_selected = "";
			$owner_selected = "";
			
			$display = "none";
			$display_values = "none";
			$name = "";
			$desc = "";
			$values = false;
			$hidden = "";
			
			if($old_field != null)
			{
				$Count++;
				
				$display = "inline";
				$name = $old_field['name'];
				$desc = $old_field['description'];
				$hidden = "<input type=hidden name=old_id_$i value={$old_field['id']}>";
				switch($old_field['visible'])
				{
					case "KNOW_OF": $know_of_selected = "selected"; break;
					case "KNOW_WELL": $know_well_selected = "selected"; break;
					case "OWNER": $owner_selected = "selected"; break;
				}
				
				switch($old_field['type'])
				{
					case "LINE": $type_selector = "Textrad<input type=hidden name=type_$i value={$old_field['type']}>"; break;
					case "TEXT": $type_selector = "Textfält<input type=hidden name=type_$i value={$old_field['type']}>"; break;
					case "RADIO": $type_selector = "Flerval, välj en<input type=hidden name=type_$i value={$old_field['type']}>"; $display_values = "inline"; break;
					case "CHECKBOX": $type_selector = "Flerval, välj flera<input type=hidden name=type_$i value={$old_field['type']}>"; $display_values = "inline"; break;
					
				}
				$values = Q("SELECT * FROM form_field_options WHERE field_id = ? ORDER BY id ASC", array($old_field['id']));				
			}
			else
			{
				$type_selector = "<select name='type_$i' onchange='ChangeType($i, this);' style='display:inherit;'><option value='LINE'>Textrad</option><option value='TEXT'>Textfält</option><option value='RADIO'>Flerval, välj en</option><option value='CHECKBOX'>Flerval, välj flera</option>";
			}
			
			$html .= "<div id=form_content_$i style='display:$display;'><table border=0 style='display:inherit;'>
			<tr><td>Fältnamn </td><td><input type=text name=name_$i style='display:inherit;' value='$name'></td></tr>			
			<tr><td>Beskrivning </td><td><textarea name=desc_$i style='display:inherit;'>$desc</textarea></td></tr>";

			if($character_form)
				$html .= "<tr><td>Synligt för </td><td><select name=visible_$i style='display:inherit;'><option value=KNOW_OF $know_of_selected>De som känner till rollen</option><option value=KNOW_WELL $know_well_selected>De som känner rollen väl</option><option value=OWNER $owner_selected>Endast de som spelar rollen och arr</option></td></tr>";
			
			$html .= "<tr><td>Typ </td><td>$type_selector</td></tr>
			<tr><td></td><td><div style='display:$display_values;' id=values_$i>$hidden";
			
			
			
			for($j =0; $j<12; $j++) 
			{
				$value = false;
				if($values)
				{							
					$value = $values->fetch_assoc();
				}
				
				$display = "none";
				$name = "";
				$GroupOptionsFixed = $GroupOptions;
				if($value['name'] != "")
				{
					$ValueCount[$Count-1]++;
					
					$name = $value['name'];
					$display = "inline";
					
					$group =  $value['group_id'];
					$GroupOptionsFixed = str_replace("selected_$group", "selected", $GroupOptions);
				}
				
				$html .= "<div id=value_{$i}_{$j} style='display:$display;'><table border=0>
					<tr><td>Namn </td><td><input type=text name=value_name_{$i}_{$j} value='$name'></td>
					<td>Gå med i Grupp </td><td><select name=value_group_{$i}_{$j}>$GroupOptionsFixed</select></td></tr>
					</table></div>";
			}
			
			$html .= "<button type=button onclick='ExpandValues($i)'>Lägg till nytt värde</button></div></td></tr>
						<tr><td>&nbsp;</td></tr></table></div>";
			
		}
		
		$html .= "</div>";
		
		$html .= "<button type=button onclick='ExpandForm();' >Lägg till nytt fält</button>";

		
		
		$html .= "<script language='javascript'>
				var Count = $Count;
				var ValueCounts = Array();";
				
				foreach($ValueCount as $key => $v)
					$html .= "ValueCounts[$key] = $v;";
				
				$html .= "
				function ExpandForm()
				{						
					$(\"#form_content_\"+Count).show('slow');
					Count++;
				}
				
				function ChangeType(Count, cntrl)
				{
					var v = cntrl.value;
					
					if(v == 'LINE' || v == 'TEXT')
					{
						$(\"#values_\"+Count).hide();						
					}					
					else
					{
						$(\"#values_\"+Count).show('slow');						
					}
				}

				function ExpandValues(Count)
				{
					if(isNaN(ValueCounts[Count]))
						ValueCounts[Count] = 0;				
					var ValueCount = ValueCounts[Count]++;
				
									
					$(\"#value_\"+Count+\"_\"+ValueCount).show('slow');
				}
				
				</script>";
		
		
		$html .= "<br><br><input type=submit name=save_form value='Spara ändringar'>";
		
		
		$html .= "</form>";
		
		
		return $html;
	}
	
	function RenderFillForm($form_id, $user_character_id)
	{
		$html = "";
	
		$res = Q("SELECT * FROM larps WHERE user_form_id=? OR character_form_id=?", array($form_id, $form_id));
		if(!$assoc = $res->fetch_assoc())
			die("404 form with id $form_id is not found in any larp");
		
		if($assoc['user_form_id'] == $form_id)
			$user_form = true;
		else
			$user_form = false;
		
		
		
		if(isset($_POST['btnsubmit_edit']))
		{
			
			if($user_form)
			{
				//Q("UPDATE users SET full_name='{$_POST['name']}' WHERE id=$user_character_id");
			}
			else
			{
				Q("UPDATE characters SET name=?, state='EDIT', update_time=NOW() WHERE id=?", array($_POST['name'], $user_character_id));
			}
			
			$res = Q("SELECT * FROM form_fields WHERE form_id =?", array($form_id));
			while($field = $res->fetch_assoc())
			{
				$res2 = Q("SELECT * FROM form_filling WHERE field_id=? AND filler_id=?", array($field['id'], $user_character_id));
				$filling = $res2->fetch_assoc();
					
				
				$data = $_POST['C_'.$field['id']];
				
				if($field['type'] == 'CHECKBOX')
					$data = implode(",", $data);
								
				Q("INSERT INTO form_filling (field_id, filler_id, `time`, `data`) VALUES (?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE time=NOW(), data=?", array($field['id'], $user_character_id, $data, $data));
				
			}
			
			if($user_form)
				SetSuccessMessage("Din användare är uppdaterad.");
			else
				SetSuccessMessage("Din roll är uppdaterad.");
				
		}
				
				
		$edit_form = new Zebra_Form('fill_form');
		$edit_form->client_side_validation(true);
		
		// User/character name
		if($user_form)
		{
			$user = Q("SELECT * FROM users WHERE id=?", array($user_character_id))->fetch_assoc();
			$name_field = "Användarnamn";
			$name_value = $user['full_name'];
		}
		else 
		{
			$character = Q("SELECT * FROM characters WHERE id=?", array($user_character_id))->fetch_assoc();
			$name_field = "Rollens namn";
			$name_value = $character['name'];
			
			$edit_form->add('label', 'label_name', 'name', $name_field);
			$obj = $edit_form->add('text', 'name', $name_value,	array('autocomplete' => 'off'));
			$obj->set_rule(array('required' => array('error', 'Du måste skriva in ett namn.')));
			
		}		
		
		
		
		$res = Q("SELECT * FROM form_fields WHERE form_id = ?", array($form_id));
		while($field = $res->fetch_assoc())
		{	
			$res2 = Q("SELECT * FROM form_filling WHERE field_id=? AND filler_id=?", array($field['id'], $user_character_id));
			$filling = $res2->fetch_assoc();
			
			if($field['type'] == 'CHECKBOX' || $field['type'] == 'RADIO')
			{		
				$options = array();
				$res3 = Q("SELECT * FROM form_field_options WHERE field_id=?", array($field['id']));
				while($option = $res3->fetch_assoc())
				{
					$options["O_". $option['id']] = $option['name'];
					
					if($option["group_id"] != -1)
						$options["O_". $option['id']] .= " (Gå med i:" . RenderGroupLink($option["group_id"]) . ")";
				}				
			}
			
			$control_name = "C_" . $field["id"];
			
			switch($field['type'])
			{
				case "LINE":
					$edit_form->add('label', 'label_'.$control_name, $control_name, $field['name']);
					$obj = $edit_form->add('text', $control_name, $filling['data'], array('autocomplete' => 'off'));					
					break;
				case "TEXT":
					$edit_form->add('label', 'label_'.$control_name, $control_name, $field['name']);
					$obj = $edit_form->add('textarea', $control_name, $filling['data'], array('autocomplete' => 'off'));
					break;
				case "RADIO":										
					$edit_form->add('label', 'label_'.$control_name, $control_name, $field['name']);
					$obj = $edit_form->add('radios', $control_name, $options, array(0 => $filling['data']));					
					break;
				case "CHECKBOX":
					$default = explode(",", $filling['data']);					
					$edit_form->add('label', 'label_'.$control_name, $control_name, $field['name']);
					$obj = $edit_form->add('checkboxes', $control_name."[]", $options, $default);
						
					
					break;
			}
			
		
		}
		
		$edit_form->add('submit', 'btnsubmit_edit', 'Spara');
				
		$html .= $edit_form->render('', true);	
		return $html;
	}
	
	function RenderFillData($form_id, $user_character_id)
	{
		$html = "";
	
		$res = Q("SELECT * FROM larps WHERE user_form_id=? OR character_form_id=?", array($form_id, $form_id));
		if(!$assoc = $res->fetch_assoc())
			die("404 form with id $form_id is not found in any larp");
	
		if($assoc['user_form_id'] == $form_id)
			$user_form = true;
		else
			$user_form = false;
	
		$res = Q("SELECT * FROM form_fields WHERE form_id = ?", array($form_id));
		while($field = $res->fetch_assoc())
		{
			$res2 = Q("SELECT * FROM form_filling WHERE field_id=? AND filler_id=?", array($field['id'], $user_character_id));
			$filling = $res2->fetch_assoc();
				
			if($field['type'] == 'CHECKBOX' || $field['type'] == 'RADIO')
			{
				$fields = explode(",", $filling['data']);
				
				for($i=0;$i<count($fields);$i++)
					$fields[$i] = substr($fields[$i], 2);
				
				$fields = implode(",", $fields);
				if($fields == "")
					$fields = "0";
				
				$data = array();
				$res3 = Q("SELECT * FROM form_field_options WHERE id IN ($fields)", array());
				while($option = $res3->fetch_assoc())									
					$data[] = $option['name'];
				
				$filling['data'] = implode(", ", $data);
			}

			$html .= "<span class=section_title>{$field['name']}</span><div class=section>{$filling['data']}</div>";
			
		}
		
		return $html;
	}
}


?>