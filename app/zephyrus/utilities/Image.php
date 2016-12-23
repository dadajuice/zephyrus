<?php namespace Zephyrus\Utilities;

use Zephyrus\Exceptions\ImageException;

class Image
{
    const BOTTOM_LEFT = 0;
    const BOTTOM_RIGHT = 1;
    const TOP_LEFT = 2;
    const TOP_RIGHT = 3;

    /**
     * @var bool
     */
    private $overwritePermitted = true;

    /**
     * @var int
     */
    private $manipulation = 0;

    /**
     * @var int
     */
    private $jpegQuality = 100;

    /**
     * @var resource
     */
    private $sourceImage;

    /**
     * @var string
     */
    private $sourcePath;

    /**
     * @var string
     */
    private $sourceDirectory;

    /**
     * @var string
     */
    private $sourceFilename;

    /**
     * @var string
     */
    private $sourceExtension;

    /**
     * @var resource
     */
    private $destinationImage;

    /**
     * @var string
     */
    private $destinationDirectory;

    /**
     * @var string
     */
    private $destinationFilename;

    /**
     * @var string
     */
    private $destinationExtension;

    /**
     * @param string $pdfPath
     * @param string $savePath
     * @param int $page
     */
    public static function pdfThumbnail($pdfPath, $savePath, $page = 0)
    {
        if (!class_exists("Imagick")) {
            throw new \RuntimeException("Imagick extension not installed");
        }

        $image = new \Imagick($pdfPath . "[$page]");
        //$image->setImageColorSpace(255);
        $image->setImageFormat("png");
        //$image->thumbnailimage($width, $height);
        //$image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);
        $image->writeImage($savePath);
        $image->clear();
        $image->destroy();
    }

    /**
     * Image constructor. If the destination is not provided, the source path
     * will be used. Which means that saving will overwrite the specified
     * source image (if no destination has been set whatsoever).
     *
     * @param string $sourcePath
     * @param string $destinationPath
     */
    public function __construct($sourcePath, $destinationPath = '')
    {
        $this->assignSource($sourcePath);
        if (!empty($destinationPath)) {
            $this->assignDestination($destinationPath);
        } else {
            $this->assignDestination($sourcePath);
        }
        $this->sourceImage = $this->loadImage($sourcePath);
    }

    /**
     * Class destructor which make sure both image resource.
     */
    public function __destruct()
    {
        @imagedestroy($this->sourceImage);
        @imagedestroy($this->destinationImage);
    }

    /**
     * Physically save the image to the specified destination path. If no
     * transformation has been done, the image is simply copied.
     *
     * @param string $filename
     * @param bool $forceExtension
     * @return string
     * @throws ImageException
     */
    public function save($filename = null, $forceExtension = true)
    {
        if (!is_null($filename)) {
            $this->setDestinationFilename($filename, $forceExtension);
        }

        if (!file_exists($this->destinationDirectory)) {
            throw new ImageException(ImageException::ERR_DIRECTORY_EXISTS, $this->destinationDirectory);
        }

        if (!is_writeable($this->destinationDirectory)) {
            throw new ImageException(ImageException::ERR_DIRECTORY_WRITABLE, $this->destinationDirectory);
        }

        $destination = $this->getDestinationTarget();

        if (!$this->overwritePermitted && file_exists($destination)) {
            throw new ImageException(ImageException::ERR_OVERWRITE, $destination);
        }

        if ($this->manipulation == 0) {
            copy($this->sourcePath, $destination);
        } else {
            switch ($this->destinationExtension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($this->destinationImage, $destination, $this->jpegQuality);
                    break;
                case 'png':
                    imagepng($this->destinationImage, $destination);
                    break;
                case 'gif':
                    imagegif($this->destinationImage, $destination);
                    break;
                case 'wbmp':
                    imagewbmp($this->destinationImage, $destination);
                    break;
                default:
                    throw new ImageException(ImageException::ERR_FORMAT, $destination);
            }
        }

        return $destination;
    }

    /**
     * Determines if an existing file in the destination folder should be
     * overridden.
     *
     * @return bool
     */
    public function isOverwritePermitted()
    {
        return (bool) $this->overwritePermitted;
    }

    /**
     * Set if an existing file in the destination folder should be overridden.
     *
     * @param bool $overwritePermitted
     */
    public function setOverwritePermitted($overwritePermitted)
    {
        $this->overwritePermitted = (bool) $overwritePermitted;
    }

