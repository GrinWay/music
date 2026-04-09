<?php

namespace App\Music\Infrastructure\MusicStrategy;

use App\Music\Domain\Contract\Service\GenericMusicServiceInterface;
use App\Music\Infrastructure\Contract\MusicStrategy\MusicStrategyInterface;
use App\Music\Infrastructure\MusicService\SpotifyMusicService;
use Symfony\Component\Finder\SplFileInfo;

// #[AutoconfigureTag(MusicStrategyInterface::class, ['key' => MusicType::Spotify->value])]
class SpotifyMusicStrategy implements MusicStrategyInterface
{
    public function __construct(
        private readonly GenericMusicServiceInterface $musicService,
        private readonly SpotifyMusicService $spotifyService,
    ) {
    }

    public function isCorrespondingToRating(SplFileInfo $music, string $rating): bool
    {
        $artist = $this->musicService->getArtistFromMetadata($music);
        $musicName = $this->musicService->getMusicNameFromMetadata($music);

        if (empty($artist) || empty($musicName)) {
            return false;
        }

        // todo not realized because of premium subscription absence
        $musicInfo = $this->spotifyService->getMusicInfo($artist, $musicName, false);

        // todo
        return false;
    }
}
