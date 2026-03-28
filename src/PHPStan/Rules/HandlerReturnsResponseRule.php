<?php

declare(strict_types=1);

namespace Semitexa\Core\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * semitexa.handlerReturnsResponse
 *
 * TypedHandlerInterface::handle() must not have Response as return type.
 *
 * @implements Rule<ClassMethod>
 */
final class HandlerReturnsResponseRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name->name !== 'handle') {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        if (!$classReflection->implementsInterface('Semitexa\\Core\\Contract\\TypedHandlerInterface')) {
            return [];
        }

        $returnType = $node->getReturnType();
        if ($returnType instanceof Node\Name) {
            $name = $returnType->toString();
            if ($name === 'Semitexa\\Core\\Response' || $name === 'Response') {
                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            '%s::handle() must return a ResourceInterface, not a Response object. '
                            . 'Use domain exceptions for errors and resource DTO methods for data.',
                            $classReflection->getName(),
                        )
                    )->identifier('semitexa.handlerReturnsResponse')->build(),
                ];
            }
        }

        return [];
    }
}
