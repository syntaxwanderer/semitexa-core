<?php

declare(strict_types=1);

namespace Semitexa\Core\Tenant\Layer;

readonly class LocaleLayer implements TenantLayerInterface
{
    public function id(): string
    {
        return 'locale';
    }

    public function defaultValue(): TenantLayerValueInterface
    {
        return LocaleValue::default();
    }
}
