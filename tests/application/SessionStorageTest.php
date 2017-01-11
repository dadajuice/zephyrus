<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\SessionStorage;

class SessionStorageTest extends TestCase
{
    public function testDefaultLifeCycle()
    {
        $storage = new SessionStorage('bob');
        $storage->start();
        self::assertEquals(session_name(), $storage->getName());
        self::assertEquals(session_id(), $storage->getId());
        self::assertTrue($storage->isStarted());
        $storage->destroy();
    }

    public function testContent()
    {
        $storage = new SessionStorage('bob');
        $storage->start();
        $_SESSION['test'] = '123';
        $content = &$storage->getContent();
        self::assertEquals('123', $content['test']);
        $content['test2'] = '456';
        self::assertEquals('456', $storage->getContent()['test2']);
        $storage->destroy();
    }

    public function testRefresh()
    {
        $storage = new SessionStorage('bob');
        $storage->start();
        $id = $storage->getId();
        $storage->refresh();
        self::assertNotEquals($id, session_id());
        $storage->destroy();
    }

    public function testRestart()
    {
        $storage = new SessionStorage('bob');
        $storage->start();
        $_SESSION['test'] = '123';
        $storage->restart();
        self::assertFalse(isset($_SESSION['test']));
    }
}