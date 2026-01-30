<?php

declare(strict_types=1);

namespace Syntexa\Core\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Symfony\Component\Process\Process;

/**
 * Composer plugin: after install/update, if syntexa/core is installed and project
 * has no server.php yet, runs "syntexa init" to scaffold the project structure.
 */
final class SyntexaPlugin implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => ['onPostInstallOrUpdate', 0],
            ScriptEvents::POST_UPDATE_CMD => ['onPostInstallOrUpdate', 0],
        ];
    }

    public function onPostInstallOrUpdate(Event $event): void
    {
        $repo = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $repo->findPackage('syntexa/core', '*');
        if ($package === null) {
            return;
        }

        $config = $this->composer->getConfig();
        $vendorDir = $config->get('vendor-dir');
        $root = \dirname($vendorDir);

        if (file_exists($root . '/server.php')) {
            return;
        }

        $this->io->write('<info>Syntexa: scaffolding project structure (syntexa init)...</info>');

        $bin = $vendorDir . '/bin/syntexa';
        if (!file_exists($bin)) {
            $this->io->write('<comment>Syntexa: vendor/bin/syntexa not found, skipping init.</comment>');
            return;
        }

        $php = PHP_BINARY;
        $process = new Process([$php, $bin, 'init'], $root);
        $process->setTimeout(30);
        $process->run(function (string $type, string $buffer): void {
            $this->io->write($buffer, false);
        });

        if (!$process->isSuccessful()) {
            $this->io->write('<comment>Syntexa init failed. Run manually: vendor/bin/syntexa init</comment>');
            return;
        }

        $this->io->write('<info>Syntexa: project structure created. Next: cp .env.example .env && php server.php</info>');
    }
}
