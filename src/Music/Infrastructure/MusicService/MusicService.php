<?php

namespace App\Music\Infrastructure\MusicService;

use App\Music\Domain\Contract\Service\GenericMusicServiceInterface;
use App\Music\Domain\Contract\Service\MusicMetadataServiceInterface;
use App\Music\Domain\Type\MusicType;
use App\Music\Infrastructure\Contract\MusicStrategy\MusicStrategyInterface;
use App\Music\Infrastructure\ModuleAdapter\Memcache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
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
        private readonly LoggerInterface $musicLogger,
        private readonly Memcache $memcache,
    ) {
        $this->maxDepth = \sprintf('<= %s', $maxDepth);
    }

    public function getArtistFromMetadata(\SplFileInfo $musicFileInfo): ?string
    {
        $artistCacheKey = \hash('xxh128', \json_encode($musicFileInfo));
        $artist = $this->memcache->get($artistCacheKey);
        if (null !== $artist) {
            return $artist;
        }

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

        $artist = \trim($artist);
        $this->memcache->set($artistCacheKey, $artist, ['app.music.artist']);
        return $artist;
    }

    public function getMusicNameFromMetadata(\SplFileInfo $musicFileInfo): ?string
    {
        $musicNameCacheKey = \hash('xxh128', \json_encode($musicFileInfo));
        $musicName = $this->memcache->get($musicNameCacheKey);
        if (null !== $musicName) {
            return $musicName;
        }

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

        $musicName = \trim($musicName);
        $this->memcache->set($musicNameCacheKey, $musicName, ['app.music.music_name']);
        return $musicName;
    }

    /**
     * Removes existing music by rating.
     *
     * @param ?callable $beforeRemoveMusicCycleHook Throw if you want to cancel removal
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function rm(
        MusicType $strategy,
        string $rating,
        bool $clearStrategyMusicInfoCache,
        ?callable $beforeRemoveMusicCycleHook = null,
    ): self {
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
            $realPath = $finderFileInfo->getRealPath();
            $this->fs->remove($realPath);
            $this->musicLogger->notice(\sprintf('Track "%s" removed', $realPath));
        }
        return $this;
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
