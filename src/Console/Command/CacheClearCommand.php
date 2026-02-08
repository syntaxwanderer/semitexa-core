<?php

declare(strict_types=1);

namespace Semitexa\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clear application cache (Twig compiled templates, etc.).
 * Use after changing templates or when you see stale layout/template behaviour.
 */
class CacheClearCommand extends BaseCommand
{
    private const CACHE_DIRS = ['twig'];

    protected function configure(): void
    {
        $this->setName('cache:clear')
            ->setDescription('Clear application cache (e.g. var/cache/twig). Use after template changes or when cache is stale.')
            ->addOption('twig', null, InputOption::VALUE_NONE, 'Clear only Twig cache (default: clear all known cache dirs)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $root = $this->getProjectRoot();
        $baseDir = $root . '/var/cache';
        $twigOnly = (bool) $input->getOption('twig');

        $dirsToClear = $twigOnly ? ['twig'] : self::CACHE_DIRS;
        $cleared = [];
        $failed = [];

        foreach ($dirsToClear as $subDir) {
            $path = $baseDir . '/' . $subDir;
            if (!is_dir($path)) {
                continue;
            }
            if ($this->removeDirectoryContents($path)) {
                $cleared[] = 'var/cache/' . $subDir;
            } else {
                $failed[] = 'var/cache/' . $subDir . ' (check permissions; if running Docker, try: docker compose exec app bin/semitexa cache:clear)';
            }
        }

        if ($cleared !== []) {
            $io->success('Cleared: ' . implode(', ', $cleared));
        }
        if ($failed !== []) {
            $io->warning('Could not clear: ' . implode('; ', $failed));
            return Command::FAILURE;
        }
        if ($cleared === [] && $failed === []) {
            $io->text('Nothing to clear (no cache directories found under var/cache).');
        }

        return Command::SUCCESS;
    }

    /**
     * Remove all contents of a directory (files and subdirs). Directory itself is kept.
     */
    private function removeDirectoryContents(string $path): bool
    {
        if (!is_dir($path) || !is_readable($path)) {
            return false;
        }
        $ok = true;
        $items = @scandir($path);
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $ok = $this->removeDirectoryRecursive($full) && $ok;
            } else {
                $ok = @unlink($full) && $ok;
            }
        }
        return $ok;
    }

    private function removeDirectoryRecursive(string $path): bool
    {
        if (!is_dir($path)) {
            return true;
        }
        $items = @scandir($path);
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $this->removeDirectoryRecursive($full);
            }
            @unlink($full);
        }
        return @rmdir($path);
    }
}
