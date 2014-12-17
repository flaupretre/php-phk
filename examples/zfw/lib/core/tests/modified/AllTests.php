<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    UnitTests
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: AllTests.php 4655 2007-05-01 21:46:19Z bkarwin $
 */

// Don't run the tests in web mode

if (PHK_Util::is_web())
	{
	echo "<p>===== Please run the tests in CLI mode =====";
	return;
	}

//===================

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

// Remove E_NOTICE messages - workaround to PHP bug #39903,
// '__COMPILER_HALT_OFFSET__ already defined'

error_reporting(($errlevel=error_reporting()) & ~E_NOTICE);

//require_once 'PHPUnit.phk';	//--KEEP--

error_reporting($errlevel);

require_once dirname(__FILE__).'/TestHelper.php'; //--KEEP--

require_once dirname(__FILE__).'/Zend/AllTests.php';

class AllTests
{
    public static function main()
    {
        $parameters = array();

	$parameters['verbose']=true;

        if (TESTS_GENERATE_REPORT && extension_loaded('xdebug')) {
            $parameters['reportDirectory'] = TESTS_GENERATE_REPORT_TARGET;
        }

        if (defined('TESTS_ZEND_LOCALE_FORMAT_SETLOCALE') && TESTS_ZEND_LOCALE_FORMAT_SETLOCALE) {
            // run all tests in a special locale
            setlocale(LC_ALL, TESTS_ZEND_LOCALE_FORMAT_SETLOCALE);
        }

		if (PHK_Util::is_web()) echo "<pre>\n";
        PHPUnit_TextUI_TestRunner::run(self::suite(), $parameters);
		if (PHK_Util::is_web()) echo "</pre>\n";
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Zend Framework');

        // $suite->addTestSuite('ZendTest');

        $suite->addTest(Zend_AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}
