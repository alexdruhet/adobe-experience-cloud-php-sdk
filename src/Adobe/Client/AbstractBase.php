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
        $this->setMajorEndpoints();
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
    public function getMetadata($resource)
    {
        $index = $this->getCurrentEndpointIndex().'/'.$resource;
        if (!isset($this->metadata[$index])) {
            $url = sprintf('resourceType/%s', $resource);
            $this->metadatas[$index] = $this->get($url);
        }

        return $this->metadatas[$index];
    }

    ///**
    // * Get json resource representation as described in Adobe documentation.
    // * Actually the endpoint api does not match with the documentation, so we can't use it yet.
    // *
    // * @see https://docs.campaign.adobe.com/doc/standard/en/api/ACS_API.html#resources-representation
    // *
    // * @param string $resource
    // *
    // * @return mixed
    // *
    // * @throws \GuzzleHttp\Exception\GuzzleException
    // * @throws \Pixadelic\Adobe\Exception\ClientException
    // * @throws \Psr\SimpleCache\InvalidArgumentException
    // */
    //public function getResource($resource)
    //{
    //    $index = $this->getCurrentEndpointIndex().'/'.$resource;
    //    if (!isset($this->resource[$index])) {
    //        $this->validateResource($resource);
    //        $resourceName = \ucfirst($resource);
    //        $this->resources[$index] = $this->get("{$this->majorEndpoints[$this->currentMajorEndpointIndex]}.json", ['_lineCount' => 1]);
    //    }
//
    //    return $this->resources[$index];
    //}

    /**
     * Get next page of a results set
     *
     * @param \stdClass $response
     *
     * @return bool|mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getNext(\stdClass $response)
    {
        if (\property_exists($response, 'next') && \property_exists($response->next, 'href')) {
            return $this->get($response->next->href);
        }

        return false;
    }

    /**
     * Retrieve endpoints
     *
     * @return array
     */
    public function getEndpoints()
    {
        return $this->endpoints;
    }

    /**
     * Retrieve major endpoints
     *
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
     * Retrieve full endpoints uri
     *
     * @return string
     */
    protected function getCurrentEndpointIndex()
    {
        return "{$this->endpoints[$this->currentEndpointIndex]}/{$this->majorEndpoints[$this->currentMajorEndpointIndex]}";
    }

    /**
     * Test if a single resource is
     * known by the current endpoint
     *
     * @param string $resource
     * @param null   $value
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function validateResource($resource, $value = null)
    {
        $metadata = $this->getMetadata($this->majorEndpoints[$this->currentMajorEndpointIndex]);
        if (!\property_exists($metadata->content, $resource)) {
            throw new ClientException("{$resource} does not exists");
        }
        if ($value && \property_exists($metadata->content->{$resource}, 'values') && !\property_exists($metadata->content->{$resource}->values, $value)) {
            throw new ClientException("{$value} is not a valid value for {$resource}");
        }
    }

    /**
     * Test if an array of resources
     * is known by the current endpoint
     *
     * @param array $resources
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function validateResources(array $resources)
    {
        foreach ($resources as $resource => $value) {
            $this->validateResource($resource, $value);
        }
    }

    /**
     * Send request with valid authorization headers
     *
     * @param string $method
     * @param string $url
     * @param null   $body
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function doRequest($method, $url, $body = null)
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

    /**
     * GET request wrapper
     *
     * @param string $url
     * @param array  $parameters
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function get($url, array $parameters = [])
    {
        return $this->doRequest('GET', $url, ['query' => $parameters]);
    }

    /**
     * POST request wrapper
     *
     * @param string $url
     * @param array  $payload
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function post($url, array $payload)
    {
        $this->validateResources($payload);

        return $this->doRequest('POST', $url, \json_encode($payload));
    }

    /**
     * PATCH request wrapper
     *
     * @param string $url
     * @param array  $payload
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function patch($url, array $payload)
    {
        $this->validateResources($payload);

        return $this->doRequest('PATCH', $url, \json_encode($payload));
    }

    /**
     * DELETE request wrapper
     *
     * @param string $url
     * @param string $pKey
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function delete($url, $pKey)
    {
        return $this->doRequest('DELETE', "{$url}/{$pKey}");
    }
}
