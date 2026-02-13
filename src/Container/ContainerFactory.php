<?php

declare(strict_types=1);

namespace Semitexa\Core\Container;

use DI\Container;
use DI\ContainerBuilder;

/**
 * Factory for creating DI container
 * Configured for Swoole long-running processes
 */
class ContainerFactory
{
    private static ?Container $container = null;
    private static ?RequestScopedContainer $requestScopedContainerInstance = null;

    /**
     * Get or create the container instance
     * In Swoole, this should be called once per worker
     */
    public static function create(): Container
    {
        if (self::$container === null) {
            $builder = new ContainerBuilder();
            
            // Enable compilation for better performance (optional)
            // $builder->enableCompilation(__DIR__ . '/../../../../var/cache/container');
            
            // Enable autowiring (required for property injection with #[Inject] attributes)
            $builder->useAutowiring(true);
            
            // Enable attributes (required for #[Inject] property injection)
            $builder->useAttributes(true);
            
            // Load definitions
            $builder->addDefinitions(self::getDefinitions());
            
            self::$container = $builder->build();
        }

        return self::$container;
    }

    /**
     * Reset the container (call after each request in Swoole)
     * 
     * Note: PHP-DI doesn't have a built-in reset() method.
     * Instead, we use factory functions for request-scoped services
     * and singleton pattern only for infrastructure services.
     * 
     * This method is kept for compatibility but does nothing.
     * The container is designed to be safe for Swoole by using
     * factory functions that create new instances for each request.
     */
    public static function reset(): void
    {
        // PHP-DI doesn't have reset(), but we use factory functions
        // for request-scoped services, so no reset is needed.
        // Infrastructure services (singletons) are safe to persist.
    }

    /**
     * Get container definitions
     * Can be extended by modules
     * 
     * IMPORTANT FOR SWOOLE:
     * - Use factory() for request-scoped services (creates new instance each time)
     * - Use create() only for infrastructure singletons that are safe to persist
     */
    private static function getDefinitions(): array
    {
        $definitions = [];

        // Core services - Environment is immutable, so singleton is safe
        $definitions[\Semitexa\Core\Environment::class] = \DI\factory(function () {
            return \Semitexa\Core\Environment::create();
        });

        // Infrastructure services - singleton is safe (stateless or connection pool)
        $definitions[\Semitexa\Core\Queue\QueueTransportRegistry::class] = \DI\factory(function () {
            $registry = new \Semitexa\Core\Queue\QueueTransportRegistry();
            $registry->initialize();
            return $registry;
        });

        $definitions[\Semitexa\Core\Event\EventDispatcher::class] = \DI\autowire();

        $definitions[\Semitexa\Core\Log\LoggerInterface::class] = \DI\autowire(\Semitexa\Core\Log\AsyncJsonLogger::class);

        // Session and Cookie: resolved from request-scoped container (set by Application at request start)
        $definitions[\Semitexa\Core\Session\SessionInterface::class] = \DI\factory(function () {
            return self::getRequestScoped()->get(\Semitexa\Core\Session\SessionInterface::class);
        });
        $definitions[\Semitexa\Core\Cookie\CookieJarInterface::class] = \DI\factory(function () {
            return self::getRequestScoped()->get(\Semitexa\Core\Cookie\CookieJarInterface::class);
        });
        $definitions[\Semitexa\Core\Request::class] = \DI\factory(function () {
            return self::getRequestScoped()->get(\Semitexa\Core\Request::class);
        });

        // Database Connection Pool - singleton (safe to persist)
        // Initialize once when container is created
        $definitions[\Semitexa\Orm\Connection\ConnectionPool::class] = \DI\factory(function () {
            // Load .env from project root
            $projectRoot = self::getProjectRoot();
            $envFile = $projectRoot . '/.env';
            $env = [];
            
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        [$key, $value] = explode('=', $line, 2);
                        $env[trim($key)] = trim($value);
                    }
                }
            }
            
            $dbConfig = [
                'host' => \Semitexa\Core\Environment::getEnvValue('DB_HOST', 'localhost'),
                'port' => (int) \Semitexa\Core\Environment::getEnvValue('DB_PORT', '5432'),
                'dbname' => \Semitexa\Core\Environment::getEnvValue('DB_NAME', 'semitexa'),
                'user' => \Semitexa\Core\Environment::getEnvValue('DB_USER', 'postgres'),
                'password' => \Semitexa\Core\Environment::getEnvValue('DB_PASSWORD', ''),
                'charset' => \Semitexa\Core\Environment::getEnvValue('DB_CHARSET', 'utf8'),
                'pool_size' => (int) \Semitexa\Core\Environment::getEnvValue('DB_POOL_SIZE', '10'),
            ];
            
            // Only initialize if Swoole is available
            if (extension_loaded('swoole')) {
                \Semitexa\Orm\Connection\ConnectionPool::initialize($dbConfig);
            }
            
