<?php namespace Models\Utilities;

use stdClass;

class ComposerPackage
{
    private const COMPOSER_FILE_PATH = ROOT_DIR . '/composer.lock';

    public static function getVersion(string $packageName): ?string
    {
        $versions = self::getVersions();
        return $versions[$packageName] ?? null;
    }

    public static function getVersions(): array
    {
        $data = [];
        $packages = json_decode(file_get_contents(self::COMPOSER_FILE_PATH))->packages;
        foreach ($packages as $package) {
            $data[$package->name] = $package->version;
        }
        return $data;
    }

    public static function getPackage(string $packageName): ?stdClass
    {
        $packages = self::getPackages();
        return $packages[$packageName] ?? null;
    }

    public static function getPackages(): array
    {
        $data = [];
        $packages = json_decode(file_get_contents(self::COMPOSER_FILE_PATH))->packages;
        foreach ($packages as $package) {
            $data[$package->name] = $package;
        }
        return $data;
    }
}
