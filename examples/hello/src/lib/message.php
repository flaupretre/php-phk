<?php

// Uncomment to trace autoload
//echo "> Autoloading message.php\n";

class Message
{

public static function display($msg)
{
if (EnvInfo::is_web()) echo "<h1>$msg</h1>";
else echo "$msg\n";
}

} // End of class
?>
