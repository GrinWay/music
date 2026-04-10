## For what?

For removing music with low rating by chosen strategy.

Imaging you downloaded plenty of tracks and want to get rid of ones with low rating.

## Prerequisite

You have to have installed `docker` and `docker compose`.

## Downloading and installation

```
git clone git@github.com:GrinWay/music.git && cd music && touch .env.local .env.dev.local && cd docker
```

## Usage

1.  Copy your music tracks to the `load_music` directory of this project
2.  Run the application (in the `docker` dir):

    ```
    docker compose down && docker compose up
    ```

3.  At last, open a new terminal and execute the removing

    ```
    docker exec -it music sh -c "cd /app/load_music && music rm '< 90'"
    ```
    
    > You say: "I want to remove music with rating < 90 from `load_music` directory"

4.  Accept or deny removal tracks with set rating.

## What is strategy?

Strategy decides what API to choose to download the music rating.

By default, music rating is taken with the help of `deezer` strategy.

To tell the truth, it’s the only available strategy, because it’s free in contrast to non-existent `spotify` strategy.

But if I could obtain an appropriate subscription for an hour, I could make it working…

## Advanced

Inside the container you could pass the strategy explicitly:

```
music rm '< 90' deezer
```

Amazing, I love Symfony.
