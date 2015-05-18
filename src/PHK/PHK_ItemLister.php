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
* A utility class used only at package creation time.
*
* This class maintains an array and appends elements to it, eliminating
* duplicate keys. When every elements have been appended, returns the resulting
* array.
*
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*/
//=============================================================================

namespace {

if (!class_exists('PHK_ItemLister',false))
{
//============================================================================

class PHK_ItemLister
{
private $a;

//---------

public function __construct()
{
$this->a=array();
}

//---------

public function add($item,$value)
{
$this->a[$item]=$value;
}

//---------

public function get()
{
return $this->a;
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
