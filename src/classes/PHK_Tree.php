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
* The PHK_Tree class
*
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*/
//============================================================================

if (!class_exists('PHK_Tree',false))
{
// <PLAIN_FILE> //---------------
require(dirname(__FILE__).'/PHK.php');
require(dirname(__FILE__).'/PHK_DC.php');
require(dirname(__FILE__).'/PHK_DataStacker.php');
require(dirname(__FILE__).'/PHK_TNode.php');
require(dirname(__FILE__).'/PHK_TDir.php');
require(dirname(__FILE__).'/PHK_TFile.php');
// </PLAIN_FILE> //---------------

//============================================================================

class PHK_Tree
{

public $fspace;		// Associated filespace

private $edata; // Exported data. Always contains a key for every node, even
				// in creator mode.

private $nodes;	// Tree nodes (key=path, value=PHK_TNode object). Contains
				// only the unserialized nodes. In creator mode, contains
				// every node.

private static $eclasses=array( 'D' => 'PHK_TDir', 'F' => 'PHK_TFile');

//---
// Create a tree from the edata stored in the PHK archive

public static function create_from_edata($serial_edata
	,PHK_FileSpace $fspace)
{
$tree=new self($fspace);

$tree->edata=unserialize($serial_edata);

return $tree;
}

//---

public function path_list()
{
return array_keys($this->edata);
}

//---

public function path_exists($rpath)
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

foreach($this->path_list() as $path)
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
		$class=self::$eclasses[$edata{0}];
		$node=$this->nodes[$path]=new $class($path,$this);
		$node->import(substr($edata,1));
		}
	return $this->nodes[$path];
	}

//echo "Lookup failed : <$path> <$rpath>\n";//TRACE
//print_r(array_keys($this->nodes));//TRACE
	
if ($exception_flag) throw new Exception($path.': path not found');
else return null;
}

//---

public function lookup_file($path,$exception_flag=true)
{
$f=$this->lookup($path,$exception_flag);

if ((!is_null($f)) && (!($f instanceof PHK_TFile)))
	{
	if ($exception_flag) throw new Exception($path.': No such file');
	else return null;
	}

return $f;
}

//---

public function display_header($html)
{
if ($html) echo '<table border=1 bordercolor="#BBBBBB" cellpadding=3 '
	.'cellspacing=0 style="border-collapse: collapse"><tr><th>T</th>'
	.'<th>Name</th><th>Size</th><th>Flags</th></tr>';
}

//---

public function display_footer($html)
{
if ($html) echo '</table>';
}

//---
// $link = wether we display an hyperlink on file names (in HTML mode)

public function display($link)
{
$html=PHK_Util::env_is_web();

$this->display_header($html);
$this->walk('display',$html,$link);
$this->display_footer($html);
}

//---

