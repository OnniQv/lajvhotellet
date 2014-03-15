<?php

require_once 'Groups.php';
require_once 'Forum.php';
require_once 'finediff.php';

if(isset($_GET['ihaveread']) && $_GET['ihaveread'] == "go")
{

	require_once("SQL.php");
		
	$article_id = $_GET['article'];
	$user_id = $_GET['user_id'];

	Q("INSERT INTO user_read_article (user_id, article_id, time) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE time=NOW()", array($user_id, $article_id));
	die();
}

class Article
{

	public $id;
	public $Content;
	public $ReadContent;
	public $LastContent;
	public $RawContent;
	public $Title;
	public $Version;
	public $HaveRead = 0; //0: No, 1: yes, 2: yes and insignificant change, 3: yes and significant change
	public $ShouldRead = 0; //0: no, 1: Should, 2: Must
	public $LastChangeTime;
	public $LastReadTime = 0;
	public $CanRead = 0; // 0 or 1
	public $Edit = 0; // 0 or 1
	public $Own = 0; // 0 or 1
	public $CreatorId; 
	public $Updates = array();	
	
	public function __construct ($id, $user_id=0)
	{		
		AddDebug("aid: $id, uid: $user_id");	
		$this->id = $id;
		
		if($user_id == 0)
			$user_id = Auth::Singleton()->id;
		$larp_id = Auth::Singleton()->LarpID();
		
		$res = Q("SELECT * FROM user_read_article WHERE article_id=? AND user_id=?", array($id, $user_id)); 
		if($assoc = $res->fetch_assoc())
		{		
			AddDebug(print_r($assoc, true));
			$this->LastReadTime = $assoc['time'];
			$this->HaveRead = 1;			
			
		}
				
		$main_article = Auth::Singleton()->LarpArticleId();

		if($main_article == $id)
			$this->CanRead = 1;		
		
				
		
		//Title (content comes later)
		$res = Q("SELECT title, version FROM article_content WHERE article_id=? ORDER BY created DESC LIMIT 1", array($id));
		list($this->Title, $this->Version) = $res->fetch_array();
				
		//Am I the owner?
		$res = Q("SELECT creator_id FROM articles WHERE id=?", array($id));
		$assoc = $res->fetch_assoc();
		$res->free();
		$this->CreatorId = $assoc["creator_id"];		
		if($this->CreatorId == $user_id || Auth::Singleton()->OrganizerMode())
		{
			$this->CanRead = 1;
			$this->Own = 1;
			$this->Edit = 1;
		}
		
		
		//Permissions
		$Groups = Groups::Singleton()->GetAll();
		
		$res = Q("SELECT * FROM article_in_group WHERE article_id=?", array($id));
		while($assoc = $res->fetch_assoc())
		{
			$group_id = $assoc['group_id'];
			
			$Viewer = false;
			$Member = false;
			$Admin = false;
			
			if(array_key_exists($group_id, $Groups[1]))
				$Viewer = true;			
			if(array_key_exists($group_id, $Groups[2]))			
				$Member = true;
			if(array_key_exists($group_id, $Groups[3]))
				$Admin = true;
			
			if($assoc['read'] == 'SHOULD' && $assoc['flag_request']==0 && ($Member || $Admin))
				$this->ShouldRead = max($this->ShouldRead, 1);			
			if($assoc['read'] == 'MUST' && $assoc['flag_request']==0 &&($Member || $Admin))
				$this->ShouldRead = 2;
			
			if($assoc['edit'] == 'VIEWERS' && ($Viewer || $Member || $Admin))
				$this->Edit = 1;			
			if($assoc['edit'] == 'MEMBERS' && ($Member || $Admin))
				$this->Edit = 1;			
			if($assoc['edit'] == 'ADMINS' && ($Admin))
				$this->Edit = 1;
			
			if($assoc['access'] == 'PUBLIC' && ($Viewer || $Member || $Admin))
				$this->CanRead = 1;
			if($assoc['access'] == 'PRIVATE' && ($Member || $Admin))
				$this->CanRead = 1;
		}
		
		
	}
	
