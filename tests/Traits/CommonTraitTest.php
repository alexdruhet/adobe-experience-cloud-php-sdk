<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 18/04/2018
 * Time: 16:16
 */

namespace Pixadelic\Adobe\Tests\Traits;

use PHPUnit\Framework\TestCase;
use Pixadelic\Adobe\Exception\ClientException;
use Pixadelic\Adobe\Traits\CommonTrait;

/**
 * Class CommonTraitTest
 */
class CommonTraitTest extends TestCase
{

    const REQUIRED_CONFIG = [
        'org_unit' => 'string',
        'private_key' => 'string',
        'api_key' => 'string',
        'tech_acct' => 'string',
        'organization' => 'string',
        'client_secret' => 'string',
        'tenant' => 'string',
        'access_endpoint' => 'string',
        'exchange_endpoint' => 'string',
        'audience' => 'string',
    ];

    const INVALID_CONFIG = [
        'wrong_key' => '',
        'private_key' => null,
        'api_key' => null,
        '_tech_acct' => '',
        '_organization' => '',
        '_client_secret' => '',
        '_tenant' => '',
        '_access_endpoint' => '',
        '_exchange_endpoint' => '',
        '_audience' => '',
    ];

    /**
     * Test configuration handling
     * with a valid configuration
     *
     * @throws \Exception
     */
    public function testConfigSuccess()
    {
        // Get trait mock
        $mock = $this->getMockForTrait(
            CommonTrait::class,
            [],
            '',
            false,
            false,
            true,
            ['setConfig'],
            false
        );

        // Set expectations
        $mock->expects($this->any())
            ->method('setConfig')
            ->will($this->returnValue($mock));

        // Assert
        $this->assertSame($mock->setConfig(self::REQUIRED_CONFIG), $mock);
    }

    /**
     * @throws \Pixadelic\Adobe\Exception\ClientException
     */
    public function testConfigFail()
    {
        $this->expectException(ClientException::class);
        $mock = $this->getMockForTrait(CommonTrait::class);
        $mock->setConfig(self::INVALID_CONFIG);
    }

    /**
     * Test cache initialization
     */
    public function testInitCache()
    {
        // Get trait mock
        $mock = $this->getMockForTrait(
            CommonTrait::class,
            [],
            '',
            false,
            false,
            true,
            ['initCache'],
            false
        );

        $mock->expects($this->any())
            ->method('initCache')
            ->will($this->returnValue($mock));

        $this->assertSame($this->callMethod($mock, 'initCache', [self::REQUIRED_CONFIG]), $mock);
    }

    /**
     * Test debug initialization
     */
    public function testInitDebug()
    {
        // Get trait mock
        $mock = $this->getMockForTrait(
            CommonTrait::class,
            [],
            '',
            false,
            false,
            true,
            ['initDebug'],
            false
        );

        $mock->expects($this->any())
            ->method('initDebug')
            ->will($this->returnValue($mock));

        $this->assertSame($this->callMethod($mock, 'initDebug', [self::REQUIRED_CONFIG]), $mock);
    }

    /**
     * Commodity to test unacessible methods
     *
     * @param string $obj
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