public function display_packages()
{
$html=PHK_Util::env_is_web();

ob_start();
$this->walk('display_package',$html);
$data=ob_get_clean();

if ($data!=='')
	{
	$this->display_header($html);
	$this->walk('display_package',$html);
	$this->display_footer($html);
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

public static function dir_base_name($path)
{
$dir=preg_replace(',/[^/]*$,','',$path);
$base=preg_replace(',^.*/,','',$path);
return array($dir,$base);
}

//---
// called from create_empty() or create_from_edata() only => private

private function __construct($fspace)
{
$this->fspace=$fspace;
$this->nodes=array();
}

// <CREATOR> //---------------

// Check for a list of forbidden chars in node names. Especially important for
// '#*' which can create conflicts in mount points (for subpackages), and ';'
// which is used as separator when exporting the list of dir children.

public function add_node($path,$node)
{
$path=self::realpath($path);

if (strpbrk($path,'#*?!&~"|`\^@[]={}$;,<>')!==false)
	throw new Exception("$path: Invalid characters in path");

if ($path != '')
	{
	list($dir,$basename)=self::dir_base_name($path);

	$dirnode=$this->rlookup($dir,false);
	if (is_null($dirnode)) $dirnode=$this->mkdir($dir);

	if (!($dirnode instanceof PHK_TDir))
		throw new Exception("Cannot add node over a non-directory node ($dir)");

	$dirnode->add_child($basename);
	}

// Add the node

$this->edata[$path]=null;
$this->nodes[$path]=$node;
}

//---
// Create an empty tree

public static function create_empty()
{
$tree=new self(null);
$tree->add_node('',new PHK_TDir('',$tree));

return $tree;
}

//---

public function export(PHK_Creator $phk,$map=null)
{
$edata=array();
$stacker=new PHK_DataStacker();

foreach($this->nodes as $path => $node)
	{
	$edata[$path]=array_search(get_class($node),self::$eclasses)
		.$node->export($phk,$stacker,$map);
	}
ksort($edata); // To display files in the right order

return array(serialize($edata),$stacker->data);
}

//---

public function add_file_tree($target_path,$source,$modifiers)
{
$target_path=self::realpath($target_path);
$this->remove($target_path);

// Don't use filetype() here because we want to follow symbolic links

if (!file_exists($source))
	{
	echo "$source : File does not exist - Ignored";
	}
elseif (is_file($source))
	{
	$this->mkfile($target_path,PHK_Util::readfile($source),$modifiers);
	}
elseif (is_dir($source))
	{
	$node=$this->mkdir($target_path,$modifiers);

	foreach(PHK_Util::scandir($source) as $subname)
		$this->add_file_tree($node->subpath($subname)
			,$source.DIRECTORY_SEPARATOR.$subname,$modifiers);
	return;
	}
else echo "$source : Unsupported file type (".filetype($source).") - Ignored\n";
}

//---

public function merge_file_tree($target_dir,$source_dir,$modifiers)
{
if (!is_dir($source_dir))
	throw new Exception($source_dir.': Should be an existing directory');

$target_dir=self::realpath($target_dir);
$tnode=$this->mkdir($target_dir,$modifiers);

foreach(PHK_Util::scandir($source_dir) as $subname)
	{
	$source=$source_dir.DIRECTORY_SEPARATOR.$subname;
	$target=$tnode->subpath($subname);
	if (is_file($source))
		{
		$this->mkfile($target,PHK_Util::readfile($source),$modifiers);
		}
	elseif (is_dir($source))
		{
		$this->merge_file_tree($target,$source,$modifiers);
		}
	else echo "$source : Unsupported file type (".filetype($source).") - Ignored\n";
	}
}

//---

private function get_subtree($path)
{
$rpath=self::realpath($path);

if ($rpath=='') return $this->path_list();

$result=array();
$prefix=$rpath.'/';
$len=strlen($prefix);
foreach($this->path_list() as $p)
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

foreach ($this->get_subtree($path) as $subpath)
	{
	$this->lookup($subpath)->modify($modifiers);
	}
}

//---
// If parent dir does not exist, add_node() will call us back to create it,
// and it goes on recursively until the root node is reached.

public function mkdir($path,$modifiers=array())
{
$rpath=self::realpath($path);

if (is_null($node=$this->rlookup($rpath,false))) // If node does not exist
	{
	$node=new PHK_TDir($path,$this);
	$node->modify($modifiers);
	$this->add_node($path,$node);
	}
else // If node already exists, check that it is a directory
	{
	if (($type=$node->type())!='dir')
		throw new Exception("mkdir: $path is already a $type");
	}
return $node;
}

//---

public function mkfile($path,$data,$modifiers=array())
{
$rpath=self::realpath($path);

$node=new PHK_TFile($rpath,$this);
$node->set_data($data);
$node->modify($modifiers);

$this->add_node($rpath,$node);

return $node;
}

//---

public function remove($path)
{
$rpath=self::realpath($path);
if ($rpath=='') throw new Exception('Cannot remove root directory');

if (is_null($this->rlookup($rpath,false))) return; // Path does not exist

foreach($this->get_subtree($rpath) as $p)
	{
	unset($this->nodes[$p]);
	unset($this->edata[$p]);
	}

list($dir,$name)=self::dir_base_name($rpath);
$this->rlookup($dir)->remove_child($name);
}

// </CREATOR> //---------------

} // End of class PHK_Tree
//-------------------------
} // End of class_exists('PHK_Tree')
//=============================================================================
?>
