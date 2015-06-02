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

namespace PHK\Build {

if (!class_exists('PHK\Build\Creator',false))
{
//============================================================================
/**
* The package creator class
*
* Note: If this file is part of a PHK package (usual case), it is
* absolutely mandatory that Automap and PHK classes as package's files
* are the same as the Automap and PHK classes included in the prolog. Which
* means that the package must be generated from its files. If it is not the
* case, packages generated from this creator package will display wrong version
* information.
*
* API status: Public
* Included in the PHK PHP runtime: No
* Implemented in the extension: No
*///==========================================================================

class Creator extends \PHK\Base
{
const MIN_RUNTIME_VERSION='3.0.0'; // The minimum version of PHK runtime able to
	// understand the packages I produce. Checked against PHK\Base::RUNTIME_VERSION
	// or, if PECL, PHP_PHK_VERSION.

//-----

public $prolog=null;
public $code=null;
private $interp='';

//-----
// init a new empty package.

public function __construct($mnt,$path,$flags)
{
parent::__construct(null,$mnt,$path,$flags,time());

$this->options=array();
$this->buildInfo=array('build_timestamp' => time());
}

//---------

public function ftree()
{
return $this->proxy()->ftree();
}

//---------

public function setOptions($opt)
{
$this->options=$opt;
}

//---------

public function updateOption($name,$a)
{
$this->options[$name]=$a;
}

//---------

public function updateBuildInfo($name,$a)
{
$this->buildInfo[$name]=$a;
}

//---------
// Allows to use an alternate prolog

public function setProlog($data)
{
$this->prolog=$data;
}

//---------

private function processPhpCode(&$buffer)
{
$buffer=str_replace('?><?php','',$buffer);
$buffer=str_replace("?>\n<?php",'',$buffer);

foreach (array('creator','plain_file') as $pcode)
	{
	if (!$this->option("prolog_code_$pcode"))
		{
		$token=strtoupper($pcode);
		while (($pos=strpos($buffer,'<'.$token.'>'))!==false)
			{
			if (($pos2=strpos($buffer,'</'.$token.'>',$pos))===false)
				throw new \Exception('Cannot find end of <'.$token.'> section');
			$buffer=substr_replace($buffer,'',$pos,$pos2-$pos+15);
			}
		}
	}

// Strip whitespaces from the prolog

if (!$this->option('plain_prolog'))
	{
	$buffer=\PHK\Virtual\File::stripWhitespaces($buffer);
	}
}

//---------
// Build runtime PHP code
//-- $dir= base dir where files are stored. In a package, it is the package's
//-- base URI, otherwise, it is the base of the source tree.

public function buildPhpCode($dir)
{
\Phool\Display::trace('Building PHP runtime');

$this->code='';

$automap_base=$dir;
if (! \PHK\Mgr::isPhkUri(__FILE__)) $automap_base .= '/submodules/automap';
$this->code .= \PHK\Tools\Util::readFile($automap_base.'/src/Automap/Mgr.php');
$this->code .= \PHK\Tools\Util::readFile($automap_base.'/src/Automap/Tools/Display.php');
$this->code .= \PHK\Tools\Util::readFile($automap_base.'/src/Automap/Map.php');
$this->code .= \PHK\Tools\Util::readFile($automap_base.'/src/Automap/Tools/Check.php');

$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Tools/Util.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/PkgFile.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Cache.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Proxy.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Mgr.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Base.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Backend.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Stream/Wrapper.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Stream/Backend.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Virtual/DC.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Virtual/Tree.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Virtual/Node.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Virtual/Dir.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Virtual/File.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/Web/Info.php');
$this->code .= \PHK\Tools\Util::readFile($dir.'/src/PHK/UnitTest/PHPUnit.php');

$this->processPhpCode($this->code);

$this->code=str_replace('<?php','',$this->code);
$this->code=str_replace('?>','',$this->code);
}

//---------
//-- Build prolog code
//-- $dir= base dir where files are stored

public function buildProlog($dir)
{
\Phool\Display::trace('Building prolog');

$this->prolog = \PHK\Tools\Util::readFile($dir.'/scripts/prolog.php');

//-- The four FF chars turn unicode detection off (see PHP bug #42396)

$this->prolog .= '<?php __halt_compiler(); ?>'.str_repeat(chr(255),4);

$this->processPhpCode($this->prolog);
}

//---------
// Create a new section and fill it with some data

public function addSection($name,$data,$modifiers=array())
{
\Phool\Display::trace("Adding section <$name>");

$this->proxy()->stree()->mkfile($name,$data,$modifiers);
}

//---------

public function dump($path=null)
{
if (is_null($path)) $path=$this->path();
\Phool\Display::trace("Writing package to disk ($path)");

$base_dir=dirname(dirname(dirname(__DIR__)));
$this->buildPhpCode($base_dir);

//-- Get creator version

if (\PHK\Mgr::isPhkUri(__FILE__))
	{
	$pkg=\PHK\Mgr::instance(\PHK\Mgr::uriToMnt(__FILE__));
	$creatorVersion=$pkg->option('version');
	}
else
	{
	$creatorVersion=getenv('SOFTWARE_VERSION');
	}
if (!is_string($creatorVersion))
	throw new \Exception('Cannot determine creator version');

//-- Build prolog if not already set

if (is_null($this->prolog)) $this->buildProlog($base_dir);

//-- Build FILES part and FTREE section
// Build map, strip sources, etc...

$needed_extensions=new \PHK\Tools\ItemLister;
$this->proxy()->ftree()->walk('getNeededExtensions',$this,$needed_extensions);

$map=new \Automap\Build\Creator();
list($files_structure,$files_data)=$this->proxy()->ftree()->export($this,$map);

$this->addSection('FTREE',$files_structure);
unset($files_structure);

//-- Build Automap section

if ($map->symbolCount())
	$this->addSection(\PHK\Proxy::AUTOMAP_SECTION,$map->serialize());

$this->updateBuildInfo('map_defined',($map->symbolCount()!=0));

//-- Tabs sections / PHK icon

foreach(array('tabs/left.gif','tabs/right.gif','tabs/bottom.gif'
	,'tabs/tabs.css.php','phk_logo.png') as $f)
	{
	$source=$base_dir.'/etc/'.$f;
	$this->addSection('STATIC/'.$f,\PHK\Tools\Util::readFile($source));
	}

//-- Build info

$this->updateBuildInfo('phk_creator_version',$creatorVersion);
$this->updateBuildInfo('automap_creator_version',\Automap\Build\Creator::VERSION);
$this->updateBuildInfo('automap_minVersion',\Automap\Build\Creator::MIN_RUNTIME_VERSION);

//-- Record the user-specified needed extensions

foreach(\PHK\Tools\Util::mkArray($this->option('required_extensions')) as $ext)
	{
	if ($ext!=='') $needed_extensions->add($ext,true);
	}

//-- Flush sections
//-- Add the uncompress needed extensions to the user required extensions

$this->proxy()->stree()->walk('getNeededExtensions',$this,$needed_extensions);

$ext=array_keys($needed_extensions->get());
if (count($ext)) $this->updateOption('required_extensions',$ext);

//-- Ensure mime_types, if present, is an array

if (!is_null($this->option('mime_types')))
	$this->updateOption('mime_types'
		,\PHK\Tools\Util::mkArray($this->option('mime_types')));

$this->addSection('OPTIONS',serialize($this->options()));

// Add build info section

$this->addSection('BUILD_INFO',serialize($this->buildInfo()));

// Now, dump the section tree

\PHK\Tools\Util::trace('--- Sections');

list($sections_structure,$sections_data)=$this->proxy()->stree()->export($this);

//---

$prolog_offset=\PHK\Proxy::INTERP_LEN+\PHK\Proxy::MAGIC_LINE_LEN;
$code_offset=$prolog_offset+strlen($this->prolog);
$sections_structure_offset=$code_offset+strlen($this->code);
$sections_offset=$sections_structure_offset+strlen($sections_structure);
$files_offset=$sections_offset+strlen($sections_data);
$sig_offset=$files_offset+strlen($files_data);
$file_size=$sig_offset;

$buf=\PHK\Proxy::fixCrc(\PHK\Proxy::interpBlock($this->interp)
	.'<?php '.\PHK\Proxy::MAGIC_STRING
	.' M'  .str_pad(self::MIN_RUNTIME_VERSION,\PHK\Proxy::VERSION_SIZE)
	.' V'  .str_pad($creatorVersion,\PHK\Proxy::VERSION_SIZE)
	.' FS' .str_pad($file_size,\PHK\Proxy::OFFSET_SIZE)
	.' PO' .str_pad($prolog_offset,\PHK\Proxy::OFFSET_SIZE)
	.' SSO'.str_pad($sections_structure_offset,\PHK\Proxy::OFFSET_SIZE)
	.' STO'.str_pad($sections_offset,\PHK\Proxy::OFFSET_SIZE)
	.' FTO'.str_pad($files_offset,\PHK\Proxy::OFFSET_SIZE)
	.' SIO'.str_pad($sig_offset,\PHK\Proxy::OFFSET_SIZE)
	.' CRC00000000'
	.' PCO'.str_pad($code_offset,\PHK\Proxy::OFFSET_SIZE)
	.' PCS'.str_pad(strlen($this->code),\PHK\Proxy::OFFSET_SIZE)
	.' ?>'
	.$this->prolog.$this->code.$sections_structure.$sections_data.$files_data);

\PHK\Tools\Util::trace('Writing PHK file to '.$path);
\PHK\Tools\Util::atomicWrite($path,$buf);
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
