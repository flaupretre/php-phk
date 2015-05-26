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
* This class manages options for \PHK\CLI\Cmd
*
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*/
//============================================================================

namespace PHK\CLI {

if (!class_exists('PHK\CLI\Options',false))
{
class Options extends \Phool\Options\Base
{

// Short/long modifier args

protected $opt_modifiers=array(
	array('short' => 'v', 'long' => 'verbose', 'value' => false),
	array('short' => 'q', 'long' => 'quiet'  , 'value' => false),
	array('short' => 's', 'long' => 'source'  , 'value' => true),
	array('short' => 'd', 'long' => 'define'  , 'value' => true)
	);

// Option values

protected $options=array(
	'psf_path' => null,
	'vars' => array()
	);


//-----------------------

protected function process_option($opt,$arg)
{
switch($opt)
	{
	case 'v':
		\Phool\Display::inc_verbose();
		break;

	case 'q':
		\Phool\Display::dec_verbose();
		break;

	case 's':
		$this->options['psf_path']=$arg;
		break;

	case 'd':
		$a=explode('=',$arg,2);
		if ((count($a)!=2)||($a[0]===''))
			throw new \Exception("Invalid variable definition ($arg)");
		$this->options['vars'][$a[0]]=$a[1];
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
