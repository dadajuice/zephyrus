<?php namespace Zephyrus\Utilities\Uploaders;

/**
 * REFERENCES
 * http://www.net-security.org/dl/articles/php-file-upload.pdf.
 */
class ImageUploader extends FileUploader
{
    const PERMITTED_MIME_TYPES = ['image/gif', 'image/jpeg', 'image/png'];
    const PERMITTED_EXTENSIONS = ['gif', 'jpeg', 'jpg', 'png'];

    /**
     * @var bool
     */
    private $forcingImageRebuild = false;

    public function __construct(UploadFile $uploadFile)
    {
        parent::__construct($uploadFile);
        parent::addAllowedExtensions(self::PERMITTED_EXTENSIONS);
        parent::addAllowedMimeTypes(self::PERMITTED_MIME_TYPES);
    }

    /**
     * @return bool
     */
    public function isForcingImageRebuild()
    {
        return $this->forcingImageRebuild;
    }

    /**
     * @param bool $forcingImageRebuild
     */
    public function setForcingImageRebuild($forcingImageRebuild)
    {
        $this->forcingImageRebuild = $forcingImageRebuild;
    }

    /**
     * @throws \Exception
     */
    protected function validateUpload()
    {
        parent::validateUpload();
        $this->validateImageMimeType();
        $info = getimagesize($this->uploadFile->getTemporaryFilename());
        if (empty($info) || $info[0] == "" || $info[1] == "") {
            throw new \Exception("Uploaded image appears to be corrupt"); // @codeCoverageIgnore
        }
        if ($this->forcingImageRebuild) {
            $this->rebuildImage($info[0], $info[1]);
        }
    }

    /**
     * Validate that the mime type is an actual valid image.
     *
     * @throws \Exception
     */
    private function validateImageMimeType()
    {
        $imageType = exif_imagetype($this->uploadFile->getTemporaryFilename());
        $mime = image_type_to_mime_type($imageType);
        if (!$imageType || !in_array($mime, parent::getAllowedMimeTypes())) {
            throw new \Exception("Uploaded image appears to be corrupt"); // @codeCoverageIgnore
        }
    }

    private function rebuildImage($width, $height)
    {
        $finalImage = imagecreatetruecolor($width, $height);
        $extension = $this->uploadFile->getExtension();
        $tempImage = $this->createImageFromExtension($extension);
        imagealphablending($finalImage, false);
        imagesavealpha($finalImage, true);
        imagecopyresampled($finalImage, $tempImage, 0, 0, 0, 0, $width, $height, $width, $height);
        $this->saveImageFromExtension($extension, $finalImage);
    }

    private function createImageFromExtension(string $extension)
    {
        $tempImage = null;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $tempImage = @imagecreatefromjpeg($this->uploadFile->getTemporaryFilename());
                break;
            case 'gif':
                $tempImage = @imagecreatefromgif($this->uploadFile->getTemporaryFilename());
                break;
            case 'png':
                $tempImage = @imagecreatefrompng($this->uploadFile->getTemporaryFilename());
                break;
        }
        if (!$tempImage) {
            throw new \Exception("Uploaded image appears to be corrupt"); // @codeCoverageIgnore
        }
        return $tempImage;
    }

    private function saveImageFromExtension(string $extension, $finalImage)
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($finalImage, $this->uploadFile->getTemporaryFilename(), 100);
                break;
            case 'gif':
                imagegif($finalImage, $this->uploadFile->getTemporaryFilename());
                break;
            case 'png':
                imagepng($finalImage, $this->uploadFile->getTemporaryFilename());
                break;
        }
    }
}
