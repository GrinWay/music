<?php

namespace App\Music\Domain\Contract\Service;

interface GenericMusicServiceInterface
{
    public function getArtistFromMetadata(\SplFileInfo $musicFileInfo): ?string;

    public function getMusicNameFromMetadata(\SplFileInfo $musicFileInfo): ?string;
}
