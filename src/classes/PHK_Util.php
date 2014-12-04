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
* The PHK_Util class
*
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*/
//=============================================================================
// Ensures PHP_VERSION_ID is set. If version < 5.2.7, emulate.

if (!defined('PHP_VERSION_ID'))
	{
	$v = explode('.',PHP_VERSION);
	define('PHP_VERSION_ID', ($v[0]*10000+$v[1]*100+$v[2]));
	}

//=============================================================================

if (!class_exists('PHK_Util',false))
{
//============================================================================

class PHK_Util	// Static only
{
//-----

private static $verbose=true;

public static function msg($msg)
{
if (self::$verbose) echo $msg."\n";
}

//-----

public static function var_type($var)
{
return is_object($var) ? 'object '.get_class($var) : gettype($var);
}

//-----
// Keep in sync with PHO_Util

public static function env_is_web()
{
return (php_sapi_name()!='cli');
}

//----
// Keep in sync with PHO_Util

public static function env_is_windows()
{
return (substr(PHP_OS, 0, 3) == 'WIN');
}

//----

public static function file_suffix($filename)
{
$dotpos=strrpos($filename,'.');
if ($dotpos===false) return '';

return strtolower(substr($filename,$dotpos+1));
}

//---------
// Warning: This is not the same code as Automap::combine_path() and
// PHO_File::combine_path(). Those were modified to support providing
// an absolute $rpath. So, the behavior is different if $rpath starts with '/'.
//
// Combines a base directory and a relative path. If the base directory is
// '.', returns the relative part without modification
// Use '/' separator on stream-wrapper URIs

public static function combine_path($dir,$rpath)
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

public static function trailing_separ($path, $separ)
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

public static function is_absolute_path($path)
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

public static function mk_absolute_path($path,$separ=false)
{
if (!self::is_absolute_path($path)) $path=self::combine_path(getcwd(),$path);
return self::trailing_separ($path,$separ);
}

//---------
// Adapted from PEAR

public static function load_extension($ext)
{
if (extension_loaded($ext)) return;

if (PHP_OS == 'AIX') $suffix = 'a';
else $suffix = PHP_SHLIB_SUFFIX;

@dl('php_'.$ext.'.'.$suffix) || @dl($ext.'.'.$suffix);

if (!extension_loaded($ext)) throw new Exception("$ext: Cannot load extension");
}

//---------
// Require several extensions. Allows to list every extensions that are not
// present.

public static function load_extensions($ext_list)
{
$failed_ext=array();
foreach($ext_list as $ext)
	{
	try { self::load_extension($ext); }
	catch (Exception $e) { $failed_ext[]=$ext; }
	}
if (count($failed_ext))
	throw new Exception('Cannot load the following required extension(s): '
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

public static function disable_mqr()
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

public static function restore_mqr()
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

public static function timestring($time=null)
{
if ($time=='unlimited') return $time;
if (is_null($time)) $time=time();
return @strftime('%d-%b-%Y %H:%M %z',$time);
}

//---------
// HTTP mode only: Compute the base URL we were called with

public static function http_base_url()
{
if (!self::env_is_web()) return '';

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

public static function http_301_redirect($path)
{
header('Location: http://'.$_SERVER['HTTP_HOST'].self::http_base_url().$path);
header('HTTP/1.1 301 Moved Permanently');
exit(0);
}

//---------------------------------
// Sends an HTTP 404 failure

public static function http_404_fail()
{
header("HTTP/1.0 404 Not Found");
exit(1);
}

//---------------------------------
// Sends an HTTP 403 failure

public static function http_403_fail()
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

public static function readfile($path)
{
if (($data=@file_get_contents($path))===false)
	throw new Exception($path.': Cannot get file content');
return $data;
}

//---------
// Throws exceptions and removes '.' and '..'

public static function scandir($path)
{
if (($subnames=scandir($path))===false)
	throw new Exception($path.': Cannot read directory');

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
        if (($fp=fopen($tfile,'a'))===false) throw new Exception($tfile.': Cannot open trace file');
        fwrite($fp,self::timestring().': '.$msg."\n");
        fclose($fp);
        }
}

//---------
// $start=microtime() float

public static function delta_ms($start)
{
$delta=microtime(true)-$start;

return round($delta*1000,2).' ms';
}

//---------

public static function mk_array($data)
{
if (is_null($data)) return array();
if (!is_array($data)) $data=array($data);
return $data;
}

//---------

public static function display_slow_path()
{
if (getenv('PHK_DEBUG_SLOW_PATH')!==false)
	{
	$html=PHK_Util::env_is_web();

	if (isset($GLOBALS['__PHK_SLOW_PATH']))
		$data="Slow path entered at:\n".$GLOBALS['__PHK_SLOW_PATH'];
	else $data="Fast path OK\n";

	PHK::info_section($html,'Fast path result');

	if ($html) echo "<pre>";
	echo $data;
	if ($html) echo "/<pre>";
	}
}

//---------

public static function slow_path()
{
if ((getenv('PHK_DEBUG_SLOW_PATH')!==false)
	&& (!isset($GLOBALS['__PHK_SLOW_PATH'])))
	{
	$e=new Exception();
	$GLOBALS['__PHK_SLOW_PATH']=$e->getTraceAsString();
	}
}

//-----
/**
* Sends an Exception with a message starting with 'Format error'
*
* @param string $msg Message to send
* @return void
* @throws Exception
*/

public static function format_error($msg)
{
throw new Exception('Format error: '.$msg);
}

//---------------------------------
// Utility functions called by PHK_Mgr. When using the accelerator, this
// data is persistent. So, retrieving it to populate the cache can be done
// in PHP.

public static function get_min_version($mnt,$caching)
{
return PHK_Stream::get_file(false,PHK_Mgr::command_uri($mnt
	,'magic_field&name=mv'),$mnt,'magic_field',array('name' => 'mv'),''
	,$caching);
}

public static function get_options($mnt,$caching)
{
return unserialize(PHK_Stream::get_file(false,PHK_Mgr::section_uri($mnt
	,'OPTIONS'),$mnt,'section',array('name' => 'OPTIONS'),'',$caching));
}

public static function get_build_info($mnt,$caching)
{
return unserialize(PHK_Stream::get_file(false,PHK_Mgr::section_uri($mnt
	,'BUILD_INFO'),$mnt,'section',array('name' => 'BUILD_INFO'),'',$caching));
}

//---------------------------------

public static function call_method($object,$method,$args)
{
return call_user_func_array(array($object,$method),$args);
}

//---------------------------------

public static function run_webinfo($phk)
{
$phk->proxy()->crc_check();	//-- check CRC before running webinfo
$phkw=new PHK_Webinfo($phk);
$phkw->run();
}

//---------------------------------

public static function atomic_write($path,$data)
{
$tmpf=tempnam(dirname($path),'tmp_');

if (file_put_contents($tmpf,$data)!=strlen($data))
	throw new Exception($tmpf.": Cannot write");

// Windows does not support renaming to an existing file (looses atomicity)

if (PHK_Util::env_is_windows()) @unlink($path);

if (!rename($tmpf,$path))
	{
	unlink($tmpf);
	throw new Exception($path.': Cannot replace file');
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
* @throws Exception
*/

private static $simul_inode_array=array();
private static $simul_inode_index=1;

public static function path_unique_id($prefix,$path,&$mtime)
{
if (($s=stat($path))===false) throw new Exception("$path: File not found");

$dev=$s[0];
$inode=$s[1];
$mtime=$s[9];

if ($inode==0) // This system does not support inodes
	{
	$rpath=realpath($path);
	if ($rpath === false) throw new Exception("$path: Cannot compute realpath");

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

//---------
} // End of class PHK_Util
//=============================================================================
} // End of class_exists('PHK_Util')
//=============================================================================
?>
