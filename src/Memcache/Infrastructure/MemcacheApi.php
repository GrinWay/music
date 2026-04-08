<?php

namespace App\Memcache\Infrastructure;

use App\Memcache\Domain\Contract\MemcacheApiInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class MemcacheApi implements MemcacheApiInterface
{
    public function __construct(
        private readonly TagAwareCacheInterface $memcache,
    ) {
    }

    public function get(string $key): mixed
    {
        if (!$this->memcache->hasItem($key)) {
            return null;
        }
        $cacheItem = $this->memcache->getItem($key);
        if ($cacheItem instanceof CacheItem) {
            return $cacheItem->get();
        }

        return $cacheItem;
    }

    public function set(string $key, mixed $value, ?array $tags, ?int $ttl): bool
    {
        /** @var CacheItem $cacheItem */
        $cacheItem = $this->memcache->getItem($key);
        $cacheItem->set($value);
        if (null !== $tags) {
            $cacheItem->tag($tags);
        }
        if (null !== $ttl) {
            $cacheItem->expiresAfter($ttl);
        }

        return $this->memcache->save($cacheItem);
    }

    public function clearByKey(string ...$keys): bool
    {
        return $this->memcache->deleteItems($keys);
    }

    public function clearByTag(string ...$tags): bool
    {
        return $this->memcache->invalidateTags($tags);
    }
}
