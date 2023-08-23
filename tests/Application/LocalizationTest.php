<?php namespace Zephyrus\Tests\Application;

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
        self::assertEquals('fr_CA', Localization::getInstance()->getLoadedLanguage()->locale);
        self::assertEquals('America/Montreal', date_default_timezone_get());
        self::assertEquals("Le courriel est invalide", Localization::getInstance()->localize("messages.errors.invalid_email"));
        self::assertEquals("Email is invalid", Localization::getInstance()->localize("en_CA.messages.errors.invalid_email"));
        self::assertEquals("L'utilisateur [bob] a été ajouté avec succès", Localization::getInstance()->localize("messages.success.add_user", ["bob"]));
        self::assertEquals("not.found", Localization::getInstance()->localize("not.found"));
        self::assertEquals("messages.success.bob", Localization::getInstance()->localize("messages.success.bob"));
        self::assertEquals("/connexion", Localization::getInstance()->localize("routes.login"));
        self::assertEquals("/admin", Localization::getInstance()->localize("routes.administration")); // subfolder test
        self::assertEquals("L'utilisateur [martin] a été ajouté avec succès", localize("messages.success.add_user", "martin"));
        self::assertEquals("L'utilisateur [martin] a été ajouté avec succès", __("L'utilisateur [%s] a été ajouté avec succès", 'martin'));
        self::assertEquals("/no/key/3", Localization::getInstance()->localize("/no/%s/{id}", ['id' => 3, 'key']));

        // Rest should be english
        Localization::getInstance()->changeLanguage("en_CA");
        self::assertEquals("Email is invalid", Localization::getInstance()->localize("messages.errors.invalid_email"));
        self::assertEquals("Password does not respect established constraints", Localization::getInstance()->localize("messages.errors.invalid_password"));

        // Back to normal
        Localization::getInstance()->changeLanguage("fr_CA");
        self::assertEquals("Le courriel est invalide", Localization::getInstance()->localize("messages.errors.invalid_email"));
        self::assertEquals("Le mot de passe ne respecte pas les contraintes", Localization::getInstance()->localize("messages.errors.invalid_password"));

        $reservedKeywords = [
            'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'clone',
            'const', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare',
            'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally',
            'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once',
            'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private',
            'protected', 'public', 'require', 'require_once', 'return', 'static', 'throw', 'trait', 'try',
            'unset', 'use', 'var', 'while', 'xor', 'yield'
        ];
        foreach ($reservedKeywords as $w) {
            self::assertEquals('a', localize("test.$w"));
        }
    }

    /**
     * @depends testLocalize
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
            self::assertEquals("Cannot use the word [123] as localize key since it doesn't respect the PHP constant definition.", $e->getMessage());
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
        $languages = Localization::getInstance()->getInstalledLanguages();
        self::assertEquals(2, count($languages));
        self::assertEquals('en', $languages['en_CA']->lang_code);
        self::assertEquals('CA', $languages['en_CA']->country_code);
        self::assertEquals('fr', $languages['fr_CA']->lang_code);
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
