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
* Contains the code to physically access PHK package files.
*
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*/
//============================================================================

namespace {

if (!class_exists('PHK_File',false))
{

//-------------------------
class PHK_File
{

private $fp=null;
private $path;
private $size;
private $open_count=0;
private $keep_open_flag;

//------
// Special case : keep sub-PHKs open (in stream wrapper memory)

public function __construct($path,$flags)
{
$this->set_params($path,$flags);

if (($this->size=filesize($path))===false)
	throw new Exception($path.': Cannot get file size');
}

//------

public function set_params($path,$flags)
{
$this->path=$path;

$this->keep_open_flag=PHK_Mgr::is_a_phk_uri($path);
}

//---

public function __sleep()
{
return array('size');
}

//------

public function __destruct()
{
$this->really_close();
}

//------

private function really_close()
{
if (!is_null($this->fp))
	{
	fclose($this->fp);
	$this->fp=null;
	$this->open_count=0;
	}
}

//------
// Open in read-only mode. Maintains a count for close(), throws exceptions,
// and force 'b' mode (for Windows).
// Called from self or PHK_FileSpace only

public function _open()
{
if (is_null($this->fp))
	{
	if (!($this->fp=fopen($this->path,'rb',false)))	//-- 'b' mode is for Windows
		throw new Exception($this->path.': Cannot open for reading');
	$this->open_count=1;
	}
else $this->open_count++;
}

//-----
// fclose() the file pointer. Maintains an open count.
// Called from self or PHK_FileSpace only

public function _close()
{
$this->open_count--;
if (($this->open_count <= 0) && (!$this->keep_open_flag)) $this->really_close();
}

//-----
// Same as PHP fread() but reads any size, throws exceptions, and checks size
// I don't use stream_get_contents() because, sometimes, it crashes
// on Windows (detected with PHP 5.1.4).

private function read($size)
{
$data='';
$nb_chunks=intval($size/8192);
$rest=$size % 8192;

PHK_Util::disable_mqr();
while ($nb_chunks > 0)
	{
	$data .= $this->read_chunk(8192);
	$nb_chunks--;
	}

if ($rest) $data .= $this->read_chunk($rest);
PHK_Util::restore_mqr();

return $data;
}

//-----
// Read up to 8192 bytes

private function read_chunk($size)
{
$buf=fread($this->fp,$size);
if ($buf===false) throw new Exception('Cannot read');
if (($bsize=strlen($buf))!=$size)
	throw new Exception("Short read ($bsize/$size)");
return $buf;
}

//-----
// Reads a block from file.
// Called only from PHK_FileSpace. So:
//		- we don't need to check bounds,
//		- we don't provide default args,
//		- we are sure that size is > 0

public function _read_block($offset,$size)
{
try
	{
	$this->_open();
	if (fseek($this->fp,$offset,SEEK_SET) == -1)
		throw new Exception('Cannot seek');
	$buf=$this->read($size);
	$this->_close();	// At the end. Everything before can raise an exception
	}				// and we don't want to close it twice
catch (Exception $e)
	{
	$this->_close();
	throw new Exception($e->getMessage());
	}
return $buf;
}

//------

public function size()
{
return $this->size;
}

//------

public function path()
{
return $this->path;
}

}	// End of class PHK_File
//-------------------------
} // End of class_exists('PHK_File')
//=============================================================================

if (!class_exists('PHK_FileSpace',false))
{
//-------------------------
class PHK_FileSpace
{

public $file;	// underlying  PHK_File object
private $offset;
private $size;

//------
// Two possibles syntaxes :
// new PHK_FileSpace(String $path,int $flags) : creates a first space for a file
// new PHK_FileSpace(PHK_FileSpace $parent, int $offset, int $size) :
//		creates a subspace inside an existing FileSpace.

public function __construct($arg1,$arg2,$size=null)
{
if (is_string($arg1))
	{
	$this->file=new PHK_File($arg1,$arg2);
	$this->offset=0;
	$this->size=$this->file->size();
	}
else
	{
	if ((!($arg1 instanceof self))
		|| (!is_numeric($arg2))
		|| (!is_numeric($size))
		|| ($arg2 < 0)
		|| (($arg2+$size) > $arg1->size))
		throw new Exception("PHK_FileSpace: cannot create - invalid arguments");

	$this->file=$arg1->file;
	$this->offset=$arg1->offset + $arg2;
	$this->size=$size;
	}
}

//------
// Default args so that read_block() without args returns the whole filespace

public function read_block($offset=0,$size=null)
{
//PHK_Util::trace("Starting PHK_FileSpace::read_block - offset=$offset - size=$size");//TRACE

if (is_null($size)) $size=$this->size-$offset; // Read up to the end

if (($offset<0)||($size<0)||($offset+$size>$this->size))
	throw new Exception('PHK_FileSpace: Read out of bound');

if ($size==0) return '';

$data=$this->file->_read_block($this->offset+$offset,$size);

//PHK_Util::trace("Ending PHK_FileSpace::read_block");//TRACE
return $data;
}

//------
// Used to force the file to remain open temporarily

public function open()
{
$this->file->_open();
}

//------

public function close()
{
$this->file->_close();
}

//------

public function size()
{
return $this->size;
}

//------
// Returns path of underlying file (can be a PHK URI)

public function path()
{
return $this->file->path();
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
