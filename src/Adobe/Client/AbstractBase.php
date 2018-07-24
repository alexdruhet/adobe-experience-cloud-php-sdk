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
        $cacheId = "aec.Metadata_".$resource;
        $index = "{$this->endpoints[$this->currentEndpointIndex]}/{$resource}";
        if (!isset($this->metadata[$index])) {
            $metadata = $this->getCache($cacheId);
            if (!$metadata) {
                $url = sprintf('resourceType/%s', $resource);
                $this->metadatas[$index] = $this->get($url);
                $this->setCache($this->metadatas[$index], null, $cacheId);
            } else {
                $this->metadatas[$index] = $metadata;
            }
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
        $cacheId = "aec.Resources_".$majorEndpoint;
        if (!count($this->resources) || !isset($this->resources[$majorEndpoint])) {
            $resources = $this->getCache($cacheId);
            if (!$resources) {
                $this->setExtended();
                $response = $this->get("{$majorEndpoint}.json", ['_lineCount' => 1]);
                $this->unsetExtended();
                if (isset($response['content'])) {
                    $this->resources[$majorEndpoint] = $response['content'];
                    $this->setCache($this->resources[$majorEndpoint], null, $cacheId);
                }
            } else {
                $this->resources[$majorEndpoint] = $resources;
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
    protected function validateResourceRaw($property, $value = null, $metadata = null, $throwException = true)
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
                        $compatibleResourcesKeys = array_keys($nestedMetadata['compatibleResources']);
                        $resourceName = array_shift($compatibleResourcesKeys);

                        // Proceed only if the property is owned by our organisation unit
                        if (in_array($resourceName, $this->orgUnitResources)) {
                            $subReturn = $this->validateResourceRaw($property, $value, $nestedMetadata, false);

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
            if (!$subReturn
                && !isset($this->validationData[$property]['valid'])
                && !isset($this->validationData[$property]['value_errors'])
            ) {
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
            // Mark property has valid
            // will be cleaned up later in the process
            $this->validationData[$property]['valid'] = true;

            $message = "{$value} is not a valid value for {$property}";
            $possibleValues = $content[$property]['values'];
            unset($possibleValues['__Invalid_value__']);
            $data = ['values' => array_keys($possibleValues)];

            if ($throwException) {
                throw new ClientException($message, 400, $data);
            }

            $this->validationData[$property]['value_errors'][] = ['message' => $message, 'data' => $data];
        }

        // Cleanup the property validation entries if
        // the process has marked it valid
        if (isset($this->validationData[$property]['valid'])) {
            $return = true;
        }

        if (!isset($this->validationData[$property]['errors'])
            && !isset($this->validationData[$property]['value_errors'])
        ) {
            $return = true;
        } elseif (isset($this->validationData[$property]['value_errors'])) {
            $return = false;
        }

        return $return;
    }

    /**
     * @param string $property
     */
    protected function validationDataCleanup($property)
    {
        // Cleanup the property validation entries if
        // the process has marked it valid
        if (isset($this->validationData[$property]['valid'])) {
            unset($this->validationData[$property]['errors']);
            unset($this->validationData[$property]['valid']);
        }

        if (!isset($this->validationData[$property]['errors'])
            && !isset($this->validationData[$property]['value_errors'])
        ) {
            unset($this->validationData[$property]);
        } elseif (isset($this->validationData[$property]['value_errors'])) {
            $this->validationData[$property]['errors'] = $this->validationData[$property]['value_errors'];
            unset($this->validationData[$property]['value_errors']);
        }
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
        $return = $this->validateResourceRaw($property, $value, $metadata, $throwException);
        $this->validationDataCleanup($property);

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
     * @return bool
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
            if (!$this->validateResourceRaw($resource, $value, $metadata, false)) {
                $return = false;
            }
            $this->validationDataCleanup($resource);
        }

        if ($throwException && count($this->validationData)) {
            $validationData = $this->validationData;
            $this->validationData = [];
            throw new ClientException('The resource is invalid', 400, $validationData);
        }

        return $return;
    }

    /**
     * @TODO: prevent case of duplicate property names between resources
     *
     * @param array  $payload
     * @param array  $metadata
     * @param string $resourceLink
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function preparePayload(array &$payload, array $metadata, $resourceLink = null)
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
                            $nestedCompatibleResourcesKeys = \array_keys($nestedMetadata['compatibleResources']);
                            $nestedResourceName = array_shift($nestedCompatibleResourcesKeys);

                            // Proceed only if the property is owned by our organisation unit
                            if (in_array($nestedResourceName, $this->orgUnitResources)) {
                                // @TODO: Automatically add custom key mandatory fields if required
                                // even if the metadata does not provide support for this feature
                                $this->preparePayload($payload, $nestedMetadata, $key);
                            }
                        }
                    }
                }
            } else {
                unset($payload[$property]);
                if ($resourceLink) {
                    $payload["{$resourceName}|{$resourceLink}"][$property] = $value;
                } else {
                    $payload["{$resourceName}"][$property] = $value;
                }
            }
        }
    }

    /**
     * Prepare payload
     *
     * Because several resources are potentially
     * concerned by the update, we have
     * to prepare their respective requests with:
     * - a POST if the resource not yet exists
     * - a PATCH + PK if it's just an update
     *
     * To do so we will extend the custom syntax used
     * in preparePayload method. The following syntax
     * will be applied to the payload keys:
     * {resourceName}|{cusLink}|{href}
     *
     * @param string $url
     * @param array  $payload
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function prepareRequests($url, array &$payload)
    {

        // First we need to retrieve the main resource to inspect it
        $data = $this->get($url);

        foreach ($payload as $key => $properties) {
            if (\strpos($key, '|')) {
                list($resourceName, $cusLink) = explode('|', $key);
            } else {
                continue;
                // Since the update is already required
                // for the main resource, we only process
                // the custom resources
            }

            if (isset($data[$cusLink])) {
                $newKey = $key;

                // Save the PKey
                if (isset($data[$cusLink]['PKey'])) {
                    $newKey = "{$newKey}|{$data[$cusLink]['PKey']}";
                }

                // Save the href
                if (isset($data[$cusLink]['href'])) {
                    $newKey = "{$newKey}|{$data[$cusLink]['href']}";
                }

                // Update the key
                if ($newKey !== $key) {
                    $payload[$newKey] = $payload[$key];
                    unset($payload[$key]);
                }
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
            // Increment request counter
            // ACS api has daily request limitation
            // applied according to the Adobe contract
            // So we count the number of requests.
            $count = $this->incrementCounter();
            $doRequest = true;
            //send request only if daily threshold not reached
            //log request otherwise
            if ($count >= $this->dailyRequestsThreshold / 2 && in_array($method, ['POST', 'PATCH', 'DELETE'])) {
                $this->logRequest($method, $url, $body);
                $content = array('code' => 200, 'message' => 'request logged');
                $doRequest = false;
            }

            if ($doRequest) {
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
        return $this->dispatchRequests('POST', $url, $payload, $metadata);
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
        return $this->dispatchRequests('PATCH', $url, $payload, $metadata);
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

    /**
     * @param string $verb
     * @param string $url
     * @param array  $payload
     * @param null   $metadata
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function dispatchRequests($verb, $url, array $payload, $metadata = null)
    {
        $response = [];
        $verb1 = null;
        $verb2 = null;

        if (in_array($verb, ['POST', 'PATCH'])) {
            if ('POST' === $verb) {
                $verb1 = 'POST';
                $verb2 = 'PATCH';
            }
            if ('PATCH' === $verb) {
                $verb1 = 'PATCH';
                $verb2 = 'POST';
            }
        }

        if (!$verb1 || !$verb2) {
            throw new ClientException('Sorry, an error occurred while dispatching requests', 500);
        }

        if ($metadata) {
            $this->validateResources($payload, $metadata);
            $this->preparePayload($payload, $metadata);
            $this->prepareRequests($url, $payload);

            foreach ($payload as $key => $properties) {
                $parameters = explode('|', $key);
                $count = count($parameters);

                // Nominal case, just run main PATCH request
                if (1 === $count) {
                    $response[] = $this->doRequest($verb1, $url, \json_encode($properties));
                } else {
                    // Otherwise we discover PKey and href values
                    $PKey = null;
                    $href = null;
                    $cusName = null;
                    for ($i = 0; $i < $count; $i++) {
                        $parameter = $parameters[$i];
                        if (0 === $i) {
                            $cusName = $parameter;
                        }
                        if (\preg_match('/^@/', $parameter)) {
                            $PKey = $parameter;
                        }
                        if (\preg_match('/^https/', $parameter)) {
                            $href = $parameter;
                        }
                    }
                    if (isset($payload['email'])) {
                        $properties['email'] = $payload['email'];
                    }
                    if ($PKey && $href) {
                        $response[] = $this->doRequest($verb1, $href, \json_encode($properties));
                    } elseif (isset($properties['email'])) {
                        $response[] = $this->doRequest($verb2, $cusName, \json_encode($properties));
                    }
                }
            }
        } else {
            $response[] = $this->doRequest($verb1, $url, \json_encode($payload));
        }

        return $response;
    }

    /**
     * Log daily count of requests
     * @return int
     */
    protected function incrementCounter()
    {
        $count = 0;
        if (isset($this->logDir) && $this->logDir && \file_exists($this->logDir)) {
            if (!file_exists("{$this->logDir}/aec-php-sdk-counts")) {
                mkdir("{$this->logDir}/aec-php-sdk-counts", 0777, true);
            }

            $date = date("Y-m-d");
            $logFilePath = "{$this->logDir}/aec-php-sdk-counts/{$date}";

            if (!\file_exists($logFilePath)) {
                $handle = fopen($logFilePath, "x+");
                \fclose($handle);
            }

            $count = file_get_contents($logFilePath);
            if (!$count) {
                $count = 1;
            } else {
                $count += 1;
            }
            file_put_contents($logFilePath, $count);
        }

        return $count;
    }

    /**
     * Log request in csv file
     * @param string $method
     * @param string $url
     * @param array  $body
     */
    protected function logRequest($method, $url, $body)
    {
        if (isset($this->logDir) && $this->logDir && \file_exists($this->logDir)) {
            if (!file_exists("{$this->logDir}/aec-php-sdk-requests")) {
                mkdir("{$this->logDir}/aec-php-sdk-requests", 0777, true);
            }

            $date = date("Y-m-d");
            $logFilePath = "{$this->logDir}/aec-php-sdk-requests/{$date}";

            if (file_exists($logFilePath)) {
                $handle = fopen($logFilePath, 'a');
            } else {
                $handle = fopen($logFilePath, 'w');
            }
            $message = $method.",".$url.",".json_encode($body);
            \fwrite($handle, $message."\n");
            \fclose($handle);
        }
    }
}
