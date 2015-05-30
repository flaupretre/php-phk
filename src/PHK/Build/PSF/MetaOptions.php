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

namespace PHK\Build\PSF {

if (!class_exists('PHK\Build\PSF\MetaOptions',false))
{

//=============================================================================
/**
* This class manages options on the %options line of a PSF
*
* API status: Private
* Included in the PHK PHP runtime: No
* Implemented in the extension: No
*///==========================================================================

class MetaOptions extends \Phool\Options\Base
{

// Short/long modifier args

protected $opt_modifiers=array(
	array('short' => 's', 'long' => 'syntax', 'value' => true)
	);

// Option values

protected $options=array(
	'syntax' => 'yaml'
	);

//-----------------------

protected function processOption($opt,$arg)
{
switch($opt)
	{
	case 's':
		$this->options['syntax']=strtolower($arg);
		break;
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
