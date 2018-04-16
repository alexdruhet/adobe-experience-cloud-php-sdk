<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 16/04/2018
 * Time: 11:31
 */

namespace Pixadelic\Adobe\Client;

use Pixadelic\Adobe\Api\AccessToken;
use Pixadelic\Adobe\Api\Request;
use Pixadelic\Adobe\Exception\ClientException;

/**
 * Class AbstractBase
 *
 * @package Pixadelic\Adobe\Client
 */
abstract class AbstractBase
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \stdClass
     */
    protected $accessToken;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var string
     */
    protected $baseUri = 'https://mc.adobe.io';

    protected $metadata;

    protected $resources;

    protected $namespace;

    protected $tenant;

    /**
     * AbstractBase constructor.
     *
     * @param array $config
     */
    function __construct(array $config)
    {
        $this->config = $config;
        $this->setNamespace();
    }

    abstract protected function setNamespace();

    /**
     * @return mixed|null|\Psr\Http\Message\StreamInterface|\stdClass
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function getAccessToken()
    {
        if (!$this->accessToken) {
            $accessToken = new AccessToken($this->config);
            $this->tenant = $accessToken->getTenant();
            $this->accessToken = $accessToken->get();
        }

        return $this->accessToken;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function getHeaders()
    {
        if (!count($this->headers)) {
            $this->prepareHeaders();
        }

        return $this->headers;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function prepareHeaders()
    {
        $accessToken = $this->getAccessToken();
        $this->headers = [
            'Authorization' => sprintf('%s %s', ucfirst($accessToken->token_type), $accessToken->access_token),
            'Cache-Control' => 'no-cache',
            'X-Api-Key' => $this->config['api_key'],
        ];
    }

    /**
     * @param $resource
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMetadata($resource)
    {
        if (!$this->metadata) {
            $url = sprintf('resourceType/%s', $resource);
            $this->metadata = $this->fetch('GET', $url);
        }

        return $this->metadata;

    }

    protected function getResources()
    {

    }

    /**
     * @return string
     */
    protected function getBaseUri()
    {
        return "{$this->baseUri}/{$this->tenant}/{$this->namespace}/";
    }

    /**
     * @param      $method
     * @param      $url
     * @param null $body
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function fetch($method, $url, $body = null)
    {
        try {
            $headers = $this->getHeaders();
            $baseUri = $this->getBaseUri();
            $request = new Request($method, $url, $body, $headers, $baseUri);
            $response = $request->send();
            $code = $response->getStatusCode();
            $reason = $response->getReasonPhrase();

            if ($code !== 200) {
                throw new ClientException($reason);
            }
        } catch (\Exception $exception) {
            throw new ClientException($exception->getMessage());
        }

        return \json_decode($response->getBody()->getContents());
    }
}