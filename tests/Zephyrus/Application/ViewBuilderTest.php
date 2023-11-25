<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zephyrus\Application\Views\PugEngine;
use Zephyrus\Application\Views\PugView;

class ViewBuilderTest extends TestCase
{
    public function testViewRenderFromString()
    {
        $engine = $this->buildPugEngine();
        $output = $engine->renderFromString('p=item.name', ['item' => ['name' => 'Bob Lewis', 'price' => 12.30]]);
        self::assertEquals('<p>Bob Lewis</p>', $output);
    }

    public function testViewRenderFromFile()
    {
        $view = new PugView("test");
        $output = $view->render(['item' => ['name' => 'Bob Lewis', 'price' => 12.30]]);
        self::assertEquals('<p>Bob Lewis</p>', $output->getContent());
    }

    public function testViewRenderInvalidFromFile()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The specified view file [dsfdfg] is not available (not readable or does not exists)");
        $view = new PugView("dsfdfg");
        $view->render();
    }

    public function testViewRenderWithMoneyFormat()
    {
        $engine = $this->buildPugEngine();
        $output = $engine->renderFromString('p Example #{item.name} is #{format(\'money\', item.price)}', [
            'item' => ['name' => 'Bob Lewis', 'price' => 12.30]
        ]);
        self::assertEquals('<p>Example Bob Lewis is 12,30 $</p>', $output);
    }

    public function testViewRenderWithConfig()
    {
        $engine = $this->buildPugEngine();
        $output = $engine->renderFromString('p Example is #{config(\'application\', \'project\')}');
        self::assertEquals('<p>Example is zephyrus</p>', $output);
    }

    public function testViewRenderWithMoneyFormatArgs()
    {
        $engine = $this->buildPugEngine();
        $output = $engine->renderFromString('p Example #{item.name} is #{format(\'money\', item.price, 3)}', [
            'item' => ['name' => 'Bob Lewis', 'price' => 12.30]
        ]);
        self::assertEquals('<p>Example Bob Lewis is 12,300 $</p>', $output);
    }

    public function testShare()
    {
        $engine = $this->buildPugEngine();
        $engine->share('test', function($amount) {
            return $amount * 2;
        });
        $output = $engine->renderFromString('p Example #{test(item.price)}', ['item' => ['price' => 4]]);
        self::assertEquals('<p>Example 8</p>', $output);
    }

    private function buildPugEngine(): PugEngine
    {
        return new PugEngine(['cache_enabled' => false]);
    }
}