<?php

namespace XLiteWeb\tests;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use XLiteTest\Framework\Web\Utils;

/**
 * @author cerber
 */
class testFake extends \XLiteWeb\AXLiteWeb
{
    /**
     *
     * @var  RemoteWebDriver
     */
    protected $driver;

    public function testRestoreDB()
    {

        Utils::restoreDB(true);
        //Utils::restoreDB(false);

    }

}
