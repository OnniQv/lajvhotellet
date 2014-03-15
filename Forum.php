<?php

require_once ("SQL.php");
require_once ("Links.php");

if(isset($_POST['follow']))
{
	if($_POST['user_id'] == 0)
		die();
	
	$enable = $_POST['enable'];
	$user_id = $_POST['user_id'];
	$thread_id = $_POST['thread_id'];
	$larp_shortname = $_POST['larp_short_name'];
	global $larp_shortname;
	require_once("auth.php");
	Auth::Singleton()->Auth($larp_shortname);

	if($enable)	
		Q("INSERT INTO user_follows_thread (user_id, thread_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id=user_id", array($user_id, $thread_id));		
	else
		Q("DELETE FROM user_follows_thread WHERE user_id = ? AND thread_id = ?", array($user_id, $thread_id));
		
	die();
}


if(isset($_POST['new_thread']))
{
	if($_POST['user_id'] == 0)
		die();
	
	require_once ("Articles.php");
	
	$user_id = $_POST['user_id'];
	$article_id = $_POST['article_id'];
	$context = 0;
	if(isset($_POST['context']))
		$context = $_POST['context'];
	
	$content = str_replace("\n", "<br/>", htmlentities(base64_decode($_POST['content'])));
	$larp_shortname = $_POST['larp_short_name'];
	global $larp_shortname;
	require_once("auth.php");
	Auth::Singleton()->Auth($larp_shortname);
	$larp_id = Auth::Singleton()->LarpId();
	Q("INSERT INTO forum_threads (author_id, article_id, context, title, created) VALUES (?, ?, ?, ?, NOW())", array($user_id, $article_id, $context, $content));
	$thread_id = SQL::S()->InsertId(); 
	Q("INSERT INTO user_follows_thread (user_id, thread_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id=user_id", array($user_id, $thread_id));
	
	
	//Get ALL users that can see this Article and make them follow this thread
	$users = Articles::Singleton()->GetAllReadingUsers($article_id);
	foreach($users as $reciever_id)
	{
		Q("INSERT INTO user_follows_thread (user_id, thread_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id=user_id", array($reciever_id, $thread_id));
		Q("INSERT INTO thread_notifications (sender_id, reciever_id, thread_id, larp_id, time, `read`) VALUES (?, ?, ?, ?,NOW(), 0) ON DUPLICATE KEY UPDATE time=NOW(), `read`=0", array($user_id, $reciever_id, $thread_id, $larp_id));
	}
	
	
	die();
}

if(isset($_POST['new_mess']))
{
	if($_POST['user_id'] == 0)
		die();
	
	$user_id = $_POST['user_id'];
	$thread_id = $_POST['thread_id'];
	$larp_shortname = $_POST['larp_short_name'];
	global $larp_shortname;
	require_once("auth.php");
	Auth::Singleton()->Auth($larp_shortname);
	$larp_id = Auth::Singleton()->LarpId();
	$content = str_replace("\n", "<br/>", htmlentities(base64_decode($_POST['content'])));

	//Insert message
	$article_id = QS("SELECT article_id FROM forum_threads WHERE id=?", array($thread_id));
	Q("INSERT INTO forum_messages (author_id, thread_id, article_id, content, created) VALUES (?, ?, ?, ?, NOW())", array($user_id, $thread_id, $article_id, $content));
	
	//I follow this
	Q("INSERT INTO user_follows_thread (user_id, thread_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id=user_id", array($user_id, $thread_id));
	
	//Tell everyone there is a new message
	$recievers = array();
	$res = Q("SELECT user_id FROM user_follows_thread WHERE thread_id=? AND user_id<>?", array($thread_id, $user_id));
	while($assoc = $res->fetch_assoc())	
		$recievers[] = $assoc['user_id'];		
	foreach($recievers as $recv)
		Q("INSERT INTO thread_notifications (sender_id, reciever_id, thread_id, larp_id, time, `read`) VALUES (?, ?, ?, ?,NOW(), 0) ON DUPLICATE KEY UPDATE time=NOW(), `read`=0", array($user_id, $recv, $thread_id, $larp_id));
		
	//We need to tell the forum if this is contextual	
	$context = QS("SELECT context FROM forum_threads WHERE id=?", array($thread_id));	
	die($context);	
}

