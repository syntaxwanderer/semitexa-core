<?php

declare(strict_types=1);

namespace Semitexa\Core\Registry;

use ReflectionClass;

/**
 * Generates contract resolver classes in src/registry/Contracts/ when an interface
 * has 2+ implementations. Resolver receives all implementations via constructor (DI)
 * and exposes getContract() for the container to obtain the chosen implementation.
 */
class RegistryContractResolverGenerator
{
    public const REGISTRY_NAMESPACE = 'App\\Registry\\Contracts';
    public const REGISTRY_CONTRACTS_DIR = 'src/registry/Contracts';

    /**
     * Generate resolver classes for interfaces with multiple implementations.
     *
     * @param array<string, array{implementations: list<array{module: string, class: string}>, active: string}> $contractDetails from ServiceContractRegistry, filtered to count(implementations) >= 2
     * @return list<array{interface: string, resolver: string}>
     */
    public static function generateAll(array $contractDetails): array
    {
        $root = self::getProjectRoot();
        $outDir = $root . '/' . self::REGISTRY_CONTRACTS_DIR;
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $result = [];
        foreach ($contractDetails as $interface => $data) {
            $implementations = $data['implementations'] ?? [];
            $active = $data['active'] ?? null;
            if (count($implementations) < 2 || $active === null) {
                continue;
            }
            $resolverClass = self::writeResolverClass($root, $interface, $implementations, $active);
            if ($resolverClass !== null) {
                $result[] = ['interface' => $interface, 'resolver' => $resolverClass];
            }
        }
        return $result;
    }

    public static function getProjectRoot(): string
    {
        $dir = __DIR__;
        while ($dir !== '' && $dir !== '/') {
            if (file_exists($dir . '/composer.json') && is_dir($dir . '/src/modules')) {
                return $dir;
            }
            $dir = dirname($dir);
        }
        return dirname(__DIR__, 4);
    }

    /**
     * @param list<array{module: string, class: string}> $implementations
     */
    private static function writeResolverClass(string $root, string $interface, array $implementations, string $active): ?string
    {
        try {
            $ref = new ReflectionClass($interface);
        } catch (\Throwable $e) {
            return null;
        }
        $shortName = $ref->getShortName();
        $resolverShortName = preg_replace('/Interface$/', 'Resolver', $shortName);
        if ($resolverShortName === $shortName) {
            $resolverShortName = $shortName . 'Resolver';
        }
        $outPath = $root . '/' . self::REGISTRY_CONTRACTS_DIR . '/' . $resolverShortName . '.php';

        $imports = [];
        $usedShortNames = [];
        $params = [];
        $paramNames = [];
        foreach ($implementations as $impl) {
            $implClass = $impl['class'];
            $typeHint = self::addImport($implClass, $imports, $usedShortNames);
            $paramName = self::uniqueParamName($implClass, $paramNames);
            $paramNames[] = $paramName;
            $params[] = "        private {$typeHint} \${$paramName},";
        }
        $paramsStr = implode("\n", $params);
        $paramsStr = rtrim($paramsStr, ',');

        $activeParamName = '';
        foreach ($implementations as $i => $impl) {
            if ($impl['class'] === $active) {
                $activeParamName = $paramNames[$i];
                break;
            }
        }
        if ($activeParamName === '') {
            $activeParamName = $paramNames[0];
        }

        $interfaceTypeHint = self::addImport($interface, $imports, $usedShortNames);
        $useBlock = self::formatUseBlock($imports);

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Registry\Contracts;

{$useBlock}

/**
 * AUTO-GENERATED. Regenerate via: bin/semitexa registry:sync:contracts
 * Resolver for {$interface}. Edit getContract() to choose implementation.
 */
final class {$resolverShortName}
{
    public function __construct(
{$paramsStr}
    ) {}

    public function getContract(): {$interfaceTypeHint}
    {
        return \$this->{$activeParamName};
    }
}

PHP;

        file_put_contents($outPath, $content);
        return self::REGISTRY_NAMESPACE . '\\' . $resolverShortName;
    }

    private static function uniqueParamName(string $class, array $used): string
    {
        $ref = new ReflectionClass($class);
        $short = $ref->getShortName();
        $name = lcfirst($short);
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name) ?: 'impl';
        $base = $name;
        $i = 0;
        while (in_array($name, $used, true)) {
            $i++;
            $name = $base . $i;
        }
        return $name;
    }

    private static function addImport(string $fqn, array &$imports, array &$usedShortNames): string
    {
        $ref = new ReflectionClass($fqn);
        $short = $ref->getShortName();
        if (isset($usedShortNames[$short]) && $usedShortNames[$short] !== $fqn) {
            $alias = $short . 'Alias' . count($usedShortNames);
            $imports[$fqn] = $alias;
            return $alias;
        }
        $usedShortNames[$short] = $fqn;
        $imports[$fqn] = $short;
        return $short;
    }

    private static function formatUseBlock(array $imports): string
    {
        $lines = [];
        foreach ($imports as $fqn => $alias) {
            $short = (new ReflectionClass($fqn))->getShortName();
            $lines[] = "use {$fqn}" . ($alias !== $short ? " as {$alias}" : '') . ";";
        }
        return implode("\n", $lines);
    }
}
