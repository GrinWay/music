<?php

namespace App\Music\Infrastructure\Service;

use App\Music\Domain\Contract\Service\MusicMetadataServiceInterface;

class MusicMetadataService implements MusicMetadataServiceInterface
{
    private readonly \getID3 $id3;

    public function __construct()
    {
        $this->id3 = new \getID3();
    }

    public function analyze(string $musicPath): array
    {
        $analyzedInfo = $this->id3->analyze($musicPath);
        if (empty($analyzedInfo)) {
            $analyzedInfo = [];
        }
        if (!\is_array($analyzedInfo)) {
            $analyzedInfo = [$analyzedInfo];
        }

        return $analyzedInfo;
    }
}
