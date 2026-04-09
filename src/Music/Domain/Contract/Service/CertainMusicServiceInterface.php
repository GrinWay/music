<?php

namespace App\Music\Domain\Contract\Service;

interface CertainMusicServiceInterface
{
    public function getMusicInfo(string $artist, string $musicName, bool $throw): ?array;

    /**
     * @return int|null Range: 0 - 100
     */
    public function getCurrentRating(?array $musicInfo): ?int;
}
