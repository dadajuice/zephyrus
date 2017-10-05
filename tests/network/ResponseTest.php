<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Exceptions\NetworkException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

class ResponseTest extends TestCase
{
    public function testHeader()
    {
        $response = new Response();
        $response->setContentType(ContentType::CSS);
        $response->addHeader('test', '1234');
        $response->setCharset("TEST");
        $response->send();
        $headers = xdebug_get_headers();
        self::assertTrue(in_array('Content-Type: text/css;charset=TEST', $headers));
        self::assertTrue(in_array('test:1234', $headers));
    }

    public function testHeaders()
    {
        $response = new Response();
        $response->setContentType(ContentType::HTML);
        $response->addHeaders([
            'test' => '1234',
            'test2' => '12345'
        ]);
        $response->send();
        $headers = xdebug_get_headers();
        self::assertTrue(in_array('test:1234', $headers));
        self::assertTrue(in_array('test2:12345', $headers));
    }

    /*
    public function testRedirect()
    {
        $response = new Response();
        $response->redirect('/test');
        $headers = xdebug_get_headers();
        self::assertTrue(in_array('Location:/test', $headers));
    }*/

    /**
     * @expectedException \RuntimeException
     */
    /*
    public function testAlreadySent()
    {
        $response = new OldResponse();
        $response->sendResponseCode();
        $response->sendResponseCode();
    }*/

    /**
     * @expectedException \RuntimeException
     */
    /*
    public function testAlreadySent2()
    {
        $response = new OldResponse();
        $response->sendContentType();
        $response->sendContentType();
    }

    public function testAbortNotFound()
    {
        $response = new OldResponse();
        try {
            $response->abortNotFound();
        } catch (NetworkException $e) {
            self::assertTrue($e->isNotFound());
            self::assertEquals(404, $e->getHttpStatusCode());
        }
    }

    public function testAbortInternalError()
    {
        $response = new OldResponse();
        try {
            $response->abortInternalError();
        } catch (NetworkException $e) {
            self::assertTrue($e->isInternalError());
        }
    }

    public function testAbortForbidden()
    {
        $response = new OldResponse();
        try {
            $response->abortForbidden();
        } catch (NetworkException $e) {
            self::assertTrue($e->isForbidden());
        }
    }

    public function testAbortMethodNotAllowed()
    {
        $response = new OldResponse();
        try {
            $response->abortMethodNotAllowed();
        } catch (NetworkException $e) {
            self::assertTrue($e->isMethodNotAllowed());
        }
    }

    public function testAbortNotAcceptable()
    {
        $response = new OldResponse();
        try {
            $response->abortNotAcceptable();
        } catch (NetworkException $e) {
            self::assertTrue($e->isNotAcceptable());
        }
    }

    public function testAbort()
    {
        $response = new OldResponse();
        try {
            $response->abort(407);
        } catch (NetworkException $e) {
            self::assertEquals(407, $e->getHttpStatusCode());
        }
    }*/
}