if(isset($_POST['get_threads']))
{

	$article_id = $_POST['article_id'];
	$user_id = $_POST['user_id'];
	$context = $_POST['context'];
	$larp_shortname = $_POST['larp_short_name'];
	global $larp_shortname;
	require_once("auth.php");
	Auth::Singleton()->Auth($larp_shortname);
	
	$json = array();
	$i=0;
	$res = Q("SELECT forum_threads.*, users.id AS user_id, users.full_name, groups.name, user_follows_thread.thread_id<>0 AS follow FROM forum_threads INNER JOIN users ON forum_threads.author_id = users.id LEFT JOIN groups ON forum_threads.context = groups.id LEFT JOIN user_follows_thread ON forum_threads.id = user_follows_thread.thread_id AND user_follows_thread.user_id=? WHERE forum_threads.article_id=? AND forum_threads.context IN ($context) ORDER BY forum_threads.created DESC", array($user_id, $article_id));
	
	$read_time = QS("SELECT time FROM user_read_article_threads WHERE user_id=? AND article_id=?", array($user_id, $article_id));
	
	while($assoc = $res->fetch_assoc())
	{
		$thread_id = $assoc['id'];
		
		//TODO: fix read-flag
		$assoc['read'] = 1;
		
		/*
		if($read_time < $assoc['created']) this only works in SQL
		
		
		if(QS("SELECT COUNT(*) FROM thread_messages JOIN user_read_article_threads ON thread_messages WHERE reciever_id='$user_id' AND thread_id=$thread_id AND `read`=0"))		//dont set to read yet, lets get messages first
			$assoc['read'] = 0;			
		*/
		
		$assoc['id'] = base64_encode($assoc['id']);
		$assoc['full_name'] = base64_encode(RenderUserLink($assoc['user_id'], $assoc['full_name']));
		$assoc['title'] = base64_encode($assoc['title']);
		$assoc['context'] = base64_encode($assoc['context']);
		$assoc['name'] = base64_encode($assoc['name']);
		$assoc['follow'] = base64_encode($assoc['follow']);
		$assoc['created'] = base64_encode(RenderTime($assoc['created']));
		$assoc['read'] = base64_encode($assoc['read']);
		$json[$i++] = $assoc;
	}
	
	
	
	if(count($json) == 0)
		die(json_encode (json_decode ("{}")));
	
	die(json_encode($json));
}

if(isset($_POST['get_messages']))
{
	$article_id = $_POST['article_id'];
	$thread_id = $_POST['thread_id'];
	$user_id = $_POST['user_id'];
	$larp_shortname = $_POST['larp_short_name'];
	global $larp_shortname;	
	require_once("auth.php");
	Auth::Singleton()->Auth($larp_shortname);
	
	
	$read_time = QS("SELECT time FROM user_read_article_threads WHERE user_id=? AND article_id=?", array($user_id, $article_id));
	Q("UPDATE thread_notifications SET `read`=1, read_time=NOW() WHERE reciever_id=? AND thread_id=?", array($user_id, $thread_id));
	Q("INSERT INTO user_read_article_threads (user_id, article_id, time) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE time=NOW()", array($user_id, $article_id));
	
	
	
	$res = Q("SELECT forum_messages.*, users.id AS user_id, users.full_name, (forum_messages.created > ?) AS unread FROM forum_messages INNER JOIN users on forum_messages.author_id = users.id WHERE forum_messages.thread_id=? ORDER BY forum_messages.created ASC", array($read_time, $thread_id));	
	$json = array();
	$i = 0;
	while($assoc = $res->fetch_assoc())
	{
		//if I don't have a notification, this is not my buissiness, dont mark as unread
		if($read_time == "")
			$assoc['unread'] = "0";
		
		$assoc['full_name'] = base64_encode(RenderUserLink($assoc['user_id'], $assoc['full_name']));
		$assoc['content'] = base64_encode($assoc['content']);
		$assoc['created'] = base64_encode(RenderTime($assoc['created']));
		$assoc['unread'] = base64_encode($assoc['unread']);
		
		$json[$i++] = $assoc;
	}
	
	if(count($json) == 0)
		die(json_encode (json_decode ("{}")));
	
	die(json_encode($json));

}

