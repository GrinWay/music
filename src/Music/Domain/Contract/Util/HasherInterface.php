<?php

namespace App\Music\Domain\Contract\Util;

interface HasherInterface
{
    public static function fastHash(mixed $value): string;
}
