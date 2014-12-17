<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2002-2007, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2007 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id: AllTests.php 745 2007-07-04 05:35:19Z sb $
 * @link       http://www.phpunit.de/
 * @since      File available since Release 2.0.0
 */

error_reporting(E_ALL | E_STRICT);

PHPUnit_Util_Filter::addFileToFilter(__FILE__);

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
    //chdir(dirname(__FILE__));
}

if (is_readable(getcwd().DIRECTORY_SEPARATOR.'TestConfiguration.php')) {
    require_once getcwd().DIRECTORY_SEPARATOR.'TestConfiguration.php';	//--KEEP--
} else {
    require_once dirname(__FILE__).'/TestConfiguration.php.dist';	//--KEEP--
}

require_once dirname(__FILE__).'/../Framework/TestSuite.php';
require_once dirname(__FILE__).'/../TextUI/TestRunner.php';

require_once dirname(__FILE__).'/Framework/AllTests.php';
require_once dirname(__FILE__).'/Extensions/AllTests.php';
require_once dirname(__FILE__).'/Runner/AllTests.php';
require_once dirname(__FILE__).'/Util/AllTests.php';

/**
 *
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2007 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 3.2.2
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 2.0.0
 */
class AllTests
{
    public static function main()
    {
	if (PHK_Util::is_web()) echo "<pre>\n";
        PHPUnit_TextUI_TestRunner::run(self::suite());
	if (PHK_Util::is_web()) echo "</pre>\n";
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit');

        $suite->addTest(Framework_AllTests::suite());
        $suite->addTest(Extensions_AllTests::suite());
        $suite->addTest(Runner_AllTests::suite());
        $suite->addTest(Util_AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}
?>
