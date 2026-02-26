<?php

declare(strict_types=1);

namespace Semitexa\Core\Console\Command;

use Semitexa\Core\Attributes\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'response:generate', description: 'Build/refresh registry resources (delegates to registry:sync:resources)')]
class ResponseGenerateCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('response:generate')
            ->setDescription('Build/refresh registry resources (delegates to registry:sync:resources)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->note('response:generate now delegates to registry:sync:resources');

        $command = $this->getApplication()->find('registry:sync:resources');
        return $command->run(new ArrayInput([]), $output);
    }
}
