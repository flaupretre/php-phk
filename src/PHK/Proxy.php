<?php
//=============================================================================
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

namespace PHK {

if (!class_exists('PHK\Proxy',false))
{
//=============================================================================
/**
* The 'back-end' object providing physical access to the package file. This
* object is created and called by the stream wrapper on cache misses.
*
* Runtime code -> 100% read-only except set_buffer_interp().
*
* @see \PHK\Stream\Wrapper
* @see PHK
* @see \PHK\Mgr
* @package PHK
*/

class Proxy
{
//========== Class constants ===============

/** Class version */

const VERSION='1.3.0';

/** The size of the interp line (fixed size) */

const INTERP_LEN=64;

/** The size of a version string in the magic block - Up to 12 bytes */

const VERSION_SIZE=12;

/** The size of each offset field. As offset are limited to 11 bytes, the
* theoritical max size of a PHK archive is 100 G bytes. Not tested :) */

const OFFSET_SIZE=11;

/** The magic string. This is the string we identify to recognize a PHK archive */

const MAGIC_STRING="#PHK M\024\x8\6\3";
const MAGIC_STRING_LEN=10;

/** INTERP_LEN + 6 */

const MAGIC_STRING_OFFSET=70;

/** The size of the magic block */

const MAGIC_LINE_LEN=177;

/** The name of the optional Automap section, when it exists */

const AUTOMAP_SECTION='AUTOMAP';

/** The offset of the CRC field, from the beginning of the archive file.
* The CRC field is 8 bytes long (32 bits in hexadecimal) */

const CRC_OFFSET=200;

//========== Instance data ===============

/** @var string Package path */

private	$path;

/** @var \PHK\Virtual\Tree	Section tree */

protected	$stree=null;

/** @var \PHK\Virtual\Tree	File tree */

public		$ftree=null;

/** @var integer	Mount flags */

protected	$flags;

/** @var \PHK\PkgFileSpace	File Handler */

protected	$fspace;

/** @var array		Magic values */

private		$magic=null;

//========== Class methods ===============

/**
* Constructor
*
* This method must be called only from \PHK\Mgr::proxy()
*
* @param string mount point
*
* @throws \Exception
*/

public function __construct($path,$flags)
{
try
{
Tools\Util::slow_path();

//Tools\Util::trace("Starting proxy init");//TRACE

$this->path=$path;
$this->flags=$flags;

if (!($this->flags & \PHK::IS_CREATOR))
	{
	// file_is_package() moved here from \PHK\Mgr::compute_mnt() because we don't
	// need to check this if data is already in cache.

	if (! self::file_is_package($path))
		throw new \Exception($path.'is not a PHK package');

	$this->fspace= new PkgFileSpace($path,$flags);
	$this->fspace->open();

	// Get magic block

	$this->get_magic_values();

	// Check that file size corresponds to the value stored in the magic block.
	// Done only once in slow path because, if the file size changes, the
	// modification date will change too, and thus the mount point.

	if ($this->fspace->size()!=$this->magic['fs']) // Check file size
		Tools\Util::format_error('Invalid file size. Should be '.$this->magic['fs']);

	// Import section tree

	$this->stree=Virtual\Tree::create_from_edata(
		$this->fspace->read_block($this->magic['sso']
			,$this->magic['sto']-$this->magic['sso'])
		,new PkgFileSpace($this->fspace,$this->magic['sto']
			,$this->magic['fto']-$this->magic['sto']));

	$this->ftree=Virtual\Tree::create_from_edata($this->section('FTREE')
		,new PkgFileSpace($this->fspace,$this->magic['fto']
			,$this->magic['sio']-$this->magic['fto']));

	$this->fspace->close(); // We keep the file open during init phase
	}
else
	{
	$this->ftree=Virtual\Tree::create_empty();
	$this->stree=Virtual\Tree::create_empty();
	}
}
catch (\Exception $e)
	{
	throw new \Exception('While initializing PHK proxy - '.$e->getMessage());
	}
//Tools\Util::trace("Ending init - path=$path");//TRACE
}

//---------

public function crc_check()
{
try
	{
	self::check_crc_buffer($this->fspace->read_block());
	}
catch(\Exception $e)
	{
	throw new \Exception($this->path.': file is corrupted - '.$e->getMessage());
	}
}

//---------------------------------
/**
* Inserts or clears a CRC in a memory buffer
*
* @static
* @param string $buffer	The original buffer whose CRC will be overwritten
* @param string $crc	If set, the CRC as an 8-char string (in hexadecimal). If
*	not set, we clear the CRC (set it to '00000000').
* @return string	The modified buffer
*/

public static function insert_crc($buffer,$crc)
{
return substr_replace($buffer,$crc,self::CRC_OFFSET,8);
}

//--------------------------------
/**
* Returns the CRC extracted from a memory buffer (not the computed one)
*
* @param string $buffer
* @return string The extracted 8-char hex CRC
*/

private static function get_crc($buffer)
{
return substr($buffer,self::CRC_OFFSET,8);
}

//---------------------------------
/**
* Computes a CRC from a given memory buffer
*
* As the given buffer already contains a CRC, we first clear it.
*
* @param string $buffer
* @return string The computed 8-char hex CRC
*/

private static function compute_crc($buffer)
{
return hash('crc32',self::insert_crc($buffer,'00000000'));
}

//---------------------------------
/**
* Checks a memory buffer's CRC
*
* The memory buffer is supposed to contain a whole PHK archive.
*
* No return value: if the CRC check fails, an exception is thrown.
*
* @param string $buffer
* @return void
* @throws \Exception
*/

public static function check_crc_buffer($buffer)
{
if (self::compute_crc($buffer) !== self::get_crc($buffer))
	throw new \Exception('CRC check failed');
}

//---------------------------------
/**
* Computes and inserts a CRC in a memory buffer
*
* @param string $buffer
* @return string	The modified buffer
*/

public static function fix_crc($buffer)
{
return self::insert_crc($buffer,self::compute_crc($buffer));
}

//---------
/**
* Check if a given path contains a PHK package
*
* @param string $path	path to check (can be virtual)
* @return boolean
*/

public static function file_is_package($path)
{
if (filesize($path)< (self::INTERP_LEN+self::MAGIC_LINE_LEN)) return false;
if (($fp=fopen($path,'rb',false))===false) return false;
if (fseek($fp,self::MAGIC_STRING_OFFSET) != 0) return false;
if (($m=fread($fp,self::MAGIC_STRING_LEN))===false) return false;
fclose($fp);
return ($m===self::MAGIC_STRING);
}

//---------
/**
* Check if a data buffer contains a PHK package
*
* @param string $data	data buffer to check
* @return boolean
*/

public static function data_is_package($data)
{
if (strlen($data) < (self::INTERP_LEN+self::MAGIC_LINE_LEN)) return false;
return (substr($data,self::MAGIC_STRING_OFFSET,self::MAGIC_STRING_LEN)
	===self::MAGIC_STRING);
}

//---------------------------------
/**
* Extracts the value values out of a magic line buffer
*
* Note: A package is signed if (Signature offset != File size)
*
* @param string $buf A magic line content
* @return array An array containing the magic values
*/

public function get_magic_values()
{
$buf=$this->fspace->read_block(self::INTERP_LEN,self::MAGIC_LINE_LEN);

$fsize=(int)substr($buf,47,self::OFFSET_SIZE);
$sio=(int)substr($buf,121,self::OFFSET_SIZE);
$crc=null;
sscanf(substr($buf,136,8),'%08x',$crc);

$this->magic=array(
	'mv'  => trim(substr($buf,18,self::VERSION_SIZE)),	// Minimum required version
	'v'	  => trim(substr($buf,32,self::VERSION_SIZE)),	// Version
	'fs'  => $fsize,									// File size
	'po'  => (int)substr($buf,61,self::OFFSET_SIZE),	// Prolog offset
	'sso' => (int)substr($buf,76,self::OFFSET_SIZE),	// Serialized sections offset
	'sto' => (int)substr($buf,91,self::OFFSET_SIZE),	// Section table offset
	'fto' => (int)substr($buf,106,self::OFFSET_SIZE),	// File table offset
	'sio' => $sio,										// Signature offset
	'pco' => (int)substr($buf,148,self::OFFSET_SIZE),	// PHP code offset
	'pcs' => (int)substr($buf,163,self::OFFSET_SIZE),	// PHP code length
	'crc' => $crc,
	'signed' => ($sio != $fsize));
}

//---------------------------------

public function magic_field($name)
{
return $this->magic[$name];
}

//---------------------------------
/**
* Brings all data in memory
*
* After this function has run, we never access the package file any more.
*
* @see \PHK\Virtual\DC implements the data cache
*
* @return void
*/

private function cache_data()
{
$this->stree->walk('read');
$this->ftree->walk('read');
}

//---------------------------------
/**
* Clears the data cache
*
* @see \PHK\Virtual\DC implements the data cache
*
* @return void
*/

private function clear_cache()
{
$this->stree->walk('clear_cache');
$this->ftree->walk('clear_cache');
}

//---------------------------------

public function path_list()
{
return $this->ftree->path_list();
}

//---------------------------------

public function section_list()
{
return $this->stree->path_list();
}


//---------------------------------
/**
* Is this package digitally signed ?
*
* @return boolean
*/

public function signed()
{
return $this->magic['signed'];
}

//-----
/**
* Gets interpreter string
*
* If the interpreter is defined, returns it. Else, returns an empty string
*
* @return string
* @throws \Exception if the interpreter string is invalid
*/

public function interp()
{
$block=$this->fspace->read_block(0,self::INTERP_LEN);

if ((($block{0}!='#')||($block{1}!='!')) && (($block{0}!='<')||($block{1}!='?')))
	throw new \Exception('Invalid interpreter block');
return ($block{0}=='#') ? trim(substr($block,2)) : '';
}

//-----
/**
* Builds an interpreter block from an interpreter string
*
* Note: can be applied to a signed package as the signature ignores the
* interpreter block and the CRC.

* @param string $interp Interpreter to set or empty string to clear
* @return string Interpreter block (INTERP_LEN). Including trailing '\n'
*/

public static function interp_block($interp)
{
if (($interp!=='') && (strlen($interp) > (\PHK\Proxy::INTERP_LEN-3)))
	throw new \Exception('Length of interpreter string is limited to '
		.(\PHK\Proxy::INTERP_LEN-3).' bytes');

// Keep '<?'.'php' or it will be translated when building the runtime code

if ($interp==='') return str_pad('<?'.'php',\PHK\Proxy::INTERP_LEN-2).'?'.'>';
else return '#!'.str_pad($interp,\PHK\Proxy::INTERP_LEN-3)."\n";
}

//-----
/**
* Inserts a new interpreter block in a file's content
*
* Allows a PHK user to change its interpreter string without
* having to use the \PHK\Build\Creator kit.
*
* Note: can be applied to a signed package as the signature ignores the
* interpreter block and the CRC.
*
* @param string $path PHK archive's path
* @param string $interp Interpreter string to set (empty to clear)
* @return string The modified buffer (the file is not overwritten)
*/

public static function set_buffer_interp($path,$interp='')
{
return self::fix_crc(substr_replace(Tools\Util::readfile($path)
	,self::interp_block($interp),0,\PHK\Proxy::INTERP_LEN));
}

//-----
/**
* The version of the \PHK\Build\Creator tool this package was created from
*
* @return string Version
*/

public function version()
{
return $this->magic['v'];
}

//-----
/**
* Returns the $path property
*
* @return string
*/

public function path()
{
return $this->fspace->path();
}

//-----
/**
* Get a section's content
*
* @param string $name The section name
* @return string The section's content
* @throws \Exception if section does not exist or cannot be read
*/

public function section($name)
{
try { $node=$this->stree->lookup_file($name); }
catch (\Exception $e) { throw new \Exception($name.': Unknown section'); }

try { return $node->read(); }
catch (\Exception $e)
	{ throw new \Exception($name.': Cannot read section - '.$e->getMessage()); }
}

//-----

public function ftree()
{
return $this->ftree;
}

//-----

public function stree()
{
return $this->stree;
}

//-----
/**
* Returns the $flags property
*
* @return integer
*/

public function flags()
{
return $this->flags;
}

//---------------------------------

public function display_packages()
{
$this->ftree->display_packages();
}

//---------------------------------
// Display the file tree

public function showfiles()
{
$this->ftree->display(true);
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
