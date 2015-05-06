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

// When used outside of a PHK package, the main script includes every other
// scripts. When used in a PHK package, we use the autoloader.

// <PHK:ignore>
require(dirname(__FILE__).'/../classes/external/automap/Automap.php');
require(dirname(__FILE__).'/../classes/external/automap/Automap_Creator.php');
require(dirname(__FILE__).'/../classes/external/automap/Automap_Parser.php');
require(dirname(__FILE__).'/../classes/external/automap/Automap_Display.php');
require(dirname(__FILE__).'/../classes/external/automap/Automap_Map.php');

require(dirname(__FILE__).'/../../submodules/phool/src/classes/PHO_Display.php');
require(dirname(__FILE__).'/../../submodules/phool/src/classes/PHO_File.php');
require(dirname(__FILE__).'/../../submodules/phool/src/classes/PHO_Getopt.php');
require(dirname(__FILE__).'/../../submodules/phool/src/classes/PHO_Options.php');
require(dirname(__FILE__).'/../../submodules/phool/src/classes/PHO_Util.php');

require(dirname(__FILE__).'/../../submodules/Yaml/Dumper.php');
require(dirname(__FILE__).'/../../submodules/Yaml/Escaper.php');
require(dirname(__FILE__).'/../../submodules/Yaml/Exception/ExceptionInterface.php');
require(dirname(__FILE__).'/../../submodules/Yaml/Exception/RuntimeException.php');
require(dirname(__FILE__).'/../../submodules/Yaml/Exception/DumpException.php');
require(dirname(__FILE__).'/../../submodules/Yaml/Exception/ParseException.php');
require(dirname(__FILE__).'/../../submodules/Yaml/Inline.php');
require(dirname(__FILE__).'/../../submodules/Yaml/Parser.php');
require(dirname(__FILE__).'/../../submodules/Yaml/Unescaper.php');
require(dirname(__FILE__).'/../../submodules/Yaml/Yaml.php');

require(dirname(__FILE__).'/../classes/PHK_Base.php');
require(dirname(__FILE__).'/../classes/PHK_Backend.php');
require(dirname(__FILE__).'/../classes/PHK_Cache.php');
require(dirname(__FILE__).'/../classes/PHK_Cmd_Options.php');
require(dirname(__FILE__).'/../classes/PHK_Cmd.php');
require(dirname(__FILE__).'/../classes/PHK_Creator.php');
require(dirname(__FILE__).'/../classes/PHK_DataStacker.php');
require(dirname(__FILE__).'/../classes/PHK_DC.php');
require(dirname(__FILE__).'/../classes/PHK_File.php');
require(dirname(__FILE__).'/../classes/PHK_ItemLister.php');
require(dirname(__FILE__).'/../classes/PHK_Mgr.php');
require(dirname(__FILE__).'/../classes/PHK.php');
require(dirname(__FILE__).'/../classes/PHK_PHPUnit.php');
require(dirname(__FILE__).'/../classes/PHK_Proxy.php');
require(dirname(__FILE__).'/../classes/PHK_PSF_Cmd_Options.php');
require(dirname(__FILE__).'/../classes/PHK_PSF_Options_Options.php');
require(dirname(__FILE__).'/../classes/PHK_PSF.php');
require(dirname(__FILE__).'/../classes/PHK_Stream_Backend.php');
require(dirname(__FILE__).'/../classes/PHK_Stream.php');
require(dirname(__FILE__).'/../classes/PHK_TNode.php');
require(dirname(__FILE__).'/../classes/PHK_TDir.php');
require(dirname(__FILE__).'/../classes/PHK_TFile.php');
require(dirname(__FILE__).'/../classes/PHK_Tree.php');
require(dirname(__FILE__).'/../classes/PHK_Util.php');
require(dirname(__FILE__).'/../classes/PHK_Webinfo.php');
// <PHK:end>

try
{
ini_set('display_errors',true);
PHK_Mgr::php_version_check();

$args=$_SERVER['argv'];
array_shift($args);
PHK_Cmd::run($args);
}
catch(Exception $e)
	{
	if (getenv('PHK_DEBUG')!==false) throw $e;
	else echo "*** ERROR: ".$e->getMessage()."\n\n";
	exit(1);
	}
