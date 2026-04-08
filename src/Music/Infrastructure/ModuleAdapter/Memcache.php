<?php

namespace App\Music\Infrastructure\ModuleAdapter;

use App\Memcache\Domain\Contract\MemcacheApiInterface;
use App\Memcache\Infrastructure\MemcacheApi;

class Memcache implements MemcacheApiInterface
{
    public function __construct(
        private readonly MemcacheApi $memcacheService,
    ) {
    }

    public function get(string $key): mixed
    {
        return $this->memcacheService->get($key);
    }

    public function set(string $key, $value, ?array $tags = null, ?int $ttl = null): mixed
    {
        return $this->memcacheService->set($key, $value, $tags, $ttl);
    }

    public function clearByKey(string ...$keys): mixed
    {
        return $this->memcacheService->clearByKey(...$keys);
    }

    public function clearByTag(string ...$tags): mixed
    {
        return $this->memcacheService->clearByTag(...$tags);
    }
}
