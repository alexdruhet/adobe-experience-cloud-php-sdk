<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 25/04/2018
 * Time: 15:55
 */

namespace Pixadelic\Adobe\Tests\Client;

use PHPUnit\Framework\TestCase;
use Pixadelic\Adobe\Client\AbstractBase;

/**
 * Class AbstractBaseTest
 *
 * @package Pixadelic\Adobe\Tests\Client
 */
class AbstractBaseTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testConstructor()
    {
        $classname = AbstractBase::class;
        $config = ['config' => true];

        // Get mock, without the constructor being called
        $mock = $this->getMockBuilder($classname)
            ->disableOriginalConstructor()
            ->getMock();

        // set expectations for constructor calls
        $mock->expects($this->once())
            ->method('setConfig')
            ->with(
                $this->equalTo($config)
            );

        // now call the constructor
        $reflectedClass = new \ReflectionClass($classname);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($mock, $config);
    }

    /**
     *
     */
    public function testGetMetadata()
    {
        $mock = $this->getMockForAbstractClass(
            AbstractBase::class,
            [],
            '',
            false,
            false,
            true,
            ['getMetadata'],
            false
        );

        $metadataStub = new \stdClass();
        $metadataStub->test = true;
        $resource = 'stub';

        $mock->expects($this->any())
            ->method('getMetadata')
            ->with($this->equalTo($resource))
            ->will($this->returnValue($metadataStub));

        $this->assertSame($mock->getMetadata($resource), $metadataStub);
    }

    /**
     *
     */
    public function testGetNext()
    {
        $mock = $this->getMockForAbstractClass(
            AbstractBase::class,
            [],
            '',
            false,
            false,
            true,
            ['getNext'],
            false
        );

        $responseStub = new \stdClass();
        $responseStub->test = true;
        $obj = new \stdClass();
        $obj->next = new \stdClass();
        $obj->next->href = 'test_url';

        $mock->expects($this->any())
            ->method('getNext')
            ->with($this->equalTo($obj))
            ->will($this->returnValue($responseStub));

        $this->assertSame($mock->getNext($obj), $responseStub);
    }
}
