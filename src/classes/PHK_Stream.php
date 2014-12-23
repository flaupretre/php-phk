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

if (!class_exists('PHK_Stream',false))
{
//=============================================================================
/**
* The PHK stream wrapper
*
* Handles every file access to a 'phk://...' URI
*
* Note: Always catch exceptions before returning to PHP.
*/

class PHK_Stream extends PHK_Util
{

private $uri;

private $mnt;		// Mount point (string)
private $path;		// File path in PHK (string|null)
private $command;	// Command (string|null)
private $params;	// Command parameter (array|null)

private $data;	// File data (regular file) or dir content
private $size;	// Size of buffer or number of dir entries
private $position; // Byte position or position in array

private $raise_errors=true;

//---------------------------------
// Display a warning if they are allowed

private function raise_warning($msg)
{
if ($this->raise_errors) trigger_error("PHK: $msg",E_USER_WARNING);
}

//---------------------------------

public static function get_file($dir,$uri,$mnt,$command,$params,$path,$cache=null)
{
$cache_id=PHK_Cache::cache_id('node',$uri);
PHK_Util::trace("get_file: Cache ID=<$cache_id>");//TRACE

if (is_null($data=PHK_Cache::get($cache_id)))	// Miss
	{
	$can_cache=true;
	
	if (is_null($data=($dir ?
		PHK_Stream_Backend::get_dir_data($mnt,$command,$params,$path)
		: PHK_Stream_Backend::get_file_data($mnt,$command,$params,$path
			,$can_cache)))) throw new Exception("$uri: File not found");

	if ($can_cache && (($cache===true) || (is_null($cache)
		&& PHK_MGR::cache_enabled($mnt,$command,$params,$path))))
			PHK_Cache::set($cache_id,$data);
	}

if ($dir && (!is_array($data))) throw new Exception('Not a directory');
if ((!$dir) && (!is_string($data))) throw new Exception('Not a regular file');

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
PHK_Util::trace("Starting stream_open: uri=$uri");//TRACE

try
{
$this->uri=$uri;
$this->raise_errors=($options & STREAM_REPORT_ERRORS);
if ($options & STREAM_USE_PATH) $opened_path=$uri;

if (($mode!='r')&&($mode!='rb'))
	throw new Exception($mode.': unsupported mode (Read only)');

self::parse_uri($uri,$this->command,$this->params,$this->mnt,$this->path);

if (!is_null($this->mnt)) PHK_Mgr::validate($this->mnt);

$this->data=self::get_file(false,$uri,$this->mnt,$this->command
	,$this->params,$this->path);

$this->size=strlen($this->data);
$this->position=0;
}
catch (Exception $e)
	{
	$msg=$uri.': Open error - '.$e->getMessage();
	$this->raise_warning($msg);
	return false;
	}
PHK_Util::trace("Exiting stream_open: uri=$uri");//TRACE
return true;
}

//---------------------------------
// Read on an open file

public function stream_read($nb)
{
PHK_Util::trace("Starting stream_read: uri=".$this->uri." - nb=$nb - position=".$this->position." size=".$this->size);//TRACE

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
PHK_Util::trace("Starting stream_seek: uri=".$this->uri." - offset=$offset - whence=$whence");//TRACE

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
$this->raise_errors=($options & STREAM_REPORT_ERRORS);

self::parse_uri($uri,$this->command,$this->params,$this->mnt
	,$this->path);

if (!is_null($this->mnt)) PHK_Mgr::validate($this->mnt);

$this->data=self::get_file(true,$uri,$this->mnt,$this->command
	,$this->params,$this->path);

$this->size=count($this->data);
$this->position=0;
}
catch (Exception $e)
	{
	$msg=$uri.': PHK opendir error - '.$e->getMessage();
	$this->raise_warning($msg);
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

private static function stat_array($mode,$size,$mtime)
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
PHK_Util::trace("Entering stream_stat");//TRACE

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
PHK_Util::trace("Entering url_stat($uri,$flags,$fstat)");//TRACE

try
{
$this->raise_errors=!($flags & STREAM_URL_STAT_QUIET);

// If we are coming from stream_fstat(), the uri is already parsed.

if (!$fstat)
	{
	self::parse_uri($uri,$this->command,$this->params,$this->mnt
		,$this->path);

	if (!is_null($this->mnt)) PHK_Mgr::validate($this->mnt);
	}

$cache_id=PHK_Cache::cache_id('stat',$uri);
if (is_null($data=PHK_Cache::get($cache_id)))	// Miss - Slow path
	{
	PHK_Util::trace("url_stat($uri): not found in cache");//TRACE
	try
		{
		$cache=true;
		$mode=$size=$mtime=null;
		PHK_Stream_Backend::get_stat_data($this->mnt,$this->command
			,$this->params,$this->path,$cache,$mode,$size,$mtime);
		$data=array($mode,$size,$mtime);
		}
	catch (Exception $e) // Mark entry as non-existent
		{
		PHK_Util::trace("url_stat($uri): lookup failed");//TRACE
		$data='';
		}

	if ($cache && (!is_null($this->mnt)) && PHK_MGR::cache_enabled($this->mnt
		,$this->command,$this->params,$this->path))
		{
		PHK_Cache::set($cache_id,$data);
		}
	}

if (is_array($data))
	{
	list($mode,$size,$mtime)=$data;
	return self::stat_array($mode,$size,$mtime);
	}
else throw new Exception('File not found');	// Negative hit
}
catch (Exception $e)
	{
	$msg=$uri.': PHK Stat error - '.$e->getMessage();
	$this->raise_warning($msg);
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
* @throws Exception on invalid syntax
*/

public static function parse_uri($uri,&$command,&$params,&$mnt,&$path)
{
PHK_Util::trace("Entering parse_uri($uri)");//TRACE

if (! PHK_Mgr::is_a_phk_uri($uri=str_replace('\\','/',$orig_uri=$uri)))
	throw new Exception('Not a PHK URI');
$uri=substr($uri,6);	// Remove 'phk://'

if (($pos=strpos($uri,'?'))!==false)	// If the uri contains a command
	{
	$cmd=PHK_Util::substr($uri,$pos+1);
	$uri=substr($uri,0,$pos);
	if (($pos=strpos($cmd,'&'))!==false)	// params present
		{
		$command=substr($cmd,0,$pos);
		parse_str(PHK_Util::substr($cmd,$pos+1),$params); // Get parameters
		}
	else $command=$cmd;
	if ($command=='') throw new Exception('Empty command');
	}

$uri=trim($uri,'/');	// Suppress leading and trailing '/'
if ($uri!='') // Not a global command
	{
	$a=explode('/',$uri,2);	//-- Separate mnt and path
	$mnt=$a[0];
	$path=isset($a[1]) ? $a[1] : '';
	}

if (is_null($command) && is_null($mnt)) throw new Exception('Empty URI');
}

//---------------------------------
} // End of class PHK_Stream
//=============================================================================
// Register the PHK wrapper

stream_wrapper_register('phk','PHK_Stream');

//=============================================================================
} // End of class_exists('PHK_Stream')
//=============================================================================
?>
