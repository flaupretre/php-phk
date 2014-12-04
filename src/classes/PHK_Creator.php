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
//============================================================================

if (!class_exists('PHK_Creator',false))
{
// <PHK:ignore>
require(dirname(__FILE__).'/external/phool/PHO_Display.php');
require(dirname(__FILE__).'/external/automap/Automap_Creator.php');
require(dirname(__FILE__).'/PHK_Proxy.php');
require(dirname(__FILE__).'/PHK_Base.php');
require(dirname(__FILE__).'/PHK_Mgr.php');
require(dirname(__FILE__).'/PHK_Tree.php');
// <PHK:end>

//============================================================================
/*
* The package creator class
*
* Note: If PHK_Creator is inside a PHK package (usual case), it is
* absolutely mandatory that Automap and PHK classes as package's files
* are the same as the Automap and PHK classes included in the prolog. Which
* means that the package must be generated from its files. If it is not the
* case, packages generated from this creator package will display wrong version
* information.
*/

class PHK_Creator extends PHK_Base
{
const VERSION='2.1.0';	// Must be the same as in PHK_Creator.psf
const MIN_VERSION='2.0.0';

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
$this->build_info=array('build_timestamp' => time());
}

//---------

public function ftree()
{
return $this->proxy()->ftree();
}

//---------

public function set_options($opt)
{
$this->options=$opt;
}

//---------

public function update_option($name,$a)
{
$this->options[$name]=$a;
}

//---------

public function update_build_info($name,$a)
{
$this->build_info[$name]=$a;
}

//---------

public function set_prolog($data)
{
$this->prolog=$data;
}

//---------

private function process_php_code(&$buffer)
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
				throw new Exception('Cannot find end of <'.$token.'> section');
			$buffer=substr_replace($buffer,'',$pos,$pos2-$pos+15);
			}
		}
	}

// php_strip_whitespace takes a file as arg. So, instead of creating a temp
// file, we use our stream wrapper to feed it with the current prolog data

if (!$this->option('plain_prolog'))
	{
	$buffer=PHK_Stream_Backend::_strip_string($buffer);
	}
}

//---------
// Build runtime PHP code
//-- $dir= base dir where files are stored

public function build_php_code($dir)
{
$this->code='';

$this->code .= PHK_Util::readfile($dir.'/classes/external/automap/Automap.php');
$this->code .= PHK_Util::readfile($dir.'/classes/external/automap/Automap_Display.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_Util.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_File.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_Cache.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_Proxy.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_Mgr.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_Base.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_Backend.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_Stream.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_Stream_Backend.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_DC.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_Tree.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_TNode.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_TDir.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_TFile.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_Webinfo.php');
$this->code .= PHK_Util::readfile($dir.'/classes/PHK_PHPUnit.php');

$this->process_php_code($this->code);

$this->code=str_replace('<?php','',$this->code);
$this->code=str_replace('?>','',$this->code);
}

//---------
//-- Build prolog code
//-- $dir= base dir where files are stored

public function build_prolog($dir)
{
$this->prolog = PHK_Util::readfile($dir.'/scripts/PHK_Prolog.php');

//-- The four FF chars turn unicode detection off (see PHP bug #42396)

$this->prolog .= '<?php __halt_compiler(); ?>'.str_repeat(chr(255),4);

$this->process_php_code($this->prolog);
}

//---------
// Create a new section and fill it with some data

public function add_section($name,$data,$modifiers=array())
{
$this->proxy()->stree()->mkfile($name,$data,$modifiers);
}

//---------

