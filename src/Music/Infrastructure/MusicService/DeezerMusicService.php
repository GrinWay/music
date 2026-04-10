<?php

namespace App\Music\Infrastructure\MusicService;

use App\Music\Application\Util\Hasher;
use App\Music\Infrastructure\ModuleAdapter\Memcache;
use Psr\Log\LoggerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeezerMusicService extends AbstractCertainMusicService
{
    public const CACHE_SESSION_KEY = 'app.deezer.found_result';
    public const GENERIC_CACHE_TAG = 'app.deezer';

    public function __construct(
        private readonly HttpClientInterface $appDeezerSearchHttpClient,
        private readonly SluggerInterface $slugger,
        private readonly Memcache $memcache,
        private readonly LoggerInterface $musicLogger,
    ) {
    }

    private function getCacheKey(string $searchString): string
    {
        return (string) $this->slugger->slug(
            \sprintf(
                '%s.%s',
                self::CACHE_SESSION_KEY,
                $searchString,
            )
        );
    }

    /**
     * @throws \Throwable if throw is true
     */
    public function getMusicInfo(string $artist, string $musicName, bool $throw): ?array
    {
        try {
            $searchString = \sprintf('%s %s', $artist, $musicName);
            $cachedMusicInfo = $this->memcache->get(
                $this->getCacheKey($searchString)
            );
            if (\is_array($cachedMusicInfo)) {
                return $cachedMusicInfo;
            }
            $musicInfo = $this->appDeezerSearchHttpClient->request('GET', '', [
                'query' => [
                    'q' => $searchString,
                ],
            ])->toArray();
            if (empty($musicInfo)) {
                $musicInfo = [];
            }
            if (!\is_array($musicInfo)) {
                $musicInfo = [$musicInfo];
            }
        } catch (\Throwable $e) {
            if (true === $throw) {
                throw $e;
            }
            $this->musicLogger->critical($e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return null;
        }

        if (!empty($musicInfo)) {
            $this->memcache->set($this->getCacheKey($searchString), $musicInfo, [self::GENERIC_CACHE_TAG]);
        }

        return $musicInfo;
    }

    public function getCurrentRating(?array $musicInfo): ?int
    {
        $hashMusicInfo = Hasher::fastHash($musicInfo);
        $musicInfoCacheKey = $this->getCacheKey($hashMusicInfo);

        $cachedRating = $this->memcache->get($musicInfoCacheKey);
        if (\is_int($cachedRating)) {
            return $cachedRating;
        }

        if (null === $musicInfo) {
            return null;
        }

        $maxRank = \array_reduce($musicInfo['data'] ?? [], static function ($a, $n) {
            $rank = $n['rank'] ?? null;
            if (null !== $rank && $a < $rank) {
                return $rank;
            }

            return $a;
        }, 0);

        $rating = $this->getForm0To100ResizedRating($maxRank);
        $this->memcache->set($musicInfoCacheKey, $rating, ['app.music.rating']);

        return $rating;
    }

    public function clearCacheByGenericTag(): self
    {
        $this->memcache->clearByTag(self::GENERIC_CACHE_TAG);

        return $this;
    }
}
