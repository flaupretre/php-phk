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

namespace {

if (!class_exists('PHK',false))
{
//=============================================================================
/**
* This class is just an empty extension of the PHK\Base class. This is done
* this way so that PHK\Build\Creator uses the PHP code even if the extension
* is present.
*
* @see PHK\Base
*
* API status: Public
* Included in the PHK PHP runtime: Yes
* Implemented in the extension: Yes
*///==========================================================================

class PHK extends \PHK\Base
{

//---------------
// If we get here, the PHP runtime is already loaded,but the method must exist.

public static function needPhpRuntime()
{
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
