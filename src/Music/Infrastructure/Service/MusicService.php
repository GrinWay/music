<?php

namespace App\Music\Infrastructure\Service;

use App\Music\Domain\MusicStrategy\MusicStrategyInterface;
use App\Music\Domain\Type\MusicType;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class MusicService
{
    private readonly string $maxDepth;

    public function __construct(
        #[Autowire(env: 'APP_MUSIC_SERVICE_MAX_DEPTH')] int $maxDepth,
        #[Autowire(param: 'app.music_service.music_extensions')] private readonly array $musicExtensions,
        #[AutowireLocator(MusicStrategyInterface::class, 'key')] private readonly ContainerInterface $strategies,
        private readonly Filesystem $fs,
    ) {
        $this->maxDepth = \sprintf('<= %s', $maxDepth);
    }

    /**
     * Removes existing music by rating.
     *
     * @param ?callable $beforeRemoveMusicCycleHook Throw on cancel removal
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function rm(
        MusicType $strategy,
        string $rating,
        bool $throw,
        ?callable $beforeRemoveMusicCycleHook = null,
    ): bool {
        $isAllRemoved = true;
        try {
            /** @var MusicStrategyInterface $strategy */
            $strategy = $this->strategies->get($strategy->value);

            $musicFinder = $this->getMusicFinder();
            $musicFinder = $this->getFilteredByMusicRating($musicFinder, $strategy, $rating);
            /** @var SplFileInfo $finderFileInfo */
            foreach ($musicFinder as $finderFileInfo) {
                if (null !== $beforeRemoveMusicCycleHook) {
                    try {
                        $beforeRemoveMusicCycleHook($finderFileInfo);
                    } catch (\Throwable) {
                        continue;
                    }
                }
                $this->fs->remove($finderFileInfo->getRealPath());
            }
        } catch (\Throwable $e) {
            $isAllRemoved = false;
            if (true === $throw) {
                throw $e;
            }
        }

        return $isAllRemoved;
    }

    private function getMusicFinder(): Finder
    {
        $musicNamePatterns = \array_map(
            static fn (string $ext): string => \sprintf('*.%s', $ext),
            $this->musicExtensions
        );

        return new Finder()
            ->in(\getcwd())
            ->files()
            ->ignoreUnreadableDirs()
            ->ignoreDotFiles(true)
            ->name($musicNamePatterns)
            ->depth($this->maxDepth);
    }

    private function getFilteredByMusicRating(Finder $musicFinder, MusicStrategyInterface $strategy, string $rating): \CallbackFilterIterator
    {
        // false excludes
        $filter = function (SplFileInfo $file) {
            // todo current
            return false;
        };

        return new \CallbackFilterIterator($musicFinder->getIterator(), $filter);
    }
}
