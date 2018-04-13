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
use Symfony\Component\Yaml\Yaml;

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

    protected static $config;

    /**
     * Load config at setup
     *
     * @throws \Exception
     */
    public static function setUpBeforeClass()
    {
        $configPath = __DIR__.'/../../'.$_ENV['CONFIG_PATH'];
        if (!file_exists($configPath)) {
            throw new \Exception(sprintf('Configuration file cannot be found at %s', $configPath));
        }
        $config = Yaml::parseFile($configPath);
        if (isset($config['adobe']['campaign']['credentials']['private_key'])) {
            $privateKeyPath = __DIR__.'/../../'.$config['adobe']['campaign']['credentials']['private_key'];
            if (!file_exists($privateKeyPath)) {
                throw new \Exception(sprintf('Private key cannot be found at %s', $privateKeyPath));
            }
            $config['adobe']['campaign']['credentials']['private_key'] = $privateKeyPath;
        }
        self::$config = $config['adobe']['campaign']['credentials'];
    }

    /**
     * Test config size briefly
     */
    public function testRequiredConfig()
    {
        foreach (self::REQUIRED_CONFIG as $key => $type) {
            $this->assertArrayHasKey($key, self::$config);
            $this->assertInternalType($type, self::$config[$key]);
        }

    }

    /**
     * Test AccessToken construction
     * with a valid configuration
     *
     * @throws \Exception
     */
    public function testConfigSuccess()
    {
        $classname = 'Pixadelic\Adobe\Api\AccessToken';

        // @url http://miljar.github.io/blog/2013/12/20/phpunit-testing-the-constructor/
        // Get mock, without the constructor being called
        $mock = $this->getMockBuilder($classname)
            ->disableOriginalConstructor()
            ->setMethods(array('setConfig'))
            ->getMock();

        // set expectations for constructor calls
        $mock->expects($this->once())
            ->method('setConfig')
            ->with(
                $this->equalTo(self::$config)
            );

        // now call the constructor, applying expectations
        $reflectedClass = new ReflectionClass($classname);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($mock, self::$config);

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
     */
    public function testGet()
    {
        $accessToken = new AccessToken(self::$config);
        $response = $accessToken->get();
        $this->assertObjectHasAttribute('token_type', $response);
        $this->assertEquals('bearer', $response->token_type);
        $this->assertObjectHasAttribute('access_token', $response);
        $this->assertInternalType('string', $response->access_token);
        $this->assertObjectHasAttribute('expires_in', $response);
        $this->assertInternalType('int', $response->expires_in);
    }

    /**
     * Test the expiration setting
     */
    public function testSetExpiration()
    {
        // The expiration setting seems to be ignored by the JWT exchange endpoint
        // so we skip this for the moment.
        $this->markTestSkipped(
            'The expiration setting seems to be ignored by the JWT exchange endpoint'
        );

        $expiration = 5;
        $accessToken = new AccessToken(self::$config);
        $accessToken->setExpiration($expiration);
        $response = $accessToken->get();
        $this->assertObjectHasAttribute('token_type', $response);
        $this->assertEquals('bearer', $response->token_type);
        $this->assertObjectHasAttribute('access_token', $response);
        $this->assertInternalType('string', $response->access_token);
        $this->assertObjectHasAttribute('expires_in', $response);
        $this->assertInternalType('int', $response->expires_in);
        $this->assertLessThanOrEqual($expiration, $response->expires_in);
    }

    /**
     * Test that disabling cache hit the curl request
     */
    public function testDisableCache()
    {
        $accessToken = new AccessToken(self::$config);
        $response = $accessToken->get(true);
        $this->assertObjectHasAttribute('token_type', $response);
        $this->assertEquals('bearer', $response->token_type);
        $this->assertObjectHasAttribute('access_token', $response);
        $this->assertInternalType('string', $response->access_token);
        $this->assertObjectHasAttribute('expires_in', $response);
        $this->assertInternalType('int', $response->expires_in);


        $classname = 'Pixadelic\Adobe\Api\AccessToken';

        // @url http://miljar.github.io/blog/2013/12/20/phpunit-testing-the-constructor/
        // Get mock, without the constructor being called
        $mock = $this->getMockBuilder($classname)
            ->disableOriginalConstructor()
            ->setMethods(array('setConfig'))
            ->getMock();

        // set expectations for constructor calls
        $mock->expects($this->once())
            ->method('setConfig')
            ->with(
                $this->equalTo(self::$config)
            );

        // now call the constructor, applying expectations
        $reflectedClass = new ReflectionClass($classname);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($mock, self::$config);
    }

    /**
     * Close operation
     */
    public static function tearDownAfterClass()
    {
        self::$config = null;
    }

}

