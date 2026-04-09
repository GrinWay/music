<?php

namespace App\Music\Domain\Contract\Service;

interface MusicMetadataServiceInterface
{
    public function analyze(string $musicPath): array;
}
