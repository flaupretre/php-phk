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

if (!class_exists('PHK\Virtual\Dir',false))
{
//============================================================================
/**
* Virtual directory
*
* API status: Private
* Included in the PHK PHP runtime: Yes
* Implemented in the extension: No
*///==========================================================================

class Dir extends Node
{

private $children; // array of basenames

//---

public function type() { return 'dir'; }
public function mode() { return 040555; }
public function size() { return count($this->children); }
public function getNeededExtensions() {}

//---

public function display($html,$link)
{
$path=$this->path;
if ($path=='') $path='/';

if ($html) echo '<tr><td nowrap colspan=4>&nbsp;<b><i>'.$path
		.'</i></b></td></tr>';
else echo "D      $path\n";
}

//---

public function getDir()
{
return $this->children;
}

//---

public function dump($base)
{
$path=$base.$this->path;
if (mkdir($path)===false) throw new \Exception($path.': cannot create directory');
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

// <Prolog:creator> //---------------

public function addChild($name)
{
if (array_search($name,$this->children)===false) $this->children[]=$name;
}

//---

public function removeChild($name)
{
if (($key=array_search($name,$this->children))===false)
	unset ($this->children[$key]);
}

//---

public function export(\PHK\Build\Creator $phk,\PHK\Build\DataStacker $stacker,$map)
{
return $this->nodeExport(implode(';',$this->children));
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
