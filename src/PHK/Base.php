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

if (!class_exists('PHK\Base',false))
{
//=============================================================================
/**
* A mounted PHK archive file
*
* This file contains the front-end class for PHK object. This class gets its
* data from the stream wrapper, which retrieves them from the cache or from
* PHK\Proxy. This class is also used as base class for PHK\Build\Creator instances.
*
* This class does never access the package file directly.
*
* Note: When dealing with a sub-package, the mounted archive file is a 'phk://'
* virtual file contained in a higher-level PHK archive (which, itself, can be
* virtual). There is no limit to the nesting level of sub-packages.
*
* Runtime code -> 100% read-only
*
* PHK objects are created and destructed from the PHK manager (PHK\Mgr class).
*
* You get the PHK object instance by calling \PHK\Mgr::instance with a mount
* point. This mount point is generally returned either by an include,
* when including a package, or by \PHK\Mgr::uriToMnt().
*
* @see \PHK\Mgr
* @see \PHK\Proxy
*
* API status: Public
* Included in the PHK PHP runtime: Yes
* Implemented in the extension: Yes. Not used when extension is active.
*///==========================================================================
*/

abstract class Base
{
//========== Class constants ===============

/** Class version */

const VERSION='3.0.0';

//-----
// Mount flags
/* The values defined here must be the same as in the accelerator extension */
/* PHK mount flags must not conflict with \Automap\Mgr::T_xx load flags as they can be */
/* combined. */

/** Mount flag - If set, force a CRC check when creating the PHK instance */

const CRC_CHECK=16;

/** Mount flag - If set, don't call mount/umount scripts */

const NO_MOUNT_SCRIPT=32;

/** Mount flag - If set, create a \PHK\Build\Creator object, instead of a PHK object */

const IS_CREATOR=64;

//========== Class properties ===============

/** @var bool		Whether instance is valid or not (unmounted) */

private $valid;

/** @var string		Current mount point */

protected $mnt;

/** @var string		Parent mount point (for a subpackage) or null */

protected $parentMnt;

/** @var array		Package options */

protected $options=null;	// Array

/** @var array		Build-time information */

protected $buildInfo=null;

/** @var integer	Mount flags */

protected $flags;

/** @var string|null	Automap load ID (if a map is present) */

protected $automapID;

/** @var integer	Package path (URI when subpackage) */

protected $path;

/** @var Object|null	Plugin object, if defined */

protected $plugin=null;

/** @var boolean|null Allows to temporarily enable/disable caching */

protected $caching=null;

/** @var int The modification time for every subfiles */

protected $mtime;

/** @var \PHK\Backend The slow backend object (created only when needed) */

protected $backend=null;

/** @var array	File extension to mime type (constant)
*
* Would be cleaner if PHP class constants could contain arrays */

protected static $mimeTable=array(
	''     => 'text/plain',
	'gif'  => 'image/gif',
	'jpeg' => 'image/jpeg',
	'jpg'  => 'image/jpeg',
	'png'  => 'image/png',
	'psd'  => 'image/psd',
	'bmp'  => 'image/bmp',
	'tif'  => 'image/tiff',
	'tiff' => 'image/tiff',
	'iff'  => 'image/iff',
	'wbmp' => 'image/vnd.wap.wbmp',
	'ico'  => 'image/x-icon',
	'xbm'  => 'image/xbm',
	'txt'  => 'text/plain',
	'htm'  => 'text/html',
	'html' => 'text/html',
	'css'  => 'text/css',
	'php'  => 'application/x-httpd-php',
	'phk'  => 'application/x-httpd-php',
	'inc'  => 'application/x-httpd-php',
	'hh'   => 'application/x-httpd-php',
	'pdf'  => 'application/pdf',
	'js'   => 'application/x-javascript',
	'swf'  => 'application/x-shockwave-flash',
	'xml'  => 'application/xml',
	'xsl'  => 'application/xml',
	'xslt' => 'application/xslt+xml',
	'mp3'  => 'audio/mpeg',
	'ram'  => 'audio/x-pn-realaudio',
	'svg'  => 'image/svg+xml'
	);

//========== Class methods ===============

// Methods to get read-only properties

public function mnt()       { $this->validate(); return $this->mnt; }
public function flags()     { $this->validate(); return $this->flags; }
public function path()      { $this->validate(); return $this->path; }
public function mtime()     { $this->validate(); return $this->mtime; }
public function automapID() { $this->validate(); return $this->automapID; }
public function options()   { $this->validate(); return $this->options; }
public function parentMnt() { $this->validate(); return $this->parentMnt; }
public function plugin()    { $this->validate(); return $this->plugin; }

//-----

public function __construct($parentMnt,$mnt,$path,$flags,$mtime)
{
$this->valid=true;
$this->parentMnt=$parentMnt;
$this->mnt=$mnt;
$this->path=$path;
$this->flags=$flags;
$this->mtime=$mtime;
}

//-----

public function validate()
{
if (!$this->valid)
	throw new \Exception("Accessing invalid or unmounted object");
}

//-----

public function init($options,$buildInfo)
{
try
{
$this->options=$options;
$this->buildInfo=$buildInfo;

$this->supportsPhpVersion();

if ($this->option('crc_check') || ($this->flags & self::CRC_CHECK))
	$this->crcCheck();

// As required extensions are added to the enclosing package when a subpackage
// is inserted, we don't have to check subpackages for required extensions.

if (is_null($this->parentMnt))
	{
	if (!is_null($extensions=$this->option('required_extensions')))
		\PHK\Tools\Util::loadExtensions($extensions);
	}

if ($this->mapDefined())
	{
	// Transmit PHK mount flags to Automap
	$this->automapID=\Automap\Mgr::load($this->automapURI()
		,$this->flags,$this->baseURI());
	}
else $this->automapID=0;

//-- Call the mount script - if the mount script wants to refuse the mount,
//-- it throws an exception.

if (!($this->flags & \PHK::NO_MOUNT_SCRIPT)
	&& (!is_null($mpath=$this->option('mount_script'))))
		{ require $this->uri($mpath); }

//-- Create the plugin_object

if (!is_null($c=$this->option('plugin_class')))
	$this->plugin=new $c($this->mnt);
}
catch (\Exception $e)
	{
	throw new \Exception('While initializing PHK instance - '.$e->getMessage());
	}
}

//---------

public function mapDefined()
{
$this->validate();

if ($this->flags & \PHK::IS_CREATOR) return false;

return $this->buildInfo('map_defined');
}

//---------

public function setCache($toggle)
{
$this->validate();

$this->caching=$toggle;
}

//---------
/**
* Check if a given path contains a PHK package
*
* @param string $path	path to check (can be virtual)
* @return boolean
*/

public static function fileIsPackage($path)
{
return \PHK\Proxy::fileIsPackage($path);
}

//---------
/**
* Check if a data buffer contains a PHK package
*
* @param string $data	data buffer to check
* @return boolean
*/

public static function dataIsPackage($data)
{
return \PHK\Proxy::dataIsPackage($data);
}

//-----

public function cacheEnabled($command,$params,$path)
{
$this->validate();

if ($this->flags & \PHK::IS_CREATOR) return false;

if ($this->option('no_cache')===true) return false;

if (!\PHK\Cache::cachePresent()) return false;

if (!is_null($this->caching)) return $this->caching;

return true;
}

//-----
// Umount this entry.
// We dont use __destruct because :
//	1. We don't want this to be called on script shutdown
//	2. Exceptions cannot be caught when sent from a destructor.

public function umount()
{
$this->validate();

//-- Destroy the plugin

if (!is_null($this->plugin)) unset($this->plugin);

//-- Call the umount script

if (!($this->flags & \PHK::NO_MOUNT_SCRIPT))	// Call the umount script
	{
	if (!is_null($upath=$this->option('umount_script')))
		{ require($this->uri($upath)); }
	}

//-- Unload the automap

if ($this->automapID) \Automap\Mgr::unload($this->automapID);

$this->valid=false;
}

//-----

public function uri($path)
{
$this->validate();
return \PHK\Mgr::uri($this->mnt,$path);
}

//-----

public function sectionURI($section)
{
$this->validate();
return \PHK\Mgr::sectionURI($this->mnt,$section);
}

//-----

public function commandURI($command)
{
$this->validate();
return \PHK\Mgr::commandURI($this->mnt,$command);
}

//-----

public function baseURI()
{
$this->validate();
return \PHK\Mgr::baseURI($this->mnt);
}

//-----
/**
* Returns the URI of the map
*
* @return string
*/

public function automapURI()
{
$this->validate();
return \PHK\Mgr::automapURI($this->mnt);
}

//-----
/**
* Returns an option
*
* If the option is not set, returns null.
*
* The 'OPTIONS' section is mandatory in a package.
*
* @param string $key The option name
* @return any|null Option value or null if the requested option is not set
*/


public function option($key)
{
$this->validate();
return (isset($this->options[$key]) ? $this->options[$key] : null);
}

//---------------------------------

public function webAccessAllowed($path)
{
$this->validate();
$plen=strlen($path);

foreach(\PHK\Tools\Util::mkArray($this->option('web_access')) as $apath)
	{
	if ($apath=='/') return true;
	$alen=strlen($apath);
	if (($plen >= $alen) && (substr($path,0,$alen)==$apath)
		&& (($alen==$plen)||($path{$alen}=='/')))
		return true;
	}

return false;
}

//---------------------------------
// Transfer control to main script (web mode)
// Two methods: redirect or transparently execute main script.

private function gotoMain($web_run_script)
{
if ($this->option('web_main_redirect'))
	{
	\PHK\Tools\Util::http301Redirect($web_run_script);
	}
else return 'require(\''.$this->uri($web_run_script).'\');';
}

//---------------------------------
// Returns the code to display or execute a subfile from the calling code. We
// cannot directly include the subfile from this function because the variable
// scope must be the calling one.
// Use as : eval($phk->webTunnel([$path [,webinfo mode]]));
// This function is supposed to transfer control in as transparent a manner as
// possible.
// If the given path is a directory, tries to find an index.[htm|html|php] file.
// This function does not support subpaths in PHK subfiles.

public function webTunnel($path=null,$webinfo=false)
{
$this->validate();
if (is_null($path)) $path=\PHK::setSubpath();
$last_slash=(substr($path,-1)=='/');
if ($path!='/') $path=rtrim($path,'/');
$web_run_script=$this->option('web_run_script');
$mnt=$this->mnt();

if ($path=='')
	{
	if (!is_null($web_run_script)) return $this->gotoMain($web_run_script); 
	else \PHK\Tools\Util::http301Redirect('/'); // Redirect to the virtual root dir
	}

// If a package use a path as both file and http paths, we can receive
// a PHK URI. Handle this. Ugly: to be suppressed when PHP makes
// current directory compatible with stream wrappers.
// We check for one or two '/' between 'phk:' and $mnt because Apache removes
// the 2nd '/'.
// Suppressed in v 1.4.0: don't know if still useful ?

//$path=str_replace('phk:/'.$mnt.'/','',$path);
//$path=str_replace('phk://'.$mnt.'/','',$path);

// Access enabled ? If not in the enabled paths, go to the main script
// Allows to support a mod_rewrite-like feature where a single entry point
// gets every request.

if ((!$webinfo) && (!$this->webAccessAllowed($path))
	&& ($path!==$web_run_script))
	{
	if (!is_null($web_run_script)) return $this->gotoMain($web_run_script); 
	else \PHK\Tools\Util::http403Fail();	// Returns 'Forbidden'
	}

// File exists ?

$uri=$this->uri($path);

if (($a=@stat($uri))===false) \PHK\Tools\Util::http404Fail();

if (($a['mode'] & 0170000) == 040000)	// Special case for directory
	{
	$file_path=null;
	if ($last_slash)	// Search a DirectoryIndex
		{
		foreach(array('index.htm', 'index.html', 'index.php') as $fname)
			{
			if (is_file($this->uri($path.'/'.$fname)))
				{
				$file_path=$path.'/'.$fname;
				break;
				}
			}
		if (is_null($file_path)) \PHK\Tools\Util::http404Fail(); // No Directory Index
		}
	else \PHK\Tools\Util::http301Redirect($path.'/');
	}
else $file_path=$path;

// Now, we return the string which will be used by the calling environment
// to execute the file if it is a PHP source, or to output its content
// with the correct mime type. Execution is disabled in webinfo mode

if ((!$webinfo) && ($this->isPHPSourcePath($file_path)))
	{
	return "require('".$this->uri($file_path)."');";
	}
else
	{
	return "\PHK\Mgr::instance('".$this->mnt."')->mimeHeader('$file_path');\n"
		."readfile('".$this->uri($file_path)."');";
	}
}

//---------------------------------
/**
* Sends a mime header corresponding to a path
*
* Actually, we use only the file suffix (the path can correspond to an existing
*	node or not).
*
* If the suffix does not correspond to anything we know, nothing is sent
*	(defaults to text/html on Apache, don't know if it can change on another
*	SAPI).
*
* @param string $path
* @return void
*/

public function mimeHeader($path)
{
$this->validate();
if (!is_null($type=$this->mimeType($path))) header('Content-type: '.$type);
}

//---------
/**
* Returns the mime-type corresponding to a given path, or null if the
* suffix does not correspond to anything we know
*
* Searches :
*
* 1. The 'mime-types' option
* 2. The built-in mime table
* 3. If the suffix contains 'php', sets the type to 'application/x-httpd-php'
*
* @param string $path
* @return string|null The mime type or null if file suffix is unknown
*/

public function mimeType($path)
{
$this->validate();
$ext=\PHK\Tools\Util::fileSuffix($path);

if ((!is_null($mtab=$this->option('mime_types'))) && isset($mtab[$ext]))
	return $mtab[$ext];

if (isset(self::$mimeTable[$ext])) return self::$mimeTable[$ext];

if (strpos($ext,'php')!==false)	return 'application/x-httpd-php';

return null;
}

//---------
/**
* Should we consider this path as a PHP source file ?
*
* In order to be identified as PHP source, a path must be associated with Mime
* type 'application/x-httpd-php'.
*
* @param string $path
* @return boolean
*/

public function isPHPSourcePath($path)
{
$this->validate();
return ($this->mimeType($path)==='application/x-httpd-php');
}

//---------

public function proxy()
{
$this->validate();
return \PHK\Mgr::proxy($this->mnt);
}

//---------
/**
* Checks the CRC of the PHK archive file
*
* Generates an exception if the check fails
*
* @return void
* @throws \Exception
*/

public function crcCheck()
{
$this->validate();
$this->proxy()->crcCheck();
}

//---------

private function supportsPhpVersion()
{
$this->validate();
if ((!is_null($minv=$this->option('min_php_version')))
	&& (version_compare(PHP_VERSION,$minv) < 0))
		throw new \Exception("PHP minimum supported version: $minv (current is ".PHP_VERSION.")");

if ((!is_null($maxv=$this->option('max_php_version')))
	&& (version_compare(PHP_VERSION,$maxv) > 0))
		throw new \Exception("PHP maximum supported version: $maxv (current is ".PHP_VERSION.")");
}

//-----
/**
* Is the PHK accelerator in use or not ?
*
* @return boolean
*/

public static function acceleratorIsPresent()
{
return false;
}

//-----
/**
* Returns a build-time information field or the whole array
*
* Unlike options, an unknown key throws an error
*
* @param string|null $name Field name
* @return array|string|null The field's content or null if it does not exist
*/

public function buildInfo($name=null)
{
$this->validate();
if (is_null($name)) return $this->buildInfo;

if (!isset($this->buildInfo[$name]))
	throw new \Exception($name.': unknown build info');

return $this->buildInfo[$name];
}

//---------------------------------

public static function subpathURL($path)
{
return \PHK\Backend::subpathURL($path);
}

//---------------------------------
// Get the sub-path from an URL. Because of the problems with CGI mode, we
// have to support 2 syntaxes :
// http://<site>/.../<phk_file><path>
// http://<site>/.../<phk_file>?_phk_path=<path>

public static function setSubpath()
{
$path='';

if (isset($_REQUEST['_phk_path'])) $path=urldecode($_REQUEST['_phk_path']);
else
	{
	$path=isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	if ($path=='' && isset($_SERVER['ORIG_PATH_INFO']))
		$path=$_SERVER['ORIG_PATH_INFO'];
	}

if (($path!='') && ($path{0}!='/')) $path='/'.$path;

return $path;
}

//----
// Undocumented

private function backend()
{
$this->validate();
if (is_null($this->backend)) $this->backend=new \PHK\Backend($this);

return $this->backend;
}

//--------------
// Forward unknown method calls to the slow backend

public function __call($method,$args)
{
$this->validate();
return \PHK\Tools\Util::callMethod($this->backend(),$method,$args);
}

//---------

public static function prolog($file,&$cmd,&$ret)
{
# Do we run in CLI mode ?

if ($cli=(!\PHK\Tools\Util::envIsWeb()))
	{
	ini_set('display_errors',true);
	ini_set('memory_limit','1024M'); // Only in CLI mode
	}

\PHK\Mgr::checkPhpVersion();	//-- Check PHP version - if unsupported, no return

//-----
// Mount the PHK file (or get the mount point if previously mounted)

$mnt=\PHK\Mgr::mount($file);
$phk=\PHK\Mgr::instance($mnt);

//\PHK\Tools\Util::trace("Prolog mounted $file on $mnt");//TRACE

//-----
// Am I a main script ?
// When there are symbolic links in the path, get_included_files() returns
// 2 paths, the logical one first, and then the real one.

$tmp=get_included_files();
$main=(($tmp[0]===$file) || (realpath($tmp[0]) === $file));

if (!$main)	// Not main script
	{
	if (!is_null($script=$phk->option('lib_run_script')))
		{ require($phk->uri($script)); }

	if ($phk->option('auto_umount'))
		{
		\PHK\Mgr::umount($mnt);
		$ret='';
		}
	else $ret=$mnt;
	return;
	}

//-----------------
// Main script - Dispatch

if ($cli)
	{
	if (($_SERVER['argc']>1) && ($_SERVER['argv'][1]!='')
		&& ($_SERVER['argv'][1]{0}=='@'))
		{
		$ret=$phk->builtinProlog($file);
		return;
		}

	// Not a command: call cli_run

	if (!is_null($run_path=$phk->option('cli_run_script')))
		{
		$cmd="\$_phk_ret=require('".$phk->uri($run_path)."');";
		}
	return;
	}
else	// HTTP mode
	{
	if (file_exists($file.'.webinfo'))	// Slow path
		{
		\PHK\Tools\Util::runWebInfo($phk);
		}
	else
		{
		$cmd=$phk->webTunnel();
		}
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
