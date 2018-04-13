<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 09:06
 */

namespace Pixadelic\Adobe\Api;


use Pixadelic\Adobe\Exception\AccessTokenException;
use Symfony\Component\Cache\Simple\FilesystemCache;

class AccessToken
{
    /**
     * Error messages
     */
    const ERROR_MESSAGES = [
        'setExpiration' => 'Expiration time should be an integer value',
        'generateJwt.privateKey.undefined' => 'Private key undefined',
        'generateJwt.privateKey.notFound' => 'Private key not found',
        'get' => 'Unable to get an access token',
    ];

    /**
     * Path to private key
     *
     * The private key filename or string literal to use to sign the token
     *
     * @var string
     */
    protected $privateKey;

    /**
     * API key (Client ID)
     *
     * The issuer, usually the client_id
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Technical Account ID
     *
     * @var string
     */
    protected $techAcct;

    /**
     * Organization ID
     *
     * The subject, usually a user_id
     *
     * @var string
     */
    protected $organization;

    /**
     * Client secret
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * Tenant WTF?
     *
     * @var string
     */
    protected $tenant;

    /**
     * Audience
     *
     * The audience, usually the URI for the oauth server
     *
     * @var string
     */
    protected $audience;

    /**
     * Access endpoint url
     *
     * @var string
     */
    protected $accessEndpoint;

    /**
     * Exchange endpoint url
     *
     * @var string
     */
    protected $exchangeEndpoint;

    /**
     * Expiration delay
     * in seconds.
     *
     * 24h as default
     *
     * @var int
     */
    protected $expiration = 3600 * 24;

    /**
     * @var bool
     */
    protected $enableCache = true;

    /**
     * @var \Symfony\Component\Cache\Simple\FilesystemCache
     */
    protected $cache;

    /**
     * @var string
     */
    protected $cacheId = 'aec.access_token';

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var \stdClass
     */
    protected $debugInfo;

    /**
     * AccessToken constructor.
     *
     * @param array $config
     *
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);

        if ($this->enableCache) {
            $this->cache = new FilesystemCache();
        }
        if ($this->debug) {
            $this->debugInfo = new \stdClass();
            $this->debugInfo->config = $config;
        }
    }

    /**
     * @param array $config
     *
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     */
    protected function setConfig(array $config)
    {
        try {
            // Required parameters
            $this->privateKey = $config['private_key'];
            $this->apiKey = $config['api_key'];
            $this->techAcct = $config['tech_acct'];
            $this->organization = $config['organization'];
            $this->clientSecret = $config['client_secret'];
            $this->tenant = $config['tenant'];
            $this->accessEndpoint = $config['access_endpoint'];
            $this->exchangeEndpoint = $config['exchange_endpoint'];
            $this->audience = $config['audience'];

            // Optional parameters
            if (isset($config['expiration'])) {
                $this->expiration = (int) $config['expiration'];
            }
            if (isset($config['cache'])) {
                $this->enableCache = (bool) $config['cache'];
            }
            if (isset($config['debug'])) {
                $this->debug = (bool) $config['debug'];
            }
        } catch (\Exception $exception) {
            throw new AccessTokenException($exception->getMessage());
        }
    }

    /**
     * Set expiration delay
     *
     * @param integer $seconds The expiration delay expressed in seconds
     *
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     */
    public function setExpiration($seconds)
    {
        if (!\is_int($seconds)) {
            throw new AccessTokenException(self::ERROR_MESSAGES['setExpiration']);
        }
        if ($this->enableCache && $this->cache->has($this->cacheId)) {
            $this->cache->delete($this->cacheId);
        }
        $this->expiration = $seconds;
    }

    /**
     * Return an access token
     *
     * @param bool $force bypass caching or not, default not
     *
     * @return mixed|null|\Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($force = false)
    {
        // Retrieve from cache if possible
        if (!$force && $this->enableCache && $this->cache->has($this->cacheId)) {
            return $this->cache->get($this->cacheId);
        }

        // Prepare request payload
        $jwt = $this->generateJwt();
        $payload = [
            'client_id' => $this->apiKey,
            'client_secret' => $this->clientSecret,
            'jwt_token' => $jwt,
        ];

        // Send request
        $request = new Request('POST', $this->exchangeEndpoint, $payload);
        $response = $request->send();
        $status = $response->getStatusCode();
        $body = $response->getBody();
        $content = json_decode($body->getContents());

        // Error handling
        if ($status !== 200) {
            $message = self::ERROR_MESSAGES['get'];
            if (isset($content->error)) {
                $message = $content->error.\PHP_EOL.$content->error_description;
            }
            throw new AccessTokenException($message);
        }

        // Add debug info to response if necessary
        if ($this->debug) {
            $content->debug = $this->debugInfo;
        }

        // Caching response
        if ($this->enableCache) {
            $this->cache->set($this->cacheId, $content, $content->expires_in);
        }

        return $content;
    }

    /**
     * Generate a JWT
     *
     * @return string
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     */
    private function generateJwt()
    {
        // Check that we can reach the private key
        if (file_exists($this->privateKey)) {
            $privateKey = file_get_contents($this->privateKey);
        } else {
            throw new AccessTokenException(self::ERROR_MESSAGES['generateJwt.privateKey.notFound']);
        }
        if (!isset($privateKey)) {
            throw new AccessTokenException(self::ERROR_MESSAGES['generateJwt.privateKey.undefined']);
        }

        // Preparing payload
        $algorithm = 'RS256';
        $expiration = time() + $this->expiration;
        $payload = array(
            'exp' => $expiration,
            'iss' => $this->organization,
            'sub' => $this->techAcct,
            $this->accessEndpoint => true,
            'aud' => $this->audience,
        );
        $header = array('typ' => 'JWT', 'alg' => $algorithm);
        $find = array('+', '/', '\r', '\n', '=');
        $replace = array('-', '_');
        $segments = array(
            str_replace($find, $replace, base64_encode(json_encode($header))),
            str_replace($find, $replace, base64_encode(json_encode($payload))),
        );
        $signing_input = implode('.', $segments);

        // Generate signature
        @openssl_sign($signing_input, $signature, $privateKey, 'sha256');
        $segments[] = str_replace($find, $replace, base64_encode($signature));

        return implode('.', $segments);
    }

}