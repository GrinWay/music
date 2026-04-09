<?php

namespace App\Music\Infrastructure\Contract\Service;

interface GenericMusicServiceInterface
{
    public function getArtistFromMetadata(\SplFileInfo $musicFileInfo): ?string;

    public function getMusicNameFromMetadata(\SplFileInfo $musicFileInfo): ?string;
}
