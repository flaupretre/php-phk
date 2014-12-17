<?php
// PHP Unit launcher
// Adapted by F. Laupretre from the original pear-phpunit for PHK port

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

require dirname(__FILE__).'/TextUI/Command.php';
?>
