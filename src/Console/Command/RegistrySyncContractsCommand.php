<?php

declare(strict_types=1);

namespace Semitexa\Core\Console\Command;

use Semitexa\Core\Container\ServiceContractRegistry;
use Semitexa\Core\IntelligentAutoloader;
use Semitexa\Core\ModuleRegistry;
use Semitexa\Core\Registry\RegistryContractResolverGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Discover service contracts with 2+ implementations, generate resolver classes
 * in src/registry/Contracts/. Single-implementation contracts are not generated
 * (container binds interface to that implementation directly).
 */
class RegistrySyncContractsCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('registry:sync:contracts')
            ->setDescription('Generate contract resolvers in src/registry/Contracts/ for interfaces with 2+ implementations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $root = $this->getProjectRoot();
        $this->ensureRegistryDirs($root);

        IntelligentAutoloader::initialize();
        ModuleRegistry::initialize();

        $contractRegistry = new ServiceContractRegistry();
        $details = $contractRegistry->getContractDetails();
        $multiImpl = array_filter($details, fn(array $d): bool => count($d['implementations'] ?? []) >= 2);

        $generated = RegistryContractResolverGenerator::generateAll($multiImpl);

        $io = new SymfonyStyle($input, $output);
        $io->success('Registry contract resolvers synced: ' . count($generated) . ' resolver(s) in src/registry/Contracts/.');
        return Command::SUCCESS;
    }

    private function ensureRegistryDirs(string $root): void
    {
        $dir = $root . '/' . RegistryContractResolverGenerator::REGISTRY_CONTRACTS_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
