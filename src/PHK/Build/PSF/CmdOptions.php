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

if (!class_exists('PHK\Build\PSF\CmdOptions',false))
{

//=============================================================================
/**
* This class manages options for lines in the first block of a PSF
*
* API status: Private
* Included in the PHK PHP runtime: No
* Implemented in the extension: No
*///==========================================================================

class CmdOptions extends \Phool\Options\Base
{

// Short/long modifier args

protected $opt_modifiers=array(
	array('short' => 'a', 'long' => 'autoload'  , 'value' => false),
	array('short' => 'n', 'long' => 'no-autoload'  , 'value' => false),
	array('short' => 's', 'long' => 'strip'  , 'value' => false),
	array('short' => 'p', 'long' => 'plain'  , 'value' => false),
	array('short' => 'c', 'long' => 'compression'  , 'value' => true),
	array('short' => 't', 'long' => 'target-path'  , 'value' => true),
	array('short' => 'b', 'long' => 'target-base'  , 'value' => true),
	array('short' => 'C', 'long' => 'directory'  , 'value' => true)
	);

// Option values

protected $options=array(
	'autoload' => null,
	'strip' => null,
	'compression' => null,
	'target-path' => null,
	'target-base' => null,
	'directory' => null
	);

//-----------------------

protected function processOption($opt,$arg)
{
switch($opt)
	{
	case 'a':
		$this->options['autoload']=true;
		break;

	case 'n':
		$this->options['autoload']=false;
		break;

	case 's':
		$this->options['strip']=true;
		break;

	case 'p':
		$this->options['strip']=false;
		break;

	case 'c':
		$this->options['compression']=strtolower($arg);
		break;

	case 't':
		$this->options['target-path']=$arg;
		break;

	case 'b':
		$this->options['target-base']=$arg;
		break;

	case 'C':
		$this->options['directory']=$arg;
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
