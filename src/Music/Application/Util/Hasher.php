<?php

namespace App\Music\Application\Util;

use App\Music\Domain\Contract\Util\HasherInterface;

class Hasher implements HasherInterface
{
    /**
     * Fast hash function.
     */
    public static function fastHash(mixed $value): string
    {
        return \hash('xxh128', \json_encode($value));
    }
}
