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
        'access_token' => $_ENV['ACCESS_TOKEN'],
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
     * @throws \Pixadelic\Adobe\Exception\ClientException
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
