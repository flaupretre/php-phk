<?php

include(dirname(__FILE__).'/env.php');
include(dirname(__FILE__).'/message.php');

Message::display('Hello, world (no autoload, explicit includes)');

?>
