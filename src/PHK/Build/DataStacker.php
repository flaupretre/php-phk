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

//=============================================================================
/**
* A utility class used only at package creation time.
*
* This class maintains a string buffer and appends strings to it, returning
* the current offset. When every strings have been appended, returns the
* resulting buffer.
*///===========================================================================

namespace PHK\Build {

if (!class_exists('PHK\Build\DataStacker',false))
{
//============================================================================

class DataStacker
{
public $offset;
public $data;

//---------

public function __construct()
{
$this->offset=0;
$this->data='';
}

//---------

public function push($data)
{
$this->data .= $data;
$ret_offset=$this->offset;
$this->offset += strlen($data);
return $ret_offset;
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
