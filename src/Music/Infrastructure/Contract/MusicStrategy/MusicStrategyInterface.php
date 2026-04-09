<?php

namespace App\Music\Infrastructure\Contract\MusicStrategy;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Finder\SplFileInfo;

#[AutoconfigureTag(MusicStrategyInterface::class)]
interface MusicStrategyInterface
{
    public function isCorrespondingToRating(SplFileInfo $music, string $rating): bool;
}
