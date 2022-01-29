<?php namespace Zephyrus\Tests\Utilities\Uploader;

use PHPUnit\Framework\TestCase;
use Zephyrus\Exceptions\UploaderException;
use Zephyrus\Network\ContentType;
use Zephyrus\Utilities\FileSystem\Directory;
use Zephyrus\Utilities\FileSystem\File;
use Zephyrus\Utilities\Uploader\FileUpload;

class FileUploadTest extends TestCase
{
    public function testInvalidInitialisation()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UploaderException::ERROR_NOT_UPLOADED_FILE);
        new FileUpload($this->buildUploadData());
    }

    public function testValidInitialisation()
    {
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        self::assertEquals("working.png", $upload->getOriginalFilename());
        self::assertEquals(sys_get_temp_dir() . '/upload_test', $upload->getTemporaryFilepath());
        self::assertEquals("png", $upload->getExtension());
        self::assertEquals(581, $upload->getSize());
        self::assertEquals("image/png", $upload->getMimeType());
    }

    public function testValidExtension()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UploaderException::ERROR_UPLOAD_EXTENSION);
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        self::assertTrue($upload->isExtensionAllowed());
        $upload->setAllowedExtensions(['pdf', 'docx', '.gif']);
        self::assertFalse($upload->isExtensionAllowed());
        $upload->setAllowedExtensions(['.png']);
        self::assertTrue($upload->isExtensionAllowed());
        $upload->setAllowedExtensions(['gif']);
        self::assertFalse($upload->isExtensionAllowed());
        $upload->setAllowedExtensions(['png']);
        self::assertTrue($upload->isExtensionAllowed());
        $upload->setAllowedExtensions(['gif']);
        self::assertFalse($upload->isExtensionAllowed());
        $upload->setAllowedExtensions([]);
        self::assertTrue($upload->isExtensionAllowed());
        $upload->setAllowedExtensions(['gif']);
        self::assertFalse($upload->isExtensionAllowed());
        $upload->upload("test", "test"); // Wont reach
    }

    public function testValidMimeType()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UploaderException::ERROR_UPLOAD_MIME_TYPE);
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        self::assertTrue($upload->isMimeTypeAllowed());
        $upload->setAllowedMimeTypes([ContentType::AUDIO_WAV, ContentType::IMAGE_TIFF]);
        self::assertFalse($upload->isMimeTypeAllowed());
        $upload->setAllowedMimeTypes([ContentType::IMAGE_PNG]);
        self::assertTrue($upload->isMimeTypeAllowed());
        $upload->setAllowedMimeTypes([ContentType::IMAGE_GIF]);
        self::assertFalse($upload->isMimeTypeAllowed());
        $upload->setAllowedMimeTypes([ContentType::ANY]);
        self::assertTrue($upload->isMimeTypeAllowed());
        $upload->setAllowedMimeTypes([ContentType::IMAGE_GIF]);
        self::assertFalse($upload->isMimeTypeAllowed());
        $upload->setAllowedMimeTypes([]);
        self::assertTrue($upload->isMimeTypeAllowed());
        $upload->setAllowedMimeTypes([ContentType::IMAGE_GIF]);
        self::assertFalse($upload->isMimeTypeAllowed());
        $upload->upload("test", "test"); // Wont reach
    }

    public function testValidSize()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UploaderException::ERROR_UPLOAD_SIZE);
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        self::assertTrue($upload->isSizeAllowed());
        $upload->setAllowedSize(1000);
        self::assertTrue($upload->isSizeAllowed());
        $upload->setAllowedSize(200);
        self::assertFalse($upload->isSizeAllowed());
        $upload->upload("test", "test"); // Wont reach
    }

    public function testInvalidSize()
    {
        self::expectException(\InvalidArgumentException::class);
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        $upload->setAllowedSize(-300);
    }

    public function testInvalidUploadDestination()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UploaderException::ERROR_INVALID_DESTINATION);
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        $upload->setDestinationCreationPermitted(false);
        $upload->upload(ROOT_DIR . '/lib/upload_test/inner_destination/deeper', 'test.png');
    }

    public function testInvalidUploadFileExists()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UploaderException::ERROR_DESTINATION_ALREADY_EXISTS);
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        $upload->setOverwritePermitted(false);
        $upload->upload(ROOT_DIR . '/lib', 'custom_filter_rules.json');
    }

    public function testInvalidUploadMoveFailed()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UploaderException::ERROR_MOVE_UPLOADED_FILE_FAILED);
        $upload = $this->buildFlawedMockUploadFile($this->buildUploadData());
        $upload->upload(ROOT_DIR . '/lib/upload_test/inner_destination/deeper', 'test.png');
    }

    public function testUploadWithDestinationCreation()
    {
        self::assertTrue(file_exists(sys_get_temp_dir() . '/upload_test'));
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        $upload->setDestinationCreationPermitted(true);
        $path = $upload->upload(ROOT_DIR . '/lib/upload_test/inner_destination/deeper', 'test.png');
        self::assertEquals(ROOT_DIR . '/lib/upload_test/inner_destination/deeper/test.png', $path);
        self::assertTrue(Directory::exists(ROOT_DIR . '/lib/upload_test/inner_destination/deeper'));
        self::assertTrue(File::exists(ROOT_DIR . '/lib/upload_test/inner_destination/deeper/test.png'));
        (new Directory(ROOT_DIR . '/lib/upload_test/inner_destination'))->remove();
    }

    public function testUploadWithOriginalName()
    {
        self::assertTrue(file_exists(sys_get_temp_dir() . '/upload_test'));
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        $upload->setKeepOriginalName(true);
        self::assertTrue($upload->verify());
        $path = $upload->upload(ROOT_DIR . '/lib/upload_test/');
        self::assertTrue(File::exists($path));
        self::assertEquals(ROOT_DIR . '/lib/upload_test/working.png', $path);
        (new File($path))->remove();
    }

    public function testUploadWithDefaultGeneratedName()
    {
        self::assertTrue(file_exists(sys_get_temp_dir() . '/upload_test'));
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        $upload->setKeepOriginalName(false);
        $path = $upload->upload(ROOT_DIR . '/lib/upload_test/');
        self::assertTrue(File::exists($path));
        self::assertNotEquals(ROOT_DIR . '/lib/upload_test/working.png', $path);
        self::assertTrue(preg_match('/[0-9A-Za-z]{24}\.png/', pathinfo($path, PATHINFO_FILENAME)) !== false);
        (new File($path))->remove();
    }

    public function testUploadWithUserDefinedGeneratedName()
    {
        self::assertTrue(file_exists(sys_get_temp_dir() . '/upload_test'));
        $upload = $this->buildMockUploadFile($this->buildUploadData());
        $upload->setKeepOriginalName(false);
        $upload->setCustomFilenameGenerator(function (): string {
            return "batman_was_here.png";
        });
        $path = $upload->upload(ROOT_DIR . '/lib/upload_test/');
        self::assertTrue(File::exists($path));
        self::assertEquals(ROOT_DIR . '/lib/upload_test/batman_was_here.png', $path);
        (new File($path))->remove();
    }

    private function buildUploadData(): array
    {
        (new File(ROOT_DIR . '/lib/images/working.png'))->copy(sys_get_temp_dir() . '/upload_test');
        return [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => sys_get_temp_dir() . '/upload_test',
            'size' => 1300000 /* fake sizing */
        ];
    }

    /**
     * Builds an UploadFile mock instance that overwrites the PHP related upload function to make sure the tests can
     * work.
     *
     * @param array $data
     * @return FileUpload
     */
    private function buildMockUploadFile(array $data): FileUpload
    {
        return new class($data) extends FileUpload {

            /**
             * Mock the move_uploaded_file because it cannot be properly tested within unit testing since PHP does
             * additional verifications over the file origins.
             *
             * @param string $temporaryFilepath
             * @param string $destinationFilepath
             * @return bool
             */
            protected function moveUploadedFile(string $temporaryFilepath, string $destinationFilepath): bool
            {
                (new File($temporaryFilepath))->copy($destinationFilepath);
                return true;
            }

            /**
             * Mock the is_uploaded_file to return always true because it cannot be properly tested within unit testing
             * since PHP does additional verifications over the file origins.
             *
             * @param string $temporaryFilepath
             * @return bool
             */
            protected function isUploadedFile(string $temporaryFilepath): bool
            {
                return true;
            }
        };
    }

    /**
     * Builds an UploadFile mock instance but forcing the usage of the read move_uploaded_file which will fail.
     *
     * @param array $data
     * @return FileUpload
     */
    private function buildFlawedMockUploadFile(array $data): FileUpload
    {
        return new class($data) extends FileUpload {

            /**
             * Mock the is_uploaded_file to return always true because it cannot be properly tested within unit testing
             * since PHP does additional verifications over the file origins.
             *
             * @param string $temporaryFilepath
             * @return bool
             */
            protected function isUploadedFile(string $temporaryFilepath): bool
            {
                return true;
            }
        };
    }
}
