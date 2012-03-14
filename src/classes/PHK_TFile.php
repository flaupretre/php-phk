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
* The PHK_TFile class
*
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*/
//=============================================================================

if (!class_exists('PHK_TFile',false))
{
//============================================================================

class PHK_TFile extends PHK_TNode // Regular file
{

private $dc;	// Data container

//---

public function type() { return 'file'; }
public function mode() { return 0100444; }

//---
// If a method is unknown, forward to the DC (poor man's multiple inheritance :)

public function __call($method,$args)
{
try { return call_user_func_array(array($this->dc,$method),$args); }
catch (Exception $e)
	{ throw new Exception($this->path.': '.$e->getMessage()); }
}

//---
// Must be defined as __call() is tried after PHK_TNode methods, and read()
// has a default in PHK_TNode.

public function read()
{
return $this->dc->read();
}

//---

public function flag_string()
{
$string=parent::flag_string().','.$this->dc->flag_string();
$string=trim($string,',');

return $string;
}

//---

public function display_package($html)
{
if ($this->flags & self::TN_PKG) $this->display($html);
}

//---
// In HTML, create an hyperlink only for files, not for sections

public function display($html,$link=false)
{
$flag_string=$this->flag_string();
$path=$this->path;

if ($html)
	{
	if ($this->flags & self::TN_PKG) $link=false;
	$field= ($link ? '<a href="'.PHK::subpath_url('/view/'
		.trim($path,'/')).'">'.$path.'</a>' : $path);
	echo '<tr><td nowrap>F</td><td nowrap>'.$field.'</td><td nowrap>'
		.$this->size().'</td><td nowrap>'.$flag_string.'</td></tr>';
	}
else
	{
	if ($flag_string!='') $flag_string = ' ('.$flag_string.')';
	echo 'F '.str_pad($this->size(),11).' '.$path.$flag_string."\n";
	}
}

//---

public function dump($base)
{
$path=$base.$this->path;
if (file_put_contents($path,$this->read())===false)
	throw new Exception($path.': cannot dump file');
}

//---

public function __construct($path,$tree)
{
parent::__construct($path,$tree);

$this->dc=new PHK_DC();
$this->dc->set_fspace($tree->fspace);
}

//---

public function import($edata)
{
$this->dc->import(parent::import($edata));
}

//---

public function set_flags($flags)
{
parent::set_flags($flags);
$this->dc->set_flags($flags);
}

//---
// <CREATOR> //---------------

// If PHK Package, move required extensions up
// Don't umount the package, it will be used later.

public function get_needed_extensions(PHK_Creator $phk
	,PHK_ItemLister $item_lister)
{
if (PHK::data_is_package($this->read()))
	{
	$mnt=require($phk->uri($this->path));
	$source_phk=PHK_Mgr::instance($mnt);
	if (!is_null($elist=$source_phk->option('required_extensions')))
		{
		foreach ($elist as $ext) $item_lister->add($ext,true);
		}
	
	}

return $this->dc->get_needed_extensions($phk,$item_lister);	// Now, ask DC
}

//---------------
// Process PHP scripts (Automap and source stripping)
// Register Automap symbols only if $map is non-null
// Distinction between files and sections: for files, $map is not null

public function export(PHK_Creator $phk,PHK_DataStacker $stacker,$map)
{
$path=$this->path;

PHK_Util::msg('Processing '.$path);

if (!is_null($map)) // This is a real file
	{
	if (getenv('PHK_NO_STRIP')!==false)	$this->flags &= ~self::TN_STRIP_SOURCE;

	if (PHK::data_is_package($this->read()))	//-- Package ?
		{
		//-- Set 'package' flag, clear 'strip source', and keep autoload
		$this->flags |= self::TN_PKG;
		$this->flags &= ~self::TN_STRIP_SOURCE;
		}

	//-- If it is a sub-package and autoload is true, merge its symbols
	//-- in the current map, but with different values.

	if ($this->flags & self::TN_PKG)
		{
		if (!($this->flags & self::TN_NO_AUTOLOAD))
			{
			PHK_Util::msg("	Merging Automap symbols");
			$map->register_package($phk->uri($path),$path);
			}
		}
	elseif ($phk->is_php_source_path($path))
		{
		//--- Register in automap
		if (!($this->flags & self::TN_NO_AUTOLOAD))
			{
			PHK_Util::msg("	Registering Automap symbols");
			$map->register_script($phk->uri($path),$path);
			}

		//--- Strip script files

		if ($this->flags & self::TN_STRIP_SOURCE)
			{
			PHK_Util::msg("	Stripping script");

			// We must save the Automap comments from php_strip_whitespace()
			// because we must be able to rebuild the Automap (when upgrading
			// the package, for instance).
			// If there are Automap comments in the file, we put them in a PHP
			// block after the stripped script.

			$comment_buf='';
			foreach(file($phk->uri($path)) as $line)
				{
				$line=trim($line);
				if (!preg_match(Automap_Creator::AUTOMAP_COMMENT,$line,$regs))
					continue;
				// Warning: keep '//' and '<Automap> separate below or it will be
				// detected as an Automap comment when processing this file.
				$comment_buf .= '//'.' <Automap>:'.$regs[1].$regs[2]."\n";
				}
			// Keep '<?'.'php' or it will be translated when building the
			// runtime code
			if ($comment_buf!='') $comment_buf="<?"."php\n".$comment_buf."?".">";
			$this->set_data(php_strip_whitespace($phk->uri($path)).$comment_buf);
			}
		}
	else
		{
		$this->flags = ($this->flags & ~self::TN_STRIP_SOURCE) 
			| self::TN_NO_AUTOLOAD;
		}
	}

return $this->tnode_export($this->dc->export($phk,$stacker));
}

// </CREATOR> //---------------

} // End of class PHK_TFile
//-------------------------
} // End of class_exists('PHK_TFile')
//=============================================================================
?>
