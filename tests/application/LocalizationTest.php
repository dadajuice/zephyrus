<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Localization;
use Zephyrus\Application\Session;
use Zephyrus\Exceptions\LocalizationException;

class LocalizationTest extends TestCase
{
    public function testLocalize()
    {
        Session::getInstance()->start();
        Localization::getInstance()->generate();
        copy(ROOT_DIR . '/locale/routes.json', ROOT_DIR . '/locale/fr_CA/routes.json');
        Localization::getInstance()->generate();
        self::assertEquals("Le courriel est invalide", Localization::getInstance()->localize("messages.errors.invalid_email"));
        self::assertEquals("L'utilisateur [bob] a été ajouté avec succès", Localization::getInstance()->localize("messages.success.add_user", ["bob"]));
        self::assertEquals("not.found", Localization::getInstance()->localize("not.found"));
        self::assertEquals("messages.success.bob", Localization::getInstance()->localize("messages.success.bob"));
        self::assertEquals("routes.login", Localization::getInstance()->localize("routes.login")); // available next reload
        unlink(ROOT_DIR . '/locale/fr_CA/routes.json');
        //$this->removeDirectory(ROOT_DIR . '/locale/cache');
        Session::kill();
    }

    public function testErrorInJson()
    {
        Session::getInstance()->start();
        copy(ROOT_DIR . '/locale/broken.json', ROOT_DIR . '/locale/fr_CA/broken.json');
        try {
            Localization::getInstance()->generate(true);
            self::assertTrue(false); // should not reach this point
        } catch (LocalizationException $e) {
            self::assertEquals(JSON_ERROR_SYNTAX, $e->getCode());
            self::assertEquals("broken.json", basename($e->getJsonFile()));
        }
        unlink(ROOT_DIR . '/locale/fr_CA/broken.json');
        Session::kill();
    }

    public function testError2InJson()
    {
        Session::getInstance()->start();
        copy(ROOT_DIR . '/locale/broken2.json', ROOT_DIR . '/locale/fr_CA/broken2.json');
        try {
            Localization::getInstance()->generate(true);
            self::assertTrue(false); // should not reach this point
        } catch (LocalizationException $e) {
            self::assertEquals(JSON_ERROR_SYNTAX, $e->getCode());
        }
        unlink(ROOT_DIR . '/locale/fr_CA/broken2.json');
        Session::kill();
    }

    public function testChangeLocale()
    {
        Session::getInstance()->start();
        Localization::getInstance()->generate();
        Localization::getInstance()->changeLocale("en_CA");
        self::assertEquals("en_CA", Session::getInstance()->read("__zephyrus_lang", "fail"));
        $this->removeDirectory(ROOT_DIR . '/locale/cache');
        Session::kill();
    }

    private function removeDirectory($path)
    {
        $files = recursiveGlob($path . '/*');
        $directories = [];
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                $directories[] = $file;
            }
        }
        for ($i = count($directories) - 1; $i >= 0; --$i) {
            rmdir($directories[$i]);
        }
        rmdir($path);
    }
}
