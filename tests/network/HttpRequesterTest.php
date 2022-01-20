<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Exceptions\HttpRequesterException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\HttpRequester;
use Zephyrus\Utilities\FileSystem\File;

class HttpRequesterTest extends TestCase
{
    public function testGet()
    {
        $request = HttpRequester::post("https://raw.githubusercontent.com/dadajuice/zephyrus/master/tests/lib/filesystem/existing.txt");
        $request = HttpRequester::delete("https://raw.githubusercontent.com/dadajuice/zephyrus/master/tests/lib/filesystem/existing.txt");
        $request = HttpRequester::patch("https://raw.githubusercontent.com/dadajuice/zephyrus/master/tests/lib/filesystem/existing.txt");
        $request = HttpRequester::get("https://raw.githubusercontent.com/dadajuice/zephyrus/master/tests/lib/filesystem/existing.txt");
        $request->setConnectionTimeout(10);
        $request->setContentType(ContentType::PLAIN);
        $request->setFollowRedirection(true);
        $request->setSslVerification(false);
        $request->setUserAgent("PHPUnit Test");
        $request->execute(['t' => time()]);
        self::assertEquals(200, $request->getResponseHttpCode());
        self::assertEquals(200, $request->getResponseInfo()['http_code']);
        self::assertEquals('One ring to rule them all', $request->getResponse());

        $request = HttpRequester::put("https://raw.githubusercontent.com/dadajuice/zephyrus/master/tests/lib/filesystem/existing.txt");
        $request->addHeaders(['X-APP' => 'PHPUnit']);
        $request->addOptions([CURLOPT_RETURNTRANSFER => true]);
        $request->execute();
        self::assertEquals(ContentType::FORM, $request->getContentType());

        $request = HttpRequester::get("https://raw.githubusercontent.com/dadajuice/zephyrus/master/tests/lib/filesystem/existing.txt");
        $request->executeStream(function ($result, $info) {
            self::assertEquals('One ring to rule them all', $result);
        });
    }

    public function testDownload()
    {
        $request = HttpRequester::get("https://raw.githubusercontent.com/dadajuice/zephyrus/master/tests/lib/filesystem/existing.txt");
        $filePath = $request->executeDownload();
        self::assertTrue(file_exists($filePath));

        $file = new File($filePath);
        self::assertEquals('One ring to rule them all', $file->read());

        $file->remove();
        self::assertFalse(file_exists($filePath));
    }

    public function testInvalidDownloadFile()
    {
        $this->expectException(HttpRequesterException::class);
        $request = HttpRequester::get("https://raw.githubusercontent.com/dadajuice/zephyrus/master/tests/lib/filesystem/existing.txt");
        $request->executeDownload([], '/etc/new_files.txt');
    }

    public function testUpload()
    {
        $request = HttpRequester::post("https://raw.githubusercontent.com/dadajuice/zephyrus/master/tests/lib/filesystem/sdfdgdfdgfdfg.txt");
        $file = HttpRequester::prepareUploadFile(ROOT_DIR . '/lib/filesystem/existing.txt', 'test.txt');
        self::assertTrue($file instanceof \CURLFile);
        self::assertEquals(ROOT_DIR . '/lib/filesystem/existing.txt', $file->getFilename());
        $request->executeUpload($file, 'file', ['test' => ['name' => 't', 'age' => 3, 'classes' => ['nest', 'nest 2']]]);
        self::assertEquals(ContentType::FORM_MULTIPART, $request->getContentType());
    }

    public function testUploadWithExecuteNested()
    {
        $request = HttpRequester::post("https://raw.githubusercontent.com/dadajuice/zephyrus/master/tests/lib/filesystem/sdfdgdfdgfdfg.txt");
        $file = HttpRequester::prepareUploadFile(ROOT_DIR . '/lib/filesystem/existing.txt', 'test.txt');
        self::assertTrue($file instanceof \CURLFile);
        $request->execute(['test' => ['name' => 't', 'file' => $file, 'classes' => ['nest', 'nest 2']]]);
        self::assertEquals(ContentType::FORM_MULTIPART, $request->getContentType());
    }

    public function testInvalidUpload()
    {
        $this->expectException(\InvalidArgumentException::class);
        HttpRequester::prepareUploadFile(ROOT_DIR . '/lib/filesystem/existidsfsdfdsfdsdfdsfsdfsdfng.txt', 'test.txt');
    }
}
