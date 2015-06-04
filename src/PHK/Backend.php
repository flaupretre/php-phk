<?php
//=============================================================================
//
// Copyright Francois Laupretre <phk@tekwire.net>
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//   you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//       http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.
//
//=============================================================================
/**
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*///==========================================================================

namespace PHK {

if (!class_exists('PHK\Backend',false))
{
//=============================================================================
/**
* This class contains the non-accelerated runtime code. This code must
* never be accessed during 'fast path' scenarios.
*
* Each \PHK\Backend instance is associated with a 'front-end' PHK instance
* (accelerated or not).
*
* <Public API>
*/

class Backend
{

private $front; // PHK front-end

//--------------

public function __construct($front)
{
$this->front=$front;
}

//---------------------------------

public function __get($name)
{
return $this->front->$name();
}

//---------------------------------

public function __call($method,$args)
{
return \PHK\Tools\Util::callMethod($this->front,$method,$args);
}

//---------------------------------

public function test()
{
// Remove E_NOTICE messages if the test script is a package - workaround
// to PHP bug #39903 ('__COMPILER_HALT_OFFSET__ already defined')

error_reporting(($errlevel=error_reporting()) & ~E_NOTICE);

if (!is_null($test_script=$this->option('test_script')))
	{
	$test_uri=$this->uri($test_script);
	require($test_uri);
	}
elseif (!is_null($phpunit_test_package=$this->option('phpunit_test_package')))
	{
	if (!is_null($phpunit_package=$this->option('phpunit_package')))
		{ $phpunit_package_mnt=require $this->uri($phpunit_package); }
	else $phpunit_package_mnt=null;

	$phpunit_test_package_mnt=require $this->uri($phpunit_test_package);

	\PHK\UnitTest\_phk_load_phpunit_interface();
	define('PHPUnit_MAIN_METHOD', 'PHPUnit_TextUI_\PHK::main');
	PHPUnit_TextUI_\PHK::main();

	if (!is_null($phpunit_package_mnt)) \PHK\Mgr::umount($phpunit_package_mnt);

	if (!is_null($phpunit_test_package_mnt))
		\PHK\Mgr::umount($phpunit_test_package_mnt);
	}
else echo "No unit tests\n";

error_reporting($errlevel);
}

//---------------------------------
// Display the environment
// This function cannot be cached

public function envinfo()
{
$html=\PHK\Tools\Util::envIsWeb();

//-- Accelerator

self::infoSection($html,'PHK Accelerator');

self::startInfoTable($html);
if (\PHK::acceleratorIsPresent()) \PHK::accelTechInfo();
else self::showInfoLine($html,'PHK Accelerator','No');

self::infoSection($html,'Cache');

self::showInfoLine($html,'Cache system used',\PHK\Cache::cacheName());
self::endInfoTable($html);

//-- Environment

self::infoSection($html,'Environment');

self::startInfoTable($html);
self::showInfoLine($html,'PHP SAPI',php_sapi_name());
self::showInfoLine($html,'Mount point',$this->mnt);

//-- Mount options

$string='';
$class=new ReflectionClass('\PHK');
foreach($class->getConstants() as $name => $value)
	{
	if ((strlen($name)>1) && (substr($name,0,2)=='F_')
		&& ($this->flags & $value)) $string .= ','.strtolower(substr($name,2));
	}
unset($class);
$string=trim($string,',');
self::showInfoLine($html,'Current mount options'
	,$string=='' ? '<none>' : $string);
self::endInfoTable($html);
}

//---------------------------------
// Display the file tree

public function showfiles()
{
$this->proxy()->showfiles();
}

//---------------------------------
// Display map content or <empty> message

public function showmap($subfile_to_url_function=null)
{
if ($this->mapDefined())
	\Automap\Mgr::map($this->automapID)->show(null,$subfile_to_url_function);
else echo "This package does not contain a map\n";
}

//-----
/**
* <Info> Displays information about the plugin
*
* If the plugin object defines a method name '_webinfo', this method is called
* with the $html parameter.
*
* If the plugin is not defined, just displays a small informative message
*
* @param boolean $html Whether to display in html or raw text
* @return void
*/

private function pluginInfo($html)
{
self::infoSection($html,'Plugin');

if (is_null($class=$this->option('plugin_class')))
	{
	echo ($html ? '<p>' : '')."Not defined\n";
	return;
	}

if ($this->isCallablePluginMethod('_webinfo'))
	{
	$this->callPluginMethod('_webinfo',$html);
	echo $html ? '<p>' : "\n";
	}

self::startInfoTable($html);

self::showInfoLine($html,'Class',$class);

$rc=new ReflectionClass($class);

foreach ($rc->getMethods() as $method)
	{
	if ((!$method->isPublic())||($method->isStatic())
		||($method->isConstructor())||($method->isDestructor())
		||($method->getName()==='_webinfo')) continue;
	$name=$method->getName();
	$a=array();
	foreach($method->getParameters() as $param)
		{
		$s='$'.$param->getName();
		if ($param->isPassedByReference()) $s='&'.$s;
		if ($param->isArray()) $s = 'Array '.$s;
		if ($param->isOptional())
			{
			if ($param->isDefaultValueAvailable())
				$s .= ' = '.var_export($param->getDefaultValue(),true);
			$s = '['.$s.']';
			}
		$a[]=$s;
		}
	self::showInfoLine($html,'Method',$name.' ( '.implode(', ',$a).' )');
	}
		
self::endInfoTable($html);
}

//-----
/**
* <Info> Displays an option and its value
*
* An URL can be set in an option. Syntax: 'text to display <url>'
* In HTML mode, only the text is displayed and an hyperlink is generated
* In text mode, it is displayed as-is.
* URLs starting with 'http://' are automatically recognized

* @param boolean $html Whether to display in html or raw text
* @param string $opt Option name
* @return void
*/

private function showOption($html,$opt,$default=null)
{
$str1=ucfirst(str_replace('_',' ',$opt));

$url=null;
$newwin=true;
if (is_null($val=$this->option($opt))) $val=$default;

if ($html && preg_match('/^(.*)\s<(\S+)>.*$/',$val,$regs))
	{	// If the value contains an URL
	$str2=trim($regs[1]);
	$url=$regs[2];
	if ($str2=='') $str2=$url;
	}
else
	{
	$str2=$val;
	$vlen=strlen($val);
	if (($vlen>=7)&&(substr($val,0,7)=='http://')) $url=$val;
	elseif (($vlen>=1) && ($val{0}=='/') && file_exists($this->uri($val)))
		{
		// Warning: We build an HTTP URL going to \PHK\Web\Info, not a stream wrapper URI.
		$url=\PHK::subpathURL('/view/'.trim($val,'/'));
		$newwin=false;
		}
	}

self::showInfoLine($html,$str1,$str2,$url,$newwin);
}

//-----
/**
* <Info> Start a new information section
*
* @param boolean $html Whether to display in html or raw text
* @param string $title The title to display
* @return void
*/

public static function infoSection($html,$title)
{
echo $html ? '<h2>'.htmlspecialchars($title).'</h2>'
	: "\n==== ".str_pad($title.' ',70,'='). "\n\n";
}

//-----
/**
* <Info> Displays an information line
*
* In html mode, the information is displayed in a table. This table must
* have been opened by a previous call to \PHK::startInfoTable().
*
* Note: The URLs starting with a '/' char are internal (generated by \PHK\Web\Info
* ) and, so, are displayed in html mode only.
*
* @param boolean $html Whether to display in html or raw text
* @param string $string The left side (without ':')
* @param string|boolean $value The value to display. If boolean, displays 'Yes'
*    or 'No'.
* @param string|null $url An URL to associate with this value. Null if no URL.
* @param boolean $newwin Used in html mode only. Whether the URL link opens
*    a new window when clicked.
* @return void
*
* @see startInfoTable()
* @see endInfoTable()
*/

public static function showInfoLine($html,$string,$value,$url=null
	,$newwin=true)
{
if (is_null($value)) $value='<>';
if (is_bool($value)) $value=\PHK\Tools\Util::bool2str($value);

if ($html)
	{
	echo '<tr><td>'.htmlspecialchars($string).':&nbsp;</td><td>';
	if ($url)
		{
		echo '<a href="'.$url.'"';
		if ($newwin) echo ' target="_blank"';
		echo '>';
		}
	echo htmlspecialchars($value);
	if ($url) echo '</a>';
	echo '</td></tr>';
	}
else
	{
	echo "$string: $value";
	if ((!is_null($url)) && ($url{0}!='/')) echo " <$url>";
	echo "\n";
	}
}

//-----
/**
* <Info> Starts an HTML table
*
* In text mode, does nothing.
*
* This function is public because it can be called from the plugin's _webinfo
* method.
*
* @param boolean $html Whether to display in html or raw text
* @return void
*/

public static function startInfoTable($html)
{
echo $html ? '<table border=0>' : '';
}

//-----

public static function endInfoTable($html)
{
echo $html ? '</table>' : '';
}

//-----
// Display non technical information
// Webinfo default welcome page

public function info()
{
$html=\PHK\Tools\Util::envIsWeb();

if ($html && (!is_null($info_script=$this->option('info_script'))))
	{ require($this->uri($info_script)); }
else
	{
	self::startInfoTable($html);
	$this->showOption($html,'name');
	$this->showOption($html,'summary');
	$this->showOption($html,'version');
	$this->showOption($html,'release');
	$this->showOption($html,'distribution');
	$this->showOption($html,'license');
	$this->showOption($html,'copyright');
	$this->showOption($html,'url');
	$this->showOption($html,'author');
	$this->showOption($html,'packager');
	$this->showOption($html,'requires');

	$req=implode(' ',\PHK\Tools\Util::mkArray($this->option('required_extensions')));
	if ($req=='') $req='<none>';
	self::showInfoLine($html,'Required extensions',$req);

	self::endInfoTable($html);
	}
}

//-----

public function techinfo()
{
$html=\PHK\Tools\Util::envIsWeb();

self::infoSection($html,'Package');

self::startInfoTable($html);
$this->showOption($html,'name');
$this->showOption($html,'summary');
$this->showOption($html,'version');
$this->showOption($html,'release');
$this->showOption($html,'distribution');
$this->showOption($html,'license');
$this->showOption($html,'copyright');
$this->showOption($html,'url');
$this->showOption($html,'author');
$this->showOption($html,'packager');
$this->showOption($html,'requires');
self::showInfoLine($html,'Signed',$this->proxy()->signed());
self::showInfoLine($html,'Automap defined',$this->mapDefined());
self::showInfoLine($html,'File path',$this->path);
self::showInfoLine($html,'File size',filesize($this->path));

$req=implode(', ',\PHK\Tools\Util::mkArray($this->option('required_extensions')));
if ($req=='') $req='<none>';
self::showInfoLine($html,'Required extensions',$req);

self::showInfoLine($html,'Build date'
	,\PHK\Tools\Util::timeString($this->buildInfo('build_timestamp')));
$this->showOption($html,'icon');
$this->showOption($html,'crc_check',false);
$this->showOption($html,'help_prefix');
$this->showOption($html,'license_prefix');
$this->showOption($html,'auto_umount',false);
$this->showOption($html,'no_cache',false);
$this->showOption($html,'no_opcode_cache',false);
$this->showOption($html,'prolog_code_creator',false);
$this->showOption($html,'plain_prolog',false);
self::showInfoLine($html,'File count',count($this->pathList()));
self::endInfoTable($html);

$this->pluginInfo($html);

self::infoSection($html,'Package scripts');

self::startInfoTable($html);
$this->showOption($html,'cli_run_script');
$this->showOption($html,'web_run_script');
$this->showOption($html,'lib_run_script');
$this->showOption($html,'info_script');
$this->showOption($html,'mount_script');
$this->showOption($html,'umount_script');
$this->showOption($html,'test_script');
$this->showOption($html,'phpunit_package');
$this->showOption($html,'phpunit_test_package');

self::endInfoTable($html);

self::infoSection($html,'Module versions');

self::startInfoTable($html);
self::showInfoLine($html,'PHK Creator',$this->buildInfo('phk_creator_version'));
self::showInfoLine($html,'Automap Creator',$this->buildInfo('automap_creator_version'));
self::showInfoLine($html,'Automap min version',$this->buildInfo('automap_minVersion'));
self::endInfoTable($html);

self::infoSection($html,'Sub-packages');

ob_start();
$this->proxy()->displayPackages();
$data=ob_get_clean();
if ($data==='')	echo ($html ? '<p>' : '')."None\n";
else echo $data;

self::infoSection($html,'Web direct access');

self::startInfoTable($html);
$list=\PHK\Tools\Util::mkArray($this->option('web_access'));
self::showInfoLine($html,'State',count($list) ? 'Enabled' : 'Disabled');
$this->showOption($html,'web_main_redirect',false);
foreach($list as $path) self::showInfoLine($html,'Path',$path);
self::endInfoTable($html);

//-- Options

self::infoSection($html,'Package options');

$a=$this->options();
$data=(is_null($a) ? '<>' : print_r($a,true));
echo ($html ? ('<pre>'.htmlspecialchars($data).'</pre>') : $data);

//-- Sections

self::infoSection($html,'Sections');
$this->proxy()->stree()->display(false);
}

//-----
/**
* Returns a subfile content for a multi-type metafile
*
* File name : <prefix>.<type> - Type is 'txt' or 'htm'.
*
* A text file can be transformed to html, but the opposite is not possible.
*
* Type is determined by the SAPI type (CLI => txt, else => htm).
*
* @param string $prefix Prefix to search for
* @return string|null The requested content or null if not found
*/

public function autoFile($prefix)
{
$html=\PHK\Tools\Util::envIsWeb();
$txt_suffixes=array('.txt','');
$suffixes=($html ? array('.htm','.html') : $txt_suffixes);

$base_path=$this->uri($prefix);
foreach($suffixes as $suffix)
	{
	if (is_readable($base_path.$suffix))
		{
		return \PHK\Tools\Util::readFile($base_path.$suffix);
		break;
		}
	}

// If html requested and we only have a txt file, tranform it to html

if ($html)
	{
	foreach ($txt_suffixes as $suffix)
		if (is_readable($base_path.$suffix))	
			return '<pre>'.htmlspecialchars(\PHK\Tools\Util::readFile($base_path.$suffix))
				.'</pre>';
	}

return null;
}

//-----
/**
* Returns a multi-type content from an option name
*
* Option ($name.'_prefix') gives the prefix to send to autoFile()
*
* @param string $name Option prefix
* @return string Requested content or an informative error string.
*/

public function autoOption($name)
{
$data=null;

$prefix=$this->option($name.'_prefix');

if (!is_null($prefix)) $data=$this->autoFile($prefix);

if (is_null($data))
	{
	$data='<No '.$name.' file>'."\n";
	if (\PHK\Tools\Util::envIsWeb()) $data=htmlspecialchars($data);
	}

return $data;
}

//-----
/**
* Checks if the plugin class is defined and contains a given method
*
* @param string $method
* @return boolean
*/

public function isCallablePluginMethod($method)
{
return (is_null($this->plugin)) ? false
	: is_callable(array($this->plugin,$method));
}

//-----
/**
* Calls a given method in the plugin object
*
* @param string method
* @return * the method's return value
* @throws \Exception if the plugin or the method does not exist
*/

public function callPluginMethod($method)
{
if (!$this->isCallablePluginMethod($method))
	throw new \Exception($method.': Undefined plugin method');

$args=func_get_args();
array_shift($args);

return call_user_func_array(array($this->plugin,$method),$args);
}

//-----

public function pathList()
{
return unserialize(file_get_contents($this->commandURI(__FUNCTION__)));
}

//-----

public function sectionList()
{
return unserialize(file_get_contents($this->commandURI(__FUNCTION)));
}

//-----
/**
* Check a package
*
* TODO: There's a lot more to check...
*
* @return array of error messages
*/

public function check()
{
$errors=array();

// Check package CRC

try { $this->proxy()->crcCheck(); }
catch (\Exception $e) {	$errors[]=$e->getMessage(); }

// Check symbol map

$id=$this->automapID();
if ($id)
	{
	$map=\Automap\Mgr::map($id);
	$errors=array_merge($errors,$map->check());
	}

return $errors;
}

//---------------------------------
// Workaround for PHP bug/issue when trying to use PATH_INFO when PHP is
// run as an Apache CGI executable. In this mode, an url in the form of
// 'http://.../.../file.php/args' does not go to file.php but returns
// 'No input file specified'. There, we have to pass args the 'usual'
// way (via $_REQUEST).
// Drawback: as the URL now contains a '?' char, most browsers refuse to cache
// it, even with the appropriate header fields, causing some useless traffic
// when navigating in the tabs and flicking on the screen. So, the preferred
// method is via PATH_INFO.
// Allows a PHK package to become fully compatible with CGI mode by computing
// every relative URLs through this method.

public static function subpathURL($path)
{
if ($path{0}!='/') $path=\PHK::setSubpath().'/'.$path; //-- Make path absolute
$path=preg_replace(',//+,','/',$path);

return \PHK\Tools\Util::httpBaseURL().((php_sapi_name()=='cgi')
	? ('?_phk_path='.urlencode($path)) : $path);
}

//-----

private static function cmdUsage($msg=null)
{
if (!is_null($msg)) echo "** ERROR: $msg\n";

echo "\nAvailable commands:\n\n";
echo "	- @help             Display package help\n";
echo "	- @license          Display license\n";
echo "	- @get <path>       Display a subfile content\n";
echo "	- @showmap          Display symbol map, if present\n";
echo "	- @showfiles        List subfiles\n";
echo "	- @check            Check package\n";
echo "	- @option <name>    Display a package option\n";
echo "	- @set_interp <string>  Set the first line of the PHK to '#!<string>'\n";
echo "	- @info             Display information about the PHK file\n";
echo "	- @techinfo         Display technical information\n";
echo "	- @dump <directory> Extracts the files\n";
echo "	- @test [switches] [UnitTest]  Run the package's unit tests\n";

if (!is_null($msg)) exit(1);
}

//-----

public function builtinProlog($file)
{
$retcode=0;
$args=$_SERVER['argv'];

try
{
$this->proxy()->crcCheck();

$command=\PHK\Tools\Util::substr($args[1],1);
array_shift($args);
$param=isset($args[1]) ? $args[1] : null;

switch($command)
	{
	case 'get':
		if (is_null($param))
			self::cmdUsage($command.": needs argument");
		$uri=$this->uri($param);
		if (is_file($uri)) readfile($uri);
		else throw new \Exception("$param: file not found");
		break;

	case 'test':
	case 'showmap':
	case 'info':
	case 'techinfo':
	case 'showfiles':
		$this->$command();
		break;

	case 'check':
		$errs=$this->check();
		if (count($errs))
			{
			foreach($errs as $err) echo "$err\n";
			throw new \Exception("*** The check procedure found errors in $phk_path");
			}
		echo "Check OK\n";
		break;

	case 'option':
		$res=$this->$command($param);
		if (is_null($res)) throw new \Exception('Option not set');
		echo "$res\n";
		break;

	case 'set_interp':
		if (is_null($param))
			self::cmdUsage($command.": needs argument");

		//-- This is the only place in the runtime code where we write something
		//-- into an existing PHK archive.

		if (file_put_contents($file
			,\PHK\Proxy::setBufferInterp($file,$param))===false)
			throw new \Exception('Cannot write file');
		break;

	case 'license':
	case 'licence':
		echo $this->autoOption('license');
		break;

	case 'help':
		echo $this->autoOption($command);
		break;

	case 'dump':
		if (is_null($param))
			self::cmdUsage($command.": needs argument");
		$this->proxy()->ftree()->dump($param);
		break;

	case '':
		self::cmdUsage();
		break;

	default:
		self::cmdUsage($command.': Unknown command');
	}

\PHK\Tools\Util::displaySlowPath();
}
catch (\Exception $e)
	{
	if (getenv('SHOW_EXCEPTION')!==false) throw $e;
	echo "** ERROR: Command failed ($command) - ".$e->getMessage()."\n";
	$retcode=1;
	}

return $retcode;
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
