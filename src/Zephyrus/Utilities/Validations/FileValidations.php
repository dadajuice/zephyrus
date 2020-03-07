<?php namespace Zephyrus\Utilities\Validations;

use Zephyrus\Utilities\FileSystem\File;

/**
 * Trait FileValidations
 * @package Zephyrus\Utilities\Validations
 *
 * These validations concern uploaded file stored in $_FILES super global. Each $data
 * variable passed has argument must be the $_FILES['name'] details. A correct
 * uploaded file should have the following keys : error, tmp_name, type, name and
 * size. It is recommended to always use the "isUpload" validation before.
 *
 * error: will contain an PHP error code if something went wrong with the upload.
 * tmp_name: filepath referring the temporary path on server.
 * type: mime type sent to the server (shall not be blindly trusted).
 * name: file name sent to the server (original filename the user uploaded).
 * size: size sent to the server (shall not be blindly trusted).
 */
trait FileValidations
{
    /**
     * Validates if the given data is a correctly formed uploaded file as the
     * $_FILES super global is formed. Makes sure there are all needed keys
     * and no upload error.
     *
     * @param $data
     * @return bool
     */
    public static function isUpload($data): bool
    {
        $neededKeys = ['error', 'tmp_name', 'type', 'name', 'size'];
        $missingKeys = array_diff_key(array_flip($neededKeys), $data);
        if (!empty($missingKeys)) {
            return false;
        }
        return $data['error'] == UPLOAD_ERR_OK;
    }

    /**
     * Validates that the REAL file mime type (using the finfo PHP library) is
     * correct according to a specified list.
     *
     * @param $data
     * @param array $allowedMimeTypes
     * @return bool
     */
    public static function isMimeTypeAllowed($data, array $allowedMimeTypes): bool
    {
        if (!isset($data['tmp_name'])) {
            return false;
        }
        $mime = (new File($data['tmp_name']))->getMimeType();
        return in_array($mime, $allowedMimeTypes);
    }

    /**
     * Validates that the REAL image mime type (using exif PHP library) is
     * correct according to a specific list.
     *
     * @param $data
     * @param array $allowedMimeTypes
     * @return bool
     */
    public static function isImageMimeTypeAllowed($data, array $allowedMimeTypes): bool
    {
        if (!isset($data['tmp_name'])) {
            return false;
        }
        $imageType = exif_imagetype($data['tmp_name']);
        $mime = image_type_to_mime_type($imageType);
        if (!$imageType || !in_array($mime, $allowedMimeTypes)) {
            return false;
        }
        return true;
    }

    /**
     * Validates that the given extension (based on the original file name) is
     * correct according to a specified list written in lowercase. Will
     * automatically lowercase the given extension.
     *
     * @param $data
     * @param array $allowedExtensions (in lowercase)
     * @return bool
     */
    public static function isExtensionAllowed($data, array $allowedExtensions): bool
    {
        if (!isset($data['name'])) {
            return false;
        }
        $info = pathinfo($data['name']);
        return in_array(strtolower($info['extension']), $allowedExtensions);
    }

    /**
     * Validates that the REAL file size on the server is lower than the
     * specified maximum.
     *
     * @param $data
     * @param $maxSizeInMb
     * @return bool
     */
    public static function isFileSizeCompliant($data, $maxSizeInMb): bool
    {
        if (!isset($data['tmp_name'])) {
            return false;
        }
        return (filesize($data['tmp_name']) / 1024 / 1024) <= $maxSizeInMb;
    }

    /**
     * Validates that an uploaded file that should be considered as an image is
     * indeed a correct image. It does do by trying to get the image dimensions
     * using the GD library. To make absolutely sure the image does not hide
     * any malicious code, developers should reconstruct the image after all
     * validations pass.
     *
     * @param $data
     * @return bool
     */
    public static function isImageAuthentic($data): bool
    {
        if (!isset($data['tmp_name'])) {
            return false;
        }
        $info = getimagesize($data['tmp_name']);
        if (empty($info) || $info[0] == "" || $info[1] == "") {
            return false;
        }
        return true;
    }
}
