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

if (!class_exists('PHK\Virtual\Tree',false))
{
//============================================================================
/**
* A virtual tree
*
* A virtual tree contains virtual nodes (files and directories)
*
* API status: Private
* Included in the PHK PHP runtime: Yes
* Implemented in the extension: No
*///==========================================================================

class Tree
{

public $fspace;		// Associated filespace

private $edata; // Exported data. Always contains a key for every node, even
				// in creator mode.

private $nodes;	// Tree nodes (key=path, value=Node object). Contains
				// only the unserialized nodes. In creator mode, contains
				// every node.

private static $char_to_class=array( 'D' => 'Dir', 'F' => 'File');

//---
// Create a tree from the edata stored in the PHK archive

public static function createFromEdata($serial_edata,\PHK\PkgFileSpace $fspace)
{
$tree=new self($fspace);

$tree->edata=unserialize($serial_edata);

return $tree;
}

//---

public function pathList()
{
return array_keys($this->edata);
}

//---

public function pathExists($rpath)
{
return array_key_exists($rpath,$this->edata);
}

//---

public function count()
{
return count($this->edata);
}

//---

public function walk($method)
{
$args=func_get_args();
array_shift($args);

foreach($this->pathList() as $path)
	{
	$node=$this->rlookup($path);
	call_user_func_array(array($node,$method),$args);
	}
}

//---
// Reduce the path to a canonical path - suppress '..' and '.' components
// Root=''
// Non-root: /xxx[/yyy...]

private function realpath($path)
{
$a=explode('/',trim($path,'/'));
$ra=array();
foreach($a as $comp)
	{
	switch($comp)
		{
		case '':
		case '.':
			break;
		case '..':
			if (count($ra)) array_pop($ra);
			break;
		default:
			$ra[]=$comp;
		}
	}
if (!count($ra)) return '';
return '/'.implode('/',$ra);
}

//---

public function lookup($path,$exception_flag=true)
{
return $this->rlookup(self::realpath($path),$exception_flag);
}

//---
// Lookup without path canonicalization - faster if self::realpath() has
// already been called

private function rlookup($path,$exception_flag=true)
{
if (array_key_exists($path,$this->edata))
	{
	if (!array_key_exists($path,$this->nodes))
		{
		$edata=$this->edata[$path];
		$class=__NAMESPACE__.'\\'.self::$char_to_class[$edata{0}];
		$node=$this->nodes[$path]=new $class($path,$this);
		$node->import(substr($edata,1));
		}
	return $this->nodes[$path];
	}

//echo "Lookup failed : <$path> <$rpath>\n";//TRACE
//print_r(array_keys($this->nodes));//TRACE
	
if ($exception_flag) throw new \Exception($path.': path not found');
else return null;
}

//---

public function lookupFile($path,$exception_flag=true)
{
$f=$this->lookup($path,$exception_flag);

if ((!is_null($f)) && (!($f instanceof File)))
	{
	if ($exception_flag) throw new \Exception($path.': No such file');
	else return null;
	}

return $f;
}

//---

public function displayHeader($html)
{
if ($html) echo '<table border=1 bordercolor="#BBBBBB" cellpadding=3 '
	.'cellspacing=0 style="border-collapse: collapse"><tr><th>T</th>'
	.'<th>Name</th><th>Size</th><th>Flags</th></tr>';
}

//---

public function displayFooter($html)
{
if ($html) echo '</table>';
}

//---
// $link = wether we display an hyperlink on file names (in HTML mode)

public function display($link)
{
$html=\PHK\Tools\Util::envIsWeb();

$this->displayHeader($html);
$this->walk('display',$html,$link);
$this->displayFooter($html);
}

//---

public function displayPackages()
{
$html=\PHK\Tools\Util::envIsWeb();

ob_start();
$this->walk('displayPackage',$html);
$data=ob_get_clean();

if ($data!=='')
	{
	$this->displayHeader($html);
	$this->walk('displayPackage',$html);
	$this->displayFooter($html);
	}
}

//---

public function dump($base)
{
$this->walk('dump',$base);
}

//---
// Same as dirname() function except:
// - Always use '/' as separator
// - Returns '' for 1st level paths ('/xxx')

public static function dirBaseName($path)
{
$dir=preg_replace(',/[^/]*$,','',$path);
$base=preg_replace(',^.*/,','',$path);
return array($dir,$base);
}

//---
// called from createEmpty() or createFromEdata() only => private

private function __construct($fspace)
{
$this->fspace=$fspace;
$this->nodes=array();
}

// <Prolog:creator> //---------------

// Check for a list of forbidden chars in node names. Especially important for
// '#*' which can create conflicts in mount points (for subpackages), and ';'
// which is used as separator when exporting the list of dir children.

public function addNode($path,$node)
{
$path=self::realpath($path);

if (strpbrk($path,'#*?!&~"|`\^@[]={}$;,<>')!==false)
	throw new \Exception("$path: Invalid characters in path");

if ($path != '')
	{
	list($dir,$basename)=self::dirBaseName($path);

	$dirnode=$this->rlookup($dir,false);
	if (is_null($dirnode)) $dirnode=$this->mkdir($dir);

	if (!($dirnode instanceof Dir))
		throw new \Exception("Cannot add node over a non-directory node ($dir)");

	$dirnode->addChild($basename);
	}

// Add the node

$this->edata[$path]=null;
$this->nodes[$path]=$node;
}

//---
// Create an empty tree

public static function createEmpty()
{
$tree=new self(null);
$tree->addNode('',new Dir('',$tree));

return $tree;
}

//---

public function export(\PHK\Build\Creator $phk,$map=null)
{
$edata=array();
$stacker=new \PHK\Build\DataStacker();

foreach($this->nodes as $path => $node)
	{
	$edata[$path]=array_search(substr(get_class($node),strlen(__NAMESPACE__)+1),self::$char_to_class)
		.$node->export($phk,$stacker,$map);
	}
ksort($edata); // To display files in the right order

return array(serialize($edata),$stacker->data);
}

//---
// target: absolute target path. '&' is replaced by source basename
// sapath: Absolute source path
// modifiers: array received from \PHK\Build\PSF\CmdOptions

public function mergeIntoFileTree($target,$sapath,$modifiers)
{
if (!file_exists($sapath))
	throw new \Exception($sapath.': Path not found');

$target=self::realpath(str_replace('&',basename($sapath),$target));

switch($type=filetype($sapath))
	{
	case 'file':
		if ($target=='') throw new \Exception('Cannot replace root dir with a file');
		$this->remove($target);
		$this->mkfile($target,\PHK\Tools\Util::readFile($sapath),$modifiers);
		break;

	case 'dir':
		foreach(\PHK\Tools\Util::scandir($sapath) as $subname)
			{
			$this->mergeIntoFileTree($target.'/'.$subname,$sapath.'/'.$subname
				,$modifiers);
			}
		break;

	default:
		\Phool\Display::info("$sapath : Unsupported file type ($type) - Ignored");
	}
}

//---

private function getSubtree($path)
{
$rpath=self::realpath($path);

if ($rpath=='') return $this->pathList();

$result=array();
$prefix=$rpath.'/';
$len=strlen($prefix);
foreach($this->pathList() as $p)
	{
	if (($p==$rpath)||((strlen($p)>=$len)&&(substr($p,0,$len)==$prefix)))
		$result[]=$p;
	}
return $result;
}

//---

public function modify($path,$modifiers)
{
$path=self::realpath($path);

foreach ($this->getSubtree($path) as $subpath)
	{
	$this->lookup($subpath)->modify($modifiers);
	}
}

//---
// If parent dir does not exist, addNode() will call us back to create it,
// and it goes on recursively until the root node is reached.

public function mkdir($path,$modifiers=array())
{
$rpath=self::realpath($path);

if (is_null($node=$this->rlookup($rpath,false))) // If node does not exist
	{
	$node=new Dir($path,$this);
	$node->modify($modifiers);
	$this->addNode($path,$node);
	}
else // If node already exists, check that it is a directory
	{
	if (($type=$node->type())!='dir')
		throw new \Exception("mkdir: $path is already a $type");
	}
return $node;
}

//---

public function mkfile($path,$data,$modifiers=array())
{
$rpath=self::realpath($path);

$node=new File($rpath,$this);
$node->setData($data);
$node->modify($modifiers);

$this->addNode($rpath,$node);

return $node;
}

//---

public function remove($path)
{
$rpath=self::realpath($path);
if ($rpath=='') throw new \Exception('Cannot remove root directory');

if (is_null($this->rlookup($rpath,false))) return; // Path does not exist

foreach($this->getSubtree($rpath) as $p)
	{
	unset($this->nodes[$p]);
	unset($this->edata[$p]);
	}

list($dir,$name)=self::dirBaseName($rpath);
$this->rlookup($dir)->removeChild($name);
}

// </Prolog:creator> //---------------

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
