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

    const EXTENDED_SUFFIX = 'Ext';

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
     * @var bool
     */
    protected $useExtended = false;

    /**
     * AbstractBase constructor.
     *
     * @param array $config
     *
     * @throws \Pixadelic\Adobe\Exception\ClientException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->setConfig($config);
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
        $index = "{$this->endpoints[$this->currentEndpointIndex]}/{$resource}";
        if (!isset($this->metadata[$index])) {
            $url = sprintf('resourceType/%s', $resource);
            $this->metadatas[$index] = $this->get($url);
        }

        return $this->metadatas[$index];
    }

    /**
     * Get json resource representation as described in Adobe documentation.
     * Actually the endpoint api does not match with the documentation, so we can't use it yet.
     *
     * @see https://docs.campaign.adobe.com/doc/standard/en/api/ACS_API.html#resources-representation
     *
     * @param string $resource
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getResource($resource)
    {
        $index = $this->getCurrentEndpointIndex().'/'.$resource;
        if (!isset($this->resource[$index])) {
            $this->validateResource($resource);
            $resourceName = \ucfirst($resource);
            $this->resources[$index] = $this->get("{$this->majorEndpoints[$this->currentMajorEndpointIndex]}/{$resourceName}.json", ['_lineCount' => 1]);
        }

        return $this->resources[$index];
    }

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
     * @return $this
     */
    protected function setExtended()
    {
        $this->useExtended = true;

        return $this;
    }

    /**
     * @return $this
     */
    protected function unsetExtended()
    {
        $this->useExtended = false;

        return $this;
    }

    /**
     * @return mixed|null|\Psr\Http\Message\StreamInterface|\stdClass
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     * @throws \Pixadelic\Adobe\Exception\ClientException
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
     * @throws \Pixadelic\Adobe\Exception\ClientException
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
     * @throws \Pixadelic\Adobe\Exception\ClientException
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
        $currentEndpoint = $this->getCurrentEndpointIndex();

        return "{$this->baseUri}/{$this->tenant}/{$currentEndpoint}/";
    }

    /**
     * Retrieve full endpoints uri
     *
     * @return string
     */
    protected function getCurrentEndpointIndex()
    {
        $currentEndpoint = $this->endpoints[$this->currentEndpointIndex];
        if ($this->useExtended) {
            $currentEndpoint .= self::EXTENDED_SUFFIX;
        }

        return $currentEndpoint;
    }

    /**
     * Retrieve full endpoints uri
     *
     * @return string
     */
    protected function getCurrentFullEndpointIndex()
    {
        $currentEndpoint = $this->getCurrentEndpointIndex();

        return "{$currentEndpoint}/{$this->majorEndpoints[$this->currentMajorEndpointIndex]}";
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
            throw new ClientException("{$resource} does not exists", 400);
        }
        if ($value && \property_exists($metadata->content->{$resource}, 'values')
            && !\property_exists($metadata->content->{$resource}->values, $value)
        ) {
            throw new ClientException("{$value} is not a valid value for {$resource}", 400);
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
            $request = new Request($method, $url, $body, $headers, $baseUri, $this->debug);
            $request->setDebug($this->debug);
            $response = $request->send();
            $requestDebugInfo = $request->getDebugInfo();
            if ($requestDebugInfo) {
                $this->addDebugInfo('request', $requestDebugInfo);
            }
            $code = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
            $content = \json_decode($response->getBody()->getContents());

            if (!$content) {
                $content = new \stdClass();
                $content->code = $code;
                $content->message = $reason;
            }

            if ($this->debug) {
                $content->debug = $this->getDebugInfo();
            }

            if (400 <= $code) {
                throw new ClientException($reason, $code);
            }
        } catch (\Exception $e) {
            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }

        return $content;
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
        //$this->validateResources($payload);

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
     * @param string $linkIdentifier
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function delete($linkIdentifier)
    {
        return $this->doRequest('DELETE', $linkIdentifier);
    }
}
