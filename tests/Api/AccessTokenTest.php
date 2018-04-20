<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:49
 */

namespace Pixadelic\Adobe\Tests\Api;

use PHPUnit\Framework\TestCase;
use Pixadelic\Adobe\Api\AccessToken;

/**
 * Class AccessTokenTest
 */
final class AccessTokenTest extends TestCase
{

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
        // @codingStandardsIgnoreStart
        $this->assertEquals('bearer', $response->token_type);
        $this->assertObjectHasAttribute('access_token', $response);
        $this->assertInternalType('string', $response->access_token);
        $this->assertObjectHasAttribute('expires_in', $response);
        $this->assertInternalType('int', $response->expires_in);
        // @codingStandardsIgnoreEnd
    }
}