    /**
     * @return string
     */
    public function getDestinationExtension()
    {
        return $this->destinationExtension;
    }

    /**
     * @return int
     */
    public function getSourceWidth()
    {
        return imagesx($this->sourceImage);
    }

    /**
     * @return int
     */
    public function getSourceHeight()
    {
        return imagesy($this->sourceImage);
    }

    /**
     * Apply the uploaded file directory to be used as a destination. Specified
     * path starts from the project root directory. Will trim unnecessary
     * leading slashes.
     *
     * @param string $destinationDirectory
     */
    public function setDestinationDirectory($destinationDirectory)
    {
        $destinationDirectory = ltrim($destinationDirectory, DIRECTORY_SEPARATOR);
        if ($destinationDirectory[strlen($destinationDirectory) - 1] != DIRECTORY_SEPARATOR) {
            $destinationDirectory .= DIRECTORY_SEPARATOR;
        }
        $this->destinationDirectory = ROOT_DIR . DIRECTORY_SEPARATOR . $destinationDirectory;
    }

    /**
     * @return string
     */
    public function getDestinationDirectory()
    {
        return $this->destinationDirectory;
    }

    /**
     * Apply the uploaded filename to be used in the destination directory. To
     * keep the same extension, simply omit to specify it in the filename and
     * if it is needed to explicitly upload a file with no extension, disable
     * the last argument (forceExtension).
     *
     * @param string $filename
     * @param bool $forceExtension (optional)
     */
    public function setDestinationFilename($filename, $forceExtension = true)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (empty($extension) && $forceExtension) {
            $filename .= '.' . $this->destinationExtension;
        }
        $this->destinationFilename = $filename;
    }

    /**
     * Retrieve only the destination filename (can be useful for database
     * storage). May not contain extension if user did disable the forceExtension
     * argument of the setDestinationFilename method.
     *
     * @return string
     */
    public function getDestinationFilename()
    {
        return $this->destinationFilename;
    }

    /**
     * Retrieve the filename without extension.
     *
     * @return string
     */
    public function getDestinationBasename()
    {
        return pathinfo($this->destinationFilename, PATHINFO_FILENAME);
    }

    /**
     * Retrieve the complete path to the destination image. If no path has
     * been specified, the source path is used by default.
     *
     * @return string
     */
    public function getDestinationTarget()
    {
        return $this->destinationDirectory . $this->destinationFilename;
    }

    /**
     * Resize the image to the specified width and height.
     *
     * @param int $width
     * @param int $height
     * @return Image
     */
    public function resize($width, $height)
    {
        $sourceWidth = imagesx($this->sourceImage);
        $sourceHeight = imagesy($this->sourceImage);

        $this->createDestinationImage($width, $height);
        imagecopyresampled(
            $this->destinationImage,
            $this->sourceImage, 0, 0, 0, 0,
            $width,
            $height,
            $sourceWidth,
            $sourceHeight
        );

        ++$this->manipulation;
        $this->sourceImage = $this->destinationImage;
        return $this;
    }

    /**
     * Resize the image to the specified width while keeping the original
     * aspect ratio.
     *
     * @param int $width
     * @return Image
     */
    public function resizeAutoWidth($width)
    {
        $sourceWidth = imagesx($this->sourceImage);
        $sourceHeight = imagesy($this->sourceImage);
        $height = (int)(($width / $sourceWidth) * $sourceHeight);
        return $this->resize($width, $height);
    }

    /**
     * Resize the image to the specified height while keeping the original
     * aspect ratio.
     *
     * @param int $height
     * @return Image
     */
    public function resizeAutoHeight($height)
    {
        $sourceWidth = imagesx($this->sourceImage);
        $sourceHeight = imagesy($this->sourceImage);
        $width = (int)(($height / $sourceHeight) * $sourceWidth);
        return $this->resize($width, $height);
    }

    /**
     * Resize the image and keep the original aspect ratio. Tries to do the
     * best resizing depending on the original image size. If the image is
     * wider than higher, the resize will be done accordingly.
     *
     * @param int $size
     * @return Image
     */
    public function resizeAuto($size)
    {
        $sourceWidth = imagesx($this->sourceImage);
        $sourceHeight = imagesy($this->sourceImage);
        return ($sourceWidth > $sourceHeight)
            ? $this->resizeAutoHeight($size)
            : $this->resizeAutoWidth($size);
    }

    /**
     * Crops the image to the specified size. Starts at the specified
     * coordinates.
     *
     * @param int $zoneWidth
     * @param int $zoneHeight
     * @param int $zoneX
     * @param int $zoneX
     * @return Image
     */
    public function crop($zoneWidth, $zoneHeight, $zoneX, $zoneY)
    {
        $this->createDestinationImage($zoneWidth, $zoneHeight);
        imagecopyresampled(
            $this->destinationImage,
            $this->sourceImage, 0, 0,
            $zoneX,
            $zoneY,
            $zoneWidth,
            $zoneHeight,
            $zoneWidth,
            $zoneHeight
        );
        ++$this->manipulation;
        $this->sourceImage = $this->destinationImage;
        return $this;
    }

    /**
     * Crops the image to the specified size. Horizontally and vertically centers the
     * cropping area before doing so.
     *
     * @param int $zoneWidth
     * @param int $zoneHeight
     * @return Image
     */
    public function cropCenter($zoneWidth, $zoneHeight)
    {
        $sourceWidth = imagesx($this->sourceImage);
        $sourceHeight = imagesy($this->sourceImage);
        $cropX = ($sourceWidth - $zoneWidth) / 2;
        $cropY = ($sourceHeight - $zoneHeight) / 2;
        return $this->crop($zoneWidth, $zoneHeight, $cropX, $cropY);
    }

    /**
     * Crops the image to the specified size. Horizontally centers the cropping
     * area before doing so.
     *
     * @param int $zoneWidth
     * @param int $zoneHeight
     * @return Image
     */
    public function cropCenterHorizontal($zoneWidth, $zoneHeight)
    {
        $sourceWidth = imagesx($this->sourceImage);
        $cropX = ($sourceWidth - $zoneWidth) / 2;
        $cropY = 0;
        return $this->crop($zoneWidth, $zoneHeight, $cropX, $cropY);
    }

    /**
     * Crops the image to the specified size. Vertically centers the cropping
     * area before doing so.
     *
     * @param int $zoneWidth
     * @param int $zoneHeight
     * @return Image
     */
    public function cropCenterVertical($zoneWidth, $zoneHeight)
    {
        $sourceHeight = imagesy($this->sourceImage);
        $cropX = 0;
        $cropY = ($sourceHeight - $zoneHeight) / 2;
        return $this->crop($zoneWidth, $zoneHeight, $cropX, $cropY);
    }

    /**
     * Adds a specified watermark image to the destination.
     *
     * @param string $image
     * @param int $position
     * @param int $padding
     * @return Image
     * @throws ImageException
     */
    public function watermark($image, $position = self::BOTTOM_RIGHT, $padding = 0)
    {
        $sourceWidth = imagesx($this->sourceImage);
        $sourceHeight = imagesy($this->sourceImage);

        $watermark = $this->loadImage($image);
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);

        switch ($position) {
            case self::BOTTOM_LEFT:
                $watermarkX = 0 + $padding;
                $watermarkY = $sourceHeight - ($watermarkHeight + $padding);
                break;
            case self::BOTTOM_RIGHT:
                $watermarkX = $sourceWidth - ($watermarkWidth + $padding);
                $watermarkY = $sourceHeight - ($watermarkHeight + $padding);
                break;
            case self::TOP_LEFT:
                $watermarkX = 0 + $padding;
                $watermarkY = 0 + $padding;
                break;
            case self::TOP_RIGHT:
                $watermarkX = $sourceWidth - ($watermarkWidth + $padding);
                $watermarkY = 0 + $padding;
                break;
            default:
                throw new ImageException(ImageException::ERR_UNSUPPORTED_POSITION);
        }

        $this->createDestinationImage($sourceWidth, $sourceHeight);
        imagecopyresampled(
            $this->destinationImage,
            $this->sourceImage, 0, 0, 0, 0,
            $sourceWidth,
            $sourceHeight,
            $sourceWidth,
            $sourceHeight
        );
        $this->imageCopyMergeAlpha(
            $this->destinationImage,
            $watermark,
            $watermarkX,
            $watermarkY, 0, 0,
            $watermarkWidth,
            $watermarkHeight, 100
        );

        @imagedestroy($watermark);
        ++$this->manipulation;
        $this->sourceImage = $this->destinationImage;
        return $this;
    }

    /**
     * Apply a monochrome effect on the image.
     *
     * @return Image
     */
    public function monochrome()
    {
        return $this->applyFilter(IMG_FILTER_GRAYSCALE);
    }

    /**
     * Invert image colors.
     *
     * @return Image
     */
    public function negate()
    {
        return $this->applyFilter(IMG_FILTER_NEGATE);
    }

    /**
     * Alter the image brightness.
     *
     * @param int $level
     * @return Image
     */
    public function brightness($level)
    {
        return $this->applyFilter(IMG_FILTER_BRIGHTNESS, [$level]);
    }

    /**
     * Alter the image contrast.
     *
     * @param int $level
     * @return Image
     */
    public function contrast($level)
    {
        return $this->applyFilter(IMG_FILTER_CONTRAST, [$level]);
    }

    /**
     * Alter the image colors. Each value range from 0 to 255.
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $alpha
     * @return Image
     */
    public function colorize($red, $green, $blue, $alpha)
    {
        return $this->applyFilter(IMG_FILTER_COLORIZE, [$red, $green, $blue, $alpha]);
    }

    /**
     * Highlights image edges.
     *
     * @return Image
     */
    public function edgeDetect()
    {
        return $this->applyFilter(IMG_FILTER_EDGEDETECT);
    }

    /**
     * Apply an emboss effect on the image.
     *
     * @return Image
     */
    public function emboss()
    {
        return $this->applyFilter(IMG_FILTER_EMBOSS);
    }

    /**
     * Applies a blur effect on the image using the Gaussian method.
     *
     * @return Image
     */
    public function blurGaussian()
    {
        return $this->applyFilter(IMG_FILTER_GAUSSIAN_BLUR);
    }

    /**
     * Applies a blur effect on the image.
     *
     * @return Image
     */
    public function blur()
    {
        return $this->applyFilter(IMG_FILTER_SELECTIVE_BLUR);
    }

    /**
     * Applies a sketch effect on the image.
     *
     * @return Image
     */
    public function sketch()
    {
        return $this->applyFilter(IMG_FILTER_MEAN_REMOVAL);
    }

    /**
     * Applies a certain smooth level on the image.
     *
     * @param int $level
     * @return Image
     */
    public function smooth($level)
    {
        return $this->applyFilter(IMG_FILTER_SMOOTH, [$level]);
    }

    /**
     * Applies a pixelate effect on the image.
     *
     * @param int $block_size
     * @param bool|false $effect
     * @return Image
     */
    public function pixelate($block_size = 2, $effect = false)
    {
        return $this->applyFilter(IMG_FILTER_PIXELATE, [$block_size, $effect]);
    }

    /**
     * Retrieve the complete path to the source image.
     *
     * @return string
     */
    public function getSourcePath()
    {
        return $this->sourcePath;
    }

    /**
     * Applies a quality level (0-100) for any future JPEG image creations.
     *
     * @param int $quality
     */
    public function setJpegQuality($quality)
    {
        if ($quality < 0 || $quality > 100) {
            $quality = 60;
        }
        $this->jpegQuality = (int) $quality;
    }

    /**
     * Retrieve the current quality level (0-100) of a JPEG image.
     *
     * @return int
     */
    public function getJpegQuality()
    {
        return (int) $this->jpegQuality;
    }

    /**
     * Retrieve the number of transformations the resulting image resource
     * had.
     *
     * @return int
     */
    public function getNbManipulations()
    {
        return (int) $this->manipulation;
    }

    /**
     * Load an image specified by the path argument into a usable resource by
     * the GD2 imaging library. Make sure the file is valid and readable.
     *
     * @param string $path
     * @return resource
     * @throws ImageException
     */
    private function loadImage($path)
    {
        if (!is_readable($path)) {
            throw new ImageException(ImageException::ERR_FILE, $path);
        }

        $image = $this->loadResourceFromPath($path);
        if (!$image) {
            throw new ImageException(ImageException::ERR_CORRUPT, $path);
        }

        return $image;
    }

    /**
     * Tries to convert the specified file into a GD2 image resource based on
     * the file extension. Throws an exception if the extension is not
     * supported.
     *
     * @param string $path
     * @return resource
     * @throws ImageException
     */
    private function loadResourceFromPath($path)
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                return @imagecreatefromjpeg($path);
            case 'png':
                return @imagecreatefrompng($path);
            case 'gif':
                return @imagecreatefromgif($path);
            case 'wbmp':
                return @imagecreatefromwbmp($path);
            default:
                throw new ImageException(ImageException::ERR_FORMAT, $path);
        }
    }

    /**
     * Assigns the source path information (complete path, directory, filename
     * and extension).
     *
     * @param string $path
     */
    private function assignSource($path)
    {
        $this->sourcePath = $path;
        $this->sourceDirectory = pathinfo($path, PATHINFO_DIRNAME);
        $this->sourceFilename = pathinfo($path, PATHINFO_BASENAME);
        $this->sourceExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Assigns the destination path information (complete path, directory,
     * filename and extension).
     *
     * @param string $path
     */
    private function assignDestination($path)
    {
        $this->destinationDirectory = pathinfo($path, PATHINFO_DIRNAME);
        $this->setDestinationFilename(pathinfo($path, PATHINFO_BASENAME));
        $this->destinationExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Create an empty image of the specified width and height while keeping
     * alpha property enabling transparency.
     *
     * @param int $width
     * @param int $height
     */
    private function createDestinationImage($width, $height)
    {
        $this->destinationImage = imagecreatetruecolor($width, $height);
        imagealphablending($this->destinationImage, false);
        imagesavealpha($this->destinationImage, true);
    }

    /**
     * Applies a specified filter to the image.
     *
     * @param int $filter
     * @param array $specificArguments
     * @return $this
     */
    private function applyFilter($filter, $specificArguments = [])
    {
        $this->destinationImage = $this->sourceImage;
        $arguments = [
            $this->destinationImage, $filter
        ];
        $arguments = array_merge($arguments, $specificArguments);
        call_user_func_array("imagefilter", $arguments);
        ++$this->manipulation;
        $this->sourceImage = $this->destinationImage;
        return $this;
    }

    /**
     * Modification of imageCopyMerge allowing transparency while copying
     * image resource.
     *
     * @author thciobanu
     * @see http://php.net/manual/en/function.imagecopymerge.php
     *
     * @param resource $dst_im
     * @param resource $src_im
     * @param int $dst_x
     * @param int $dst_y
     * @param int $src_x
     * @param int $src_y
     * @param int $src_w
     * @param int $src_h
     * @param int $pct
     * @param null $trans
     * @return bool
     */
    private function imageCopyMergeAlpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct, $trans = NULL)
    {
        $dst_w = imagesx($dst_im);
        $dst_h = imagesy($dst_im);

        // bounds checking
        $src_x = max($src_x, 0);
        $src_y = max($src_y, 0);
        $dst_x = max($dst_x, 0);
        $dst_y = max($dst_y, 0);

        if ($dst_x + $src_w > $dst_w)
            $src_w = $dst_w - $dst_x;

        if ($dst_y + $src_h > $dst_h)
            $src_h = $dst_h - $dst_y;

        for ($x_offset = 0; $x_offset < $src_w; $x_offset++) {
            for ($y_offset = 0; $y_offset < $src_h; $y_offset++) {
                // get source & dest color
                $srccolor = imagecolorsforindex($src_im, imagecolorat($src_im, $src_x + $x_offset, $src_y + $y_offset));
                $dstcolor = imagecolorsforindex($dst_im, imagecolorat($dst_im, $dst_x + $x_offset, $dst_y + $y_offset));

                // apply transparency
                if (is_null($trans) || ($srccolor !== $trans)) {
                    $src_a = $srccolor['alpha'] * $pct / 100;

                    // blend
                    $src_a = 127 - $src_a;
                    $dst_a = 127 - $dstcolor['alpha'];
                    $dst_r = ($srccolor['red'] * $src_a + $dstcolor['red'] * $dst_a * (127 - $src_a) / 127) / 127;
                    $dst_g = ($srccolor['green'] * $src_a + $dstcolor['green'] * $dst_a * (127 - $src_a) / 127) / 127;
                    $dst_b = ($srccolor['blue'] * $src_a + $dstcolor['blue'] * $dst_a * (127 - $src_a) / 127) / 127;
                    $dst_a = 127 - ($src_a + $dst_a * (127 - $src_a) / 127);
                    $color = imagecolorallocatealpha($dst_im, $dst_r, $dst_g, $dst_b, $dst_a);

                    // paint
                    if (!imagesetpixel($dst_im, $dst_x + $x_offset, $dst_y + $y_offset, $color))
                        return false;

                    imagecolordeallocate($dst_im, $color);
                }
            }
        }

        return true;
    }
}