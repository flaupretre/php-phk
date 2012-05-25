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

// <PHK:ignore>
require(dirname(__FILE__).'/external/YAML/lib/sfYaml.php');
// <PHK:end>

//============================================================================

class PHK_PSF 
{
private $filename;
private $line_nb;
private $variables;

const VERSION='0.2.0';

//---------

public function __construct($psf,$args)
{
$this->filename=$psf;
$this->variables=array();

$this->set_variable('PSF_DIR',dirname($psf));

$n=1;
foreach($args as $arg) $this->set_variable($n++,$arg);
}

//---- Exception with file name and line number
// Can be called recursively. So, we decorate the message only once

private function send_error($msg)
{
if (($msg!='') && ($msg{0}=='>')) throw new Exception($msg);
else throw new Exception('> '.$this->filename.'(line '.$this->line_nb.') : '.$msg);
}

//---- Read a line with continuation '\' and strip comments (#) + trim()
//---- Returns false on EOF

private function get_line()
{
$line='';

while (true)
	{
	$this->line_nb++;
	if (($line1=fgets($this->fp))===false) // EOF
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
		$val=$this->get_variable($var);
		$line=substr_replace($line,$val,$pos,$pos2+1-$pos);
		}

	//-- Remove leading/trailing blanks

	$line=str_replace('	',' ',trim($line));	// Replaces tabs with spaces

	if ($line!='') break;	// Skip empty lines
	}
return $line;
}

//---------

private function set_variable($name,$value)
{
$this->variables[$name]=$value;
}

//---------

private function get_variable($name)
{
if (isset($this->variables[$name])) return $this->variables[$name];
if (($val=getenv($name))!==false) return $val;
$this->send_error($name.': reference to undefined variable');
}

//---------

private function get_word(&$line,$error_flag=true)
{
if (($line=trim($line))=='')
	{
	if ($error_flag) $this->send_error('Short line');
	else return null;
	}
$pos=strpos($line,' ');
if ($pos==false)
	{
	$word=$line;
	$line='';
	}
else
	{
	$word=substr($line,0,$pos);
	$line=ltrim(substr($line,$pos));
	}

//-- Globbing

if ((strpos($word,'*')!==false) || (strpos($word,'?')!==false))
	{
	$g=@glob($word);
	if ($g===false) return $word;
	if (count($g)) $line=implode(' ',$g).' '.$line;
	return $this->get_word($line,$error_flag);
	}

return $word;
}

//---------

public function get_modifiers(&$line)
{
$modifiers=array();

while (!is_null($word=$this->get_word($line,false)))
	{
	if (($word!='') && ($word{0}!='-')) break; // If it is not an option
	if ($word=='-') continue;
	if (count($a=explode('=',strtolower(substr($word,1)),2))!==2)
		throw new Exception($word.': Wrong modifier format');
	$modifiers[$a[0]]=$a[1];
	}

if ($word) $line=$word.' '.$line; // unget word
return $modifiers;
}

//---------
// Globbing will be implemented in a future version...
// On entry, $phk is a PHK_Creator object

public function apply_to($phk)
{
if (!($phk instanceof PHK_Creator))
	throw new Exception('Object must be PHK_Creator');

if (!($this->fp=fopen($this->filename,'rb',false)))
	throw new Exception($this->filename.': Cannot open');
	
$this->line_nb=0;

try {
while (!is_null($line=$this->get_line()))
	{
	$command=strtolower($this->get_word($line));
	if ($command{0}==='%') break;
	switch($command)
		{
		case 'add':	// add <target> <source> [<source>...]
			$modifiers=$this->get_modifiers($line);
			// Beware of trailing '/'
			if (($target_spec=rtrim($this->get_word($line),'/'))=='')
				throw new Exception('Cannot replace root dir');

			while (!is_null($source=$this->get_word($line,false)))
				{
				$target=str_replace('&',basename($source),$target_spec);
				$phk->ftree()->add_file_tree($target,$source,$modifiers);
				}
			break;

		case 'merge':	// merge <target (dir)> <source-dir> [<source_dir>...]
			$modifiers=$this->get_modifiers($line);
			$target_dir_spec=rtrim($this->get_word($line));

			while (!is_null($source_dir=$this->get_word($line,false)))
				{
				$target_dir=str_replace('&',basename($source_dir)
					,$target_dir_spec);
				$phk->ftree()->merge_file_tree($target_dir,$source_dir,$modifiers);
				}
			break;

		case 'modify':
			$modifiers=$this->get_modifiers($line);
			while (!is_null($path=$this->get_word($line,false)))
				$phk->ftree()->modify($path,$modifiers);
			break;

		case 'mount':
			$mnt_var=$this->get_word($line);
			$file=$this->get_word($line);
			$mnt=PHK_Mgr::mount($file,PHK::F_NO_MOUNT_SCRIPT | PHK::F_CHECK_CRC);
			$this->set_variable($mnt_var,$mnt);
			break;

		case 'remove':	// remove <path> [<path>...]
			while ($path=trim($this->get_word($line,false),'/'))
				{
				$phk->ftree()->remove($path);
				}
			break;

		case 'import':	// import <phk-path>
			$phk->import_phk($this->get_word($line));
			break;

		case 'set':
			$var=$this->get_word($line);
			$this->set_variable($var,$line);
			break;

		case 'section':		//-- Undocumented
			$name=$this->get_word($line);
			$file=$this->get_word($line);

			$phk->add_section($name,PHK_Util::readfile($file));
			break;
			
		case 'prolog':	//-- Undocumented - maybe useless
			$phk->set_prolog(PHK_Util::readfile($this->get_word($line)));
			break;

		default:
			$this->send_error($command.': Unknown command');
		}
	}
} catch (Exception $e) { $this->send_error($e->getMessage()); }

if (!is_null($line)) // If we met a '%'
	{
	$data='';
	while (($line=fgets($this->fp))!==false) $data .= $line;

	$save=PHK_Stream_Backend::set_tmp_data($data);
	$a=sfYaml::load(PHK_Stream_Backend::TMP_URI);
	PHK_Stream_Backend::set_tmp_data($save);

	if (!(is_array($a)))
		throw new Exception('Options block should return an array');
	$phk->set_options($a);
	}

fclose($this->fp);
}

//---------
// Build a new PHK package from a PSF file

public static function build($phk_file,$psf_file,$args)
{
//-- Create empty output object

$mnt=PHK_Mgr::mount($phk_file,PHK::F_CREATOR);
$phk=PHK_Mgr::instance($mnt);

//-- Run PSF file

$psf=new PHK_PSF($psf_file,$args);
$psf->apply_to($phk);

//-- Dump to file

$phk->dump();
}

//---------
} // End of class PHK_PSF
//============================================================================
?>
