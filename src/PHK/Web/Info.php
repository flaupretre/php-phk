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

namespace PHK\Web {

if (!class_exists('PHK\Web\Info',false))
{
//============================================================================
/**
* Web info
*
* This class handles the 'webinfo' mode
*
* API status: Private
* Included in the PHK PHP runtime: Yes
* Implemented in the extension: No
*///==========================================================================

class Info
{
private $PHK;	// Associated PHK instance

private $cmd_titles=array(
	'info' => 'Home',
	'techinfo' => 'Technical information',
	'showmap' => 'Symbol map',
	'showfiles' => 'Files',
	'test' => 'Unit tests');

//----

public function __construct($phk)
{
$this->PHK=$phk;

\PHK\Mgr::setCache(false); // Don't cache anything in webinfo mode
}

//----

private static function displayTab($url,$name)
{
echo '<li id="'.$name.'"><a href="'.\PHK::subpathURL($url)
	.'"><span>'.$name.'</span></a></li>';
}

//----

private function header($title=null)
{
if (is_null($name=$this->PHK->option('name')))
	$name=basename($this->PHK->path());
$win_title=(is_null($title) ? $name : "$name - $title");

echo '<head>'
	."<title>$win_title</title>"
	.'<link href="'.\PHK::subpathURL('/php_section/STATIC/tabs/tabs.css.php')
	.'" rel="stylesheet" type="text/css">'
	."<style type=text/css><!--\n"
	."a,a:active,a:link { color: blue; text-decoration: none; }\n"
	."a:hover { color: blue; text-decoration: underline; }\n"
	.'--></style>'
	."</head>\n";

echo '<table width=100% border=0 cellpadding=0 cellspacing=0>';

//-- Tabs

echo '<tr><td><div class="tabs"><ul>';

self::displayTab('/info','Home');

if (!is_null($this->PHK->option('help_prefix')))
	self::displayTab('/autoOption/help','Help');

if (!is_null($this->PHK->option('license_prefix')))
	self::displayTab('/autoOption/license','License');

self::displayTab('/techinfo','Info');
self::displayTab('/showfiles','Files');

if ($this->PHK->mapDefined())
	self::displayTab('/showmap','Symbol map');

if ((!is_null($this->PHK->option('test_script')))
	||(!is_null($this->PHK->option('phpunit_test_package'))))
	self::displayTab('/test','Tests');

//-- Package specific tabs

if (!is_null($tabs=$this->PHK->option('tabs')))
	foreach($tabs as $n => $url) self::displayTab($url,$n);

echo '</ul></div></td></tr>';

//--

$bg_string=(is_null($opt=$this->PHK->option('icon_bgcolor'))
	? '' : 'bgcolor="'.$opt.'"');

if (is_null($icon_width=$this->PHK->option('icon_width'))) $icon_width='150';
 
echo '<tr><td width=100%><table width=100% border=1 bordercolor="#aaaaaa"'
	.' cellpadding=3 cellspacing=0>';
echo "<tr><td width=$icon_width $bg_string align=center>";

$url=$this->PHK->option('url');
if (!is_null($url)) echo '<a href="'.$url.'" target=_blank>';
if (!is_null($icon_path=$this->PHK->option('icon')))
	echo '<img border=0 src="'.\PHK::subpathURL('/file/'.trim($icon_path,'/'))
		.'" alt="Package Home">';
elseif (!is_null($url)) echo '&lt;Website&gt;';
if (!is_null($url)) echo '</a>';
echo "</td>\n";

echo '<td bgcolor="#D7E2FF" align=center><h1>'.$name.'</h1></td>';

echo '<td width=151 align=center><a href="http://phk.tekwire.net"'
	.' target=_blank><img width=151 height=88 border=0 src="'
	.\PHK::subpathURL('/section/STATIC/phk_logo.png')
	.'" alt="PHK Home"></a></td>';
echo '</tr>';

echo '</table></td></tr></table>';

// Page title

if (!is_null($title)) echo "<p><h1>$title</h1>";
@flush(); //-- Tries to flush the header as the command can be quite long
}

//----

public function run()
{
#-- Debug mode

if (isset($_REQUEST['debug']))
	{
	echo "<hr>";
	echo "<h2>Environment:</h2>";

	echo "<h3>_REQUEST :</h3>";
	echo "<pre>";
	var_dump($_REQUEST);
	echo "</pre>";

	echo "<h3>_SERVER :</h3>";
	echo "<pre>";
	print_r($_SERVER);
	echo "</pre>";
	}

#-- Get the command and optional arg. Supports both URL formats

$command=trim(\PHK::setSubpath(),'/');
if (($pos=strpos($command,'/'))!==false)
	{
	$arg=substr($command,$pos+1);
	$command=substr($command,0,$pos);
	}
else $arg='';

if ($command=='') $command='info'; //-- Default command

#-- Run command

self::sendCacheHeader();

switch($command)
	{
	case 'view':
		$arg='/'.$arg;
		$this->header("File: $arg");
		$path=$this->PHK->uri($arg);
		if (!is_file($path))
			{
			echo '* ERROR: '.$arg.': File not found<p>';
			break;
			}

		echo "<table border=0>\n";
		echo '<tr><td>Size:</td><td>'.filesize($path).'</td></tr>';
		echo '<tr><td>Storage flags:</td><td>'
			.$this->PHK->proxy()->ftree()->lookup($arg)->flagString().'</td></tr>';
		echo "</table><hr/>";

		switch($mime_type=$this->PHK->mimeType($arg))
			{
			case 'application/x-httpd-php':
				highlight_file($path);
				break;

			case 'text/html':
				echo \PHK\Tools\Util::readFile($path);
				break;

			default:
				if (strpos($mime_type,'image/')===0) // Is it an image ?
					echo 'Image: <img src="'.\PHK::subpathURL('/file'.$arg).'">';
				else echo '<pre>'.htmlspecialchars(\PHK\Tools\Util::readFile($path))
					.'</pre>';
			}
		break;

	case 'run':
		$this->header();
		eval($this->PHK->webTunnel($arg,true));
		break;

	case 'file':	// Bare file
		eval($this->PHK->webTunnel($arg,true));
		return; // Don't put anything after the file

	case 'info':
	case 'techinfo':
	case 'envinfo':
	case 'showmap':
	case 'showfiles':
	case 'test':
		if (isset($this->cmd_titles[$command])) $t=$this->cmd_titles[$command];
		else $t=ucfirst($command);
		$this->header($t);
		$this->PHK->$command(array(__CLASS__,'viewSubfileURL'));
		break;

	case 'auto_file':
		$this->header();
		echo $this->PHK->autoFile('/'.$arg);
		break;

	case 'auto_option':
		$this->header(ucfirst($arg));
		echo $this->PHK->autoOption($arg);
		break;

	case 'php_section':
		require($this->PHK->sectionURI($arg));
		return; // Don't put anything after the file

	case 'section':	// Bare section (image, css,...) with PHP source auto-exec
		eval($this->PHK->webTunnel('/?section&name='.$arg,true));
		return; // Don't put anything after the file

	default:
		echo '<b>'.$command.': Unknown subcommand</b><p>';
	}

self::footer();
}

//----
// Convert a subfile path to an URL. Needed because Automap must not
// directly reference PHK or \PHK\Web\Info (to avoid cyclic dependencies).

public static function viewSubfileURL($fname)
{
return \PHK::subpathURL('/view/'.trim($fname,'/'));
}

//----
// Set headers to cache this url during 10 mins
// Taken from http-conditional (http://alexandre.alapetite.net)
// very important because, if it not sent, tabs background images are not
// cached by the browser.
// Unfortunately, since we had to change the syntax of webinfo URLs to be
// compatible with PHP in CGI mode, most browsers won't cache URLs containing
// a '?' char.

private static function sendCacheHeader()
{
header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T',time()+600));
header('Cache-Control: public, max-age=600'); //rfc2616-sec14.html#sec14.9
header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T',time()));
}

//----

private static function footer()
{
echo '<hr>';
echo '<font size="-1"><i>For more information about the PHK package format:'
		.' <a href="http://phk.tekwire.net" target="_blank">'
		.'http://phk.tekwire.net</i></font>';
}

//---
} // End of class
//===========================================================================
} // End of class_exists
//===========================================================================
} // End of namespace
//===========================================================================
?>
