<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:38
 */

namespace Pixadelic\Adobe\Api;

use GuzzleHttp\Client;

/**
 * Class Request
 *
 * @package Pixadelic\Adobe\Api
 */
class Request
{
    protected $method;
    protected $url;
    protected $client;
    protected $options = [];

    /**
     * Request constructor.
     *
     * @param $method
     * @param $url
     */
    public function __construct($method, $url, $body = null)
    {
        $this->method = $method;
        $this->url = $url;
        if ($body) {
            if (\is_array($body)) {
                $this->options['form_params'] = $body;
            } else {
                $this->options['body'] = $body;
            }
        }
        $this->client = new Client();

    }

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send()
    {
        return $this->client->request($this->method, $this->url, $this->options);
    }
}