<?php

declare(strict_types=1);

namespace Semitexa\Core\Tenant\Layer;

readonly class OrganizationValue implements TenantLayerValueInterface
{
    public function __construct(
        public string $id,
        public ?string $name = null,
    ) {}

    public function layer(): TenantLayerInterface
    {
        return new OrganizationLayer();
    }

    public function rawValue(): string
    {
        return $this->id;
    }
}
