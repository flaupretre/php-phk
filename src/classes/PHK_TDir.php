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
* The PHK_TDir class
*
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*/
//=============================================================================

if (!class_exists('PHK_TDir',false))
{
//============================================================================

class PHK_TDir extends PHK_TNode
{

private $children; // array of basenames

//---

public function type() { return 'dir'; }
public function mode() { return 040555; }
public function size() { return count($this->children); }
public function get_needed_extensions() {}

//---

public function display($html,$link)
{
$path=$this->path;
if ($path=='') $path='/';

if ($html) echo '<tr><td nowrap colspan=4>&nbsp;<b><i>'.$path
		.'</i></b></td></tr>';
else echo "D $path\n";
}

//---

public function getdir()
{
return $this->children;
}

//---

public function dump($base)
{
$path=$base.$this->path;
if (mkdir($path)===false) throw new Exception($path.': cannot create directory');
}

//---

public function import($edata)
{
$this->children=explode(';',parent::import($edata));
}

//---

public function __construct($path,$tree)
{
parent::__construct($path,$tree);

$this->children=array();
}

//---

public function subpath($name)
{
return $this->path.'/'.$name;
}

// <CREATOR> //---------------

public function add_child($name)
{
if (array_search($name,$this->children)===false) $this->children[]=$name;
}

//---

public function remove_child($name)
{
if (($key=array_search($name,$this->children))===false)
	unset ($this->children[$key]);
}

//---

public function export(PHK_Creator $phk,PHK_DataStacker $stacker,$map)
{
return $this->tnode_export(implode(';',$this->children));
}

// </CREATOR> //---------------

} // End of class PHK_TDir
//-------------------------
} // End of class_exists('PHK_TDir')
//=============================================================================
?>
