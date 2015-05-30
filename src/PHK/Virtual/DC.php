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

namespace PHK\Virtual {

if (!class_exists('PHK\Virtual\DC',false))
{
//=============================================================================
/**
* Data container
*
* Contains string data and supports compression.
*
* API status: Private
* Included in the PHK PHP runtime: Yes
* Implemented in the extension: No
*///==========================================================================

class DC	// Data Container
{

private $csz;	// Compressed size
private $rsz;	// Real size
private $flags;	// Compression method
private $off;	// Offset
private $data=null;	// Data cache (null if unset)
private $fspace=null;

//---------

const COMPRESS_TYPE=7;	// Space reserved for 8 compression types

const COMPRESS_NONE=0;
const COMPRESS_GZIP=1;
const COMPRESS_BZIP2=2;

private static $compression_method_names=array('none','gzip','bzip2');

private static $compression_needed_extensions=array(null,'zlib','bz2');

//---------
// Clears the data cache

public function clearCache()
{
$data=null;
}

//---------

public function setFspace($fspace)
{
$this->fspace=$fspace;
}

//---------

private function compressionType()
{
return $this->flags & self::COMPRESS_TYPE;
}

//---------
// Uncompress a buffer, given the compression method

private function expand($buf)
{
$ctype=$this->compressionType();

if ($buf==='' || $ctype==self::COMPRESS_NONE) return $buf;

switch($ctype)
	{
	case self::COMPRESS_BZIP2:
		if(is_int($rbuf=bzdecompress($buf)))
			throw new \Exception("Cannot bzdecompress data - Error code $buf");
		break;

	case self::COMPRESS_GZIP:
		if(($rbuf=gzuncompress($buf))===false)
			throw new \Exception("Cannot gzuncompress data");
		break;

	default:
		throw new \Exception("Unknown compression method : $ctype");
	}
return $rbuf;
}

//---
// Read/uncompress/verify and cache data

public function read()
{
if (is_null($this->data))
	{
	if ($this->rsz==0) $this->data='';	// Empty file
	else
		{
		$rbuf=$this->expand($this->fspace->readBlock($this->off,$this->csz));
		if (strlen($rbuf)!=$this->rsz) throw new \Exception('Wrong expanded size');
		$this->data=$rbuf;
		}
	}
return $this->data;
}

//---

private static function compressionRatio($rsz,$csz)
{
return ($rsz==0) ? '-' : (round(($csz/$rsz)*100));
}

//---

public function flagString()
{
if ($ctype=$this->flags & self::COMPRESS_TYPE)
	return 'compress/'.self::$compression_method_names[$ctype]
		.' ('.self::compressionRatio($this->rsz,$this->csz).'%)';

return '';
}

//---

public function size() { return $this->rsz; }

//---

public function import($edata)
{
list($this->flags,$this->csz,$this->rsz,$this->off)
	=array_values(unpack('va/V3b',$edata));
	
$this->data=null; // Must be reset as the object is created as an empty file
}

//---

public function __construct()
{
$this->setFlags(0);
$this->setData('');
$this->csz=$this->off=null;
}

//---
// Set only the DC flags

public function setFlags($flags)
{
$this->flags=($flags & \PHK\Virtual\Node::TN_DC_FLAG_MASK);
}

//---

public function setData($data)
{
$this->rsz=strlen($this->data=$data);
}

// <CREATOR> //---------------

public function getNeededExtensions(\PHK\Build\Creator $phk
	,\PHK\Tools\ItemLister $item_lister)
{
if (!is_null($ext=self::$compression_needed_extensions
	[$this->flags & self::COMPRESS_TYPE]))
		$item_lister->add($ext,true);
}

//---

public function appendData($data)
{
$this->data.=$data;
$this->rsz+=strlen($data);
}

//---

public function export(\PHK\Build\Creator $phk,\PHK\Build\DataStacker $stacker)
{
$cbuf=$this->compress($this->data,$phk);
$this->csz=strlen($cbuf);
$this->off=$stacker->offset;
$stacker->push($cbuf);
return pack('vV3',$this->flags,$this->csz,$this->rsz,$this->off);
}

//------

private function denyCompress($msg,$buf)
{
\PHK\Tools\Util::trace("	No compression: $msg");
$this->flags &= ~self::COMPRESS_TYPE; // Set to COMPRESS_NONE
return $buf;
}

//------

private function compress($buf,\PHK\Build\Creator $phk)
{
if (!($ctype=$this->compressionType())) return $buf;

$comp_min_size=$phk->option('compress_min_size');
$comp_max_size=$phk->option('compress_max_size');
$comp_ratio_limit=$phk->option('compress_ratio_limit');

if ($buf==='') return $this->denyCompress('Empty file',$buf);
if ((!is_null($comp_min_size)) && (strlen($buf) < $comp_min_size))
		return $this->denyCompress('File too small',$buf);
if ((!is_null($comp_max_size)) && (strlen($buf) > $comp_max_size))
		return $this->denyCompress('File too large',$buf);

switch($ctype)
	{
	case self::COMPRESS_BZIP2:
		\PHK\Tools\Util::loadExtension('bz2');
		\PHK\Tools\Util::trace("	Compressing (bzip2)");
		if(is_int($cbuf=bzcompress($buf,9)))
			throw new \Exception("Cannot bzcompress data - Error code $buf");
		break;

	case self::COMPRESS_GZIP:
		\PHK\Tools\Util::loadExtension('zlib');
		\PHK\Tools\Util::trace("	Compressing (gzip)");
		if(($cbuf=gzcompress($buf))===false) 
			throw new \Exception("Cannot gzcompress data");
		break;

	default:
		throw new \Exception("Unknown compression method : $ctype");
	}

// Default: Deny if compressed buffer is larger than 90% of original

if (is_null($comp_ratio_limit)) $comp_ratio_limit=90;
if (($r=self::compressionRatio(strlen($buf),strlen($cbuf))) >$comp_ratio_limit)
	return $this->denyCompress("Compression ratio exceeded ($r%)",$buf);

return $cbuf;
}

// </CREATOR> //---------------

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
