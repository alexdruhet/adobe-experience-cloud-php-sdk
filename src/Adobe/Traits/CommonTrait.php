<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 17/04/2018
 * Time: 16:38
 */

namespace Pixadelic\Adobe\Traits;

use Pixadelic\Adobe\Exception\ClientException;
use Symfony\Component\Cache\Simple\FilesystemCache;

/**
 * Trait CommonTrait
 */
trait CommonTrait
{
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
    protected $cacheId;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var \stdClass
     */
    protected $debugInfo;

    /**
     * Decides whether we are running
     * our calls against production
     * or staging instance.
     *
     * Default to staging.
     *
     * @var bool
     */
    protected $staging = true;

    /**
     * @var string
     */
    protected $stagingSuffix = '-mkt-stage1';

    /**
     * @param mixed $content
     * @param int   $expiration
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setCache($content, $expiration = null)
    {
        if ($this->enableCache) {
            if (!$expiration || !\is_numeric($expiration)) {
                $expiration = $this->expiration;
            }
            $this->addDebugInfo('cache_expiration', $expiration);
            $this->cache->set($this->cacheId, $content, (int) $expiration);
        }
    }

    /**
     * @return mixed|null
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getCache()
    {
        if ($this->hasCache()) {
            return $this->cache->get($this->cacheId);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasCache()
    {
        return $this->enableCache && $this->cache->has($this->cacheId);
    }

    /**
     * @param int $seconds
     *
     * @return $this
     */
    public function setExpiration($seconds)
    {
        $this->flush();

        if (!\is_numeric($seconds)) {
            $seconds = 86400;
        }

        $this->expiration = (int) $seconds;

        return $this;
    }

    /**
     *
     */
    public function flush()
    {
        if ($this->cache && $this->cache->has($this->cacheId)) {
            $this->cache->delete($this->cacheId);
        }
    }

    /**
     * @return bool|\stdClass
     */
    public function getDebugInfo()
    {
        if ($this->debug) {
            return $this->debugInfo;
        }

        return false;
    }

    /**
     * @param array $config
     *
     * @throws \Pixadelic\Adobe\Exception\ClientException
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
            if (isset($config['staging'])) {
                $this->staging = (bool) $config['staging'];
            }
            if ($this->staging) {
                $this->tenant .= $this->stagingSuffix;
                $config['tenant'] = $this->tenant;
            }
            if (isset($config['debug'])) {
                $this->debug = (bool) $config['debug'];
            }
        } catch (\Exception $exception) {
            throw new ClientException($exception->getMessage());
        }

        $this
            ->initDebug()
            ->initCache()
            ->addDebugInfo('config', $config);
    }

    /**
     * @return $this
     */
    protected function initCache()
    {
        if ($this->enableCache) {
            $this->cache = new FilesystemCache();

            return $this->initCacheId();
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function initDebug()
    {
        if ($this->debug) {
            $this->debugInfo = new \stdClass();
            $this->flush();
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     */
    protected function addDebugInfo($name, $value)
    {
        if ($this->debug) {
            $this->debugInfo->{$name} = $value;
        }
    }

    /**
     * @return $this
     */
    protected function initCacheId()
    {
        if (!$this->cacheId) {
            $hash = sha1(md5(str_replace('\\', '', get_class($this))));
            $this->cacheId = "aec.{$hash}";
            $this->addDebugInfo('className', $hash);
            $this->addDebugInfo('cacheId', $this->cacheId);
        }

        return $this;
    }
}