if(isset($_POST['everything_read']))
{
	$article_id = $_POST['article_id'];	
	$user_id = $_POST['user_id'];
	$larp_shortname = $_POST['larp_short_name'];
	global $larp_shortname;
	require_once("auth.php");
	Auth::Singleton()->Auth($larp_shortname);
	Q("INSERT INTO user_read_article_threads (user_id, article_id, time) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE time=NOW()", array($user_id, $article_id));
}


//////////////////////////////////////////////////////////////////////////////////////////////////

class Forum
{
	private static $singleton = null;
	
	public static function Singleton ()
	{
		if (! isset(self::$singleton))
		{
			self::$singleton = new Forum();
		}
	
		return self::$singleton;
	}
	
	
	
	function RenderForum($article_id, $render_user_user = false)
	{
		$user_id = Auth::Singleton()->id;
		$larp_shortname = Auth::Singleton()->current_shortname;
		
		$Groups = Groups::Singleton()->GetAll();
		
		//TODO: this almost works
		LongPoll::S()->RegisterMessageType("forum_refresh", "ForumRefresh.php", "ForumRefreshInit", "ForumRefresh", "ForumRefresh", $article_id, false);
		
		$MemberGroups = array();
		foreach($Groups[2] as $id => $group)				
			$MemberGroups[$id] = $group;		
		foreach($Groups[3] as $id => $group)		
			$MemberGroups[$id] = $group;
			
		$res = Q("SELECT group_id, access FROM article_in_group WHERE article_id=?", array($article_id));		
		
		$context = array();
		while($assoc = $res->fetch_assoc())
		{			
			if(isset($MemberGroups[$assoc['group_id']]))
				$context[$assoc['group_id']] = $MemberGroups[$assoc['group_id']]['name'];
		}
		
		$context2 = $context;
		$context2[0] = "Alla";
		$context_text = implode(",", array_keys($context2));
		
				
		$html = "";
		
		$create_thread = "Skapa ny forumtråd till denna artikel...";
		if($render_user_user)
			$create_thread = "Skapa ny forumtråd med denna användare...";
		
		if(Auth::singleton()->LoggedIn())
		{
			$html .= "<span class=section_title>Forum</span>";
			$html .= "<div class=section><br>";
			$html .= "<input type=text id=new_thread size=50 value='$create_thread'/>";
			if(!$render_user_user)
			{	
				$html .= "<select id=context><option value='0'>Visa för alla</option>";
				foreach($context as $group_id => $group_name)
					$html .= "<option value='$group_id'>$group_name</option>";
				$html .= "</select>";
			}		
			
			$html .= "<br><br></div>";
		}
		
		$html .= "<script language='javascript'>
				
				function base64_encode(str) {
				
				if(str == null)
					str = '';
				
			        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
			        var encoded = [];
			        var c = 0;
			        while (c < str.length) {
			            var b0 = str.charCodeAt(c++);
			            var b1 = str.charCodeAt(c++);
			            var b2 = str.charCodeAt(c++);
			            var buf = (b0 << 16) + ((b1 || 0) << 8) + (b2 || 0);
			            var i0 = (buf & (63 << 18)) >> 18;
			            var i1 = (buf & (63 << 12)) >> 12;
			            var i2 = isNaN(b1) ? 64 : (buf & (63 << 6)) >> 6;
			            var i3 = isNaN(b2) ? 64 : (buf & 63);
			            encoded[encoded.length] = chars.charAt(i0);
			            encoded[encoded.length] = chars.charAt(i1);
			            encoded[encoded.length] = chars.charAt(i2);
			            encoded[encoded.length] = chars.charAt(i3);
			        }
			        return encoded.join('');
			    }
				
				function base64_decode(str) {
					if(str == null)
						str = '';
				
			        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
			        var invalid = {
			            strlen: (str.length % 4 != 0),
			            chars:  new RegExp('[^' + chars + ']').test(str),
			            equals: (/=/.test(str) && (/=[^=]/.test(str) || /={3}/.test(str)))
			        };
			        if (invalid.strlen || invalid.chars || invalid.equals)
			            throw new Error('Invalid base64 data');
			        var decoded = [];
			        var c = 0;
			        while (c < str.length) {
			            var i0 = chars.indexOf(str.charAt(c++));
			            var i1 = chars.indexOf(str.charAt(c++));
			            var i2 = chars.indexOf(str.charAt(c++));
			            var i3 = chars.indexOf(str.charAt(c++));
			            var buf = (i0 << 18) + (i1 << 12) + ((i2 & 63) << 6) + (i3 & 63);
			            var b0 = (buf & (255 << 16)) >> 16;
			            var b1 = (i2 == 64) ? -1 : (buf & (255 << 8)) >> 8;
			            var b2 = (i3 == 64) ? -1 : (buf & 255);
			            decoded[decoded.length] = String.fromCharCode(b0);
			            if (b1 >= 0) decoded[decoded.length] = String.fromCharCode(b1);
			            if (b2 >= 0) decoded[decoded.length] = String.fromCharCode(b2);
			        }
			        return decoded.join('');
			    }
				
				";
		

		$html .= "
		$(\"#new_thread\").keyup(function(event)
		{
			if(event.keyCode == 13)
			{	
				var cont = base64_encode($(\"#new_thread\").val());
			
				$(\"#new_thread\").val(\"$create_thread\");
				$(\"#new_thread\").blur();
					
				$.post(\"/Forum.php\", { new_thread: \"1\", article_id:\"$article_id\", context: $(\"#context\").val(), user_id:\"$user_id\", content: cont, larp_short_name:\"$larp_shortname\"},  function(respons)
				{					
					//alert(respons);
					UpdateForum();
				});
			}			
		});
			
		$(\"#new_thread\").focus(function(event)
		{
			if($(\"#new_thread\").val() == \"$create_thread\")
				$(\"#new_thread\").val(\"\");
		});
		$(\"#new_thread\").focusout(function(event)
		{
			if($(\"#new_thread\").val() == \"\")
				$(\"#new_thread\").val(\"$create_thread\");
		});
	
		</script>";
		$html .= "";
			
		$html .= "<div id=forum></div>";
		
		$html .= "<script language='javascript'>
				
				
			    
			    function TextKeyUp(event, thread_id)
			    {	
			    					   
					if(event.keyCode == 13 && !event.ctrlKey && !event.shiftKey)
					{		
						document.getElementById('forum_thread_'+thread_id).innerHTML += '<img src=\"/img/wait.gif\">';
						
						var cont = base64_encode($(\"#thread_\"+thread_id).val());
					
						$(\"#thread_\"+thread_id).val(\"Skriv en kommentar...\");
						$(\"#thread_\"+thread_id).blur();
								
						$.post(\"/Forum.php\", { new_mess: \"1\", thread_id: thread_id, user_id:\"$user_id\", content: cont, larp_short_name : \"$larp_shortname\"},  function(respons)
						{		
								UpdateThread(thread_id);								
						});
					}
					while($(this).outerHeight() < this.scrollHeight + parseFloat($(this).css(\"borderTopWidth\")) + parseFloat($(this).css(\"borderBottomWidth\")))
					{
						$(this).height($(this).height()+1);
					}
									     
			    }
			    
			    function TextFocus(thread_id)
			    {
				    
					if($(\"#thread_\"+thread_id).val() == \"Skriv en kommentar...\")
						$(\"#thread_\"+thread_id).val(\"\");
					
			    }
			    function TextFocusOut(thread_id)
			    {				   
					if($(\"#thread_\"+thread_id).val() == \"\")
						$(\"#thread_\"+thread_id).val(\"Skriv en kommentar...\");					
			    }

			    function FollowClick(thread_id, enable)
			    {
			    	document.getElementById('follower_'+thread_id).innerHTML = '<img src=\"/img/wait.gif\">';
			    	
			    	$.post(\"/Forum.php\", { follow: \"1\", thread_id: thread_id, user_id:\"$user_id\", enable: enable, larp_short_name : \"$larp_shortname\"},  function(respons)
					{	
						//alert(respons);
				    	if(enable)
							follow_html = '<a href=\"javascript:void(0);\" id=follower_'+thread_id+'  onclick=\"FollowClick('+thread_id+', 0)\">Sluta följa tråd</a>';
						else
							follow_html = '<a href=\"javascript:void(0);\" id=follower_'+thread_id+'  onclick=\"FollowClick('+thread_id+', 1)\">Börja följa tråd</a>';
							
						document.getElementById('follower_'+thread_id).innerHTML = follow_html;
					});
			    }
	    
			    function UpdateThread(thread_id)
			    {		
			    	
			    	document.getElementById('forum_thread_'+thread_id).innerHTML += '<img src=\"/img/wait.gif\">';
			    		    	 	
			    	$.post(\"/Forum.php\", { get_messages: \"1\", thread_id: thread_id , user_id : \"$user_id\", larp_short_name : \"$larp_shortname\", article_id : \"$article_id\" }, function (json)
			    	{
			    		//alert(json);
			    		var html = '';
						$.each(JSON.parse(json), function(i, item) 
						{
  							this.content = base64_decode(this.content);
							this.full_name = base64_decode(this.full_name );
							this.created = base64_decode(this.created);
							this.unread = base64_decode(this.unread);
							this.context = base64_decode(this.context);
													
							//alert(this.unread);
							
							html += '<div class=';
							if(this.unread == 1)
								html += 'forum_message_unread';
							else if(this.context == 0)
								html += 'forum_message';
							else
								html += 'forum_message_secret';
								
							
								
							html += ' id=forum_message_'+this.id+'>'+this.full_name+': '+this.content + '<span class=meta_data>'+ this.created + '</span></div>';
							
						});
			    	
			    		document.getElementById('forum_thread_'+thread_id).innerHTML = html;
			    		TimeStart();
			    		JT_init();
			    	});
			    	
			    }
			    
				function UpdateForum()
				{
					$.post(\"/Forum.php\", { get_threads: \"1\", article_id:\"$article_id\", context: \"$context_text\", user_id : \"$user_id\", larp_short_name : \"$larp_shortname\" }, function (json)
					{	
						//alert(json);						
						var html = '';						
						$.each(JSON.parse(json), function(i, item) 
						{	
							this.id = base64_decode(this.id);
  							this.title = base64_decode(this.title);
							this.full_name = base64_decode(this.full_name );
							this.context = base64_decode(this.context);
							this.name = base64_decode(this.name);
							this.follow = base64_decode(this.follow); 
							this.created = base64_decode(this.created);
							this.read = base64_decode(this.read);
							
							var follow_html = '';
			";
		
			if(!$render_user_user)
				$html .= "
							if(this.follow == 0)
								follow_html = '<a href=\"javascript:void(0);\" id=follower_'+this.id+'  onclick=\"FollowClick('+this.id+', 1)\">Börja följa tråd</a>';
							else
								follow_html = '<a href=\"javascript:void(0);\" id=follower_'+this.id+'  onclick=\"FollowClick('+this.id+', 0)\">Sluta följa tråd</a>';
							";
			$html .= "
							var forum_class_name = 'forum_thread';
							var message_class_name = 'forum_message';
							if(this.context != 0)
							{
								forum_class_name = 'forum_thread_secret';
								message_class_name = 'forum_message_secret';
							}
							if(this.read == 0)
								forum_class_name = 'forum_thread_unread';
								
							html += '<div class='+forum_class_name+'>'+this.full_name+': <b>'+this.title+'</b><div align=\"right\">'+follow_html+'</div><br><span class=meta_data>' +this.created+'</span></div>';
							html += '<div id=forum_thread_'+this.id+'></div>';
							";
		
							if(Auth::singleton()->LoggedIn())
								$html .="html += \"<div class=\"+message_class_name+\"><textarea cols=50 rows=3 id=thread_\"+this.id+\" onkeyup='TextKeyUp(event, \"+this.id+\")' onfocus='TextFocus(\"+this.id+\")' onfocusout='TextFocusOut(\"+this.id+\")'>Skriv en kommentar...</textarea></div>\";";

							$html .= "	
						});

						
						
						document.getElementById('forum').innerHTML = html;
						
						$.each(JSON.parse(json), function(i, item) 
						{	
							UpdateThread(base64_decode(this.id));  
						});
						TimeStart();
						JT_init();
						$.post(\"/Forum.php\", { everything_read: \"1\", article_id:\"$article_id\", user_id : \"$user_id\", larp_short_name : \"$larp_shortname\" }, function (json){});
					});
				}
				UpdateForum();
				
				function ForumRefresh(mess)
				{
					
					if(mess == 'NewThread')
						UpdateForum();
					else
					{
						//alert('update thread ' + mess);
						UpdateThread(mess);
					}
				}
				
		</script>";		
		return $html;
	}
	
}


?>