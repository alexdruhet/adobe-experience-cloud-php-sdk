<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:50
 */

namespace Pixadelic\Adobe\Tests\Api;

use PHPUnit\Framework\TestCase;
use Pixadelic\Adobe\Api\Request;

/**
 * Class RequestTest
 */
final class RequestTest extends TestCase
{
    /**
     *
     */
    public function testFormParams()
    {
        $formParams = ['form_params' => ['form_param_1' => 'form_param_1_value']];

        $request = new Request('GET', 'test', $formParams);
        $this->assertSame($formParams, $request->getOptions());
    }

    /**
     *
     */
    public function testBody()
    {
        $body = 'body test';

        $request = new Request('GET', 'test', $body);
        $this->assertSame(['body' => $body], $request->getOptions());
    }
}
