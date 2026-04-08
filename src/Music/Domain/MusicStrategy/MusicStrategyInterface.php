<?php

namespace App\Music\Domain\MusicStrategy;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(MusicStrategyInterface::class)]
interface MusicStrategyInterface
{
}
