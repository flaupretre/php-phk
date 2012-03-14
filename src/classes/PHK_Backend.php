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
*/
//=============================================================================

if (!class_exists('PHK_Backend',false))
{
//=============================================================================
/**
* This class contains the non-accelerated runtime code. This code must
* never be called by the 'fast path' scenarios.
*
* Each PHK_Backend instance is associated with a 'front-end' PHK instance
* (accelerated or not).
*/

class PHK_Backend
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
return call_user_func_array(array($this->front,$method),$args);
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

	_phk_load_phpunit_interface();
	define('PHPUnit_MAIN_METHOD', 'PHPUnit_TextUI_PHK::main');
	PHPUnit_TextUI_PHK::main();

	if (!is_null($phpunit_package_mnt)) PHK_Mgr::umount($phpunit_package_mnt);

	if (!is_null($phpunit_test_package_mnt))
		PHK_Mgr::umount($phpunit_test_package_mnt);
	}
else echo "No unit tests\n";

error_reporting($errlevel);
}

//---------------------------------
// Display the environment
// This function cannot be cached

public function envinfo()
{
$html=PHK_Util::is_web();

//-- Accelerator

self::info_section($html,'PHK Accelerator');

self::start_info_table($html);
if (PHK::accelerator_is_present()) PHK::accel_techinfo();
else self::show_info_line($html,'PHK Accelerator','No');

self::info_section($html,'Automap Accelerator');

self::start_info_table($html);
if (Automap::accelerator_is_present()) Automap::accel_techinfo();
else self::show_info_line($html,'Automap Accelerator','No');

self::info_section($html,'Cache');

self::show_info_line($html,'Cache system used',PHK_Cache::cache_name());
self::end_info_table($html);

//-- Environment

self::info_section($html,'Environment');

self::start_info_table($html);
self::show_info_line($html,'PHP SAPI',php_sapi_name());
self::show_info_line($html,'Mount point',$this->mnt);

//-- Mount options

$string='';
$class=new ReflectionClass('PHK');
foreach($class->getConstants() as $name => $value)
	{
	if ((strlen($name)>1) && (substr($name,0,2)=='F_')
		&& ($this->flags & $value)) $string .= ','.strtolower(substr($name,2));
	}
unset($class);
$string=trim($string,',');
self::show_info_line($html,'Current mount options'
	,$string=='' ? '<none>' : $string);
self::end_info_table($html);
}

//---------------------------------
// Display the file tree

public function showfiles()
{
$this->proxy()->showfiles();
}

//---------------------------------
// Display Automap map content or <empty> message

public function showmap($subfile_to_url_function=null)
{
if ($this->map_defined())
	Automap::instance($this->mnt)->show($subfile_to_url_function);
else echo "Automap not defined\n";
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

private function plugin_info($html)
{
self::info_section($html,'Plugin');

if (is_null($class=$this->option('plugin_class')))
	{
	echo ($html ? '<p>' : '')."Not defined\n";
	return;
	}

if ($this->is_callable_plugin_method('_webinfo'))
	{
	$this->call_plugin_method('_webinfo',$html);
	echo $html ? '<p>' : "\n";
	}

self::start_info_table($html);

self::show_info_line($html,'Class',$class);

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
	self::show_info_line($html,'Method',$name.' ( '.implode(', ',$a).' )');
	}
		
self::end_info_table($html);
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

private function show_option($html,$opt,$default=null)
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
		// Warning: We build an HTTP URL going to PHK_Webinfo, not a
		// stream wrapper URI.
		$url=PHK::subpath_url('/view/'.trim($val,'/'));
		$newwin=false;
		}
	}

self::show_info_line($html,$str1,$str2,$url,$newwin);
}

//-----
/**
* <Info> Start a new information section
*
* @param boolean $html Whether to display in html or raw text
* @param string $title The title to display
* @return void
*/

public static function info_section($html,$title)
{
echo $html ? '<h2>'.htmlspecialchars($title).'</h2>'
	: "\n==== ".str_pad($title.' ',70,'='). "\n\n";
}

//-----
/**
* <Info> Displays an information line
*
* In html mode, the information is displayed in a table. This table must
* have been opened by a previous call to PHK::start_info_table().
*
* Note: The URLs starting with a '/' char are internal (generated by PHK_Webinfo
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
* @see start_info_table()
* @see end_info_table()
*/

