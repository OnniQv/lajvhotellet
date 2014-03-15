<?php

function check_email ($value, $user_id)
{
	if($user_id != null)
		$count = QS("SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?", array($value, $user_id));
	else
		$count = QS("SELECT COUNT(*) FROM users WHERE email = ?", array($value));
	

	return $count == 0;
}


class Users
{
	
	static function RenderCreateForm()
	{
		return Users::RenderForm();
	}
	
	static function RenderEditForm($user_id)
	{
		return Users::RenderForm($user_id);
	}
	
	static function RenderForm($user_id=null)
	{
		$html = "";
		if($user_id != null)
		{
			$user = Q("SELECT * FROM users WHERE id=?", array($user_id))->fetch_assoc();
		}
		else
		{
			$user = array("full_name" => " ", "email" => "");
		}
		
		$user['surname'] = substr($user['full_name'], 0, strpos($user['full_name'], " "));
		$user['lastname'] = substr($user['full_name'], strpos($user['full_name'], " ")+1);
		
		$form = new Zebra_Form('register_form');
		$form->client_side_validation(true);
		
		// surname
		$form->add('label', 'label_surname', 'surname', 'Förnamn');
		$obj = $form->add('text', 'surname', $user['surname'],
				array('autocomplete' => 'off'));
		$obj->set_rule(
				array(
						// error messages will be sent to a variable called
						// "error", usable in custom templates
						'required' => array('error',
								'Du måste skriva in ett förnamn.')));
		
		// last name
		$form->add('label', 'label_lastname', 'lastname',
				'Efternamn');
		$obj = $form->add('text', 'lastname', $user['lastname'],
				array('autocomplete' => 'off'));
		$obj->set_rule(
				array(
						// error messages will be sent to a variable called
						// "error", usable in custom templates
						'required' => array('error',
								'Du måste skriva in ett efternamn.')));
		
		// the label for the "email" element
		$form->add('label', 'label_email', 'email', 'Email');
		$obj = $form->add('text', 'email', $user['email'],
				array('autocomplete' => 'off'));
		$obj->set_rule(
				array(	// error messages will be sent to a variable called
						// "error", usable in custom templates
						'required' => array('error',
								'Du måste skriva in en email-adress.'),
						'email' => array('error',
								'Det ser inte ut som en fungerande email-adress.'),
						'custom' => array("check_email", $user_id, 'error',
								'Den email-adressen är redan registrerad.')));
		
		if($user['password_hash'] != "")
		{
			// "password 1"
			$form->add('label', 'label_password', 'psw1', ($user_id==null?'Lösenord':'Nytt Lösenord (om du vill byta)'));
			$obj = $form->add('password', 'psw1', '',
					array('autocomplete' => 'off'));
			if($user_id == null)
			{
				$obj->set_rule(array(
							'required' => array('error',
									'Du måste skriva in ett lösenord.'),
							'length' => array(3, 100, 'error',
									'Lösenordet måste vara minst 3 tecken.')));
			}
			
			// "password 2"
			$form->add('label', 'label_password2', 'psw2', ($user_id==null?'Lösenord':'Nytt Lösenord (om du vill byta)'));
			$obj = $form->add('password', 'psw2', '',
					array('autocomplete' => 'off'));
			
			if($user_id == null)
			{
				$obj->set_rule(array(
						'required' => array('error',
								'Du måste skriva in ett lösenord.'),
						'compare' => array('psw1', 							// name of the control to compare values with
								'error', 							// variable to add the error message to
								'Lösenorden matchar inte!')));					// error message if value doesn't
			}
			else
			{
				$obj->set_rule(array(					
						'compare' => array('psw1', 							// name of the control to compare values with
								'error', 							// variable to add the error message to
								'Lösenorden matchar inte!')));					// error message if value doesn't
			}
			
					
							
			// "password 3"
			if($user_id!=null)
			{
				$form->add('label', 'label_password3', 'psw3', 'Gammla Lösenordet');
				$obj = $form->add('password', 'psw3', '',
						array('autocomplete' => 'off'));
				$obj->set_rule(array('required' => array('error','Du måste skriva in ett lösenord.')));							
			}

		}
		// "submit"
		$form->add('submit', 'btnsubmit_register', 'Registrera');
		
		if ($form->validate())
		{
			
			$surname = ($_POST['surname']);
			$lastname = ($_POST['lastname']);
			$email = ($_POST['email']);
			$psw = ($_POST['psw1']);			
				
			$psw_hash = crypt($psw, "Blubbeliblubb");
				
			$full_name = $surname . " " . $lastname;
				
			if($user_id == null)
			{
				$res = Q("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)", array($full_name, $email, $psw_hash));
					
				$id = SQLInsertId();
				SetSuccessMessage("Din användare är nu skapad! Logga in med din email ovan.");
			}
			else
			{
				if($user['password_hash'] != "")
				{
					$old_psw = ($_POST['psw3']);
					$old_psw_hash = crypt($old_psw, "Blubbeliblubb");
					if($old_psw_hash != $user['password_hash'])
					{
						SetFailMessage("Fel lösenord");
						return "";
					}
				}
				
				if($psw != "")					
					Q("UPDATE users SET full_name=?, email=?, password_hash=? WHERE id=?", array($full_name, $email, $psw_hash, $user_id));
				else
					Q("UPDATE users SET full_name=?, email=? WHERE id=?", array($full_name, $email, $user_id));
			
				SetSuccessMessage("Din användare är nu uppdaterad!");
				SetRedirect(GetPageUrl("ViewUser", $user_id));
			}
			
		}
		else
		{
			$html .= $form->render('', true);
		}
		return $html;
	}
	
}

?>