<?php

declare(strict_types=1);
namespace Tests ;

use PHPUnit\Framework\TestCase ;
use stdClass;
use Core\Cache\{Common\PhpCacheItem,ApcuCachePool } ;
 

final class ApcuCacheTest extends TestCase
{
    

    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('apcu')) {
            self::markTestSkipped('Required extension "apcu" is not loaded');
        }

        if (!ini_get('apc.enable_cli')) {
            self::markTestSkipped('APC is installed but not enabled. Enable with "apc.enable_cli=1" from php.ini. Skipping.');
        }
    }

    public function dataProvider(): array
    {
        $object = new stdClass();
        $object->test_field = 'test_value';
        return [
            'integer' => ['test_integer', 1],
            'double' => ['test_double', 1.1],
            'string' => ['test_string', 'a'],
            'boolean_true' => ['test_boolean_true', true],
            'boolean_false' => ['test_boolean_false', false],
            'object' => ['test_object', $object],
            'array' => ['test_array', ['test_key' => 'test_value']],
            'null' => ['test_null', null],
            'supported_key_characters' => ['AZaz09_.', 'b'],
            '64_characters_key_max' => ['bVGEIeslJXtDPrtK.hgo6HL25_.1BGmzo4VA25YKHveHh7v9tUP8r5BNCyLhx4zy', 'c'],
            'string_with_number_key' => ['111', 11],
            'string_with_number_key_1' => ['022', 22],
        ];
    }

    /**
     * @covers
     * @dataProvider dataProvider
     *
     * @param $key
     * @param $value
     *
     * @throws InvalidArgumentException
     */
    public function testSet($key, $value): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->assertTrue(ApcuCachePool::storeItemInCache(new PhpCacheItem($key, $value)));
        }
    }

    /**
     * @covers
     * @dataProvider dataProvider
     *
     * @param $key
     * @param $value
     *
     * @throws InvalidArgumentException
     */
    public function testGet($key, $value): void
    {
        ApcuCachePool::storeItemInCache(new PhpCacheItem($key, $value));
        $valueFromCache = ApcuCachePool::fetchObjectFromCache($key);

        $this->assertEquals($value, $valueFromCache);
    }


}
