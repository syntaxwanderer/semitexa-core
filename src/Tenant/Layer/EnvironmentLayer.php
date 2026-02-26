<?php

declare(strict_types=1);

namespace Semitexa\Core\Tenant\Layer;

readonly class EnvironmentLayer implements TenantLayerInterface
{
    public function id(): string
    {
        return 'environment';
    }

    public function defaultValue(): TenantLayerValueInterface
    {
        return EnvironmentValue::prod();
    }
}
