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

if (!class_exists('PHK\Stream\Backend',false))
{
//=============================================================================
/**
* The 'slow' backend to \PHK\Stream\Wrapper
*
* This class is called when \PHK\Stream\Wrapper cannot retrieve the information it needs
* from the cache or for uncacheable information.
*
* Note (30-NOV-2007): As long as the extension does not trap exceptions
* correctly, we trap them here and return null instead.
*/

class Backend
{

/** @var string		Used as temp storage by stringInclude() */

private static $tmp_data=null;

/** tmp pseudo-file URI */

const TMP_URI='phk://?tmp';

//---------------------------------
// Returns pseudo-file data string.
// If a command does not want to be cached, it sets $cache to false.
// stat() calls are used mainly for file existence/type. Size is less
// important.

private static function commandOpenOrStat($stat_flag,$mnt,$command,$params
	,$path,&$cache)
{
$cache=true; // Default

try
{
if (is_null($mnt))	// Global command
	{
	switch($command)
		{
		case 'test':
			return "Test line 1/3\nTest line2/3\nTest line 3/3\n";
			break;

		case 'tmp':	// Special: Used by \PHK::stringInclude();
			$cache=false;
			return self::$tmp_data;
			break;

		default:
			throw new \Exception($command.': Unknown global command');
		}
	}
else // Slow path
	{
	$proxy=\PHK\Mgr::proxy($mnt);

	switch ($command)
		{
		// These commands :
		//	- go to the proxy
		//	- are cached
		//	- take a mandatory 'name' argument and send it to the method
		//	- take the data returned by the method

		case 'section':
 		case 'magicField':
			if (!isset($params['name']))
				throw new \Exception($command
					.': command needs this argument: name');
			return $proxy->$command($params['name']);

		// These commands :
		//	- go to the proxy
		//	- are cached
		//	- serialize the data returned by the method

		case 'pathList':
		case 'sectionList':
			return serialize($proxy->$command());

		default:
			throw new \Exception($command.': Unknown command');
		}
	}
}
catch (\Exception $e)
	{
	throw new \Exception($command.': Error during command execution - '
		.$e->getMessage());
	}
}

//---------------------------------
// Segfault in extension if this function throws an exception. As long as
// this bug is not fixed, trap the exception before returning to PHK\Stream\Wrapper

public static function getFileData($mnt,$command,$params,$path,&$cache)
{
$cache=true;

try
{
if (is_null($command))
	{
	$node=\PHK\Mgr::proxy($mnt)->ftree()->lookupFile($path,false);
	if (is_null($node)) return null;
	return $node->read();
	}
else
	{
	return self::commandOpenOrStat(false,$mnt,$command,$params,$path,$cache);
	}
}
catch (\Exception $e) { return null; }
}

//---------------------------------
// Must accept the same parameters as getFileData()

public static function getDirData($mnt,$command,$params,$path)
{
try
{
if (!is_null($command)) return null;

$node=\PHK\Mgr::proxy($mnt)->ftree()->lookup($path,false);
if (is_null($node)) return null;
return $node->getDir();
}
catch (\Exception $e) { return null; }
}

//---------------------------------

public static function getStatData($mnt,$command,$params,$path,$cache
	,&$mode,&$size,&$mtime)
{
if (!is_null($command))
	{
	$mode=0100444;	// Pseudo regular file
	// Throws exception if command invalid or no target
	$size=strlen(self::commandOpenOrStat(true,$mnt,$command,$params
		,$path,$cache));
	}
else
	{
	$node=\PHK\Mgr::proxy($mnt)->ftree()->lookup($path);

	$mode=$node->mode();
	$size=$node->size();
	}
$mtime=(is_null($mnt) ? time() : \PHK\Mgr::instance($mnt)->mtime());
}

//----
// Undocumented

public static function setTmpData($str)
{
$prev=self::$tmp_data;
self::$tmp_data=$str;
return $prev;
}

//----
// Undocumented
// Applies php_strip_whitespace() to a string

public static function _stripString($str)
{
if (getenv('PHK_NO_STRIP')!==false) return $str;

$save=self::setTmpData($str);
$res=php_strip_whitespace(self::TMP_URI);
self::setTmpData($save);
return $res;
}

//----
// Undocumented
// Include a string as if it was in a source file

public static function _includeString($str)
{
$save=self::setTmpData($str);
$res=require(self::TMP_URI);
self::setTmpData($save);
return $res;
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
