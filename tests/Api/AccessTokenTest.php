<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:49
 */

use PHPUnit\Framework\TestCase;
use Pixadelic\Adobe\Api\AccessToken;
use Pixadelic\Adobe\Exception\AccessTokenException;

/**
 * Class AccessTokenTest
 */
final class AccessTokenTest extends TestCase
{
    const REQUIRED_CONFIG = [
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

    const SUCCESS_RESPONSE = [
        'token_type' => 'bearer',
        'access_token' => 'eyJ4NXUiOiJpbXNfbmExLWtleS0xLmNlciIsImFsZyI6IlJTZjU2In0.eyJpZCI6IjE1MjM2MTI1NDI2MTRfXGVkNWI5Y2EtN2ZjNS00MTZhLTk3OYQtNjlmOWRkOTA1ZjNlX3VlMSIsImNsaWVudF9pZCI6IjNhNGZkOWJkMmEzZDRlYWY5ZjI4NTA3M2RhOWMyODA1IiwidXNlcl9pZCI6IjQ0NEYzNkMxNUFDQjNEMjkwQTQ5NUU0N0B0ZWNoYWNjdC5hZG9iZS5jb20iLCJ0eXBlIjoiYWNjZXNzX3Rva2VuIiwiYXMiOiJpbXMtbmExIiwiZmciOiJTSzRHTUtCSEhMTzdDVFFDQUFBQUFBQUFZQT09PT09PSIsIm1vaSI6ImFmMzY2YTMiLCJjIjoiclZsTFR0UWpqZEs1S1g2NnFiZmxJdz09IiwiZXhwaXJlc19pbiI6Ijg2NDAwMDAwIiwiY3JlYXRlZF9hdCI6IjE1MjM2MTI1NDI2MTQi',
        'expires_in' => 86399994,
    ];

    const ERROR_RESPONSE = [
        'error' => true,
        'error_message' => 'Expected error message',
    ];

    /**
     * Test AccessToken construction
     * with a valid configuration
     *
     * @throws \Exception
     */
    public function testConfigSuccess()
    {
        $classname = 'Pixadelic\Adobe\Api\AccessToken';

        // Get mock, without the constructor being called
        $mock = $this->getMockBuilder($classname)
            ->disableOriginalConstructor()
            ->setMethods(array('setConfig'))
            ->getMock();

        // Set expectations for constructor calls
        $mock->expects($this->once())
            ->method('setConfig')
            ->with($this->equalTo(self::REQUIRED_CONFIG));

        // Now call the constructor, applying expectations
        $reflectedClass = new ReflectionClass($classname);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($mock, self::REQUIRED_CONFIG);

    }

    /**
     * Test invalid config throw an AccessTokenException
     *
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     */
    public function testConfigFail()
    {
        $this->expectException(AccessTokenException::class);
        new AccessToken(self::INVALID_CONFIG);
    }

    /**
     * Test retrieved attributes and types
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGet()
    {
        // Creating stubbed request
        $stub = $this->createMock(AccessToken::class);

        // Setting stub
        $stub->method('get')
            ->willReturn((object) self::SUCCESS_RESPONSE);

        // Getting stubbed data
        $response = $stub->get();

        // Applying assertions
        $this->assertObjectHasAttribute('token_type', $response);
        $this->assertEquals('bearer', $response->token_type);
        $this->assertObjectHasAttribute('access_token', $response);
        $this->assertInternalType('string', $response->access_token);
        $this->assertObjectHasAttribute('expires_in', $response);
        $this->assertInternalType('int', $response->expires_in);
    }

}