public function dump($path=null)
{
if (is_null($path)) $path=$this->path();

if (! PHK::file_is_package(__FILE__)) // If building PHK_Creator package
	{
	$base_dir=dirname(__FILE__).'/..';
	}
else
	{
	$mnt=PHK_Mgr::path_to_mnt(__FILE__);
	$base_dir=PHK_Mgr::base_uri($mnt);
	}

$this->build_php_code($base_dir);

//-- Build prolog if not already set

if (is_null($this->prolog)) $this->build_prolog($base_dir);

//-- Build FILES part and FTREE section
// Build Automap, strip sources, etc...

$needed_extensions=new PHK_ItemLister;

$this->proxy()->ftree()->walk('get_needed_extensions',$this,$needed_extensions);

$map=new Automap_Creator();
list($files_structure,$files_data)=$this->proxy()->ftree()->export($this,$map);

$this->add_section('FTREE',$files_structure);
unset($files_structure);

//-- Build Automap section

if ($map->symbol_count())
	$this->add_section(PHK_Proxy::AUTOMAP_SECTION,$map->serialize());

$this->update_build_info('map_defined',($map->symbol_count()!=0));

//-- Tabs sections / PHK icon

foreach(array('tabs/left.gif','tabs/right.gif','tabs/bottom.gif'
	,'tabs/tabs.css.php','phk_logo.png') as $f)
	{
	$source=$base_dir.'/etc/'.$f;
	$this->add_section('STATIC/'.$f,PHK_Util::readfile($source));
	}

//-- Build info

$this->update_build_info('PHK_Creator_version',self::VERSION);
$this->update_build_info('PHK_min_version',self::MIN_VERSION);
$this->update_build_info('Automap_creator_version',Automap_Creator::VERSION);
$this->update_build_info('Automap_min_version',Automap_Creator::MIN_VERSION);
$this->update_build_info('PHK_PSF_version',PHK_PSF::VERSION);

//-- Record the user-specified needed extensions

foreach(PHK_Util::mk_array($this->option('required_extensions')) as $ext)
	{
	if ($ext!=='') $needed_extensions->add($ext,true);
	}

//-- Flush sections
//-- Add the uncompress needed extensions to the user required extensions

$this->proxy()->stree()->walk('get_needed_extensions',$this,$needed_extensions);

$ext=array_keys($needed_extensions->get());
if (count($ext)) $this->update_option('required_extensions',$ext);

//-- Ensure mime_types, if present, is an array

if (!is_null($this->option('mime_types')))
	$this->update_option('mime_types'
		,PHK_Util::mk_array($this->option('mime_types')));

$this->add_section('OPTIONS',serialize($this->options()));

// Add build info section

$this->add_section('BUILD_INFO',serialize($this->build_info()));

// Now, dump the section tree

PHK_Util::trace('--- Sections');

list($sections_structure,$sections_data)=$this->proxy()->stree()->export($this);

//---

$prolog_offset=PHK_Proxy::INTERP_LEN+PHK_Proxy::MAGIC_LINE_LEN;
$code_offset=$prolog_offset+strlen($this->prolog);
$sections_structure_offset=$code_offset+strlen($this->code);
$sections_offset=$sections_structure_offset+strlen($sections_structure);
$files_offset=$sections_offset+strlen($sections_data);
$sig_offset=$files_offset+strlen($files_data);
$file_size=$sig_offset;

$buf=PHK_Proxy::fix_crc(PHK_Proxy::interp_block($this->interp)
	.'<?php '.PHK_Proxy::MAGIC_STRING
	.' M'  .str_pad(self::MIN_VERSION,PHK_Proxy::VERSION_SIZE)
	.' V'  .str_pad(self::VERSION,PHK_Proxy::VERSION_SIZE)
	.' FS' .str_pad($file_size,PHK_Proxy::OFFSET_SIZE)
	.' PO' .str_pad($prolog_offset,PHK_Proxy::OFFSET_SIZE)
	.' SSO'.str_pad($sections_structure_offset,PHK_Proxy::OFFSET_SIZE)
	.' STO'.str_pad($sections_offset,PHK_Proxy::OFFSET_SIZE)
	.' FTO'.str_pad($files_offset,PHK_Proxy::OFFSET_SIZE)
	.' SIO'.str_pad($sig_offset,PHK_Proxy::OFFSET_SIZE)
	.' CRC00000000'
	.' PCO'.str_pad($code_offset,PHK_Proxy::OFFSET_SIZE)
	.' PCS'.str_pad(strlen($this->code),PHK_Proxy::OFFSET_SIZE)
	.' ?>'
	.$this->prolog.$this->code.$sections_structure.$sections_data.$files_data);

PHK_Util::trace('Writing PHK file to '.$path);
PHK_Util::atomic_write($path,$buf);
}

//---------
} //-- End of class PHK_Creator
//-------------------------
} // End of class_exists('PHK_Creator')
//============================================================================
?>
