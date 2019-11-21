<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Localization;
use Zephyrus\Exceptions\LocalizationException;

class LocalizationTest extends TestCase
{
    public function testLocalize()
    {
        copy(ROOT_DIR . '/locale/routes.json', ROOT_DIR . '/locale/fr_CA/routes.json');
        Localization::getInstance()->start();
        self::assertEquals("Le courriel est invalide", Localization::getInstance()->localize("messages.errors.invalid_email"));
        self::assertEquals("L'utilisateur [bob] a été ajouté avec succès", Localization::getInstance()->localize("messages.success.add_user", ["bob"]));
        self::assertEquals("not.found", Localization::getInstance()->localize("not.found"));
        self::assertEquals("messages.success.bob", Localization::getInstance()->localize("messages.success.bob"));
        self::assertEquals("/connexion", Localization::getInstance()->localize("routes.login"));
        //unlink(ROOT_DIR . '/locale/fr_CA/routes.json');
    }

    /**
     * @depends testLocalize
     */
    public function testExceptionAlreadyStarted()
    {
        try {
            Localization::getInstance()->start();
        } catch (\RuntimeException $e) {
            self::assertEquals("Localization environment already started", $e->getMessage());
        }
    }

    /**
     * @depends testExceptionAlreadyStarted
     */
    public function testErrorInJson()
    {
        copy(ROOT_DIR . '/locale/broken.json', ROOT_DIR . '/locale/fr_CA/broken.json');
        try {
            Localization::getInstance()->generate(true);
            self::assertTrue(false); // should not reach this point
        } catch (LocalizationException $e) {
            self::assertEquals(JSON_ERROR_SYNTAX, $e->getCode());
            self::assertEquals("broken.json", basename($e->getJsonFile()));
        }
        unlink(ROOT_DIR . '/locale/fr_CA/broken.json');
    }

    /**
     * @depends testErrorInJson
     */
    public function testError2InJson()
    {
        copy(ROOT_DIR . '/locale/broken2.json', ROOT_DIR . '/locale/fr_CA/broken2.json');
        try {
            Localization::getInstance()->generate(true);
            self::assertTrue(false); // should not reach this point
        } catch (LocalizationException $e) {
            self::assertEquals(JSON_ERROR_SYNTAX, $e->getCode());
        }
        unlink(ROOT_DIR . '/locale/fr_CA/broken2.json');
    }

    /**
     * @depends testError2InJson
     */
    public function testPreparationAfterCacheClear()
    {
        $this->removeDirectory(ROOT_DIR . '/locale/cache');
        Localization::getInstance()->generate();
        self::assertEquals("/connexion", Localization::getInstance()->localize("routes.login"));
    }

    /**
     * @depends testPreparationAfterCacheClear
     */
    public function testCacheOutdated()
    {
        // Simulate json changes
        unlink(ROOT_DIR . '/locale/fr_CA/routes.json');
        copy(ROOT_DIR . '/locale/routes2.json', ROOT_DIR . '/locale/fr_CA/routes.json');
        Localization::getInstance()->generate();
        self::assertEquals("/connexion", Localization::getInstance()->localize("routes.login")); // next reload
        $this->removeDirectory(ROOT_DIR . '/locale/cache');
        unlink(ROOT_DIR . '/locale/fr_CA/routes.json');
    }

    public function testInstalledLanguages()
    {
        $languages = Localization::getInstalledLanguages();
        self::assertEquals(2, count($languages));
        self::assertEquals('en', $languages[0]->lang);
        self::assertEquals('CA', $languages[0]->country);
        self::assertEquals('fr', $languages[1]->lang);
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
