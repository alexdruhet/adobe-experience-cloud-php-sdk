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
     * @return mixed|\Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send()
    {
        $this->getRawCurlRequest();

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

    /**
     * Activate debug mode
     *
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        if ($debug) {
            $this->options['debug'] = fopen('php://temp', 'r+');
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
            fputs($this->options['debug'], "{$message}\n");
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
        if (isset($this->options['debug'])
            && isset($this->options['base_uri'])
            && isset($this->options['headers'])
        ) {
            $headers = '';
            foreach ($this->options['headers'] as $name => $value) {
                $headers .= "-H \"{$name}: {$value}\"".\PHP_EOL;
            }
            $rawCurlRequest = "RAW CURL REQUEST: ".\PHP_EOL."curl ".\PHP_EOL."-X {$this->method} {$this->options['base_uri']}{$this->url} ".\PHP_EOL.$headers;
            if (isset($this->options['body'])) {
                $rawCurlRequest .= \PHP_EOL."-i -d \"{$this->options['body']}\"";
            }
            $rawCurlRequest .= \PHP_EOL;
            //\print_r($rawCurlRequest);
            //echo \PHP_EOL.\PHP_EOL;
            //echo '<br>---<br>';
            //echo \PHP_EOL.\PHP_EOL;
            $this->addDebugInfo($rawCurlRequest);
        }

        return $rawCurlRequest;
    }
}
