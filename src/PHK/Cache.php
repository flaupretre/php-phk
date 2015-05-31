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

namespace PHK {

if (!class_exists('PHK\Cache',false))
{

//=============================================================================
/**
* The PHK cache gateway
*
* The cache key is based on the mount point because it uniquely defines the
* PHK file on the current host.
*
* API status: Public
* Included in the PHK PHP runtime: Yes
* Implemented in the extension: Yes
*///==========================================================================

class Cache	// Static only
{
const TTL=3600;	// Arbitrary TTL of one hour

//-- Global static properties

private static $caches=array("apc","xcache","eaccelerator");

private static $cacheName;	// Info

/** @var Object|false|null	The cache system we are using for PHK instances
*
* False means 'no cache'.
* null until set by setCacheObject().
*/

private static $cache=null;

private static $cache_maxsize=524288;	// 512 Kb

//-----

public static function cacheID($prefix,$key)
{
return 'phk.'.$prefix.'.'.$key;
}

//---------------------------------
// If the cache's init() method returns an exception, don't use it.

private static function setCacheObject()
{
if (is_null(self::$cache))
	{
	self::$cache=false;
	self::$cacheName='none';
	foreach(self::$caches as $c)
		{
		if (!extension_loaded($c)) continue;
		
		$class='Cache_'.$c;
		$obj=new $class;
		try { $status=$obj->init(); }
		catch (\Exception $e) { $status=false; }
		if ($status)
			{
			self::$cache=$obj;
			self::$cacheName=$c;
			break;
			}
		unset($obj);
		}
	\PHK\Tools\Util::trace("Cache system used : ".self::$cacheName);//TRACE
	}
}

//---------------------------------

public static function setCacheMaxSize($size)
{
$this->cache_maxsize=$size;
}

//---------------------------------

public static function cacheName()
{
if (is_null(self::$cache)) self::setCacheObject();

return self::$cacheName;
}

//---------------------------------

public static function cachePresent()
{
if (is_null(self::$cache)) self::setCacheObject();

return (self::$cache!==false);
}
//---------------------------------
/**
* Gets an element from cache
*
* Fast path
*
* @param string $id		Cache key
*
* @return string|null The data. Null if not found
*/

public static function get($id)
{
if (is_null(self::$cache)) self::setCacheObject();

if (self::$cache===false) return null;

$result=self::$cache->get($id);
if ($result===false) $result=null;

return $result;
}

//---------------------------------
/**
* Writes an element to cache
*
* @param string $id		Cache key
* @param string $data	Data to cache
*
* @return void
*/

public static function set($id,$data)
{
if (is_null(self::$cache)) self::setCacheObject();

if (is_object(self::$cache))
	{
	if (is_string($data) && (strlen($data) > self::$cache_maxsize)) return;

	\PHK\Tools\Util::trace("Writing cache: id=$id");//TRACE
	self::$cache->set($id,$data);
	}
}

//---------------------------------
} // End of class \PHK\Cache
//=============================================================================

abstract class CacheBase
{
// Returns true if this system can be used. \Exception if unavailable

abstract public function init();

// Return data or null

abstract public function get($id);

// Return void

abstract public function set($id,$data);
}

//=============================================================================

class Cache_apc extends CacheBase
{

public function init()
{
// Valid only in a web environment or if CLI is explicitely enabled

return \PHK\Tools\Util::envIsWeb() || ini_get('apc.enable_cli');
}

//------

public function get($id)
{
return apc_fetch($id);
}

//------

public function set($id,$data)
{
apc_store($id,$data,\PHK\Cache::TTL);
}

//---------------------------------
} // End of class \PHK\Cache_apc
//=============================================================================

class Cache_xcache extends CacheBase
{

public function init()
{
return \PHK\Tools\Util::envIsWeb(); // Valid only in a web environment
}

//------

public function get($id)
{
return xcache_get($id);
}

//------

public function set($id,$data)
{
xcache_set($id,$data,\PHK\Cache::TTL);
}

//---------------------------------
} // End of class \PHK\Cache_xcache
//=============================================================================

class Cache_eaccelerator extends CacheBase
{

public function init()
{
// eaccelerator must be compiled with shared memory functions 
// (configured with --with-eaccelerator-shared-memory)

if (!function_exists('eaccelerator_get')) return false;

return \PHK\Tools\Util::envIsWeb(); // Valid only in a web environment
}

//------

public function get($id)
{
return eaccelerator_get($id);
}

//------

public function set($id,$data)
{
eaccelerator_put($id,$data,\PHK\Cache::TTL);
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
