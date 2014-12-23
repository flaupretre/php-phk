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
* phkmgr CLI script. Builds and manages PHK packages.
*
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*/
//============================================================================

class PHK_Cmd
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
echo "
Available commands :

  - build [-s <psf-path>] [-d <var>=<value> ...]] <package-path>
        Builds a PHK package
        Options :
            -s <psf-path> : the path to the Package Specification File. If this
			                option is not set, the PSF path is computed from
							the package path (replacing file suffix with '.psf').
            -d <var>=<value> : Define a variable. In the PSF, variable expansion
                               syntax is '\$(var)'. Can be set more than once.

  - check <package-path>
        Checks an existing package's integrity
        Options :
            -f <format> : Output format. Default is 'auto'.

    - help
        Display this message

Global options :

  -v : Increase verbose level (can be set more than once)
  -q : Decrease verbose level (can be set more than once)

More information at http://phk.tekwire.net\n\n";
}

//---------
// Main
// Options can be located before AND after the action keyword.

public static function run($args)
{
$op=new PHK_Cmd_Options;
$op->parse_all($args);
$action=(count($args)) ? array_shift($args) : 'help';

switch($action)
	{
	case 'build':
		if (count($args)!=1) self::error_abort("$action requires 1 argument");
		$phk_path=array_shift($args);
		PHK_PSF::build($phk_path,$op->option('psf_path'),$op->option('vars'));
		break;

	case 'check':
		if (count($args)!=1) self::error_abort("$action requires 1 argument");
		$phk_path=array_shift($args);
		$mnt=PHK_Mgr::mount($phk_path);
		$obj=PHK_Mgr::instance($mnt);
		$obj->check();
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