            // ConnectionPool uses static methods, so we return the class name
            return \Semitexa\Orm\Connection\ConnectionPool::class;
        });

        // Entity Manager - request-scoped (new instance each request)
        // For Swoole: uses ConnectionPool (must be initialized first)
        // For CLI: creates direct PDO connection
        $definitions[\Semitexa\Orm\Entity\EntityManager::class] = \DI\factory(function (\DI\Container $c) {
            // Check if we're in Swoole context
            if (extension_loaded('swoole') && \Swoole\Coroutine::getCid() >= 0) {
                // Swoole context - ensure ConnectionPool is initialized first
                // Get ConnectionPool definition to trigger initialization
                $c->get(\Semitexa\Orm\Connection\ConnectionPool::class);
                
                // Now create EntityManager which will use ConnectionPool::get()
                return new \Semitexa\Orm\Entity\EntityManager();
            } else {
                // CLI context - create direct PDO connection
                // Load .env from project root
                $projectRoot = self::getProjectRoot();
                $envFile = $projectRoot . '/.env';
                $env = [];
                
                if (file_exists($envFile)) {
                    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($lines as $line) {
                        if (strpos($line, '#') === 0) {
                            continue;
                        }
                        if (strpos($line, '=') !== false) {
                            [$key, $value] = explode('=', $line, 2);
                            $env[trim($key)] = trim($value);
                        }
                    }
                }
                
                $dbConfig = [
                    'host' => \Semitexa\Core\Environment::getEnvValue('DB_HOST', 'localhost'),
                    'port' => (int) \Semitexa\Core\Environment::getEnvValue('DB_PORT', '5432'),
                    'dbname' => \Semitexa\Core\Environment::getEnvValue('DB_NAME', 'semitexa'),
                    'user' => \Semitexa\Core\Environment::getEnvValue('DB_USER', 'postgres'),
                    'password' => \Semitexa\Core\Environment::getEnvValue('DB_PASSWORD', ''),
                ];
                
                $dsn = sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s',
                    $dbConfig['host'],
                    $dbConfig['port'],
                    $dbConfig['dbname']
                );
                
                $pdo = new \PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);
                
                return new \Semitexa\Orm\Entity\EntityManager($pdo);
            }
        });

        // User Domain services - request-scoped (new instance each request)
        $definitions[\Semitexa\UserDomain\Domain\Service\LoginAnalyticsService::class] = \DI\factory(function () {
            return new \Semitexa\UserDomain\Domain\Service\LoginAnalyticsService();
        });

        // User repository - request-scoped (uses EntityManager)
        $userRepoInterface = \Semitexa\UserDomain\Domain\Repository\UserRepositoryInterface::class;
        $userRepoDefault = \Semitexa\UserDomain\Domain\Repository\UserRepository::class;
        if (interface_exists($userRepoInterface) && class_exists($userRepoDefault)) {
            $definitions[$userRepoInterface] = \DI\factory(function (\DI\Container $c) use ($userRepoDefault) {
                $em = $c->get(\Semitexa\Orm\Entity\EntityManager::class);
                return new $userRepoDefault($em);
            });
        }

        // Auth service - request-scoped
        $definitions[\Semitexa\UserDomain\Domain\Service\AuthService::class] = \DI\autowire();

        // Handlers with property injection - use autowire to enable property injection
        $definitions[\Semitexa\UserFrontend\Application\Handler\Request\LoginFormHandler::class] = \DI\autowire();
        $definitions[\Semitexa\UserFrontend\Application\Handler\Request\DashboardHandler::class] = \DI\autowire();

        // Example: Infrastructure singleton (safe to persist)
        // $definitions[\Semitexa\Core\Database\ConnectionPool::class] = \DI\create()
        //     ->constructor(\DI\get('db.config'));

        // Service contracts: use registry resolver when present (convention: App\Registry\Contracts\{InterfaceShortName}Resolver), else bind to single implementation
        $contractRegistry = new ServiceContractRegistry();
        foreach ($contractRegistry->getContracts() as $contract => $impl) {
            $resolverClass = self::getResolverClassForContract($contract);
            if ($resolverClass !== null && class_exists($resolverClass)) {
                $definitions[$resolverClass] = \DI\autowire($resolverClass);
                $definitions[$contract] = \DI\factory(function (\DI\Container $c) use ($resolverClass) {
                    return $c->get($resolverClass)->getContract();
                });
                // Factory* convention: if interface Factory{ContractShortName} exists, bind it to generated App\Registry\Contracts\{ContractShortName}Factory
                $factoryInterface = \Semitexa\Core\Registry\RegistryContractResolverGenerator::getFactoryInterfaceForContract($contract);
                if ($factoryInterface !== null && interface_exists($factoryInterface)) {
                    $factoryClass = \Semitexa\Core\Registry\RegistryContractResolverGenerator::getGeneratedFactoryClassForContract($contract);
                    if (class_exists($factoryClass)) {
                        $definitions[$factoryInterface] = \DI\autowire($factoryClass);
                    }
                }
            } else {
                $definitions[$contract] = \DI\autowire($impl);
            }
        }

        return $definitions;
    }

    /**
     * Resolver class by convention: App\Registry\Contracts\{ShortName}Resolver (Interface suffix → Resolver).
     */
    private static function getResolverClassForContract(string $interface): ?string
    {
        if (!interface_exists($interface)) {
            return null;
        }
        $short = (new \ReflectionClass($interface))->getShortName();
        $resolverShort = preg_replace('/Interface$/', 'Resolver', $short);
        if ($resolverShort === $short) {
            $resolverShort = $short . 'Resolver';
        }
        return 'App\\Registry\\Contracts\\' . $resolverShort;
    }

    /**
     * Get project root directory
     */
    private static function getProjectRoot(): string
    {
        $dir = __DIR__;
        while ($dir !== '/' && $dir !== '') {
            if (file_exists($dir . '/composer.json')) {
                if (is_dir($dir . '/src/modules')) {
                    return $dir;
                }
            }
            $dir = dirname($dir);
        }
        return dirname(__DIR__, 6);
    }

    /**
     * Get the singleton container instance
     */
    public static function get(): Container
    {
        return self::create();
    }

    /**
     * Get request-scoped container wrapper (singleton per worker).
     * Application uses this to set Session/Cookie and resolve handlers; container factories delegate here for Session/Cookie.
     */
    public static function getRequestScoped(): RequestScopedContainer
    {
        if (self::$requestScopedContainerInstance === null) {
            self::$requestScopedContainerInstance = new RequestScopedContainer(self::create());
        }
        return self::$requestScopedContainerInstance;
    }
}

