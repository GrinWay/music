<?php

namespace App\Music\Infrastructure\MusicStrategy;

use App\Music\Domain\MusicStrategy\MusicStrategyInterface;
use App\Music\Domain\Type\MusicType;
use App\Music\Infrastructure\Service\SpotifyService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(MusicStrategyInterface::class, ['key' => MusicType::Spotify->value])]
class SpotifyMusicStrategy implements MusicStrategyInterface
{
    public function __construct(
        private readonly SpotifyService $spotifyService,
    ) {
    }
}
