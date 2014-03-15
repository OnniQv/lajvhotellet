<html>
<head>
<script  language="javascript">
<!--

function UpdateSearch(searchString)
{

	searchString = searchString.toLowerCase();
	
	var Users=new Array();
	var Groups=new Array();

	<?
		
		$usrs = array("Onni", "Kalle", "Erik");
		$groups = array("bygardet", "stan", "Alverna");
		$i = 0;
		foreach($usrs as $u)
		{
			echo "Users[$i]=\"$u\";";
			$i++;
		}
		$i = 0;
		foreach($groups as $g)
		{
			echo "Groups[$i]=\"$g\";";
			$i++;
		}
		
	
	?>

	
	var finalHtml = "";

	
	finalHtml = "<form>";

	finalHtml += "Users:<br>";
	
	
	for(key in Users)
		if(Users[key].toLowerCase().search(searchString) >= 0)
			finalHtml += "<input type='checkbox'>" + Users[key] + "<br>";
	
	
	finalHtml += "Groups:<br>";
	for(key in Groups)
		if(Groups[key].toLowerCase().search(searchString) >= 0)
			finalHtml += "<input type='checkbox'>" + Groups[key] + "<br>";
	
	finalHtml += "</form>";

	
	document.all.result.innerHTML = finalHtml;	
	
}

UpdateSearch("");

-->
</script>
</head>
<body>



<form>
<input id="search" name="search" oninput="UpdateSearch(this.value)">

</form>

<div id="result">
</div>

<a href="javascript: window.parent.HideWebSite()">Close</a>
<body>

</html>