<?php

namespace App\Music\Infrastructure\MusicService;

use App\Music\Infrastructure\ModuleAdapter\Memcache;
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
    ) {
    }

    private function getCacheKey(string $searchString)
    {
        return $this->slugger->slug(
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
            // todo current: throw new \RuntimeException('test ELK');
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
            // todo current logging to ELK
            if (true === $throw) {
                throw $e;
            }

            return null;
        }

        if (!empty($musicInfo)) {
            $this->memcache->set($this->getCacheKey($searchString), $musicInfo, [self::GENERIC_CACHE_TAG]);
        }

        return $musicInfo;
    }

    public function getCurrentRating(?array $musicInfo): ?int
    {
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

        return $this->getForm0To100ResizedRating($maxRank);
    }

    public function clearCacheByGenericTag(): self
    {
        $this->memcache->clearByTag(self::GENERIC_CACHE_TAG);

        return $this;
    }
}
