<?PHP
$start_time = microtime(true);
mb_internal_encoding("ISO-8859-1");

include(__dir__."/Config.php");

set_include_path(include_directory);

session_start();
$uri = $_SERVER['REQUEST_URI'];

$uri = explode('?', $uri);
$uri = $uri[0];

$uri = explode('/', $uri);

//print_r($uri);

if ($uri[0] != "")
	die("0 not empty");

$i = 1;
	
if(folder != "")
{
	if ("/" . $uri[$i] != folder)
		die("Configuration error: uri[1] != folder");
	$i ++;
}
	
$larp_shortname = urldecode($uri[$i++]);

if ($larp_shortname == "dispatcher.php")
	die("404 1");

$page = "Home";

if(isset($uri[$i]))
	$page = urldecode($uri[$i++]);

	
$args = array();
while (isset($uri[$i]))
	$args[] = urldecode($uri[$i++]);

if (! file_exists("Pages/$page.php"))
	die("404 Page '$page' not found");
	
	/*
 * echo "Larp: $larp_name\r\n"; echo "Page: $page\r\n"; echo "Args:";
 * print_r($args);
 */

require_once ('Pages/BasicPage.php');
require_once ("Pages/$page.php");
require_once ("auth.php");
require_once ("SQL.php");
require_once ("PermissionsForm.php");
require_once ("Article.php");
require_once ("zebra_form/Zebra_Form.php");

$P = new Page();
$P->SetArgs($args);

Auth::Singleton()->Auth($larp_shortname);

echo $P->RenderAll();
$end_time = microtime(true);

echo "Total time: " . ($end_time - $start_time);

?>