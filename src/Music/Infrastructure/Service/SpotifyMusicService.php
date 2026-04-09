<?php

namespace App\Music\Infrastructure\Service;

use App\Music\Domain\Contract\Service\CertainMusicServiceInterface;
use App\Music\Infrastructure\ModuleAdapter\Memcache;
use SpotifyWebAPI\Session as WebApiSession;
use SpotifyWebAPI\SpotifyWebAPI;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SpotifyMusicService implements CertainMusicServiceInterface
{
    public const CACHE_SESSION_KEY = 'app.spotify.session';

    private readonly WebApiSession $webApiSession;
    private readonly SpotifyWebAPI $webApi;
    private readonly string $accessToken;

    public function __construct(
        #[Autowire(env: 'APP_SPOTIFY_CLIENT_ID')] private readonly string     $clientId,
        #[Autowire(env: 'APP_SPOTIFY_CLIENT_SECRET')] private readonly string $clientSecret,
        private readonly Memcache                                             $memcache,
    ) {
        $this->webApiSession = new WebApiSession(
            $this->clientId,
            $this->clientSecret
        );

        if (!\is_string($this->memcache->get(self::CACHE_SESSION_KEY))) {
            $this->webApiSession->requestCredentialsToken();
            $accessToken = $this->webApiSession->getAccessToken();
            $this->memcache->set(self::CACHE_SESSION_KEY, $accessToken, ['spotify'], 60 * 59);
        }
        $this->accessToken = $this->memcache->get(self::CACHE_SESSION_KEY);

        $this->webApi = new SpotifyWebAPI();
        $this->webApi->setAccessToken($this->accessToken);
    }

    public function getMusicInfo(string $artist, string $musicName, bool $throw): ?array
    {
        try {
            // \sprintf('artist:%s track:%s', $artist, $musicName)
            $query = 'Blinding Lights The Weeknd';
            $type = 'track';
            $options = ['limit' => 1];
            $musicInfo = $this->webApi->search($query, $type, $options);
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

            return null;
        }

        return $musicInfo;
    }
}
