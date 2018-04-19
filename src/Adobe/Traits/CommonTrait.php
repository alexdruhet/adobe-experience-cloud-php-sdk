<?php
/**
 * Created by PhpStorm.
 * User: pixadelic
 * Date: 17/04/2018
 * Time: 16:38
 */

namespace Pixadelic\Adobe\Traits;

use Symfony\Component\Cache\Simple\FilesystemCache;

/**
 * Trait CommonTrait
 */
trait CommonTrait
{
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
