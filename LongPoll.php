<?php


class LongPoll
{	
	private static $singleton;
	private $user_id;
	private $larp_id;
	private $MessageTypes = array();

	function __contruct ()
	{
		self::$singleton = $this;
	}
	public static function S ()
	{
		if(self::$singleton == null)
			self::$singleton = new LongPoll();
		return self::$singleton;
	}
	
	public function SetUserId($user_id, $larp_id)
	{
		$this->user_id = $user_id;
		$this->larp_id = $larp_id;
	}
	
	public function RegisterMessageType($message_type_name, $server_include, $server_init_function, $server_function, $client_java_function, $argument, $update_argument)
	{		
		$MessType = array();
		$MessType['name'] = $message_type_name;
		$MessType['server_include'] = $server_include;
		$MessType['server_init_function'] = $server_init_function;
		$MessType['server_function'] = $server_function;
		$MessType['argument'] = $argument;
		$MessType['update_argument'] = $update_argument;
		
		$MessType['client_function'] = $client_java_function;
		
		$this->MessageTypes[$MessType['name']] = $MessType;		
	}
	
	public function GetScript()
	{

		if(count($this->MessageTypes) == 0)
			return "";
		
		
		$script = "";
		$post = json_encode($this->MessageTypes);
		$script .= "<script language='javascript'>
				
				var post = '$post';
				
				function waitForMsg()
				{
			        $.ajax(
			        {
			            type: 'POST',
			            url: '/LongPollServer.php?go=1&user_id={$this->user_id}&larp_id={$this->larp_id}',
						data: 'data='+post,
			            async: true, 
			            cache: false,
			            timeout:29500, /* Timeout in ms */
			
			            success: function(json)
			            { 
			            	if(json[0] != '{' && json != '')
			            		alert(json);
			            	
							
							if(json != '')
							{
								//alert('Got message: ' + data['Message']);
								var data = JSON.parse(json);
								switch(data['Message'])
								{";
									
		foreach($this->MessageTypes as $MessType)
		{
			$script .= "case '{$MessType['name']}' : {$MessType['client_function']}(data.Data); ";
			
			if($MessType['update_argument'])
			{
				$script .= "
							var temp = JSON.parse(post);
							temp['{$MessType['name']}']['argument'] = data.Data;						
							post = JSON.stringify(temp);	
						";
			}
			
			$script .= "break;";
		}		
		
		$script .= "
								}
							}
			    
			                setTimeout( waitForMsg, 1000 );
			            },
			            error: function(XMLHttpRequest, textStatus, errorThrown)
			            {
			            	//alert('waitForMsg error' + errorThrown);    
			                setTimeout(waitForMsg, 500); //half a second
			            }
			        });
		   	 	};
		
				$(document).ready(function() { waitForMsg(); });
				
				</script>";
		
		
		return $script;
	}
	
}
?>