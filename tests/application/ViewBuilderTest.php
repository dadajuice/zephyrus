<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\ViewBuilder;

class ViewBuilderTest extends TestCase
{
    public function testViewRenderFromString()
    {
        $view = ViewBuilder::getInstance()->buildFromString('p=item.name');
        $output = $view->render(['item' => ['name' => 'Bob Lewis', 'price' => 12.30]]);
        self::assertEquals('<p>Bob Lewis</p>', $output);
    }

    public function testViewRenderFromFile()
    {
        $view = ViewBuilder::getInstance()->build('test');
        $output = $view->render(['item' => ['name' => 'Bob Lewis', 'price' => 12.30]]);
        self::assertEquals('<p>Bob Lewis</p>', $output);
    }

    /**
     * @expectedException \Exception
     */
    public function testViewRenderInvalidFromFile()
    {
        ViewBuilder::getInstance()->build('dsfdfg');
    }

    public function testViewRenderWithMoneyFormat()
    {
        $view = ViewBuilder::getInstance()->buildFromString('p Example #{item.name} is #{format(\'money\', item.price)}');
        $output = $view->render(['item' => ['name' => 'Bob Lewis', 'price' => 12.30]]);
        self::assertEquals('<p>Example Bob Lewis is 12,30 $</p>', $output);
    }

    public function testViewRenderWithMoneyFormatArgs()
    {
        $view = ViewBuilder::getInstance()->buildFromString('p Example #{item.name} is #{format(\'money\', item.price, 3)}');
        $output = $view->render(['item' => ['name' => 'Bob Lewis', 'price' => 12.30]]);
        self::assertEquals('<p>Example Bob Lewis is 12,300 $</p>', $output);
    }

    public function testAddFunction()
    {
        $builder = ViewBuilder::getInstance();
        $builder->addFunction('test', function($amount) {
            return $amount * 2;
        });
        $view = ViewBuilder::getInstance()->buildFromString('p Example #{test(item.price)}');
        $output = $view->render(['item' => ['price' => 4]]);
        self::assertEquals('<p>Example 8</p>', $output);
    }
}