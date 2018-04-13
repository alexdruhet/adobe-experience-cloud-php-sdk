<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 15:38
 */

namespace Pixadelic\Adobe\Api;


use GuzzleHttp\Client;

class Request
{
    protected $method;
    protected $url;
    protected $client;

    public function __construct($method, $url)
    {
        $this->method = $method;
        $this->url = $url;
        $this->client = new Client();


        $res = $client->request('GET', 'https://api.github.com/repos/guzzle/guzzle');
        echo $res->getStatusCode();
// 200
        echo $res->getHeaderLine('content-type');
// 'application/json; charset=utf8'
        echo $res->getBody();
// '{"id": 1420053, "name": "guzzle", ...}'

    }

    public function send() {
        $res = $this->client->request($this->method, $this->url);
        echo $res->getStatusCode();
// 200
        echo $res->getHeaderLine('content-type');
// 'application/json; charset=utf8'
        echo $res->getBody();
    }
}