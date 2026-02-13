<?php

declare(strict_types=1);

namespace Semitexa\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Process\Process;

class ServerStartCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('server:start')
            ->setDescription('Start Semitexa Environment (Docker Compose)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectRoot = $this->getProjectRoot();
        
        $io->title('Starting Semitexa Environment (Docker)');

        if (!file_exists($projectRoot . '/docker-compose.yml')) {
            $io->error('docker-compose.yml not found.');
            $io->text([
                'Run <comment>semitexa init</comment> to generate project structure including docker-compose.yml, or add docker-compose.yml manually.',
                'See docs/RUNNING.md or vendor/semitexa/core/docs/RUNNING.md for the supported way to run the app (Docker only).',
            ]);
            return Command::FAILURE;
        }

        $io->section('Starting containers...');
        
        $process = new Process(['docker', 'compose', 'up', '-d'], $projectRoot);
        $process->setTimeout(null);
        
        $process->run(function ($type, $buffer) use ($io) {
            $io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $io->error('Failed to start environment.');
            return Command::FAILURE;
        }

        $io->success('Semitexa environment started successfully!');

        $port = 9502;
        $eventsAsync = '0';
        if (file_exists($projectRoot . '/.env')) {
            $envContent = file_get_contents($projectRoot . '/.env');
            if (preg_match('/^\s*SWOOLE_PORT\s*=\s*(\d+)/m', $envContent, $m)) {
                $port = (int) $m[1];
            }
            if (preg_match('/^\s*EVENTS_ASYNC\s*=\s*(\S+)/m', $envContent, $m)) {
                $eventsAsync = trim($m[1]);
            }
        }
        $io->note('App: http://localhost:' . $port);
        $io->text('To view logs: docker compose logs -f');
        $io->text('To stop: bin/semitexa server:stop (or docker compose down)');

        $this->reportRabbitMqStatus($io, $projectRoot, $eventsAsync);

        return Command::SUCCESS;
    }

    private function reportRabbitMqStatus(SymfonyStyle $io, string $projectRoot, string $eventsAsync): void
    {
        $enabled = in_array(strtolower(trim($eventsAsync)), ['1', 'true', 'yes'], true);
        if (!$enabled) {
            return;
        }

        $cmd = [
            'docker', 'compose', 'exec', '-T', 'app',
            'php', '-r',
            '$h = getenv("RABBITMQ_HOST") ?: "127.0.0.1"; $p = (int)(getenv("RABBITMQ_PORT") ?: "5672"); $s = @fsockopen($h, $p, $err, $errstr, 3); if ($s) { fclose($s); exit(0); } exit(1);',
        ];
        $maxAttempts = 3;
        $delaySeconds = 2;
        $reachable = false;

        sleep(1);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($attempt > 1) {
                sleep($delaySeconds);
            }
            $check = new Process($cmd, $projectRoot);
            $check->setTimeout(8);
            $check->run();
            if ($check->isSuccessful()) {
                $reachable = true;
                break;
            }
        }

        if ($reachable) {
            $io->success('RabbitMQ: reachable (queued events will be sent to the queue).');
        } else {
            $io->warning([
                'RabbitMQ: not reachable after ' . $maxAttempts . ' attempts (network may still be starting).',
                'Queued events will run synchronously. If the rabbitmq service is in docker-compose, try again in a few seconds or set EVENTS_ASYNC=0 in .env to disable queue usage.',
            ]);
        }
    }
}
