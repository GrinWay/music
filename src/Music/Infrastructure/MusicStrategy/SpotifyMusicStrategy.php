<?php

namespace App\Music\Infrastructure\MusicStrategy;

use App\Music\Domain\Contract\Service\CertainMusicServiceInterface;
use App\Music\Domain\Contract\Service\GenericMusicServiceInterface;
use App\Music\Domain\Type\MusicType;
use App\Music\Infrastructure\Contract\MusicStrategy\MusicStrategyInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Finder\SplFileInfo;

//#[AutoconfigureTag(MusicStrategyInterface::class, ['key' => MusicType::Spotify->value])]
class SpotifyMusicStrategy implements MusicStrategyInterface
{
    public function __construct(
        private readonly GenericMusicServiceInterface $musicService,
        private readonly CertainMusicServiceInterface $spotifyService,
    ) {
    }

    /**
     * todo current раскидать логику по сервисам, кто что должен знать.
     */
    public function isCorrespondingToRating(SplFileInfo $music, string $rating): bool
    {
        $artist = $this->musicService->getArtistFromMetadata($music);
        $musicName = $this->musicService->getMusicNameFromMetadata($music);

        if (empty($artist) || empty($musicName)) {
            return false;
        }

        // not realized because of premium subscription absence
        $musicInfo = $this->spotifyService->getMusicInfo($artist, $musicName, false);

        // todo
        return false;
    }
}
