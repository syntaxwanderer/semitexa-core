<?php

declare(strict_types=1);

namespace Semitexa\Core\Console\Command;

use Semitexa\Core\Attributes\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'request:generate', description: 'Build/refresh registry payloads (delegates to registry:sync:payloads)')]
class RequestGenerateCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('request:generate')
            ->setDescription('Build/refresh registry payloads (delegates to registry:sync:payloads)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->note('request:generate now delegates to registry:sync:payloads');

        $command = $this->getApplication()->find('registry:sync:payloads');
        return $command->run(new ArrayInput([]), $output);
    }
}