public static function show_info_line($html,$string,$value,$url=null
	,$newwin=true)
{
if (is_null($value)) $value='<>';
if (is_bool($value)) $value=PHK_Util::bool2str($value);

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

public static function start_info_table($html)
{
echo $html ? '<table border=0>' : '';
}

//-----

public static function end_info_table($html)
{
echo $html ? '</table>' : '';
}

//-----
// Display non technical information
// Webinfo default welcome page

public function info()
{
$html=PHK_Util::is_web();

if ($html && (!is_null($info_script=$this->option('info_script'))))
	{ require($this->uri($info_script)); }
else
	{
	self::start_info_table($html);
	$this->show_option($html,'name');
	$this->show_option($html,'summary');
	$this->show_option($html,'version');
	$this->show_option($html,'release');
	$this->show_option($html,'distribution');
	$this->show_option($html,'license');
	$this->show_option($html,'copyright');
	$this->show_option($html,'url');
	$this->show_option($html,'author');
	$this->show_option($html,'packager');
	$this->show_option($html,'requires');

	$req=implode(' ',PHK_Util::mk_array($this->option('required_extensions')));
	if ($req=='') $req='<none>';
	self::show_info_line($html,'Required extensions',$req);

	self::end_info_table($html);
	}
}

//-----

public function techinfo()
{
$html=PHK_Util::is_web();

self::info_section($html,'Package');

self::start_info_table($html);
$this->show_option($html,'name');
$this->show_option($html,'summary');
$this->show_option($html,'version');
$this->show_option($html,'release');
$this->show_option($html,'distribution');
$this->show_option($html,'license');
$this->show_option($html,'copyright');
$this->show_option($html,'url');
$this->show_option($html,'author');
$this->show_option($html,'packager');
$this->show_option($html,'requires');
self::show_info_line($html,'Signed',$this->proxy()->signed());
self::show_info_line($html,'Automap defined',$this->map_defined());
self::show_info_line($html,'File path',$this->path);
self::show_info_line($html,'File size',filesize($this->path));

$req=implode(', ',PHK_Util::mk_array($this->option('required_extensions')));
if ($req=='') $req='<none>';
self::show_info_line($html,'Required extensions',$req);

self::show_info_line($html,'Build date'
	,PHK_Util::timestring($this->build_info('build_timestamp')));
$this->show_option($html,'icon');
$this->show_option($html,'crc_check',false);
$this->show_option($html,'help_prefix');
$this->show_option($html,'license_prefix');
$this->show_option($html,'auto_umount',false);
$this->show_option($html,'no_cache',false);
$this->show_option($html,'no_opcode_cache',false);
$this->show_option($html,'prolog_code_creator',false);
$this->show_option($html,'plain_prolog',false);
self::show_info_line($html,'File count',count($this->path_list()));
self::end_info_table($html);

$this->plugin_info($html);

self::info_section($html,'Package scripts');

self::start_info_table($html);
$this->show_option($html,'cli_run_script');
$this->show_option($html,'web_run_script');
$this->show_option($html,'lib_run_script');
$this->show_option($html,'info_script');
$this->show_option($html,'mount_script');
$this->show_option($html,'umount_script');
$this->show_option($html,'test_script');
$this->show_option($html,'phpunit_package');
$this->show_option($html,'phpunit_test_package');

self::end_info_table($html);

self::info_section($html,'Module versions');

self::start_info_table($html);
self::show_info_line($html,'PHK_Creator',$this->build_info('PHK_Creator_version'));
self::show_info_line($html,'PHK min version',$this->build_info('PHK_min_version'));
self::show_info_line($html,'Automap_Creator class',$this->build_info('Automap_creator_version'));
self::show_info_line($html,'Automap min version',$this->build_info('Automap_min_version'));
self::show_info_line($html,'PHK_PSF class',$this->build_info('PHK_PSF_version'));
self::end_info_table($html);

self::info_section($html,'Sub-packages');

ob_start();
$this->proxy()->display_packages();
$data=ob_get_clean();
if ($data==='')	echo ($html ? '<p>' : '')."None\n";
else echo $data;

self::info_section($html,'Web direct access');

self::start_info_table($html);
$list=PHK_Util::mk_array($this->option('web_access'));
self::show_info_line($html,'State',count($list) ? 'Enabled' : 'Disabled');
$this->show_option($html,'web_main_redirect',false);
foreach($list as $path) self::show_info_line($html,'Path',$path);
self::end_info_table($html);

//-- Options

self::info_section($html,'Package options');

$a=$this->options();
$data=(is_null($a) ? '<>' : print_r($a,true));
echo ($html ? ('<pre>'.htmlspecialchars($data).'</pre>') : $data);

//-- Sections

self::info_section($html,'Sections');
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

public function auto_file($prefix)
{
$html=PHK_Util::is_web();
$txt_suffixes=array('.txt','');
$suffixes=($html ? array('.htm','.html') : $txt_suffixes);

$base_path=$this->uri($prefix);
foreach($suffixes as $suffix)
	{
	if (is_readable($base_path.$suffix))
		{
		return PHK_Util::readfile($base_path.$suffix);
		break;
		}
	}

// If html requested and we only have a txt file, tranform it to html

if ($html)
	{
	foreach ($txt_suffixes as $suffix)
		if (is_readable($base_path.$suffix))	
			return '<pre>'.htmlspecialchars(PHK_Util::readfile($base_path.$suffix))
				.'</pre>';
	}

return null;
}

//-----
/**
* Returns a multi-type content from an option name
*
* Option ($name.'_prefix') gives the prefix to send to auto_file()
*
* @param string $name Option prefix
* @return string Requested content or an informative error string.
*/

public function auto_option($name)
{
$data=null;

$prefix=$this->option($name.'_prefix');

if (!is_null($prefix)) $data=$this->auto_file($prefix);

if (is_null($data))
	{
	$data='<No '.$name.' file>'."\n";
	if (PHK_Util::is_web()) $data=htmlspecialchars($data);
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

public function is_callable_plugin_method($method)
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
* @throws Exception if the plugin or the method does not exist
*/

public function call_plugin_method($method)
{
if (!$this->is_callable_plugin_method($method))
	throw new Exception($method.': Undefined plugin method');

$args=func_get_args();
array_shift($args);

return call_user_func_array(array($this->plugin,$method),$args);
}

//-----

public function path_list()
{
return unserialize(file_get_contents($this->command_uri(__FUNCTION__)));
}

//-----

public function section_list()
{
return unserialize(file_get_contents($this->command_uri(__FUNCTION)));
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

public static function subpath_url($path)
{
if ($path{0}!='/') $path=PHK::get_subpath().'/'.$path; //-- Make path absolute
$path=preg_replace(',//+,','/',$path);

return PHK_Util::http_base_url().((php_sapi_name()=='cgi')
	? ('?_PHK_path='.urlencode($path)) : $path);
}

//-----

private static function cmd_usage($msg=null)
{
if (!is_null($msg)) echo "** ERROR: $msg\n";

echo "\nAvailable commands:\n\n";
echo "	- @help             Display package help\n";
echo "	- @license          Display license\n";
echo "	- @get <path>       Display a subfile content\n";
echo "	- @showmap          Display automap, if present\n";
echo "	- @showfiles        List subfiles\n";
echo "	- @option <name>    Display a package option\n";
echo "	- @set_interp <string>  Set the first line of the PHK to '#!<string>'\n";
echo "	- @info             Display information about the PHK file\n";
echo "	- @techinfo         Display technical information\n";
echo "	- @dump <directory> Extracts the files\n";
echo "	- @test [switches] [UnitTest]  Run the package's unit tests\n";

if (!is_null($msg)) exit(1);
}

//-----

public function builtin_prolog($file)
{
$retcode=0;

try
{
$this->proxy()->crc_check();

$command=PHK_Util::substr($_SERVER['argv'][1],1);
array_shift($_SERVER['argv']);
$param=isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null;

switch($command)
	{
	case 'get':
		if (is_null($param))
			self::cmd_usage($command.": needs argument");
		$uri=$this->uri($param);
		if (is_file($uri)) readfile($uri);
		else throw new Exception("$param: file not found");
		break;

	case 'test':
		$this->test();
		break;

	case 'showmap':
	case 'info':
	case 'techinfo':
	case 'showfiles':
		$this->$command();
		break;

	case 'option':
		$res=$this->$command($param);
		if (is_null($res)) throw new Exception('Option not set');
		echo "$res\n";
		break;

	case 'set_interp':
		if (is_null($param))
			self::cmd_usage($command.": needs argument");

		//-- This is the only place in the runtime code where we write something
		//-- into an existing PHK archive.

		if (file_put_contents($file
			,PHK_Proxy::set_buffer_interp($file,$param))===false)
			throw new Exception('Cannot write file');
		break;

	case 'license':
	case 'help':
		echo $this->auto_option($command);
		break;

	case 'dump':
		if (is_null($param))
			self::cmd_usage($command.": needs argument");
		$this->proxy()->ftree()->dump($param);
		break;

	case '':
		self::cmd_usage();
		break;

	default:
		self::cmd_usage($command.': Unknown command');
	}

PHK_Util::display_slow_path();
}
catch (Exception $e)
	{
	if (getenv('PHK_DEBUG')!==false) throw $e;
	echo "** ERROR: Command failed ($command) - ".$e->getMessage()."\n";
	$retcode=1;
	}

return $retcode;
}

//---------------------------------
} // End of class PHK_Backend
//-------------------------
} // End of class_exists('PHK_Backend')
//=============================================================================
?>
