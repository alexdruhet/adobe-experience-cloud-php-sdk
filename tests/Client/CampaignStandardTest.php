<?php

namespace Pixadelic\Adobe\Tests\Client;

use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:50
 */
final class CampaignStandardTest extends TestCase
{
    const EXPECTED_ENDPOINTS = ['campaign/profileAndServices'];
    const EXPECTED_MAJOR_ENDPOINTS = ['profile', 'service'];

    public function testEndpoints()
    {
        $this->assertCount(1, self::EXPECTED_ENDPOINTS);
    }

    public function testMajorEndpoints()
    {
        $this->assertCount(2, self::EXPECTED_MAJOR_ENDPOINTS);
    }
}
