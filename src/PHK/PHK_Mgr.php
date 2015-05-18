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

namespace {

if (!class_exists('PHK_Mgr',false))
{
//=============================================================================
/**
* This is a static-only class. It has the responsability to manage the table
* of currently mounted packages. Each package is uniquely identified by a
* 'mount point', which is a string computed at mount time. The PHK_Mgr class
* contains the methods allowing to get information from its table, as well
* as methods to mount and umount a package.
*
* Runtime code -> 100% read-only.
*
* @package PHK
*/

//=============================================================================

class PHK_Mgr	// Static only
{
/** Class version */

const VERSION='1.4.0';

//-- Global static properties

/** @var array		Currently mounted PHK instances */

private static $phk_tab=array(); // Key=mount directory, value=PHK instance

/**
* @var array		Proxy objects for each currently mounted package. As
* long as the proxy object is not instantiated through the proxy() method,
* the corresponding value is null in this array. Contains exactly the same
* keys as $phk_tab.
*/

private static $proxy_tab=array(); // Key=mount directory, value=PHK_Proxy|null

/* @var int Running value for PHK_Creator mount points */

private static $tmp_mnt_num=0;

/* @var boolean|null Global cache toggle. If null, per-instance logic is used */

private static $caching=null;

//-----
/**
* Checks if a mount point is valid (if it corresponds to a currently mounted
* package)
*
* @param string $mnt Mount point to check
* @return boolean
*/

public static function is_mounted($mnt)
{
return isset(self::$phk_tab[$mnt]);
}

//-----
/**
* Same as is_mounted but throws an exception is the mount point is invalid.
*
* Returns the mount point so that it can be embedded in a call string.
*
* @param string $mnt Mount point to check
* @return string mount point (not modified)
* @throws Exception if mount point is invalid
*/

public static function validate($mnt)
{
if (!self::is_mounted($mnt)) throw new Exception($mnt.': Invalid mount point');

return $mnt;
}

//-----
/**
* Returns the PHK object corresponding to a given mount point
*
* @param string $mnt Mount point
* @return PHK instance
* @throws Exception if mount point is invalid
*/

public static function instance($mnt)
{
self::validate($mnt);

return self::$phk_tab[$mnt];
}

//-----
/**
* Returns the PHK_Proxy object corresponding to a given mount point
*
* If the corresponding PHK_Proxy object does not exist yet, it is created.
*
* @param string $mnt Mount point
* @return PHK_Proxy proxy object
* @throws Exception if mount point is invalid
*/

public static function proxy($mnt)
{
self::validate($mnt);

if (is_null(self::$proxy_tab[$mnt]))
	{
	$phk=self::instance($mnt);
	self::$proxy_tab[$mnt]=new PHK_Proxy($phk->path(),$phk->flags());
	}

return self::$proxy_tab[$mnt];
}

//-----
/**
* Returns the list of the defined mount points.
*
* @return array
*/

public static function mnt_list()
{
return array_keys(self::$phk_tab);
}

//---------
/**
* Sets the global caching toggle
*
* Normally, the global cache toggle is always null, except in 'webinfo'
* mode, where it is false to inhibit any caching of webinfo information
* (2 reasons: useless in terms of performance, and could use the same keys as
* the 'non-webinfo' mode, so we would have to use another key prefix).
*
* @param boolean|null $caching True: always cache, False: never cache,
* null: use per-instance logic.
* @return void
*/

public static function set_cache($caching)
{
self::$caching=$caching;
}

//---------
/**
* Determines if a an URI can be cached.
*
* For performance reasons, the input URI is splitted.
*
* Called by the wrapper to know if it should cache the data it got from its
* backend.
*
* The global cache toggle is checked first. If it is null, control is
* transferred to the instance logic.
*
* @param string|null $mnt Mount point or null if global command
* @param string|null $command command if defined
* @param array|null $params Command parameters if defined
* @param string $path Path
* @return boolean whether the data should be cached or not
* @throws Exception if mount point is invalid
*/


public static function cache_enabled($mnt,$command,$params,$path)
{
if (!is_null(self::$caching)) return self::$caching;

if (is_null($mnt)) return false;

return self::instance($mnt)->cache_enabled($command,$params,$path);
}

//---------
/**
* Given a file path, tries to determine if it is currently mounted. If it is
* the case, the corresponding mount point is returned. If not, an exception is
* thrown.
*
* Note: when dealing with sub-packages, the input path parameter can be a PHK
* URI.
*
* @param string $path Path of a PHK package
* @return the corresponding mount point
* @throws Exception if the file is not currently mounted
*/

public static function path_to_mnt($path)
{
$dummy1=$mnt=$dummy2=null;
self::compute_mnt($path,$dummy1,$mnt,$dummy2);

if (self::is_mounted($mnt)) return $mnt;

throw new Exception($path.': path is not mounted');
}

//---------
/**
* Given a PHK uri, returns the path of the first-level package for
* this path. The function recurses until it finds a physical path.
*
* @param string $path A path typically set as '__FILE__'
* @return the physical path of the 1st-level package containing this path
*/

public static function toplevel_path($path)
{
while (self::is_a_phk_uri($path))
	{
	$mnt=self::uri_to_mnt($path);
	$map=self::instance($mnt);
	$path=$map->path();
	}
return $path;
}

//---------
/**
* Mount a PHK package and returns the new (or previous, if already loaded)
* PHK mount point.
*
* Can also create empty PHK_Creator instances (when the 'CREATOR' flag is set).
*
* @param string $path The path of an existing PHK archive, or the path of the
*                     archive to create if ($flags & PHK::IS_CREATOR)
* @param int $flags Or-ed combination of PHK mount flags.
* @return string the mount point
*/

public static function mount($path,$flags=0)
{
try
{
if ($flags & PHK::IS_CREATOR)
	{
	$mnt='_tmp_mnt_'.(self::$tmp_mnt_num++);
	self::$proxy_tab[$mnt]=null;
	self::$phk_tab[$mnt]=new PHK_Creator($mnt,$path,$flags);
	}
else	// Mount an existing archive
	{
	$parent_mnt=$mnt=$mtime=$options=$build_info=null;
	self::compute_mnt($path,$parent_mnt,$mnt,$mtime);
	if (self::is_mounted($mnt)) return $mnt;

	self::$proxy_tab[$mnt]=null;
	self::$phk_tab[$mnt]=$phk=new PHK($parent_mnt,$mnt,$path,$flags,$mtime);

	self::get_store_data($mnt,$options,$build_info);
	$phk->init($options,$build_info);
	}
}
catch (Exception $e)
	{
	if (isset($mnt) && self::is_mounted($mnt)) unset(self::$phk_tab[$mnt]);
	throw new Exception($path.': Cannot mount - '.$e->getMessage());
	}

return $mnt;
}

//-----
/**
* Checks the PHK version this package requires against the current version.
* Then, retrieves the 'options' and 'build_info' arrays.
*
* This function is separated from mount() to mimic the behavior of the PHK
* extension, where this data is cached in persistent memory.
*
* @param string $mnt the mount point
* @param array $options on return, contains the options array
* @param array $build_info on return, contains the build_info array
* @return void
*/

private static function get_store_data($mnt,&$options,&$build_info)
{
$caching=(is_null(self::$caching) ? true : self::$caching);

// Must check this first

$mv=PHK_Util::get_min_version($mnt,$caching);

if (version_compare($mv,PHK::VERSION) > 0)
	{
	PHK_Util::format_error('Cannot understand this version. '
		.'Requires at least PHK version '.$mv);
	}

$options=PHK_Util::get_options($mnt,$caching);
$build_info=PHK_Util::get_build_info($mnt,$caching);
}

//---------------------------------
/**
* Computes the mount point corresponding to a given path.
*
* Also returns the parent mount point (for sub-packages), and the modification
* time (allows to call stat() only once).
*
* Mount point uniqueness is based on a combination of device+inode+mtime.
*
* When dealing with sub-packages, the input path is a PHK URI.
*
* Sub-packages inherit their parent's modification time.
*
* @param string $path The path to be mounted
* @param $parent_mnt string|null returns the parent mount point. Not
* null only for sub-packages.
* @param string $mnt returns the computed mount point
* @param int $mtime returns the modification time
* @return void
* @throws Exception with message 'File not found' if unable to stat($path).
*/

private static function compute_mnt($path,&$parent_mnt,&$mnt,&$mtime)
{
if (self::is_a_phk_uri($path)) // Sub-package
	{
	$dummy1=$dummy2=$subpath=$parent_mnt=null;
	PHK_Stream::parse_uri($path,$dummy1,$dummy2,$parent_mnt,$subpath);
	self::validate($parent_mnt);
	$mnt=$parent_mnt.'#'.str_replace('/','*',$subpath);
	$mtime=self::instance($parent_mnt)->mtime(); // Inherit mtime
	}
else
	{
	$mnt=PHK_Util::path_unique_id('p',$path,$mtime);
	$parent_mnt=null;
	}
}

//---------------------------------
/**
* Umounts a mounted package and any mounted descendant.
*
* We dont use __destruct because :
*	1. We don't want this to be called on script shutdown
*	2. Exceptions cannot be caught when sent from a destructor.
*
* Accepts to remove a non registered mount point without error
*
* @param string $mnt The mount point to umount
*/

public static function umount($mnt)
{
if (self::is_mounted($mnt))
	{
	// Umount children

	foreach (array_keys(self::$phk_tab) as $dmnt)
		{
		if (isset(self::$phk_tab[$dmnt])
			&& self::$phk_tab[$dmnt]->parent_mnt()===$mnt)
				self::umount($dmnt);
		}

	// Call instance's umount procedure

	self::$phk_tab[$mnt]->umount();

	// Remove from the list

	unset(self::$phk_tab[$mnt]);
	unset(self::$proxy_tab[$mnt]);
	}
}

//---------
/**
* Builds a 'phk://' uri, from a mount directory and a path
*
* @param string $mnt The mount point
* @param string $path The path
* @return string The computed URI
*/

public static function uri($mnt,$path)
{
return self::base_uri($mnt).ltrim($path,'/');
}

//-----
/** Checks if a string is a PHK URI
*
* @param string $uri
* @return boolean
*/

public static function is_a_phk_uri($uri)
{
$u=$uri.'      ';

// Much faster this way

return ($u{0}=='p' && $u{1}=='h' && $u{2}=='k' && $u{3}==':'
	&& $u{4}=='/' && $u{5}=='/'); 

//return (strlen($uri) >= 6) && (substr($uri,0,6)=='phk://');
}

//-----
/**
* Returns the base string used to build URIs for a given mount point.
*
* The base URI has the form : phk://<mount point>/
*
* @param string $mnt A mount point
* @return string
*/

public static function base_uri($mnt)
{
return 'phk://'.$mnt.'/';
}

//---------
/**
* Returns a 'command' URI, given a mount point and a 'command' string
*
* Command URIs have the form : phk://<mount point>/?<command>
*
* @param string $mnt A mount point
* @param string $command Command string
* @return string
*/

public static function command_uri($mnt,$command)
{
return self::uri($mnt,'?'.$command);
}

//---------
/**
* Returns the URI allowing to retrieve a section.
*
* Section URIs have the form : phk://<mount point>/?section&name=<section>
*
* @param string $mnt A mount point
* @param string $section The section to retrieve
* @return string
*/

public static function section_uri($mnt,$section)
{
return self::command_uri($mnt,'section&name='.$section);
}

//-----
/**
* Returns the URI of the Automap map if it is defined
*
* @param string $mnt A mount point
* @return string|null returns null if the package does not define an automap.
*/

public static function automap_uri($mnt)
{
if ((!self::is_mounted($mnt))||(!self::instance($mnt)->map_defined()))
	return null;

return self::section_uri($mnt,'AUTOMAP');
}

//---------------------------------
/**
* Replaces '\' characters by '/' in a URI.
*
* @param string $uri
* @return string
*/

public static function normalize_uri($uri)
{
return str_replace('\\','/',$uri);
}

//-----
/**
* Returns the mount directory of a subfile's phk uri.
* Allows to reference other subfiles in the same package if you don't want
* or cannot use Automap (the preferred method) or a relative path.
* Example : include(PHK_Mgr::uri(PHK_Mgr::uri_to_mnt(__FILE__),<path>));
*
* @param string $uri
* @return string a mount point
* @throws Exception if the input URI is not a PHK URI
*/

public static function uri_to_mnt($uri)
{
if (! self::is_a_phk_uri($uri))
	throw new Exception($uri.': Not a PHK URI');

$buf=substr(self::normalize_uri($uri),6);
$buf=substr($buf,0,strcspn($buf,'/'));
return trim($buf);
}

//---------
/**
* Check if the current PHP version is supported.
*
* Note that, if PHP version < 5.1, parsing fails and we don't even start
* execution. So, this test is only executed when PHP version >= 5.1
*
* As a side effect, until we request a version > 5.1, this function
* will never fail.
*
* Calls exit(1) if PHP version is not supported by the PHK runtime
*
* @return void
*/

public static function php_version_check()
{
if (version_compare(PHP_VERSION,'5.1.0') < 0)
	{
	echo PHP_VERSION.': Unsupported PHP version '
		.'- PHK needs at least version 5.1.0';
	exit(1);
	}
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
