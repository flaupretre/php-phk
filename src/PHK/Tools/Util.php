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
* The PHK\Tools\Util class
*
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*/
//=============================================================================

namespace PHK\Tools {

// Ensures PHP_VERSION_ID is set. If version < 5.2.7, emulate.

if (!defined('PHP_VERSION_ID'))
	{
	$v = explode('.',PHP_VERSION);
	define('PHP_VERSION_ID', ($v[0]*10000+$v[1]*100+$v[2]));
	}

//=============================================================================

if (!class_exists('PHK\Tools\Util',false))
{
//============================================================================

class Util	// Static only
{
//-----

private static $verbose=true;

public static function msg($msg)
{
if (self::$verbose) echo $msg."\n";
}

//-----

public static function varType($var)
{
return is_object($var) ? 'object '.get_class($var) : gettype($var);
}

//-----
// Keep in sync with \Phool\Util

public static function envIsWeb()
{
return (php_sapi_name()!='cli');
}

//----
// Keep in sync with \Phool\Util

public static function envIsWindows()
{
return (substr(PHP_OS, 0, 3) == 'WIN');
}

//----

public static function fileSuffix($filename)
{
$dotpos=strrpos($filename,'.');
if ($dotpos===false) return '';

return strtolower(substr($filename,$dotpos+1));
}

//---------
// Warning: This is not the same code as \Automap\Map::combinePath() and
// \Phool\File::combinePath(). Those were modified to support providing
// an absolute $rpath. So, the behavior is different if $rpath starts with '/'.
//
// Combines a base directory and a relative path. If the base directory is
// '.', returns the relative part without modification
// Use '/' separator on stream-wrapper URIs

public static function combinePath($dir,$rpath)
{
if ($dir=='.' || $dir=='') return $rpath;
$rpath=trim($rpath,'/');
$rpath=trim($rpath,'\\');

$separ=(strpos($dir,':')!==false) ? '/' : DIRECTORY_SEPARATOR;
if (($dir==='/') || ($dir==='\\')) $separ='';
else
	{
	$c=substr($dir,-1,1);
	if (($c==='/') || ($c=='\\')) $dir=rtrim($dir,$c);
	}

return (($rpath==='.') ? $dir : $dir.$separ.$rpath);
}

//---------------------------------
/**
* Adds or removes a trailing separator in a path
*
* @param string $path Input
* @param bool $flag true: add trailing sep, false: remove it
* @return bool The result path
*/

public static function trailingSepar($path, $separ)
{
$path=rtrim($path,'/\\');
if ($path=='') return '/';
if ($separ) $path=$path.'/';
return $path;
}

//---------------------------------
/**
* Determines if a given path is absolute or relative
*
* @param string $path The path to check
* @return bool True if the path is absolute, false if relative
*/

public static function isAbsolutePath($path)
{
return ((strpos($path,':')!==false)
	||(strpos($path,'/')===0)
	||(strpos($path,'\\')===0));
}

//---------------------------------
/**
* Build an absolute path from a given (absolute or relative) path
*
* If the input path is relative, it is combined with the current working
* directory.
*
* @param string $path The path to make absolute
* @param bool $separ True if the resulting path must contain a trailing separator
* @return string The resulting absolute path
*/

public static function mkAbsolutePath($path,$separ=false)
{
if (!self::isAbsolutePath($path)) $path=self::combinePath(getcwd(),$path);
return self::trailingSepar($path,$separ);
}

//---------
// Adapted from PEAR

public static function loadExtension($ext)
{
if (extension_loaded($ext)) return;

if (PHP_OS == 'AIX') $suffix = 'a';
else $suffix = PHP_SHLIB_SUFFIX;

@dl('php_'.$ext.'.'.$suffix) || @dl($ext.'.'.$suffix);

if (!extension_loaded($ext)) throw new \Exception("$ext: Cannot load extension");
}

//---------
// Require several extensions. Allows to list every extensions that are not
// present.

public static function loadExtensions($ext_list)
{
$failed_ext=array();
foreach($ext_list as $ext)
	{
	try { self::loadExtension($ext); }
	catch (\Exception $e) { $failed_ext[]=$ext; }
	}
if (count($failed_ext))
	throw new \Exception('Cannot load the following required extension(s): '
		.implode(' ',$failed_ext));
}

//---------
// Replacement for substr()
// Difference : returns '' instead of false (when index out of range)

public static function substr($buf,$position,$len=NULL)
{
$str=is_null($len) ? substr($buf,$position) : substr($buf,$position,$len);
if ($str===false) $str='';
return $str;
}

//---------
// This function must be called before every file access
// Starting with version 5.3.0, 'magic_quotes_runtimes' is deprecated and
// mustn't be used any more.

private static $mqr_exists=null;
private static $mqr_level=0;
private static $mqr_save;

public static function disableMQR()
{
if (is_null(self::$mqr_exists))
	self::$mqr_exists=((PHP_VERSION_ID < 50300)
		&& function_exists('set_magic_quotes_runtime'));

if (!self::$mqr_exists) return;

if (self::$mqr_level==0)
	{
	self::$mqr_save=get_magic_quotes_runtime();
	set_magic_quotes_runtime(0);
	}
self::$mqr_level++;
}

//---------
// This function must be called after every file access

public static function restoreMQR()
{
if (is_null(self::$mqr_exists))
	self::$mqr_exists=((PHP_VERSION_ID < 50300)
		&& function_exists('set_magic_quotes_runtime'));

if (!self::$mqr_exists) return;

self::$mqr_level--;
if (self::$mqr_level==0) set_magic_quotes_runtime(self::$mqr_save);
}

//---------
// Converts a timestamp to a string
// @ to suppress warnings about system timezone

public static function timeString($time=null)
{
if ($time=='unlimited') return $time;
if (is_null($time)) $time=time();
return @strftime('%d-%b-%Y %H:%M %z',$time);
}

//---------
// HTTP mode only: Compute the base URL we were called with

public static function httpBaseURL()
{
if (!self::envIsWeb()) return '';

if (!isset($_SERVER['PATH_INFO'])) return $_SERVER['PHP_SELF'];

$phpself=$_SERVER['PHP_SELF'];
$slen=strlen($phpself);

$pathinfo=$_SERVER['PATH_INFO'];
$ilen=strlen($pathinfo);

// Remove PATH_INFO from PHP_SELF if it is at the end. Don't know
// which config does this, but some servers put it, some don't.

if (($slen > $ilen) && (substr($phpself,$slen-$ilen)==$pathinfo))
	$phpself=substr($phpself,0,$slen-$ilen);

return $phpself;
}

//---------------------------------
// Sends an HTTP 301 redirection

public static function http301Redirect($path)
{
header('Location: http://'.$_SERVER['HTTP_HOST'].self::httpBaseURL().$path);
header('HTTP/1.1 301 Moved Permanently');
exit(0);
}

//---------------------------------
// Sends an HTTP 404 failure

public static function http404Fail()
{
header("HTTP/1.0 404 Not Found");
exit(1);
}

//---------------------------------
// Sends an HTTP 403 failure

public static function http403Fail()
{
header("HTTP/1.0 403 Forbidden");
exit(1);
}

//-----

public static function bool2str($cond)
{
return $cond ? 'Yes' : 'No';
}

//---------

public static function readFile($path)
{
if (($data=@file_get_contents($path))===false)
	throw new \Exception($path.': Cannot get file content');
return $data;
}

//---------
// Throws exceptions and removes '.' and '..'

public static function scandir($path)
{
if (($subnames=scandir($path))===false)
	throw new \Exception($path.': Cannot read directory');

$a=array();
foreach($subnames as $f)
	if (($f!='.') && ($f!='..')) $a[]=$f;

return $a;
}

//---------

public static function trace($msg)
{
if (($tfile=getenv('PHK_TRACE_FILE')) !== false)
        {
        // Append message to trace file
        if (($fp=fopen($tfile,'a'))===false) throw new \Exception($tfile.': Cannot open trace file');
        fwrite($fp,self::timeString().': '.$msg."\n");
        fclose($fp);
        }
}

//---------
// $start=microtime() float

public static function deltaMS($start)
{
$delta=microtime(true)-$start;

return round($delta*1000,2).' ms';
}

//---------

public static function mkArray($data)
{
if (is_null($data)) return array();
if (!is_array($data)) $data=array($data);
return $data;
}

//---------

public static function displaySlowPath()
{
if (getenv('PHK_DEBUG_SLOW_PATH')!==false)
	{
	$html=self::envIsWeb();

	if (isset($GLOBALS['__PHK_SLOW_PATH']))
		$data="Slow path entered at:\n".$GLOBALS['__PHK_SLOW_PATH'];
	else $data="Fast path OK\n";

	\PHK::infoSection($html,'Fast path result');

	if ($html) echo "<pre>";
	echo $data;
	if ($html) echo "/<pre>";
	}
}

//---------

public static function slowPath()
{
if ((getenv('PHK_DEBUG_SLOW_PATH')!==false)
	&& (!isset($GLOBALS['__PHK_SLOW_PATH'])))
	{
	$e=new \Exception();
	$GLOBALS['__PHK_SLOW_PATH']=$e->getTraceAsString();
	}
}

//-----
/**
* Sends an \Exception with a message starting with 'Format error'
*
* @param string $msg Message to send
* @return void
* @throws \Exception
*/

public static function formatError($msg)
{
throw new \Exception('Format error: '.$msg);
}

//---------------------------------
// Utility functions called by PHK\Mgr. When using the accelerator, this
// data is persistent. So, retrieving it to populate the cache can be done
// in PHP.

public static function getMinVersion($mnt,$caching)
{
return \PHK\Stream\Wrapper::getFile(false,\PHK\Mgr::commandURI($mnt
	,'magicField&name=mv'),$mnt,'magicField',array('name' => 'mv'),''
	,$caching);
}

public static function getOptions($mnt,$caching)
{
return unserialize(\PHK\Stream\Wrapper::getFile(false,\PHK\Mgr::sectionURI($mnt
	,'OPTIONS'),$mnt,'section',array('name' => 'OPTIONS'),'',$caching));
}

public static function getBuildInfo($mnt,$caching)
{
return unserialize(\PHK\Stream\Wrapper::getFile(false,\PHK\Mgr::sectionURI($mnt
	,'BUILD_INFO'),$mnt,'section',array('name' => 'BUILD_INFO'),'',$caching));
}

//---------------------------------

public static function callMethod($object,$method,$args)
{
// Special care to avoid endless loops

if (!method_exists($object,$method))
	throw new \Exception("$method: calling non existing method");

return call_user_func_array(array($object,$method),$args);
}

//---------------------------------

public static function runWebInfo($phk)
{
$phk->proxy()->crcCheck();	//-- check CRC before running webinfo
$phkw=new \PHK\Web\Info($phk);
$phkw->run();
}

//---------------------------------

public static function atomicWrite($path,$data)
{
$tmpf=tempnam(dirname($path),'tmp_');

if (file_put_contents($tmpf,$data)!=strlen($data))
	throw new \Exception($tmpf.": Cannot write");

// Windows does not support renaming to an existing file (looses atomicity)

if (self::envIsWindows()) @unlink($path);

if (!rename($tmpf,$path))
	{
	unlink($tmpf);
	throw new \Exception($path.': Cannot replace file');
	}
}

//---------------------------------
/**
* Computes a string uniquely identifying a given path on this host.
*
* Mount point unicity is based on a combination of device+inode+mtime.
*
* On systems which don't supply a valid inode number (eg Windows), we
* maintain a fake inode table, whose unicity is based on the path filtered
* through realpath(). It is not perfect because I am not sure that realpath
* really returns a unique 'canonical' path, but this is best solution I
* have found so far.
*
* @param string $path The path to be mounted
* @return string the computed mount point
* @throws \Exception
*/

private static $simul_inode_array=array();
private static $simul_inode_index=1;

public static function pathUniqueID($prefix,$path,&$mtime)
{
if (($s=@stat($path))===false) throw new \Exception("$path: File not found");

$dev=$s[0];
$inode=$s[1];
$mtime=$s[9];

if ($inode==0) // This system does not support inodes
	{
	$rpath=realpath($path);
	if ($rpath === false) throw new \Exception("$path: Cannot compute realpath");

	if (isset(self::$simul_inode_array[$rpath]))
		$inode=self::$simul_inode_array[$rpath];
	else
		{ // Create a new slot
		$inode=self::$simul_inode_index++;	
		self::$simul_inode_array[$rpath]=$inode;
		}
	}

return sprintf('%s_%X_%X_%X',$prefix,$dev,$inode,$mtime);
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
