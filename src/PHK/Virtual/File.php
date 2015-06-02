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

if (!class_exists('PHK\Virtual\File',false))
{
//=============================================================================
/**
* A virtual file
*
* API status: Private
* Included in the PHK PHP runtime: Yes
* Implemented in the extension: No
*///==========================================================================

class File extends Node // Regular file
{

private $dc;	// Data container

//---

public function type() { return 'file'; }
public function mode() { return 0100444; }

//---
// If a method is unknown, forward to the DC (poor man's multiple inheritance :)

public function __call($method,$args)
{
try { return call_user_func_array(array($this->dc,$method),$args); }
catch (\Exception $e)
	{ throw new \Exception($this->path.': '.$e->getMessage()); }
}

//---
// Must be defined as __call() is tried after \PHK\Virtual\Node methods, and read()
// has a default in \PHK\Virtual\Node.

public function read()
{
return $this->dc->read();
}

//---

public function flagString()
{
$string=parent::flagString().','.$this->dc->flagString();
$string=trim($string,',');

return $string;
}

//---

public function displayPackage($html)
{
if ($this->flags & self::TN_PKG) $this->display($html);
}

//---
// In HTML, create an hyperlink only for files, not for sections

public function display($html,$link=false)
{
$flagString=$this->flagString();
$path=$this->path;

if ($html)
	{
	if ($this->flags & self::TN_PKG) $link=false;
	$field= ($link ? '<a href="'.\PHK::subpathURL('/view/'
		.trim($path,'/')).'">'.$path.'</a>' : $path);
	echo '<tr><td nowrap>F</td><td nowrap>'.$field.'</td><td nowrap>'
		.$this->size().'</td><td nowrap>'.$flagString.'</td></tr>';
	}
else
	{
	if ($flagString!='') $flagString = ' ('.$flagString.')';
	echo 'F '.str_pad($this->size(),11).' '.$path.$flagString."\n";
	}
}

//---

public function dump($base)
{
$path=$base.$this->path;
if (file_put_contents($path,$this->read())===false)
	throw new \Exception($path.': cannot dump file');
}

//---

public function __construct($path,$tree)
{
parent::__construct($path,$tree);

$this->dc=new DC();
$this->dc->setFspace($tree->fspace);
}

//---

public function import($edata)
{
$this->dc->import(parent::import($edata));
}

//---

public function setFlags($flags)
{
parent::setFlags($flags);
$this->dc->setFlags($flags);
}

//---
// <CREATOR> //---------------

// If PHK Package, move required extensions up
// Don't umount the package, it will be used later.

public function getNeededExtensions(\PHK\Build\Creator $phk
	,\PHK\Tools\ItemLister $item_lister)
{
if (\PHK::dataIsPackage($this->read()))
	{
	$mnt=require($phk->uri($this->path));
	$source_phk=\PHK\Mgr::instance($mnt);
	if (!is_null($elist=$source_phk->option('required_extensions')))
		{
		foreach ($elist as $ext) $item_lister->add($ext,true);
		}
	
	}

return $this->dc->getNeededExtensions($phk,$item_lister);	// Now, ask DC
}

//---------------
// Process PHP scripts (Automap and source stripping)
// Register Automap symbols only if $map is non-null
// Distinction between files and sections: for files, $map is not null

public function export(\PHK\Build\Creator $phk,\PHK\Build\DataStacker $stacker,$map)
{
$path=$this->path;
$rpath=substr($path,1); // Remove leading '/';

\PHK\Tools\Util::trace('Processing '.$path);

if (!is_null($map)) // This is a real file
	{
	if (getenv('PHK_NO_STRIP')!==false)	$this->flags &= ~self::TN_STRIP_SOURCE;

	if (\PHK::dataIsPackage($this->read()))	//-- Package ?
		{
		//-- Set 'package' flag, clear 'strip source', and keep autoload
		$this->flags |= self::TN_PKG;
		$this->flags &= ~self::TN_STRIP_SOURCE;
		}

	//-- If it is a sub-package and autoload is true, merge its symbols
	//-- in the current map, but with different values.

	if ($this->flags & self::TN_PKG)
		{
		if (!($this->flags & self::TN_NO_AUTOLOAD))
			{
			\PHK\Tools\Util::trace("Registering Automap symbols from PHK package");
			$map->registerPhkPkg($phk->uri($path),$rpath);
			}
		}
	elseif ($phk->isPHPSourcePath($path))
		{
		//--- Register in automap
		if (!($this->flags & self::TN_NO_AUTOLOAD))
			{
			\PHK\Tools\Util::trace("	Registering Automap symbols");
			$map->registerScriptFile($phk->uri($path),$rpath);
			}

		$this->processPHPScript();	//--- Script pre-processor
		}
	else
		{
		$this->flags = ($this->flags & ~self::TN_STRIP_SOURCE) 
			| self::TN_NO_AUTOLOAD;
		}
	}

return $this->nodeExport($this->dc->export($phk,$stacker));
}

//---------------
// Called only for PHP scripts. Replaces current data buffer

const ST_OUT=0;
const ST_ADD=1;
const ST_IGNORE=2;

private function processPHPScript()
{
$buf=$this->read();

//--- Normalize line breaks (Unix-style)

$buf=preg_replace("/\r+\n/","\n",$buf);
$buf=str_replace("\r","\n",$buf);

//--- Process '// <PHK:' directives

$rbuf=$buf;
$buf='';
$lnum=0;
$state=self::ST_OUT;
$regs=null;
foreach(explode("\n",$rbuf) as $line)
	{
	$lnum++;
	if (preg_match(',^\s*//\s*<PHK:(\S+)>,',$line,$regs))
		{
		$keyword=strtolower($regs[1]);
		switch ($keyword)
			{
			case 'end':
				$state=self::ST_OUT;
				break;
			case 'ignore':
				$state=self::ST_IGNORE;
				break;
			case 'add':
				$state=self::ST_ADD;
				break;
			default:
				throw new \Exception($this->path."($lnum): Unknown preprocessor keyword: $keyword - valid keywords are ignore, add, end - ignoring line");
			}
		continue;
		}
	if ($state==self::ST_IGNORE) continue;
	if ($state==self::ST_ADD)
		{
		$line=ltrim($line);
		if (strlen($line)!=0) // Ignore empty lines
			{
			if ((strlen($line)>=2)&&(substr($line,0,2)==='//'))
				$line=substr($line,2);
			else throw new \Exception($this->path."($lnum): in an 'add' block, line should start with '//' - copying line as-is");
			}
		}
	$buf .= $line."\n";
	}
$buf=substr($buf,0,-1);

//--- Strip

if ($this->flags & self::TN_STRIP_SOURCE)
	{
	// \PHK\Tools\Util::msg("	Stripping script");
	$buf=self::stripWhitespaces($buf);
	}

$this->setData($buf);
}

//---------------

/**
* Removes whitespace from a PHP source string while preserving line numbers.
*
* Taken from composer
*
* @param  string $source A PHP string
* @return string The PHP string with the whitespace removed
*/

public static function stripWhitespaces($source)
{
$output = '';

foreach (token_get_all($source) as $token)
	{
	if (is_string($token))
		{
		$output .= $token;
		}
	elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT)))
		{
		$output .= str_repeat("\n", substr_count($token[1], "\n"));
		}
	elseif (T_WHITESPACE === $token[0])
		{
		// reduce wide spaces
		$whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
		// normalize newlines to \n
		$whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
		// trim leading spaces
		$whitespace = preg_replace('{\n +}', "\n", $whitespace);
		$output .= $whitespace;
		}
	else
		{
		$output .= $token[1];
		}
	}

return $output;
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
