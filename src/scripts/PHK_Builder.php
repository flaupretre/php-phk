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

// <PHK:ignore>
require(dirname(__FILE__).'/../classes/external/phool/PHO_Getopt.php');
require(dirname(__FILE__).'/../classes/PHK.php');
require(dirname(__FILE__).'/../classes/PHK_Proxy.php');
require(dirname(__FILE__).'/../classes/PHK_Stream.php');
require(dirname(__FILE__).'/../classes/PHK_Stream_Backend.php');
require(dirname(__FILE__).'/../classes/PHK_Cache.php');
require(dirname(__FILE__).'/../classes/PHK_PSF.php');
require(dirname(__FILE__).'/../classes/external/automap/Automap_Creator.php');
require(dirname(__FILE__).'/../classes/PHK_DataStacker.php');
require(dirname(__FILE__).'/../classes/PHK_ItemLister.php');
require(dirname(__FILE__).'/../classes/PHK_Creator.php');
// <PHK:end>

//============================================================================

//-- Check PHP version - if unsupported, no return

ini_set('display_errors',true);
PHK_Mgr::php_version_check();

//---------
// <Automap>:ignore function send_error

function send_error($msg,$usage=true)
{
if ($usage) usage($msg);
else echo "** ERROR: $msg\n";
exit(1);
}

//---------
// <Automap>:ignore function usage

function usage($msg=null)
{
if (!is_null($msg)) echo "** ERROR: $msg\n";

echo "\nUsage: <action> <params...>\n";
echo "\nActions :\n\n";
echo "	- build <PHK file> <PSF file>\n";
echo "	- help\n\n";

exit(is_null($msg) ? 0 : 1);
}

//---------

try {

if (PHK_Util::env_is_web())
	throw new Exception('This package is supposed to be run in CLI mode only');

array_shift($_SERVER['argv']);
$action=(count($_SERVER['argv'])) ? $_SERVER['argv'][0] : 'help';

switch($action)
	{
	case 'build':
		if (count($_SERVER['argv'])<3) send_error('build needs 2 args');
		$phk_file=$_SERVER['argv'][1];
		$psf_file=$_SERVER['argv'][2];
		array_shift($_SERVER['argv']);array_shift($_SERVER['argv']);array_shift($_SERVER['argv']);
		PHK_PSF::build($phk_file,$psf_file,array_values($_SERVER['argv']));
		break;		

	case 'help':
		usage();
		break;

	default:
		send_error("Unknown action: '$action'");
	}

} catch(Exception $e)
	{
	if (getenv('PHK_DEBUG')!==false) throw $e;
	else send_error($e->getMessage(),false);
	}

//============================================================================
?>
