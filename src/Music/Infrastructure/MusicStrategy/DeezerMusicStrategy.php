<?php

namespace App\Music\Infrastructure\MusicStrategy;

use App\Music\Domain\Contract\Service\GenericMusicServiceInterface;
use App\Music\Domain\Type\MusicType;
use App\Music\Infrastructure\Contract\MusicStrategy\MusicStrategyInterface;
use App\Music\Infrastructure\MusicService\DeezerMusicService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Finder\SplFileInfo;

#[AutoconfigureTag(MusicStrategyInterface::class, ['key' => MusicType::Deezer->value])]
class DeezerMusicStrategy implements MusicStrategyInterface
{
    public function __construct(
        private readonly GenericMusicServiceInterface $musicService,
        private readonly ExpressionLanguage $musicExpressionLanguage,
        private readonly DeezerMusicService $deezerMusicService,
    ) {
    }

    public function isCorrespondingToRating(SplFileInfo $music, string $rating): bool
    {
        $artist = $this->musicService->getArtistFromMetadata($music);
        $musicName = $this->musicService->getMusicNameFromMetadata($music);

        if (empty($artist) || empty($musicName)) {
            return false;
        }

        $musicInfo = $this->deezerMusicService->getMusicInfo($artist, $musicName, false);
        $currentRating = $this->deezerMusicService->getCurrentRating($musicInfo);
        if (null === $currentRating) {
            return false;
        }

        $isCorrespondingToRating = $this->musicExpressionLanguage->evaluate(
            \sprintf('%s %s', $currentRating, $rating)
        );

        return (bool) $isCorrespondingToRating;
    }

    public function clearCacheByGenericTag(): MusicStrategyInterface
    {
        $this->deezerMusicService->clearCacheByGenericTag();

        return $this;
    }
}
