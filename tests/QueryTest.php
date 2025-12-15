<?php
/*
* smolHTTP
* https://github.com/joby-lol/smol-http
* (c) 2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\Smol\URL;

use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

class QueryTest extends TestCase
{
    public function testEmptyQuery()
    {
        $query = new Query([]);
        $this->assertEquals('', (string)$query);
    }

    public function testIntegerKey()
    {
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Query keys must be strings');
        new Query([1 => 'value']);
    }

    public function testArrayValue()
    {
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Invalid query value type: array');
        new Query(['key' => []]);
    }

    public function testNullValue()
    {
        // null values should simply be omitted
        $query = new Query(['key' => null]);
        $this->assertEquals('', (string)$query);
        $this->assertEquals([], $query->args);
    }

    public function testBoolValue()
    {
        $query = new Query(['key' => true]);
        $this->assertEquals('key=1', (string)$query);
        $this->assertEquals('1', $query->args['key']);
        $this->assertEquals('1', $query->get('key'));
        $this->assertTrue($query->getBool('key'));
        $query = new Query(['key' => false]);
        $this->assertEquals('key=0', (string)$query);
        $this->assertEquals('0', $query->args['key']);
        $this->assertEquals('0', $query->get('key'));
        $this->assertFalse($query->getBool('key'));
        $this->assertNull($query->get('non-existent'));
    }

    public function testStringValue()
    {
        $query = new Query(['key' => 'value']);
        $this->assertEquals('key=value', (string)$query);
        $this->assertEquals('value', $query->args['key']);
        $this->assertEquals('value', $query->get('key'));
    }

    public function testIntValue()
    {
        $query = new Query(['key' => 2]);
        $this->assertEquals('key=2', (string)$query);
        $this->assertEquals('2', $query->args['key']);
        $this->assertEquals('2', $query->get('key'));
        $this->assertEquals(2, $query->getInt('key'));
    }

    public function testFloatValue()
    {
        $query = new Query(['key' => 2.5]);
        $this->assertEquals('key=2.5', (string)$query);
        $this->assertEquals('2.5', $query->args['key']);
        $this->assertEquals('2.5', $query->get('key'));
        $this->assertEquals(2.5, $query->getFloat('key'));
    }

    public function testGetAndRequire()
    {
        $query = new Query(['key' => 'value']);
        // happy path with getting existing values
        $this->assertEquals('value', $query->get('key'));
        $this->assertEquals('value', $query->require('key'));
        // getting non-existent values should return default value
        $this->assertNull($query->get('non-existent'));
        $this->assertEquals('default', $query->get('non-existent', 'default'));
        // require should throw exception if value is missing
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Missing required query string: non-existent');
        $query->require('non-existent');
    }

    public function testHas()
    {
        $query = new Query(['key' => 'value']);
        $this->assertTrue($query->has('key'));
        $this->assertFalse($query->has('non-existent'));
    }

    public function testOrderingNormalization()
    {
        $query = new Query(['key2' => 'value2', 'key1' => 'value1']);
        $this->assertEquals('key1=value1&key2=value2', (string)$query);
    }

    public function testWithArg()
    {
        $query = new Query(['key' => 'value']);
        $query2 = $query->withArg('key2', 'value2');
        $this->assertEquals('key=value&key2=value2', (string)$query2);
        $this->assertEquals('value', $query->args['key']);
        $this->assertEquals('value2', $query2->args['key2']);
        $this->assertEquals('value', $query2->get('key'));
        $this->assertEquals('value2', $query2->get('key2'));
        $this->assertNull($query->get('key2'));
    }

    public function testWithoutArg()
    {
        $query = new Query(['key' => 'value']);
        $query2 = $query->withoutArg('key');
        $this->assertEquals('key=value', (string)$query);
        $this->assertEquals('', (string)$query2);
        $this->assertNull($query2->args['key']);
        $this->assertNull($query2->get('key'));
    }

    public function testWithArgs()
    {
        $query = new Query(['key' => 'value']);
        $query2 = $query->withArgs(['key2' => 'value2', 'key3' => 'value3']);
        $this->assertEquals('key=value&key2=value2&key3=value3', (string)$query2);
        $this->assertEquals('value', $query->args['key']);
        $this->assertEquals('value2', $query2->args['key2']);
        $this->assertEquals('value3', $query2->args['key3']);
        $this->assertEquals('value', $query2->get('key'));
        $this->assertEquals('value2', $query2->get('key2'));
        $this->assertEquals('value3', $query2->get('key3'));
        $this->assertNull($query->get('key2'));
        $this->assertNull($query->get('key3'));
    }

    public function testWithoutArgs()
    {
        $query = new Query(['key' => 'value', 'key2' => 'value2']);
        $query2 = $query->withoutArgs(['key']);
        $this->assertEquals('key2=value2', (string)$query2);
        $this->assertEquals('value2', $query2->args['key2']);
        $this->assertEquals('value2', $query2->get('key2'));
        $this->assertNull($query2->get('key'));
    }

    public function testRequire()
    {
        $query = new Query(['key' => 'value']);
        $this->assertEquals('value', $query->require('key'));
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Missing required query string: non-existent');
        $query->require('non-existent');
    }

    public function testGetInt()
    {
        $query = new Query(['int' => '1', 'float' => '1.5']);
        $this->assertEquals(1, $query->getInt('int'));
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Invalid query integer: float');
        $query->getInt('float');
    }

    public function testGetFloat()
    {
        $query = new Query(['string' => 'abc', 'float' => '1.5']);
        $this->assertEquals(1.5, $query->getFloat('float'));
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Invalid query float: string');
        $query->getFloat('string');
    }

    public function testGetBool()
    {
        $query = new Query(['true' => '1', 'false' => '0']);
        $this->assertTrue($query->getBool('true'));
        $this->assertFalse($query->getBool('false'));
        $this->assertNull($query->getBool('non-existent'));
    }

    public function testRequireInt()
    {
        $query = new Query(['int' => '1', 'float' => '1.5']);
        $this->assertEquals(1, $query->requireInt('int'));
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Invalid query integer: float');
        $query->requireInt('float');
    }

    public function testRequireFloat()
    {
        $query = new Query(['string' => 'abc', 'float' => '1.5']);
        $this->assertEquals(1.5, $query->requireFloat('float'));
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Invalid query float: string');
        $query->requireFloat('string');
    }

    public function testRequireBool()
    {
        $query = new Query(['true' => '1', 'false' => '0', 'string' => 'abc']);
        $this->assertTrue($query->requireBool('true'));
        $this->assertFalse($query->requireBool('false'));
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Invalid query boolean: string');
        $query->requireBool('string');
    }

    public function testInvalidNonArrayValues()
    {
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Invalid query value type: object');
        new Query(['key' => new stdClass()]);
    }

    public function testStringableValues()
    {
        $query = new Query(['key' => new class implements Stringable {
            function __toString(): string
            {
                return 'value';
            }
        }]);
        $this->assertEquals('key=value', (string)$query);
        $this->assertIsString($query->args['key']);
    }
}
