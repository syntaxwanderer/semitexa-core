<?php

declare(strict_types=1);

namespace Syntexa\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Scaffolds Syntexa project structure: bin/, public/, src/, var/, .env.example, server.php, bin/syntexa.
 */
class InitCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('init')
            ->setDescription('Create Syntexa project structure (bin, public, src, var, server.php, .env.example)')
            ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'Target directory (default: current working directory)', null)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dir = $input->getOption('dir');
        $force = (bool) $input->getOption('force');

        $root = $dir !== null ? realpath($dir) : getcwd();
        if ($root === false || !is_dir($root)) {
            $io->error('Target directory does not exist or is not readable: ' . ($dir ?? getcwd()));
            return Command::FAILURE;
        }

        $io->title('Syntexa project init');
        $io->text('Project root: ' . $root);

        $dirs = [
            'bin',
            'public',
            'src/modules',
            'src/infrastructure/database',
            'src/infrastructure/migrations',
            'var/cache',
            'var/log',
            'var/docs',
        ];

        foreach ($dirs as $path) {
            $full = $root . '/' . $path;
            if (!is_dir($full)) {
                if (!@mkdir($full, 0755, true)) {
                    $io->error('Failed to create directory: ' . $path);
                    return Command::FAILURE;
                }
                $io->text('Created: ' . $path . '/');
            }
        }

        // Keep empty var subdirs in git
        foreach (['var/cache', 'var/log', 'var/docs'] as $path) {
            $gitkeep = $root . '/' . $path . '/.gitkeep';
            if (!file_exists($gitkeep)) {
                file_put_contents($gitkeep, '');
            }
        }

        $created = [];
        $skipped = [];

        $files = [
            '.env.example' => $this->getEnvExampleContent(),
            'server.php' => $this->getServerPhpContent(),
            'bin/syntexa' => $this->getBinSyntexaContent(),
            '.gitignore' => $this->getGitignoreContent(),
            'public/.htaccess' => $this->getHtaccessContent(),
        ];

        foreach ($files as $relPath => $content) {
            $full = $root . '/' . $relPath;
            if (file_exists($full) && !$force) {
                $skipped[] = $relPath;
                continue;
            }
            $dir = dirname($full);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (file_put_contents($full, $content) === false) {
                $io->error('Failed to write: ' . $relPath);
                return Command::FAILURE;
            }
            if ($relPath === 'bin/syntexa') {
                @chmod($full, 0755);
            }
            $created[] = $relPath;
        }

        foreach ($created as $f) {
            $io->text('Written: ' . $f);
        }
        foreach ($skipped as $f) {
            $io->note('Skipped (exists): ' . $f . ' (use --force to overwrite)');
        }

        $io->success('Project structure created.');
        $io->text([
            'Next steps:',
            '  1. cp .env.example .env',
            '  2. Edit .env (SWOOLE_PORT, etc.)',
            '  3. Add your modules under src/modules/',
            '  4. Run: php server.php (or use bin/syntexa server:start if using Docker)',
        ]);

        return Command::SUCCESS;
    }

    private function getEnvExampleContent(): string
    {
        return <<<'ENV'
# Environment: dev, test, prod
APP_ENV=dev
APP_DEBUG=1
APP_NAME="Syntexa App"

# Swoole server
SWOOLE_HOST=0.0.0.0
SWOOLE_PORT=9501
SWOOLE_WORKER_NUM=4
SWOOLE_MAX_REQUEST=10000
SWOOLE_MAX_COROUTINE=100000
SWOOLE_LOG_FILE=var/log/swoole.log
SWOOLE_LOG_LEVEL=1

# CORS
CORS_ALLOW_ORIGIN=*
CORS_ALLOW_METHODS=GET, POST, PUT, DELETE, OPTIONS
CORS_ALLOW_HEADERS=Content-Type, Authorization
CORS_ALLOW_CREDENTIALS=false

ENV;
    }

    private function getServerPhpContent(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

define('SYNTEXA_SWOOLE', true);

require_once __DIR__ . '/vendor/autoload.php';

if (!extension_loaded('swoole')) {
    die("Swoole extension is required.\n");
}

use Swoole\Http\Server;
use Syntexa\Core\Application;
use Syntexa\Core\ErrorHandler;
use Syntexa\Core\Request;

\Syntexa\Core\Container\ContainerFactory::create();
$app = new Application();
$env = $app->getEnvironment();
ErrorHandler::configure($env);

$server = new Server($env->swooleHost, $env->swoolePort);
$server->set([
    'worker_num' => $env->swooleWorkerNum,
    'max_request' => $env->swooleMaxRequest,
    'enable_coroutine' => true,
]);

$server->on('request', function ($request, $response) use ($app, $env) {
    $response->header('Access-Control-Allow-Origin', $env->corsAllowOrigin);
    $response->header('Access-Control-Allow-Methods', $env->corsAllowMethods);
    $response->header('Access-Control-Allow-Headers', $env->corsAllowHeaders);

    if (($request->server['request_method'] ?? 'GET') === 'OPTIONS') {
        $response->status(200);
        $response->end();
        return;
    }

    try {
        $syntexaRequest = Request::create($request);
        $syntexaResponse = $app->handleRequest($syntexaRequest);
        $response->status($syntexaResponse->getStatusCode());
        foreach ($syntexaResponse->getHeaders() as $name => $value) {
            $response->header($name, $value);
        }
        $response->end($syntexaResponse->getContent());
    } catch (\Throwable $e) {
        $response->status(500);
        $response->header('Content-Type', 'application/json');
        $response->end(json_encode([
            'error' => 'Internal Server Error',
            'message' => $e->getMessage(),
        ]));
    } finally {
        $app->getRequestScopedContainer()->reset();
    }
});

echo "Syntexa server: http://{$env->swooleHost}:{$env->swoolePort}\n";
$server->start();

PHP;
    }

    private function getBinSyntexaContent(): string
    {
        return <<<'PHP'
#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Syntexa\Core\Discovery\AttributeDiscovery;
use Syntexa\Core\Console\Application;

AttributeDiscovery::initialize();
$application = new Application();
$application->run();

PHP;
    }

    private function getGitignoreContent(): string
    {
        return <<<'GIT'
/vendor/
.env
var/cache/*
var/log/*
var/docs/*
!.gitkeep

GIT;
    }

    private function getHtaccessContent(): string
    {
        return <<<'HTA'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

HTA;
    }
}
