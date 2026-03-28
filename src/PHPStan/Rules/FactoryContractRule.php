<?php

declare(strict_types=1);

namespace Semitexa\Core\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * semitexa.factoryContract
 *
 * Flags factory interfaces (name starts with "Factory") that do not extend
 * ContractFactoryInterface or whose get() signature is not enum-keyed.
 *
 * @implements Rule<Interface_>
 */
final class FactoryContractRule implements Rule
{
    public function getNodeType(): string
    {
        return Interface_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $name = $node->name?->name ?? '';
        if (!str_starts_with($name, 'Factory')) {
            return [];
        }

        // Check if it extends ContractFactoryInterface
        $extendsFactory = false;
        foreach ($node->extends as $extend) {
            $extendName = $extend->toString();
            if ($extendName === 'Semitexa\\Core\\Contract\\ContractFactoryInterface'
                || $extendName === 'ContractFactoryInterface') {
                $extendsFactory = true;
                break;
            }
        }

        if (!$extendsFactory) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Factory interface %s must extend ContractFactoryInterface.',
                        $name,
                    )
                )->identifier('semitexa.factoryContract')->build(),
            ];
        }

        foreach ($node->getMethods() as $method) {
            if ($method->name->name !== 'get') {
                continue;
            }

            $params = $method->getParams();
            if (count($params) !== 1) {
                return [
                    RuleErrorBuilder::message(
                        sprintf('Factory interface %s::get() must accept exactly one backed enum parameter.', $name)
                    )->identifier('semitexa.factoryContract')->build(),
                ];
            }

            $type = $params[0]->type;
            if (!$type instanceof Node\Name) {
                return [
                    RuleErrorBuilder::message(
                        sprintf('Factory interface %s::get() parameter must be a backed enum type.', $name)
                    )->identifier('semitexa.factoryContract')->build(),
                ];
            }

            $typeName = $type->toString();
            if ($typeName === 'string' || $typeName === 'int' || $typeName === 'array') {
                return [
                    RuleErrorBuilder::message(
                        sprintf('Factory interface %s::get() must use a backed enum parameter, not %s.', $name, $typeName)
                    )->identifier('semitexa.factoryContract')->build(),
                ];
            }

            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf('Factory interface %s must declare get() with a backed enum parameter.', $name)
            )->identifier('semitexa.factoryContract')->build(),
        ];
    }
}
