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
//=============================================================================

namespace PHK\Stream {

if (!class_exists('PHK\Stream\Wrapper',false))
{
//=============================================================================
/**
* The PHK stream wrapper
*
* Handles every file access to a 'phk://...' URI
*
* Note: Always catch exceptions before returning to PHP.
*/

class Wrapper
{

private $uri;

private $mnt;		// Mount point (string)
private $path;		// File path in PHK (string|null)
private $command;	// Command (string|null)
private $params;	// Command parameter (array|null)

private $data;	// File data (regular file) or dir content
private $size;	// Size of buffer or number of dir entries
private $position; // Byte position or position in array

private $raiseErrors=true;

//---------------------------------
// Display a warning if they are allowed

private function raiseWarning($msg)
{
if ($this->raiseErrors) trigger_error("PHK: $msg",E_USER_WARNING);
}

//---------------------------------

public static function getFile($dir,$uri,$mnt,$command,$params,$path,$cache=null)
{
$cacheID=\PHK\Cache::cacheID('node',$uri);
\PHK\Tools\Util::trace("get_file: Cache ID=<$cacheID>");//TRACE

if (is_null($data=\PHK\Cache::get($cacheID)))	// Miss
	{
	$can_cache=true;
	
	if (is_null($data=($dir ?
		Backend::getDirData($mnt,$command,$params,$path)
		: Backend::getFileData($mnt,$command,$params,$path
			,$can_cache)))) throw new \Exception("$uri: File not found");

	if ($can_cache && (($cache===true) || (is_null($cache)
		&& \PHK\Mgr::cacheEnabled($mnt,$command,$params,$path))))
			\PHK\Cache::set($cacheID,$data);
	}

if ($dir && (!is_array($data))) throw new \Exception('Not a directory');
if ((!$dir) && (!is_string($data))) throw new \Exception('Not a regular file');

return $data;
}

//---------------------------------
// Open a file - only read mode is supported
// STREAM_USE_PATH is ignored
// Fast path: Yes (only when data is found in the cache)
//
// We must check the mount point validity because we can be called for an
// unmounted path. In this case, we must return false before searching the
// cache, or the behavior will be different when the data is in the cache
// or not. Note that we check for global commands before validating the mount
// point.

public function stream_open($uri,$mode,$options,&$opened_path)
{
\PHK\Tools\Util::trace("Starting stream_open: uri=$uri");//TRACE

try
{
$this->uri=$uri;
$this->raiseErrors=($options & STREAM_REPORT_ERRORS);
if ($options & STREAM_USE_PATH) $opened_path=$uri;

if (($mode!='r')&&($mode!='rb'))
	throw new \Exception($mode.': unsupported mode (Read only)');

self::parseURI($uri,$this->command,$this->params,$this->mnt,$this->path);

if (!is_null($this->mnt)) \PHK\Mgr::validate($this->mnt);

$this->data=self::getFile(false,$uri,$this->mnt,$this->command
	,$this->params,$this->path);

$this->size=strlen($this->data);
$this->position=0;
}
catch (\Exception $e)
	{
	$msg=$uri.': Open error - '.$e->getMessage();
	$this->raiseWarning($msg);
	return false;
	}
\PHK\Tools\Util::trace("Exiting stream_open: uri=$uri");//TRACE
return true;
}

//---------------------------------
// Read on an open file

public function stream_read($nb)
{
\PHK\Tools\Util::trace("Starting stream_read: uri=".$this->uri." - nb=$nb - position=".$this->position." size=".$this->size);//TRACE

if ($this->position==$this->size) return false;
$max=$this->size-($pos=$this->position);
if ($nb > $max) $nb=$max;
$this->position+=$nb;

return substr($this->data,$pos,$nb);
}

//---------------------------------
// Are we at the end of an open file ?

public function stream_eof()
{
return ($this->position==$this->size);
}

//---------------------------------
// Return current position in an open stream

public function stream_tell()
{
return $this->position;
}

//---------------------------------
// Seek on an open file

public function stream_seek($offset,$whence)
{
\PHK\Tools\Util::trace("Starting stream_seek: uri=".$this->uri." - offset=$offset - whence=$whence");//TRACE

switch($whence)
	{
	case SEEK_CUR: $this->position+=$offset; break;
	case SEEK_END: $this->position=$this->size+$offset; break;
	default: $this->position=$offset; break;
	}
if ($this->position > $this->size) $this->position=$this->size;
if ($this->position < 0) $this->position=0;
return true;
}

//---------------------------------
// Open a directory
//
// We must check the mount point validity because we can be called for an
// unmounted path. In this case, we must return false before searching the
// cache, or the behavior will be different when the data is in the cache
// or not. Note that we check for global commands before validating the mount
// point.

public function dir_opendir($uri,$options)
{
try
{
$this->uri=$uri;
$this->raiseErrors=($options & STREAM_REPORT_ERRORS);

self::parseURI($uri,$this->command,$this->params,$this->mnt
	,$this->path);

if (!is_null($this->mnt)) \PHK\Mgr::validate($this->mnt);

$this->data=self::getFile(true,$uri,$this->mnt,$this->command
	,$this->params,$this->path);

$this->size=count($this->data);
$this->position=0;
}
catch (\Exception $e)
	{
	$msg=$uri.': PHK opendir error - '.$e->getMessage();
	$this->raiseWarning($msg);
	return false;
	}
return true;
}

//---------------------------------
// Get next directory entry

public function dir_readdir()
{
if ($this->position==$this->size) return false;
return $this->data[$this->position++];
}

//---------------------------------
// Set open directory index to 0

public function dir_rewinddir()
{
$this->position=0;
}

//---------------------------------
// Utility function called by stream_stat and url_stat

private static function statArray($mode,$size,$mtime)
{
return array(
	'dev' => 0,
	'ino' => 0,
	'mode' => $mode,
	'nlink' => 1,
	'uid' => 0,
	'gid' => 0,
	'rdev' => -1,
	'size' => $size,
	'atime' => $mtime,
	'mtime' => $mtime,
	'ctime' => $mtime,
	'blksize' => 8192,
	'blocks' => 1);
}

//---------------------------------
// Stat an open file (fstat)

public function stream_stat()
{
\PHK\Tools\Util::trace("Entering stream_stat");//TRACE

return $this->url_stat($this->uri,0,true);
}

//---------------------------------
// Stat an open or closed path - PHP streams API
//
// url_stat does not throw exceptions. It must just return false.
//
// This method must not modify properties (except for parsing the URI),
// because it can be called on an open path.
//
// We must check the mount point validity because we can be called for an
// unmounted path. In this case, we must return false before searching the
// cache, or the behavior will be different when the data is in the cache
// or not. Note that we check for global commands before validating the mount
// point.

public function url_stat($uri,$flags,$fstat=false)
{
\PHK\Tools\Util::trace("Entering url_stat($uri,$flags,$fstat)");//TRACE

try
{
$this->raiseErrors=!($flags & STREAM_URL_STAT_QUIET);

// If we are coming from stream_fstat(), the uri is already parsed.

if (!$fstat)
	{
	self::parseURI($uri,$this->command,$this->params,$this->mnt
		,$this->path);

	if (!is_null($this->mnt)) \PHK\Mgr::validate($this->mnt);
	}

$cacheID=\PHK\Cache::cacheID('stat',$uri);
if (is_null($data=\PHK\Cache::get($cacheID)))	// Miss - Slow path
	{
	\PHK\Tools\Util::trace("url_stat($uri): not found in cache");//TRACE
	try
		{
		$cache=true;
		$mode=$size=$mtime=null;
		Backend::getStatData($this->mnt,$this->command
			,$this->params,$this->path,$cache,$mode,$size,$mtime);
		$data=array($mode,$size,$mtime);
		}
	catch (\Exception $e) // Mark entry as non-existent
		{
		\PHK\Tools\Util::trace("url_stat($uri): lookup failed");//TRACE
		$data='';
		}

	if ($cache && (!is_null($this->mnt)) && \PHK\Mgr::cacheEnabled($this->mnt
		,$this->command,$this->params,$this->path))
		{
		\PHK\Cache::set($cacheID,$data);
		}
	}

if (is_array($data))
	{
	list($mode,$size,$mtime)=$data;
	return self::statArray($mode,$size,$mtime);
	}
else throw new \Exception('File not found');	// Negative hit
}
catch (\Exception $e)
	{
	$msg=$uri.': PHK Stat error - '.$e->getMessage();
	$this->raiseWarning($msg);
	return false;
	}
}

//---------------------------------
/**
* Parses an URI and splits it into four sub-parts : mount point, command name,
* command parameters, and path. Each of these components is optional.
*
* URI syntax: phk://[<mnt>[/path]][?command[&par=val&...]]
*
* On return, if no command: command=params=null
* Global command: phk://?command[&par=val&...] => path=mnt=null
* mnt=null => global command
*
* Test cases :
* phk://   Error
* phk://mnt1   mnt=mnt1, path='', command=params=null
* phk://mnt1/p1/p2	  mnt=mnt1, path=p1/p2, command=params=null
* phk://mnt1/p1/p2?cmd&par1=2&par2=3   mnt=mnt1, path=p1/p2, command=cmd,
*	  params=array(par1 => 2, par2 => 3)
* phk://?gcmd  mnt=path=null, command=gcmd, params=null
*
* @param string $uri
* @param string|null $command Return value
* @param array|null $params Return value
* @param string|null $mnt Return value - Null only if global command
* @param string|null $path Return value - Null only if global command
* @return void
* @throws \Exception on invalid syntax
*/

public static function parseURI($uri,&$command,&$params,&$mnt,&$path)
{
\PHK\Tools\Util::trace("Entering parseURI($uri)");//TRACE

if (! \PHK\Mgr::isPhkUri($uri=str_replace('\\','/',$orig_uri=$uri)))
	throw new \Exception('Not a PHK URI');
$uri=substr($uri,6);	// Remove 'phk://'

if (($pos=strpos($uri,'?'))!==false)	// If the uri contains a command
	{
	$cmd=\PHK\Tools\Util::substr($uri,$pos+1);
	$uri=substr($uri,0,$pos);
	if (($pos=strpos($cmd,'&'))!==false)	// params present
		{
		$command=substr($cmd,0,$pos);
		parse_str(\PHK\Tools\Util::substr($cmd,$pos+1),$params); // Get parameters
		}
	else $command=$cmd;
	if ($command=='') throw new \Exception('Empty command');
	}

$uri=trim($uri,'/');	// Suppress leading and trailing '/'
if ($uri!='') // Not a global command
	{
	$a=explode('/',$uri,2);	//-- Separate mnt and path
	$mnt=$a[0];
	$path=isset($a[1]) ? $a[1] : '';
	}

if (is_null($command) && is_null($mnt)) throw new \Exception('Empty URI');
}

//---------------------------------
} // End of class
//=============================================================================
// Register the PHK wrapper

stream_wrapper_register('phk','PHK\Stream\Wrapper');

//=============================================================================
} // End of class_exists
//=============================================================================
} // End of namespace
//===========================================================================
?>
