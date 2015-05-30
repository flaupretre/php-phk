<?php

define('MAP1','auto1.map');
define('MAP1_SYMCOUNT',8);

define('MAP2','auto2.map');

//------------------------------------------------------------------------

require dirname(__FILE__).'/Tester.php';

$extension_present=extension_loaded('phk');
$t=$GLOBALS['t']=new Tester('PHK runtime ('.($extension_present ? 'with' : 'whithout')
	.' PECL accelerator)');

//------------------------------------------------------------------------
$t->start('Include pkg1.phk');

$path1=dirname(__FILE__).'/pkg1.phk';
$mnt1=require($path1);
$t->check('include() returns a valid mnt',is_string($mnt1));

//---------------------------------
$t->start('Mgr');

$t->check('isMounted() returns true',\PHK\Mgr::isMounted($mnt1)===true);
$t->check('isMounted(dummy) returns false',\PHK\Mgr::isMounted('dummy')===false);

$pkg1=\PHK\Mgr::instance($mnt1);
$t->check('instance is instance of PHK class (1)',($pkg1 instanceof \PHK));

$ex=false;
try {
	\PHK\Mgr::instance('dummy');
} catch (\Exception $e) { $ex=true; }
$t->check('instance(dummy) throws exceptions', $ex);

$l=\PHK\Mgr::mntList();
$t->check('mntList() - count == 1',count($l)==1);
$t->check('mntList() - return mnt1',$l[0]===$mnt1);

$m=\PHK\Mgr::pathToMnt($path1);
$t->check('pathToMnt() returns mnt1',$m===$mnt1);

$ex=false;
try {
	$m=\PHK\Mgr::pathToMnt('dummy');
} catch (\Exception $e) { $ex=true; }
$t->check('pathToMnt(dummy) throws exceptions', $ex);

//TODO: check topLevelPath()

//---------------------------------
$t->start('mount/umount');

$m=\PHK\Mgr::mount($path1);
$t->check('re-mount(path1) returns mnt1',$m===$mnt1);

$mapid1=$pkg1->automapID();
$t->check('automapID() returns a valid ID',\Automap\Mgr::isActiveID($mapid1)===true);
$map1=\Automap\Mgr::map($mapid1);
$scount=$map1->symbolCount();
$t->check('map object is instance of Automap\Map',($map1 instanceof \Automap\Map));

\PHK\Mgr::umount($mnt1);
$t->check('isMounted() of unmounted instance returns false',!\PHK\Mgr::isMounted($mnt1)===true);

$t->check('pkg umount unloads the map',\Automap\Mgr::isActiveID($mapid1)===false);
$t->check('map object of unmounted package is still accessible',($map1->symbolCOunt()===$scount));

$m=\PHK\Mgr::mount($path1);
$t->check('mount of unmounted path returns the same mnt',$m===$mnt1);

$ex=false;
try {
	$pkg1->automapID();
} catch (\Exception $e) { $ex=true; }
$t->check('accessing unmounted package instance throws exceptions', $ex);

$pkg1=\PHK\Mgr::instance($mnt1);
$t->check('instance is instance of PHK class (2)',($pkg1 instanceof \PHK));

//---------------------------------
$t->start('Virtual files/dirs');

define('DIR1','/classes');
define('FILE1','/classes/file11.php');
define('RFILE1','/file11.php');

$urid1=\PHK\Mgr::uri($mnt1,DIR1);
$urif1=\PHK\Mgr::uri($mnt1,FILE1);
$t->check('Consistent URI (1)',$urif1===($urid1.RFILE1));
$t->check('Virtual directory recognized',is_dir($urid1));
$t->check('Non-existing virtual directory not recognized',!is_dir($urid1.'dummy'));
$t->check('Virtual file recognized',is_file($urif1));
$t->check('Non-existing virtual file not recognized',!is_file($urif1.'dummy'));

$t->check('Read virtual file',strlen(file_get_contents($urif1))!==0);

$t->check('scandir(urid1) returns array',is_array(scandir($urid1)));
$t->check('scandir(urid1) returns correct count of entries',count(scandir($urid1))===8);

$t->check('topLevelPath(urif1) returns path1',\PHK\Mgr::topLevelPath($urif1)===$path1);

$t->check('isPhkUri(urif1) is true',\PHK\Mgr::isPhkUri($urif1)===true);
$t->check('isPhkUri(dummy) is false',\PHK\Mgr::isPhkUri($urif1)===true);

$t->check('uriToMnt(urif1) returns mnt1',\PHK\Mgr::uriToMnt($urif1)===$mnt1);

//---------------------------------
$t->start('PHK instance');

$t->check('mnt',($pkg1->mnt()===$mnt1));
$t->check('flags',($pkg1->flags()===0));
$t->check('path',($pkg1->path()===$path1));
$t->check('parentMnt',($pkg1->parentMnt()===null));
$t->check('plugin',($pkg1->plugin()===null));

$t->check('mapDefined',($pkg1->mapDefined()===true));

$t->check('fileIsPackage (true)',(\PHK::fileIsPackage($path1)===true));
$t->check('fileIsPackage (false)',(\PHK::fileIsPackage(__FILE__)===false));
$t->check('fileIsPackage on URI (false)',(\PHK::fileIsPackage($urif1)===false));

$t->check('uri',($pkg1->uri(FILE1)===$urif1));

$t->check('automapURI',($pkg1->automapURI()===\PHK\Mgr::automapURI($mnt1)));

$t->check('option(name)',($pkg1->option('name')==='PKG1'));

$t->check('webAccessAllowed (false)',($pkg1->webAccessAllowed(FILE1)===false));
$t->check('webAccessAllowed (true)',($pkg1->webAccessAllowed('/scripts/web_main.php')===true));

$t->check('mimeType(php)',($pkg1->mimeType(FILE1)==='application/x-httpd-php'));

$t->check('isPHPSourcePath(php) returns true',($pkg1->isPHPSourcePath(FILE1)===true));

$ex=false;
try {
	$pkg1->crcCheck();
} catch (\Exception $e) { $ex=true; }
$t->check('crcCheck() does not throw exception',!$ex);
	$pkg1->crcCheck();

$t->check('read buildInfo',$pkg1->buildInfo('map_defined')===true);

//----------------

$t->end();
?>
