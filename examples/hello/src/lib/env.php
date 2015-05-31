<?php

// Uncomment to trace autoload
//echo "> Autoloading env.php\n";

class EnvInfo
{

public static function is_web()
{
return (php_sapi_name()!='cli');
}

} // End of class
?>
