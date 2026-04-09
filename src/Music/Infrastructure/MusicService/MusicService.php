<?php

namespace App\Music\Infrastructure\MusicService;

use App\Music\Domain\Contract\Service\GenericMusicServiceInterface;
use App\Music\Domain\Contract\Service\MusicMetadataServiceInterface;
use App\Music\Domain\Type\MusicType;
use App\Music\Infrastructure\Contract\MusicStrategy\MusicStrategyInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class MusicService implements GenericMusicServiceInterface
{
    private readonly string $maxDepth;

    public function __construct(
        #[Autowire(env: 'APP_MUSIC_SERVICE_MAX_DEPTH')] int $maxDepth,
        #[Autowire(param: 'app.music_service.music_extensions')] private readonly array $musicExtensions,
        #[AutowireLocator(MusicStrategyInterface::class, 'key')] private readonly ContainerInterface $strategyLocator,
        private readonly Filesystem $fs,
        private readonly MusicMetadataServiceInterface $musicMetadataService,
    ) {
        $this->maxDepth = \sprintf('<= %s', $maxDepth);
    }

    public function getArtistFromMetadata(\SplFileInfo $musicFileInfo): ?string
    {
        $realPath = $musicFileInfo->getRealPath();

        $artistNameByDirName = \basename(Path::getDirectory($realPath));
        $artistNameByDirName = !empty($artistNameByDirName) ? $artistNameByDirName : null;

        $metadata = $this->musicMetadataService->analyze($musicFileInfo->getRealPath());

        $artist =
            $metadata['comments']['artist'][0]
            ?? $metadata['comments_html']['artist'][0]
            ?? $metadata['tags']['id3v2']['artist'][0]
            ?? $metadata['tags']['quicktime']['artist'][0]
            ?? $metadata['tags']['vorbiscomment']['artist'][0]
            ?? $metadata['tags']['ape']['artist'][0]
            ?? $artistNameByDirName
            ?? null;

        return \trim($artist);
    }

    public function getMusicNameFromMetadata(\SplFileInfo $musicFileInfo): ?string
    {
        $metadata = $this->musicMetadataService->analyze(
            $musicFileInfo->getRealPath()
        );

        $musicName =
            $metadata['comments']['title'][0]
            ?? $metadata['comments_html']['title'][0]
            ?? $metadata['tags']['id3v2']['title'][0]
            ?? $metadata['tags']['quicktime']['title'][0]
            ?? $metadata['tags']['vorbiscomment']['title'][0]
            ?? $metadata['tags']['ape']['title'][0]
            ?? \preg_replace('~^(.+)[.][^.]+$~', '$1', $musicFileInfo->getFilename())
            ?? null;

        return \trim($musicName);
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
        bool $clearStrategyMusicInfoCache,
        bool $throw,
        ?callable $beforeRemoveMusicCycleHook = null,
    ): bool {
        $isAllRemoved = true;
        try {
            /** @var MusicStrategyInterface $strategy */
            $strategy = $this->strategyLocator->get($strategy->value);

            if (true === $clearStrategyMusicInfoCache) {
                $strategy->clearCacheByGenericTag();
            }

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
        $filter = function (SplFileInfo $file) use ($strategy, $rating) {
            return $strategy->isCorrespondingToRating($file, $rating);
        };

        return new \CallbackFilterIterator($musicFinder->getIterator(), $filter);
    }
}