	function UpdateContent()
	{
		$id = $this->id;
		$user_id = Auth::Singleton()->id;
		$larp_id = Auth::Singleton()->LarpID();
		
		$res = Q("SELECT article_content.*, users.full_name FROM article_content INNER JOIN users ON article_content.author_id = users.id WHERE article_content.article_id=? ORDER BY created", array($id));
		
		
		while($assoc = $res->fetch_assoc())
		{
			$this->Updates[] = $assoc;
				
			$this->Title = $assoc["title"];
				
			if($this->LastReadTime >=  $assoc['created'])
				$this->ReadContent = $assoc["content"];
				
			$this->LastContent = $assoc["content"];
				
			$this->LastChangeTime = $assoc['created'];
				
			if($assoc['created'] > $this->LastReadTime && $this->LastReadTime > 0)
			{
				if($assoc['significant_change'] == 1)
				{
					$this->HaveRead = max($this->HaveRead, 3);
				}
				else
				{
					$this->HaveRead = max($this->HaveRead, 2);
				}
			}
		}
		$this->RawContent = $this->LastContent;
		
		//Diff
		if($this->LastReadTime == 0 || $this->HaveRead <= 1)
		{
			$this->Content = html_entity_decode($this->LastContent);
		}
		else
		{
			$granularityStacks = array(
					FineDiff::$paragraphGranularity,
					FineDiff::$sentenceGranularity,
					FineDiff::$wordGranularity,
					FineDiff::$characterGranularity
			);				
			
			$diff = new FineDiff(html_entity_decode($this->ReadContent), html_entity_decode($this->LastContent), $granularityStacks[2]);
			$edits = $diff->getOps();
				
			$content = $diff->renderDiffToHTML();
				
			$content = str_replace("<del>", "<span class=del>", $content);
			$content = str_replace("</del>", "</span>", $content);
			$content = str_replace("<ins>", "<span class=ins>", $content);
			$content = str_replace("</ins>", "</span>", $content);

			
			$this->Content = html_entity_decode($content);
		}
		
		
		
	}
	
		
	function Render()
	{
		$html = "";
		if(!$this->CanRead)
		{
			$html .= "Du har inte tillgång till den här artikeln.<br>";
		}
		else
		{
			//Title
			$html .= "<span class=section_title>{$this->Title}</span>";

			$html .= "<div class=section>";
			//History
			$first = true;
			$UpdateText = "<br>";
			$ShortUpdateText = "";
			foreach($this->Updates as $Update)
			{
				$UpdateText .= "Version: {$Update['version']}  Ändrad: {$Update['created']}   Redigerare: {$Update['full_name']}<br>";
				$ShortUpdateText = "Version: {$Update['version']} Ändrad: {$Update['created']}   Redigerare: {$Update['full_name']}<br>";
				
				/*if($first)
				{
					$first = false;
					$UpdateText .= "{$Update['version']} Skapad: " . $Update['created']. " Av: " . $Update['full_name'];
					$ShortUpdateText = "{$Update['version']} Skapad: " . $Update['created']. " Av: " . $Update['full_name'];
				}
				else if($Update['significant_change'] == 1)
				{
					$UpdateText .= "<br>{$Update['version']} Ändrad: " . $Update['created']. " Av: " . $Update['full_name'];
					$ShortUpdateText = "<br>{$Update['version']} Senast ändrad: " . $Update['created']. " Av: " . $Update['full_name'];
				}
				else
				{
					$UpdateText .= "<br>{$Update['version']} Liten förändring: " . $Update['created']. " Av: " . $Update['full_name'];
					$ShortUpdateText = "<br>{$Update['version']} Senast lite ändrad: " . $Update['created']. " Av: " . $Update['full_name'];
				}*/
			}
			
			$html .= "<span id=long_history class=meta_data hidden=hidden>$UpdateText</span>";
			$html .= "<span id=short_history class=meta_data>$ShortUpdateText</span><span id=history_link class=meta_data> <a onClick='$(\"#long_history\").slideDown(\"slow\"); $(\"#history_link\").hide(); $(\"#short_history\").hide(); '>Visa historik...</a></span>";

			$html .= "</div>";
			
			//Edit link
			$html .= "<div class=section>";
			if($this->Edit)
				$html .= "<div align=right>".RenderPageLink("Redigera Artikel","EditArticle", $this->id)."</div>";
				
			//Content with diff			
			$html .= $this->Content;
			
			
			
			
			
				
			//I have read
			if(Auth::singleton()->LoggedIn())
			{
				if($this->HaveRead == 1)
					$html .= "<br><span class=meta_data><img src='/img/check_green.png'> Artikeln läst " . $this->LastReadTime . "</span><br><br>";
				if($this->HaveRead == 2)
					$html .= "<br><span class=meta_data><img src='/img/check_yellow.png'> Artikeln läst, men den har förrändrats " . $this->LastReadTime . "</span><br><br>";
				if($this->HaveRead == 3)
					$html .= "<br><span class=meta_data> Artikeln läst, men den har förrändrats mycket " . $this->LastReadTime . "</span><br><br>";
					
				if($this->HaveRead != 1)
				{
					$html .= "<div id=read_button><input type=submit value=' Jag har läst artikeln ' onclick=\"IHaveRead()\"></div>";
				}
			}
			$html .= "</div>";
			
			$html .= "<br><br><br>";
			
			$html .= Forum::Singleton()->RenderForum($this->id);
		}
		return $html;
	}
	
	function RenderScript()
	{
		$user_id = Auth::Singleton()->id;
		
		$script = "<script language='javascript'>		
				
						function IHaveRead()
						{
							document.getElementById('read_button').innerHTML = '<img src=\"/img/wait.gif\">';
		
							$.get('" . BasicPage::Singleton()->RootDir() . "/Article.php?ihaveread=go&article={$this->id}&user_id=$user_id', function(respons)
							{		
								document.getElementById('read_button').innerHTML = 'Läst!';
							});
							
						}
					</script>";
		
		return $script;
	}

	function Delete()
	{
		Q("DELETE FROM articles WHERE id=?", array($this->id));
		Q("DELETE FROM article_content WHERE article_id=?", array($this->id));
			
		Q("DELETE FROM forum_threads WHERE article_id=?", array($this->id));
		Q("DELETE FROM forum_messages WHERE article_id=?", array($this->id));
			
		Q("DELETE FROM user_read_article_threads WHERE article_id=?", array($this->id));
			
		$res2 = Q("SELECT id FROM forum_threads WHERE article_id=?", array($this->id));
		while(list($thread_id) = $res2->fetch_array())
		{
			Q("DELETE FROM user_follows_thread WHERE thread_id=?", array($thread_id));
		}		
		Q("DELETE FROM article_in_group WHERE article_id=?", array($this->id));
	}
	
}

?>
