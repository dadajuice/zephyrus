<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\ViewBuilder;

class ViewBuilderTest extends TestCase
{
    public function testViewRenderFromString()
    {
        $view = ViewBuilder::getInstance()->buildFromString('p Example #{item.name}');
        $output = $view->render(['item' => ['name' => 'Bob Lewis', 'price' => 12.30]]);
        self::assertEquals('<p>Example Bob Lewis</p>', $output);
    }
}