<?php

declare(strict_types=1);

namespace Semitexa\Core\Tenant\Layer;

readonly class LocaleValue implements TenantLayerValueInterface
{
    public function __construct(
        public string $code,
    ) {
        if (!preg_match('/^[a-z]{2,3}(-[A-Z]{2,4})?$/', $code)) {
            throw new \InvalidArgumentException("Invalid locale code: {$code}");
        }
    }

    public function layer(): TenantLayerInterface
    {
        return new LocaleLayer();
    }

    public function rawValue(): string
    {
        return $this->code;
    }

    public static function default(): self
    {
        return new self('en');
    }

    public static function fromCode(string $code): self
    {
        $parts = explode('-', $code, 2);
        $normalized = strtolower($parts[0]);

        if (isset($parts[1])) {
            $normalized .= '-' . strtoupper($parts[1]);
        }

        return new self($normalized);
    }
}
