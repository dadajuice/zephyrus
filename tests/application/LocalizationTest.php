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
        self::assertEquals('fr_CA', Localization::getInstance()->getLoadedLocale());
        self::assertEquals("Le courriel est invalide", Localization::getInstance()->localize("messages.errors.invalid_email"));
        self::assertEquals("L'utilisateur [bob] a été ajouté avec succès", Localization::getInstance()->localize("messages.success.add_user", ["bob"]));
        self::assertEquals("not.found", Localization::getInstance()->localize("not.found"));
        self::assertEquals("messages.success.bob", Localization::getInstance()->localize("messages.success.bob"));
        self::assertEquals("/connexion", Localization::getInstance()->localize("routes.login"));
        self::assertEquals("L'utilisateur [martin] a été ajouté avec succès", localize("messages.success.add_user", "martin"));
        self::assertEquals("L'utilisateur [martin] a été ajouté avec succès", __("L'utilisateur [%s] a été ajouté avec succès", 'martin'));
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
    public function testError3InJson()
    {
        copy(ROOT_DIR . '/locale/invalid_words.json', ROOT_DIR . '/locale/fr_CA/invalid_words.json');
        try {
            Localization::getInstance()->generate(true);
            self::assertTrue(false); // should not reach this point
        } catch (LocalizationException $e) {
            self::assertEquals(LocalizationException::ERROR_RESERVED_WORD, $e->getCode());
            self::assertEquals("Cannot use the detected PHP reserved word [private] as localize key", $e->getMessage());
        }
        unlink(ROOT_DIR . '/locale/fr_CA/invalid_words.json');
    }

    /**
     * @depends testError3InJson
     */
    public function testError4InJson()
    {
        copy(ROOT_DIR . '/locale/invalid_words2.json', ROOT_DIR . '/locale/fr_CA/invalid_words2.json');
        try {
            Localization::getInstance()->generate(true);
            self::assertTrue(false); // should not reach this point
        } catch (LocalizationException $e) {
            self::assertEquals(LocalizationException::ERROR_RESERVED_WORD, $e->getCode());
            self::assertEquals("Cannot use the detected PHP reserved word [__line__] as localize key", $e->getMessage());
        }
        unlink(ROOT_DIR . '/locale/fr_CA/invalid_words2.json');
    }

    /**
     * @depends testError4InJson
     */
    public function testError5InJson()
    {
        copy(ROOT_DIR . '/locale/invalid_words3.json', ROOT_DIR . '/locale/fr_CA/invalid_words3.json');
        try {
            Localization::getInstance()->generate(true);
            self::assertTrue(false); // should not reach this point
        } catch (LocalizationException $e) {
            self::assertEquals(LocalizationException::ERROR_INVALID_NAMING, $e->getCode());
            self::assertEquals("Cannot use the word [123] as localize key since it doesn't respect the PHP constant definition", $e->getMessage());
        }
        unlink(ROOT_DIR . '/locale/fr_CA/invalid_words3.json');
    }

    /**
     * @depends testError5InJson
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

    public function testInvalidLocalization()
    {
        // Shall not break
        $result = Localization::getInstance()->localize('bob/3');
        self::assertEquals('bob/3', $result);
    }

    public function testInstalledLanguages()
    {
        $languages = Localization::getInstalledLanguages();
        self::assertEquals(2, count($languages));
        self::assertEquals('en', $languages[0]->lang_code);
        self::assertEquals('CA', $languages[0]->country_code);
        self::assertEquals('fr', $languages[1]->lang_code);
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
