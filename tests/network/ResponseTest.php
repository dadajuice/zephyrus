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
        $response->sendContentType(ContentType::CSS);
        $response->sendHeader('test', '1234');
        $response->sendResponseCode();
        $headers = xdebug_get_headers();
        self::assertTrue(in_array('Content-Type: text/css;charset=UTF-8', $headers));
        self::assertTrue(in_array('test:1234', $headers));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAlreadySent()
    {
        $response = new Response();
        $response->sendResponseCode();
        $response->sendResponseCode();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAlreadySent2()
    {
        $response = new Response();
        $response->sendContentType();
        $response->sendContentType();
    }

    public function testAbortNotFound()
    {
        $response = new Response();
        try {
            $response->abortNotFound();
        } catch (NetworkException $e) {
            self::assertTrue($e->isNotFound());
            self::assertEquals(404, $e->getHttpStatusCode());
        }
    }

    public function testAbortInternalError()
    {
        $response = new Response();
        try {
            $response->abortInternalError();
        } catch (NetworkException $e) {
            self::assertTrue($e->isInternalError());
        }
    }

    public function testAbortForbidden()
    {
        $response = new Response();
        try {
            $response->abortForbidden();
        } catch (NetworkException $e) {
            self::assertTrue($e->isForbidden());
        }
    }

    public function testAbortMethodNotAllowed()
    {
        $response = new Response();
        try {
            $response->abortMethodNotAllowed();
        } catch (NetworkException $e) {
            self::assertTrue($e->isMethodNotAllowed());
        }
    }

    public function testAbortNotAcceptable()
    {
        $response = new Response();
        try {
            $response->abortNotAcceptable();
        } catch (NetworkException $e) {
            self::assertTrue($e->isNotAcceptable());
        }
    }

    public function testAbort()
    {
        $response = new Response();
        try {
            $response->abort(407);
        } catch (NetworkException $e) {
            self::assertEquals(407, $e->getHttpStatusCode());
        }
    }
}
