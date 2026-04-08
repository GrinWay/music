<?php

namespace App\Memcache\Domain\Contract;

interface MemcacheApiInterface
{
    public function get(string $key): mixed;

    public function set(string $key, $value, ?array $tags, ?int $ttl): mixed;

    public function clearByKey(string ...$keys): mixed;

    public function clearByTag(string ...$tags): mixed;
}
