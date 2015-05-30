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

namespace PHK {

// When used outside of a PHK package, the main script includes every other
// scripts. When used in a PHK package, we use the autoloader.

// <PHK:ignore>
require(dirname(__FILE__).'/../submodules/automap/src/Automap/Mgr.php');
require(dirname(__FILE__).'/../submodules/automap/src/Automap/Map.php');
require(dirname(__FILE__).'/../submodules/automap/src/Automap/Build/Creator.php');
require(dirname(__FILE__).'/../submodules/automap/src/Automap/Build/ParserInterface.php');
require(dirname(__FILE__).'/../submodules/automap/src/Automap/Build/Parser.php');
require(dirname(__FILE__).'/../submodules/automap/src/Automap/Tools/Display.php');

require(dirname(__FILE__).'/../submodules/phool/src/Phool/Display.php');
require(dirname(__FILE__).'/../submodules/phool/src/Phool/File.php');
require(dirname(__FILE__).'/../submodules/phool/src/Phool/Options/Getopt.php');
require(dirname(__FILE__).'/../submodules/phool/src/Phool/Options/Base.php');
require(dirname(__FILE__).'/../submodules/phool/src/Phool/Util.php');

require(dirname(__FILE__).'/../submodules/Yaml/Dumper.php');
require(dirname(__FILE__).'/../submodules/Yaml/Escaper.php');
require(dirname(__FILE__).'/../submodules/Yaml/Exception/ExceptionInterface.php');
require(dirname(__FILE__).'/../submodules/Yaml/Exception/RuntimeException.php');
require(dirname(__FILE__).'/../submodules/Yaml/Exception/DumpException.php');
require(dirname(__FILE__).'/../submodules/Yaml/Exception/ParseException.php');
require(dirname(__FILE__).'/../submodules/Yaml/Inline.php');
require(dirname(__FILE__).'/../submodules/Yaml/Parser.php');
require(dirname(__FILE__).'/../submodules/Yaml/Unescaper.php');
require(dirname(__FILE__).'/../submodules/Yaml/Yaml.php');

require(dirname(__FILE__).'/../src/PHK/Base.php');
require(dirname(__FILE__).'/../src/PHK/Backend.php');
require(dirname(__FILE__).'/../src/PHK/Cache.php');
require(dirname(__FILE__).'/../src/PHK/CLI/Options.php');
require(dirname(__FILE__).'/../src/PHK/CLI/Cmd.php');
require(dirname(__FILE__).'/../src/PHK/Build/Creator.php');
require(dirname(__FILE__).'/../src/PHK/Build/DataStacker.php');
require(dirname(__FILE__).'/../src/PHK/Virtual/DC.php');
require(dirname(__FILE__).'/../src/PHK/PkgFile.php');
require(dirname(__FILE__).'/../src/PHK/Tools/ItemLister.php');
require(dirname(__FILE__).'/../src/PHK/Mgr.php');
require(dirname(__FILE__).'/../src/PHK.php');
require(dirname(__FILE__).'/../src/PHK/UnitTest/PHPUnit.php');
require(dirname(__FILE__).'/../src/PHK/Proxy.php');
require(dirname(__FILE__).'/../src/PHK/Build/PSF/CmdOptions.php');
require(dirname(__FILE__).'/../src/PHK/Build/PSF/MetaOptions.php');
require(dirname(__FILE__).'/../src/PHK/Build/PSF/Parser.php');
require(dirname(__FILE__).'/../src/PHK/Stream/Backend.php');
require(dirname(__FILE__).'/../src/PHK/Stream/Wrapper.php');
require(dirname(__FILE__).'/../src/PHK/Virtual/Node.php');
require(dirname(__FILE__).'/../src/PHK/Virtual/Dir.php');
require(dirname(__FILE__).'/../src/PHK/Virtual/File.php');
require(dirname(__FILE__).'/../src/PHK/Virtual/Tree.php');
require(dirname(__FILE__).'/../src/PHK/Tools/Util.php');
require(dirname(__FILE__).'/../src/PHK/Web/Info.php');
// <PHK:end>

try
{
ini_set('display_errors',true);
Mgr::checkPhpVersion();

$args=$_SERVER['argv'];
array_shift($args);
CLI\Cmd::run($args);
}
catch(\Exception $e)
	{
	if (getenv('SHOW_EXCEPTION')!==false) throw $e;
	else echo "*** ERROR: ".$e->getMessage()."\n\n";
	exit(1);
	}

} // End of namespace
//===========================================================================
