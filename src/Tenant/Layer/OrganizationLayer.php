<?php

declare(strict_types=1);

namespace Semitexa\Core\Tenant\Layer;

readonly class OrganizationLayer implements TenantLayerInterface
{
    public function id(): string
    {
        return 'organization';
    }

    public function defaultValue(): TenantLayerValueInterface
    {
        return new OrganizationValue('default', 'Default Organization');
    }
}
