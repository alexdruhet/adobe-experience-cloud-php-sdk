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
use Pixadelic\Adobe\Traits\CommonTrait;

/**
 * Class AbstractBase
 */
abstract class AbstractBase
{
    use CommonTrait;

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

    /**
     * Metadatas storage
     * indexed by resources
     *
     * @var array
     */
    protected $metadatas = [];

    /**
     * Metadatas storage
     * indexed by resources
     *
     * @var array
     */
    protected $resources = [];

    /**
     * The API available endpoints
     *
     * @var array
     */
    protected $endpoints = [];
    protected $currentEndpointIndex = 0;

    /**
     * The API major endpoints
     *
     * This terminology remains unclear, but
     * this the one given by Adobe.
     *
     * Please refer to Adobe documentation for a better
     * understanding of this concept
     *
     * @var array
     */
    protected $majorEndpoints = [];
    protected $currentMajorEndpointIndex = 0;

    /**
     * The customer instances name
     * provided by Adobe.
     *
     * <TENANT> : the production instance
     * <TENANT-mkt-stage1>: the stage instance
     *
     * Here this property is passed by the
     * AccessToken object.
     *
     * @var string
     */
    protected $tenant;

    /**
     * AbstractBase constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this
            ->initCache()
            ->initDebug();
        $this->setEndpoints();
    }

    /**
     * @param string $resource
     *
     * @return string
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMetadata($resource)
    {
        if (!isset($this->metadata[$resource])) {
            $url = sprintf('resourceType/%s', $resource);
            $this->metadatas[$resource] = $this->fetch('GET', $url);
        }

        return $this->metadatas[$resource];
    }

    /**
     * @param string $resource
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getResources($resource)
    {
        if (!isset($this->resource[$resource])) {
            $url = sprintf('resourceType/%s', $resource);
            $this->resources[$resource] = $this->fetch('GET', $url);
        }

        return $this->resources[$resource];
    }

    /**
     * @return array
     */
    public function getEndpoints()
    {
        return $this->endpoints;
    }

    /**
     * @return array
     */
    public function getMajorEndpoints()
    {
        return $this->majorEndpoints;
    }

    /**
     * Child classes should implement this method
     * to declare its endpoints
     */
    abstract protected function setEndpoints();

    /**
     * Child classes should implement this method
     * to declare its major endpoints
     */
    abstract protected function setMajorEndpoints();

    /**
     * @return mixed|null|\Psr\Http\Message\StreamInterface|\stdClass
     *
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
     *
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
        // @codingStandardsIgnoreStart
        $this->headers = [
            'Authorization' => sprintf('%s %s', ucfirst($accessToken->token_type), $accessToken->access_token),
            'Cache-Control' => 'no-cache',
            'X-Api-Key' => $this->config['api_key'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Retrieve minimal base uri
     *
     * @return string
     */
    protected function getBaseUri()
    {
        return "{$this->baseUri}/{$this->tenant}/{$this->endpoints[$this->currentEndpointIndex]}/";
    }

    /**
     * @param string $method
     * @param string $url
     * @param null   $body
     *
     * @return string
     *
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

            if (200 !== $code) {
                throw new ClientException($reason);
            }
        } catch (\Exception $exception) {
            throw new ClientException($exception->getMessage());
        }

        return \json_decode($response->getBody()->getContents());
    }
}
