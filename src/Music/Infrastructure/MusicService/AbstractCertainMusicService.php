<?php

namespace App\Music\Infrastructure\MusicService;

use App\Music\Domain\Contract\Service\CertainMusicServiceInterface;

abstract class AbstractCertainMusicService implements CertainMusicServiceInterface
{
    /**
     * Resizes range from 0 to 100.
     */
    protected function getForm0To100ResizedRating(?int $rating): ?int
    {
        if (null === $rating) {
            return null;
        }

        if (0 === $rating) {
            return 0;
        }

        return \min(100, \round(\log10($rating) / \log10(1000000) * 100)) % 101;
    }
}
