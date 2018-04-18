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
     * @param string $method
     * @param string $url
     * @param null   $body
     * @param array  $headers
     * @param string $baseUri
     */
    public function __construct($method, $url, $body = null, $headers = [], $baseUri = '')
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
        if (count($headers)) {
            $this->options['headers'] = $headers;
        }
        if ($baseUri) {
            $this->options['base_uri'] = $baseUri;
        }
        $this->client = new Client();
    }

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send()
    {
        return $this->client->request($this->method, $this->url, $this->options);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array|mixed|null
     */
    public function getConfig()
    {
        return $this->client->getConfig();
    }
}
