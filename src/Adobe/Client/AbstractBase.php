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
     * @var array
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
     * @var array
     */
    protected $validationData = [];

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
     *
     * @see https://docs.campaign.adobe.com/doc/standard/en/api/ACS_API.html#resources-representation
     *
     * @param string $majorEndpoint
     *
     * @return array|mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getResources($majorEndpoint)
    {
        if (!count($this->resources) || !isset($this->resources[$majorEndpoint])) {
            $this->setExtended();
            $response = $this->get("{$majorEndpoint}.json", ['_lineCount' => 1]);
            $this->unsetExtended();
            if (isset($response['content'])) {
                $this->resources[$majorEndpoint] = $response['content'];
            }
        }

        return $this->resources[$majorEndpoint];
    }

    /**
     * Get next page of a results set
     *
     * @param array $response
     *
     * @return bool|mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getNext(array $response)
    {
        if (isset($response['next']) && isset($response['next']['href'])) {
            return $this->get($response['next']['href']);
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
     * @return mixed|null|\Psr\Http\Message\StreamInterface|\array
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
        $this->headers = [
            'Authorization' => sprintf('%s %s', ucfirst($accessToken['token_type']), $accessToken['access_token']),
            'Cache-Control' => 'no-cache',
            'X-Api-Key' => $this->config['api_key'],
        ];
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
     * Test a single property
     * against available metadatas
     *
     * @param string $property
     * @param null   $value
     * @param null   $metadata
     * @param bool   $throwException
     *
     * @return bool
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function validateResource($property, $value = null, $metadata = null, $throwException = true)
    {
        // No property, no processing
        if (!$property) {
            return true;
        }

        // No given metadata, load default one from the
        // current major endpoint
        if (!$metadata) {
            $metadata = $this->setExtended()->getMetadata($this->majorEndpoints[$this->currentMajorEndpointIndex]);
            $this->unsetExtended();
        }

        $return = true;
        $content = $metadata['content'];

        if (!isset($content[$property])) {
            $subReturn = false;

            // Try to find the property in custom resources
            foreach ($content as $key => $nestedMetadata) {
                if (preg_match('/^cus/', $key)) {
                    // We can potentially find the nested property metadata
                    // since we load the linked property metadata in getMetadata
                    if (isset($nestedMetadata['compatibleResources'])) {
                        $compatibleResources = $nestedMetadata['compatibleResources'];
                        $resourceName = array_shift(array_keys($compatibleResources));

                        // Proceed only if the property is owned by our organisation unit
                        if (in_array($resourceName, $this->orgUnitResources)) {
                            $subReturn = $this->validateResource($property, $value, $nestedMetadata, false);

                            // Break if the property is find in a nested metadata
                            if ($subReturn) {
                                // Mark property has valid
                                $this->validationData[$property]['valid'] = true;
                                break;
                            }
                        }
                    }
                }
            }

            // If the property is not found either in the main
            // and the nested metadata we register an error
            if (!$subReturn && !isset($this->validationData[$property]['valid'])) {
                $message = "{$property} property is not found in {$metadata['name']} resource";

                if ($throwException) {
                    throw new ClientException($message, 400);
                }

                $error = ['message' => $message];
                $this->validationData[$property]['errors'][] = $error;
                $return = false;
            }
        } elseif ($value
            && isset($content[$property]['values'])
            && !isset($content[$property]['values'][$value])
        ) {
            // Invalidate the property
            if (isset($this->validationData[$property]['valid'])) {
                unset($this->validationData[$property]['valid']);
            }

            $message = "{$value} is not a valid value for {$property}";
            $possibleValues = $content[$property]['values'];
            unset($possibleValues['__Invalid_value__']);
            $data = ['values' => array_keys($possibleValues)];

            if ($throwException) {
                throw new ClientException($message, 400, $data);
            }

            $return = false;
            $this->validationData[$property]['errors'][] = ['message' => $message, 'data' => $data];
        }

        // Cleanup the property validation entries if
        // the process has marked it valid
        if (isset($this->validationData[$property]['valid'])) {
            unset($this->validationData[$property]);
        }

        return $return;
    }

    /**
     * Test if an array of resources
     * is known by the current endpoint
     *
     * @param array $resources
     * @param null  $metadata
     * @param bool  $throwException
     *
     * @return array|string
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function validateResources(array $resources, $metadata = null, $throwException = true)
    {
        $this->validationData = [];
        $return = true;
        foreach ($resources as $resource => $value) {
            $return = $this->validateResource($resource, $value, $metadata, false);
        }

        if ($throwException && !$return) {
            $validationData = $this->validationData;
            $this->validationData = [];
            throw new ClientException('The resource is invalid', 400, $validationData);
        }

        return $return;
    }

    /**
     * @param array $payload
     * @param array $metadata
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function preparePayload(array &$payload, array $metadata)
    {
        $content = $metadata['content'];
        $compatibleResources = $metadata['compatibleResources'];
        foreach ($compatibleResources as $resourceName => $resourceValue) {
            if (ctype_alnum($resourceName)) {
                break;
            }
        }
        foreach ($payload as $property => $value) {
            if (!isset($content[$property])) {
                // Try to find the property in custom resources
                foreach ($content as $key => $nestedMetadata) {
                    if (preg_match('/^cus/', $key)) {
                        // We can potentially find the nested property metadata
                        // since we load the linked property metadata in getMetadata
                        if (isset($nestedMetadata['compatibleResources'])) {
                            $nestedCompatibleResources = $nestedMetadata['compatibleResources'];
                            $nestedResourceName = array_shift(array_keys($nestedCompatibleResources));

                            // Proceed only if the property is owned by our organisation unit
                            if (in_array($nestedResourceName, $this->orgUnitResources)) {
                                $this->preparePayload($payload, $nestedMetadata);
                            }
                        }
                    }
                }
            } else {
                unset($payload[$property]);
                $payload["resource:{$resourceName}"][$property] = $value;
            }
        }
    }

    /**
     * @param string $email
     *
     * @throws \Pixadelic\Adobe\Exception\ClientException
     */
    protected function validateEmail($email)
    {
        // Then we check if the email si valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ClientException(sprintf('The given email %s is invalid', $email), 400);
        }

        // So we can ensure the tld exists
        $tld = substr(strrchr($email, "@"), 1);
        if (!checkdnsrr($tld, 'MX')) {
            throw new ClientException(sprintf('The domain of the given email %s is invalid', $email), 400);
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
            $content = \json_decode($response->getBody()->getContents(), true);

            if (!$content) {
                $content = [];
                $content['code'] = $code;
                $content['message'] = $reason;
            }

            if ($this->debug) {
                $content['debug'] = $this->getDebugInfo();
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
     * @param null   $metadata
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function post($url, array $payload, $metadata = null)
    {
        if ($metadata) {
            $this->validateResources($payload, $metadata);
            $this->preparePayload($payload, $metadata);
        }

        return $this->doRequest('POST', $url, \json_encode($payload));
    }

    /**
     * PATCH request wrapper
     *
     * @param string $url
     * @param array  $payload
     * @param null   $metadata
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function patch($url, array $payload, $metadata = null)
    {
        $this->validateResources($payload, $metadata);
        $this->preparePayload($payload, $metadata);

        $response = [];
        foreach ($payload as $resource => $resourcePayload) {
            $response[] = ['PATCH', $url, \json_encode($resourcePayload)];
            // @TODO: run these requests with the right url...
            //$response[] = $this->doRequest('PATCH', $url, \json_encode($resourcePayload));
        }

        return $response;
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
