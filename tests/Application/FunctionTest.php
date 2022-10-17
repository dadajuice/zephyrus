<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;

class FunctionTest extends TestCase
{
    public function testObjectToArray()
    {
        $object = (object) [
            'name' => "Bob",
            "age" => 23
        ];
        $array = objectToArray($object);
        self::assertEquals(['name' => "Bob", "age" => 23], $array);
    }
}
