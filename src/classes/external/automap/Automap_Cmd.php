<?php
//=============================================================================
//
// Copyright Francois Laupretre <automap@tekwire.net>
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
* The main script to build and manage automaps. This script is a wrapper around
* the Automap_Creator class.
*
* @copyright Francois Laupretre <automap@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category Automap
* @package Automap
*/
//============================================================================

// <PLAIN_FILE> //---------------
require_once(dirname(__FILE__).'/Automap_Creator.php');
// </PLAIN_FILE> //---------------

class Automap_Cmd
{
//---------

private static function error_abort($msg,$usage=true)
{
if ($usage) $msg .= " - Use 'help' command for syntax";
throw new Exception($msg);
}

//---------

private static function usage()
{
echo "\nUsage: <action> <params...>\n\n";
echo "Actions :\n";
echo "    - show <map file>\n";
echo "    - check <map file> [<base dir>]\n";
echo "    - export <map file> [<output file>]\n";

if (class_exists('Automap_Creator',1))
	{
	echo "    - register_extensions <map file> (execute using 'php -n -d extension_dir=<dir>'\n";
	echo "    - register <map file> <base dir> <relative file paths...>\n";
	echo "    - merge <target map> <relative path> <source maps...>\n";
	echo "    - import <map file> [<source file>]\n";
	}

echo "    - help\n\n";
echo "Please consult the Automap website (http://automap.tekwire.net) for more.\n\n";
}

//---------
// Main

public static function run($args)
{
array_shift($args);
$action=(count($args)) ? array_shift($args) : 'help';
if (array_key_exists(0,$args))
	{
	$mapfile=$args[0];
	array_shift($args);
	}
else $mapfile=null;

switch($action)
	{
	case 'show': //-- display <map file>
		if (is_null($mapfile)) self::error_abort('No mapfile');
		Automap::instance(Automap::mount($mapfile))->show();
		break;

	case 'check': //-- check <map file> [<base dir>]
		if (is_null($mapfile)) self::error_abort('No mapfile');
		$base_dir=((count($args)==0) ? null : $args[0]);
		$c=Automap::instance(Automap::mount($mapfile,$base_dir))->check();
		if ($c) throw new Exception("*** The check procedure found $c error(s) in file $mapfile");
		break;

	case 'register_extensions':
		//-- Must be executed with :
		//-- php -n -d <Extension_dir> Automap_Builder.php register_extensions
		//-- in order to ignore extension preloading directives in php.ini
		//-- (if an extension is already loaded, we cannot determine which file
		//-- it came from). The '-d' flag is mandatory as long as PHP cannot
		//-- dl() outside of 'extension_dir'.

		if (!class_exists('Automap_Creator',1)) self::error_abort("Unknown action: '$action'");
		if (is_null($mapfile)) self::error_abort('No mapfile');
		$mf=new Automap_Creator($mapfile);
		$mf->register_extension_dir();
		$mf->dump();
		break;

	case 'register':
		//-- register <map file> <$base> <script files (relative paths)>

		if (!class_exists('Automap_Creator',1)) self::error_abort("Unknown action: '$action'");
		if (is_null($mapfile)) self::error_abort('No mapfile');
		if (count($args)==0) self::error_abort('No base dir');
		$base=$args[0];
		$mf=new Automap_Creator($mapfile);
		array_shift($args);
		if (count($args)==0) $args=array('.');
		foreach($args as $rpath)
			{
			$abs_path=$base.DIRECTORY_SEPARATOR.$rpath;
			$mf->register_path($abs_path,$rpath);
			}
		$mf->dump();
		break;

	case 'merge':
		if (!class_exists('Automap_Creator',1)) self::error_abort("Unknown action: '$action'");
		if (is_null($mapfile)) self::error_abort('No mapfile');
		if (count($args)==0) self::error_abort('No relative path');
		$rpath=$args[0];
		$mf=new Automap_Creator($mapfile);
		array_shift($args);
		foreach($args as $source_path) $mf->merge_map($source_path,$rpath);
		$mf->dump();
		break;

	case 'export': //-- export <map file>
		if (is_null($mapfile)) self::error_abort('No mapfile');
		$output=isset($args[1]) ? $args[1] : null;
		Automap::instance(Automap::mount($mapfile))->export($output);
		break;

	case 'import': //-- import <map file>
		if (!class_exists('Automap_Creator',1)) self::error_abort("Unknown action: '$action'");
		if (is_null($mapfile)) self::error_abort('No mapfile');
		$mf=new Automap_Creator($mapfile);
		foreach($args as $rfile) $mf->import($rfile);
		$mf->dump();
		break;

	case 'help':
		self::usage();
		break;

	default:
		self::error_abort("Unknown action: '$action'");
	}
}

//============================================================================
} // End of class
?>
