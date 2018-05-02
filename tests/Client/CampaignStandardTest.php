<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:50
 */

namespace Pixadelic\Adobe\Tests\Client;

use PHPUnit\Framework\TestCase;

/**
 * Class CampaignStandardTest
 */
final class CampaignStandardTest extends TestCase
{
    const EXPECTED_ENDPOINTS = ['campaign/profileAndServices'];
    const EXPECTED_MAJOR_ENDPOINTS = ['profile', 'service'];

//    protected $config;
//    protected $testEmail;
//
//    public function setUp() {
//        $appRoot = __DIR__.'/../../';
//        $config = Yaml::parseFile($appRoot.'/app/config/config.yml');
//        if (isset($config['adobe']['campaign']['private_key'])) {
//            $config['adobe']['campaign']['private_key'] = $appRoot.'/'.$config['adobe']['campaign']['private_key'];
//        }
//        if (isset($config['parameters']['test_email'])) {
//            $testEmail = $config['parameters']['test_email'];
//        }
//    }

    /**
     *
     */
    public function testEndpoints()
    {
        $this->assertCount(1, self::EXPECTED_ENDPOINTS);
    }

    /**
     *
     */
    public function testMajorEndpoints()
    {
        $this->assertCount(2, self::EXPECTED_MAJOR_ENDPOINTS);
    }
}
