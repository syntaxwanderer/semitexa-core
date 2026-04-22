<?php

declare(strict_types=1);

namespace Semitexa\Core\Console\Command;

use Semitexa\Core\Attribute\AsCommand;
use Semitexa\Core\Container\ServiceContractRegistry;
use Semitexa\Core\Discovery\ClassDiscovery;
use Semitexa\Core\ModuleRegistry;
use Semitexa\Core\Registry\CanonicalRegistryPaths;
use Semitexa\Core\Registry\RegistryContractResolverGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Discover service contracts with 2+ implementations and generate resolver
 * classes in the canonical contracts directory. Single-implementation contracts
 * are not generated; the container binds those interfaces directly.
 */
#[AsCommand(name: 'registry:sync:contracts', description: self::DESCRIPTION)]
class RegistrySyncContractsCommand extends BaseCommand
{
    private const CONTRACTS_PATH = CanonicalRegistryPaths::REGISTRY_CONTRACTS;
    private const DESCRIPTION = 'Generate contract resolvers in ' . self::CONTRACTS_PATH . ' for interfaces with 2+ implementations.';

    public function __construct(
        private readonly ClassDiscovery $classDiscovery,
        private readonly ModuleRegistry $moduleRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('registry:sync:contracts')
            ->setDescription(self::DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $root = $this->getProjectRoot();
        $legacyContractsDir = $this->detectLegacyContractsDir($root);
        if ($legacyContractsDir !== null) {
            $io->error(sprintf(
                'Legacy contract resolver directory detected at %s. Move or remove it before syncing so %s stays the single generated location.',
                $legacyContractsDir,
                self::CONTRACTS_PATH,
            ));
            return Command::FAILURE;
        }
        $this->ensureRegistryDirs($root);

        $contractRegistry = new ServiceContractRegistry($this->classDiscovery, $this->moduleRegistry);
        $details = $contractRegistry->getContractDetails();
        $multiImpl = array_filter($details, function(array $d): bool {
            return count($d['implementations']) >= 2;
        });

        $generated = RegistryContractResolverGenerator::generateAll($multiImpl);
        $generatedFactories = RegistryContractResolverGenerator::generateAllFactories($multiImpl);

        $msg = count($generated) . ' resolver(s)';
        if (count($generatedFactories) > 0) {
            $msg .= ', ' . count($generatedFactories) . ' factory(ies)';
        }
        $io->success('Registry contract resolvers synced: ' . $msg . ' in ' . self::CONTRACTS_PATH);
        return Command::SUCCESS;
    }

    private function ensureRegistryDirs(string $root): void
    {
        $dir = $root . '/' . CanonicalRegistryPaths::REGISTRY_CONTRACTS;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function detectLegacyContractsDir(string $root): ?string
    {
        $legacyContractsDir = 'src/Registry/Contracts';
        $legacyDir = $root . '/' . $legacyContractsDir;
        if ($legacyContractsDir === CanonicalRegistryPaths::REGISTRY_CONTRACTS) {
            return null;
        }

        return is_dir($legacyDir) ? $legacyContractsDir : null;
    }
}
