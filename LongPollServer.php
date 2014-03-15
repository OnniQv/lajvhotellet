<?php

if(isset($_GET["go"]) && $_GET["go"] == "1")
{
	$_POST['data'] = str_replace("\\", "", $_POST['data']);
	$data = json_decode($_POST['data'], true);

	if(count($data) == 0)
		die("No Data" . $_POST['data']);
		
	foreach($data as $d)
	{
		require_once($d['server_include']);
		call_user_func($d['server_init_function'], $_GET['user_id'], $_GET['larp_id'], $d['argument']);
	}

	for($i=0; $i<27; $i+=3)
	{

		foreach($data as $d)
		{
			$res = call_user_func($d['server_function'], $_GET['user_id'], $_GET['larp_id'], $d['argument']);
			if($res !== false)
			{				
				$arr['Message'] = $d['name'];
				$arr['Data'] = $res;
				die (json_encode($arr));
			}
		}

		sleep(3);
	}
}


?>