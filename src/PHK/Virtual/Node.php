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

if (!class_exists('PHK\Virtual\Node',false))
{
//============================================================================
/**
* A virtual node
*
* This is a node of the virtual tree. This abstract class can be extended with
* a directory (Dir class) or a file (File class)
*
* API status: Private
* Included in the PHK PHP runtime: Yes
* Implemented in the extension: No
*///==========================================================================

abstract class Node		// Base class - never instantiated
{
protected $flags;

protected $path;

protected $tree=null;	// Back pointer to the tree

//---- Flags

const TN_DC_FLAG_MASK=DC::COMPRESS_TYPE; // Low bits reserved for compr type

const TN_STRIP_SOURCE=8;	// Strip source files
const TN_NO_AUTOLOAD=16;	// Don't register symbols in Automap
const TN_PKG=32;			// File is a PHK package

//---

abstract public function type(); // returns type string

//---
// Default: do nothing

public function displayPackage($html) {}

//---

public function isPackage()
{
return ($this->flags & self::TN_PKG);
}

//---
// Default: error if the method is not overloaded

public function getDir()
{
throw new \Exception($this->path.': Cannot getDir() on a '.$this->type());
}

//---
// Default: error

public function read()
{
throw new \Exception($this->path.': Cannot read() a '.$this->type());
}

//---

protected function flagString()
{
$flagString='';
if ($this->flags & self::TN_PKG) $flagString .=',package';
else
	{
	if ($this->flags & self::TN_STRIP_SOURCE) $flagString .=',strip';
	if ($this->flags & self::TN_NO_AUTOLOAD) $flagString .=',no_autoload';
	}

return $flagString;
}

//---

// Cannot call setFlags() here, as it will call the derived
// method when it is defined (as in \PHK\Virtual\File)

protected function __construct($path,$tree)
{
$this->path=$path;
$this->tree=$tree;
$this->flags=0;
}

//---

protected function import($edata)
{
list($this->flags)=array_values(unpack('va',$edata));
return substr($edata,2);
}

// <CREATOR> //---------------

abstract public function export(\PHK\Build\Creator $phk,\PHK\Build\DataStacker $stacker,$map);

//---

protected function nodeExport($derived_edata)
{
return pack('v',$this->flags).$derived_edata;
}

//---

public function setFlags($flags)
{
$this->flags=$flags;
}

//---

private static function computeFlags(array $modifiers,$flags=0)
{
foreach($modifiers as $name => $value)
	{
	if (is_null($value)) continue;
	switch ($name)
		{
			case 'autoload':
				if ($value) $flags &= ~self::TN_NO_AUTOLOAD;
				else $flags |= self::TN_NO_AUTOLOAD;
				break;

			case 'strip':
				if ($value) $flags |= self::TN_STRIP_SOURCE;
				else $flags &= ~self::TN_STRIP_SOURCE;
				break;

			case 'compression':
				switch($value)
					{
					case 'no':
					case 'none':
						$c=DC::COMPRESS_NONE;
						break;
					case 'gz':
					case 'gzip':
						$c=DC::COMPRESS_GZIP;
						break;
					case 'bz':
					case 'bz2':
					case 'bzip2':
						$c=DC::COMPRESS_BZIP2;
						break;
					default:
						throw new \Exception($value.': Unknown compression method');
					}
				$flags = ($flags & ~DC::COMPRESS_TYPE) | $c;
				break;
		// Ignore other modifiers
		}
	}
return $flags;
}

//---

public function modify($modifiers)
{
$this->setFlags(self::computeFlags($modifiers,$this->flags));
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
