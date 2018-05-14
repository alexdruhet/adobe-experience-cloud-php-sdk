<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 09/04/2018
 * Time: 09:06
 */

namespace Pixadelic\Adobe\Api;

use Pixadelic\Adobe\Exception\AccessTokenException;
use Pixadelic\Adobe\Traits\CommonTrait;

/**
 * Class AccessToken
 */
class AccessToken
{
    use CommonTrait;

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
     * AccessToken constructor.
     *
     * @param array $config
     *
     * @throws \Pixadelic\Adobe\Exception\ClientException
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

    /**
     * @return string
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    /**
     * @param bool $force
     *
     * @return mixed|null
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     * @throws \Pixadelic\Adobe\Exception\ClientException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($force = false)
    {
        // Retrieve from cache if possible
        if (!$force && $this->hasCache()) {
            return $this->getCache();
        }

        // Prepare request payload
        $jwt = $this->generateJwt();
        $payload = [
            'form_params' => [
                'client_id' => $this->apiKey,
                'client_secret' => $this->clientSecret,
                'jwt_token' => $jwt,
            ],
        ];

        // Send request
        $request = new Request('POST', $this->exchangeEndpoint, $payload);
        $request->setDebug($this->debug);
        $response = $request->send();
        $requestDebugInfo = $request->getDebugInfo();
        if ($requestDebugInfo) {
            $this->addDebugInfo('request', $requestDebugInfo);
        }
        $status = $response->getStatusCode();
        $body = $response->getBody();
        $content = json_decode($body->getContents(), true);

        // Error handling
        if (200 !== $status) {
            $message = self::ERROR_MESSAGES['get'];
            if (isset($content['error'])) {
                $message = $content['error'].\PHP_EOL.$content['error_description'];
            }
            throw new AccessTokenException($message, $status);
        }

        // Add debug info to response if necessary
        if ($this->debug) {
            $content['debug'] = $this->debugInfo;
        }

        // Caching response
        $this->setCache($content, $content['expires_in']);

        return $content;
    }

    /**
     * Generate a JWT
     *
     * @return string
     *
     * @throws \Pixadelic\Adobe\Exception\AccessTokenException
     */
    private function generateJwt()
    {
        // Check that we can reach the private key
        if (file_exists($this->privateKey)) {
            $privateKey = file_get_contents($this->privateKey);
        } else {
            throw new AccessTokenException(self::ERROR_MESSAGES['generateJwt.privateKey.notFound'], 500);
        }
        if (!isset($privateKey)) {
            throw new AccessTokenException(self::ERROR_MESSAGES['generateJwt.privateKey.undefined'], 500);
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
        $signingInput = implode('.', $segments);

        // Generate signature
        @openssl_sign($signingInput, $signature, $privateKey, 'sha256');
        $segments[] = str_replace($find, $replace, base64_encode($signature));

        return implode('.', $segments);
    }
}
