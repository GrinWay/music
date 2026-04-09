<?php

namespace App\Music\Domain\Contract\Service;

interface CertainMusicServiceInterface
{
    public function getMusicInfo(string $artist, string $musicName, bool $throw): ?array;
}
