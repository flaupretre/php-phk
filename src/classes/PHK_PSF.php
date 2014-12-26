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
* The PHK_PSF class
*
* @copyright Francois Laupretre <phk@tekwire.net>
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, V 2.0
* @category PHK
* @package PHK
*/
//============================================================================

class PHK_PSF 
{
private $path; // PSF absolute path

private $vars;	// Variables

private $line_nb;

//---------

private function __construct($path,$vars)
{
$this->path=$path;
$this->vars=$vars;
}

//---- Exception with file name and line number
// Can be called recursively. So, we decorate the message only once

private function send_error($msg)
{
if (($msg!='') && ($msg{0}=='>')) throw new Exception($msg);
else throw new Exception('> '.$this->path.'(line '.$this->line_nb.') : '.$msg);
}

//---- Read a line with continuation '\' and strip comments (#) + trim()
//---- Returns false on EOF

private function get_line($fp)
{
$line='';

while (true)
	{
	$this->line_nb++;
	if (($line1=fgets($fp))===false) // EOF
		{
		if ($line=='') return null;
		else $this->send_error('Unexpected EOF'); // If it was a continuation
		}
	if (($pos=strpos($line1,'#'))!==false)	// Remove comments
		$line1=substr($line1,0,$pos);
	$line1=trim($line1);
	if ($line1=='') continue;

	if ($line1[strlen($line1)-1]=='\\')	// Continued on next line
		{
		$cont=true;
		$line1=substr($line1,0,-1);	// Remove \ at EOL
		$line .=' '; // As we trim every line, add a space for continuation
		}
	else $cont=false;
	$line .= $line1;
	if ($cont) continue;
	//-- Variables substitution

	while (($pos=strpos($line,'$('))!==false) // always search the whole line
		{
		$pos2=strpos($line,')',$pos);
		if ($pos2==($pos+2)) $this->send_error('Empty variable');
		if ($pos2===false) $this->send_error('No variable end');
		$var=substr($line,$pos+2,$pos2-($pos+2));
		$val=$this->get_var($var);
		$line=substr_replace($line,$val,$pos,$pos2+1-$pos);
		}

	//-- Convert tabs to space, Remove leading/trailing blanks, and suppress
	//-- multiple spaces

	$line=preg_replace('/\s+/',' ',str_replace('	',' ',trim($line)));

	if ($line!='') break;	// Skip empty lines
	}
return $line;
}

//---------

private function get_var($name)
{
if (isset($this->vars[$name])) return $this->vars[$name];
if (($val=getenv($name))!==false) return $val;
$this->send_error($name.': reference to undefined variable');
}

//---------

private function set_var($name,$value)
{
$this->vars[$name]=$value;
}

//---------
// On entry, $phk is a PHK_Creator object

public function apply_to($phk)
{
if (!($phk instanceof PHK_Creator))
	throw new Exception('Object must be PHK_Creator');

if (!($fp=fopen($this->path,'rb',false)))
	throw new Exception($this->path.': Cannot open');
	
$this->line_nb=0;

try {
while (!is_null($line=$this->get_line($fp)))
	{
	if ($line{0}==='%') break;	// Next block found
	$op=new PHK_PSF_Cmd_Options;
	$words=explode(' ',$line);
	if (!count($words)) throw new Exception('No command');
	$command=strtolower(array_shift($words));
	$op->parse_all($words);
	switch($command)
		{
		case 'add':	// add [-t <target-path>] [-d <target-base>] [-C <dir>]
					//   [-c <compression-scheme>] <source1> [<source2>...]
			
			if (count($words)==0)
				throw new Exception('Usage: add [options] <path1> [<path2> ...]');
			$base_dir=PHO_File::combine_path(dirname($this->path)
				,$op->option('directory'));
			foreach($words as $spath)
				{
				$spath=rtrim($spath,'/');	// Beware of trailing '/'
				if (PHO_File::is_absolute_path($spath))
					throw new Exception("$spath: Arg must be a relative path");
				$sapath=PHO_File::combine_path($base_dir,$spath);
				if (is_null($target=$op->option('target-path')))
					{
					$tbase=$op->option('target-base');
					if (is_null($tbase)) $tbase='';
					$target=$tbase.'/'.$spath;
					}
				$phk->ftree()->merge_into_file_tree($target,$sapath,$op->options());
				}
			break;

		case 'modify':
			foreach($words as $tpath)
				$phk->ftree()->modify($tpath,$op->options());
			break;

		case 'mount':
			if (count($words)!=2)
				throw new Exception('Usage: mount <phk-path> <var-name>');
			list($path,$mnt_var)=$words;
			$mnt=PHK_Mgr::mount($path,PHK::NO_MOUNT_SCRIPT);
			$this->set_var($mnt_var,'phk://'.$mnt);
			break;

		case 'remove':	// remove <path> [<path>...]
			if (count($words)==0)
				throw new Exception('Usage: remove <path1> [<path2> ...]');
			foreach($words as $tpath) $phk->ftree()->remove(trim($tpath,'/'));
			break;

		case 'set':
			if (count($words)!=2)
				throw new Exception('Usage: set <var-name> <value>');
			$var=array_shift($words);
			$this->set_var($var,implode(' ',$words));
			break;

		case 'section':		//-- Undocumented
			if (count($words)!=2)
				throw new Exception('Usage: section [-C <dir>] <name> <path>');
			list($name,$path)=$words;
			$phk->add_section($name,PHO_File::readfile($path));
			break;
			
		default:
			$this->send_error($command.': Unknown command');
		}
	}
} catch (Exception $e) { $this->send_error($e->getMessage()); }

if (!is_null($line)) // If we met a '%'
	{
	// Get package options (metainfo)
	// Default syntax: YAML

	$op=new PHK_PSF_Options_Options;

	$args=explode(' ',$line);
	$op->parse_all($args);

	$data='';
	while (($line=fgets($fp))!==false) $data .= $line;

	switch($op->option('syntax'))
		{
		case 'yaml':
			$save=PHK_Stream_Backend::set_tmp_data($data);
			$options=sfYaml::load(PHK_Stream_Backend::TMP_URI);
			PHK_Stream_Backend::set_tmp_data($save);
			break;

		case 'php':
			$options=PHK_Stream_Backend::_include_string("<?php\n".$data."\n?>");
			break;

		default:
			throw new Exception("$syntax: Unknown options syntax");
		}

	if (!(is_array($options)))
		throw new Exception('Options block should define an array');
	$phk->set_options($options);
	}

fclose($fp);
}

//---------
// Build a new PHK package from a PSF file

public static function build($phk_path,$psf_path,$vars)
{
//-- Create empty output object

$phk_path=PHO_File::mk_absolute_path($phk_path);
$mnt=PHK_Mgr::mount($phk_path,PHK::IS_CREATOR);
$phk=PHK_Mgr::instance($mnt);

if (is_null($psf_path)) // Compute PSF path from PHK path
	{
	$base=basename($phk_path);
	$dotpos=strrpos($base,'.');
	if ($dotpos===false) $base=$base.'.psf';
	else $base=substr_replace($base,'.psf',$dotpos);
	$psf_path=dirname($phk_path).'/'.$base;
	}
else	// Make PSF path absolute
	{
	$psf_path=PHO_File::mk_absolute_path($psf_path);
	}

//-- Interpret PSF

$psf=new PHK_PSF($psf_path,$vars);
$psf->apply_to($phk);

//-- Dump to file

$phk->dump();
}

//---------
} // End of class PHK_PSF
//============================================================================
?>
