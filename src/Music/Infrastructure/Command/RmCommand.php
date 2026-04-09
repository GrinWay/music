<?php

namespace App\Music\Infrastructure\Command;

use App\Music\Domain\Type\MusicType;
use App\Music\Infrastructure\Service\MusicService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Invocable commands.
 *
 * https://symfony.com/blog/new-in-symfony-7-4-improved-invokable-commands
 */
#[AsCommand(
    name: 'rm',
    description: 'todo current',
)]
class RmCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly MusicService        $musicService,
        private readonly TranslatorInterface $t,
        private readonly Filesystem          $fs,
        ?string                              $name = null,
        ?callable                            $code = null,
    ) {
        parent::__construct($name, $code);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    public function __invoke(
        #[Argument] MusicType $strategy,
        #[Argument] string $rating,
    ): int {
        $filesToRemove = [];
        $beforeRmCycleHook = static function (SplFileInfo $splFileInfo) use (&$filesToRemove): void {
            $filesToRemove[] = $splFileInfo->getRealPath();
            throw new \RuntimeException('Do not remove immediately.');
        };
        $this->musicService->rm(
            $strategy,
            $rating,
            false,
            $beforeRmCycleHook
        );

        if (empty($filesToRemove)) {
            $this->io->warning($this->t->trans('command.rm.no_files_to_remove'));

            return Command::SUCCESS;
        }

        $params = [
            '{{ files_to_remove }}' => \implode(\PHP_EOL, $filesToRemove),
        ];
        $question = $this->t->trans(
            'is.remove_music',
            $params
        );
        if ($this->io->confirm($question, false)) {
            foreach ($filesToRemove as $musicRealPath) {
                $this->removeMusic($musicRealPath);
            }
        } else {
            $this->io->info($this->t->trans('command.rm.canceled'));

            return Command::SUCCESS;
        }

        $this->io->success($this->t->trans('command.rm.success'));

        return Command::SUCCESS;
    }

    private function removeMusic(string $musicRealPath): void
    {
        try {
            $this->fs->remove($musicRealPath);
        } catch (\Throwable) {
            $this->io->warning([
                $this->t->trans('error.remove_music', ['{{ music_real_path }}' => $musicRealPath]),
            ]);
        }
    }
}
