<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Views\PugEngine;
use Zephyrus\Application\Views\PugView;

class ViewBuilderTest extends TestCase
{
    public function testViewRenderFromString()
    {
        $engine = new PugEngine(['cache_enabled' => false]);
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
        $this->expectException(\RuntimeException::class);
        $view = new PugView("dsfdfg");
        $view->render();
    }

    public function testViewRenderWithMoneyFormat()
    {
        $engine = new PugEngine(['cache_enabled' => false]);
        $output = $engine->renderFromString('p Example #{item.name} is #{format(\'money\', item.price)}', [
            'item' => ['name' => 'Bob Lewis', 'price' => 12.30]
        ]);
        self::assertEquals('<p>Example Bob Lewis is 12,30 $</p>', $output);
    }

    public function testViewRenderWithConfig()
    {
        $engine = new PugEngine(['cache_enabled' => false]);
        $output = $engine->renderFromString('p Example is #{config(\'application\', \'project\')}');
        self::assertEquals('<p>Example is zephyrus</p>', $output);
    }

    public function testViewRenderWithMoneyFormatArgs()
    {
        $engine = new PugEngine(['cache_enabled' => false]);
        $output = $engine->renderFromString('p Example #{item.name} is #{format(\'money\', item.price, 3)}', [
            'item' => ['name' => 'Bob Lewis', 'price' => 12.30]
        ]);
        self::assertEquals('<p>Example Bob Lewis is 12,300 $</p>', $output);
    }

    public function testAddFunction()
    {
        $engine = new PugEngine(['cache_enabled' => false]);
        $engine->addFunction('test', function($amount) {
            return $amount * 2;
        });
        $output = $engine->renderFromString('p Example #{test(item.price)}', ['item' => ['price' => 4]]);
        self::assertEquals('<p>Example 8</p>', $output);
    }
}