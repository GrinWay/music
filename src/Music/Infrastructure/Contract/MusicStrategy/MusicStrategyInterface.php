<?php

namespace App\Music\Infrastructure\Contract\MusicStrategy;

use Symfony\Component\Finder\SplFileInfo;

interface MusicStrategyInterface
{
    public function isCorrespondingToRating(SplFileInfo $music, string $rating): bool;

    public function clearCacheByGenericTag(): self;
}
