<?php

declare(strict_types=1);

define('SEMITEXA_SWOOLE', true);

require_once __DIR__ . '/vendor/autoload.php';

if (!extension_loaded('swoole')) {
    die("Swoole extension is required.\n");
}

use Swoole\Http\Server;
use Semitexa\Core\Application;
use Semitexa\Core\ErrorHandler;
use Semitexa\Core\Request;

\Semitexa\Core\Container\ContainerFactory::create();
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
        $semitexaRequest = Request::create($request);
        $semitexaResponse = $app->handleRequest($semitexaRequest);
        $response->status($semitexaResponse->getStatusCode());
        foreach ($semitexaResponse->getHeaders() as $name => $value) {
            $response->header($name, $value);
        }
        $response->end($semitexaResponse->getContent());
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

echo "Semitexa server: http://{$env->swooleHost}:{$env->swoolePort}\n";
$server->start();
