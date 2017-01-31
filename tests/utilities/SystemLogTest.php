<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\SystemLog;

class SystemLogTest extends TestCase
{
    public function testError()
    {
        SystemLog::addError("test");
        $content = file_get_contents(ROOT_DIR . '/logs/errors.log');
        self::assertTrue(strpos($content, 'test') !== false);
        SystemLog::getErrorsLogger()->addWarning('testing');
        $content = file_get_contents(ROOT_DIR . '/logs/errors.log');
        self::assertTrue(strpos($content, 'testing') !== false);
        $this->cleanUp();
    }

    public function testSecurity()
    {
        SystemLog::addSecurity("test");
        $content = file_get_contents(ROOT_DIR . '/logs/security.log');
        self::assertTrue(strpos($content, 'test') !== false);
        SystemLog::getSecurityLogger()->addAlert('testing');
        $content = file_get_contents(ROOT_DIR . '/logs/security.log');
        self::assertTrue(strpos($content, 'testing') !== false);
        $this->cleanUp();
    }

    public function testVerbose()
    {
        SystemLog::addVerbose("test");
        $content = file_get_contents(ROOT_DIR . '/logs/verbose.log');
        self::assertTrue(strpos($content, 'test') !== false);
        SystemLog::getVerboseLogger()->addDebug('testing');
        $content = file_get_contents(ROOT_DIR . '/logs/verbose.log');
        self::assertTrue(strpos($content, 'testing') !== false);
        $this->cleanUp();
    }

    private function cleanUp()
    {
        @unlink(ROOT_DIR . '/logs/security.log');
        @unlink(ROOT_DIR . '/logs/errors.log');
        @unlink(ROOT_DIR . '/logs/verbose.log');
        rmdir(ROOT_DIR . '/logs');
    }
}