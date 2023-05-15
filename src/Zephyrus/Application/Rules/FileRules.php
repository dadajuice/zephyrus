<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;

trait FileRules
{
    public static function fileUpload(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isUpload'], $errorMessage, "fileUpload");
    }

    public static function fileMimeType(string $errorMessage = "", array $allowedMimeTypes = ['image/gif', 'image/jpeg', 'image/png']): Rule
    {
        return new Rule(function ($data) use ($allowedMimeTypes) {
            return Validation::isMimeTypeAllowed($data, $allowedMimeTypes);
        }, $errorMessage, "fileMimeType");
    }

    public static function fileExtension(string $errorMessage = "", array $allowedExtensions = ['gif', 'jpeg', 'png', 'jpg', 'pdf', 'txt']): Rule
    {
        return new Rule(function ($data) use ($allowedExtensions) {
            return Validation::isExtensionAllowed($data, $allowedExtensions);
        }, $errorMessage, "fileExtension");
    }

    public static function fileSize(string $errorMessage = "", $maxSizeInMb = 8): Rule
    {
        return new Rule(function ($data) use ($maxSizeInMb) {
            return Validation::isFileSizeCompliant($data, $maxSizeInMb);
        }, $errorMessage, "fileSize");
    }

    public static function imageMimeType(string $errorMessage = "", array $allowedMimeTypes = ['image/gif', 'image/jpeg', 'image/png']): Rule
    {
        return new Rule(function ($data) use ($allowedMimeTypes) {
            return Validation::isImageMimeTypeAllowed($data, $allowedMimeTypes);
        }, $errorMessage, "imageMimeType");
    }

    public static function imageAuthentic(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isImageAuthentic'], $errorMessage, "imageAuthentic");
    }
}
