<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:38
 */

namespace Pixadelic\Adobe\Api;

use GuzzleHttp\Client;
use Pixadelic\Adobe\Exception\ClientException;

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
     * @param bool   $debug
     */
    public function __construct($method, $url, $body = null, $headers = [], $baseUri = '', $debug = false)
    {
        $this->method = $method;
        $this->url = $url;
        if ($body) {
            if (\is_array($body)) {
                $this->options = $body;
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
        if ($debug) {
            $this->options['debug'] = true;
        }
        $this->client = new Client();
    }

    /**
     * @return bool|mixed|\Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     */
    public function send()
    {
        $this->getRawCurlRequest();
        $response = null;

        try {
            $curlOpts = $this->options;
            unset($curlOpts['debug']);
            $response = $this->client->request($this->method, $this->url, $curlOpts);
        } catch (\Exception $e) {
            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }

        return $response;
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

    /**
     * Activate debug mode
     *
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        if ($debug) {
            @fclose($this->options['debug']);
            $this->options['debug'] = fopen('php://temp', 'w+');
        }
    }

    /**
     * Get Guzzle debug info
     * from temp php stream
     * if the option is enabled.
     *
     * @return bool|string
     */
    public function getDebugInfo()
    {
        if (isset($this->options['debug'])) {
            fseek($this->options['debug'], 0);

            return stream_get_contents($this->options['debug']);
        }

        return false;
    }

    /**
     * @param string $message
     */
    protected function addDebugInfo($message)
    {
        if (isset($this->options['debug'])) {
            fwrite($this->options['debug'], "{$message}");
        }
    }

    /**
     * Retrieve the curl raw request
     *
     * @return string
     */
    protected function getRawCurlRequest()
    {
        $rawCurlRequest = '';
        if (isset($this->options['debug'], $this->options['base_uri'], $this->options['headers'])) {
            $headers = '';
            $qsa = '?';
            foreach ($this->options['headers'] as $name => $value) {
                $headers .= " -H '{$name}: {$value}'";
            }
            $url = 0 === strpos($this->url, $this->options['base_uri']) ? $this->url : $this->options['base_uri'].$this->url;
            if (isset($this->options['query'])) {
                foreach ($this->options['query'] as $name => $value) {
                    $qsa .= "{$name}={$value}&";
                }
                if ('?' !== $qsa) {
                    $url .= trim($qsa, '&');
                }
            }
            $rawCurlRequest = "curl -X {$this->method} '{$url}'".$headers;
            if (isset($this->options['body'])) {
                $rawCurlRequest .= " -i -d '{$this->options['body']}'";
            }
            $this->addDebugInfo($rawCurlRequest);
        }

        return $rawCurlRequest;
    }
}
