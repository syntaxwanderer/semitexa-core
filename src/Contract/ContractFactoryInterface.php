<?php

declare(strict_types=1);

namespace Semitexa\Core\Contract;

/**
 * Base interface for "Factory of implementations" contracts.
 *
 * When an interface name starts with "Factory" (e.g. FactoryItemListProviderInterface),
 * the framework binds it to a generated implementation that lets the developer choose
 * from all registered implementations of the base contract (e.g. ItemListProviderInterface).
 *
 * Define your factory interface in the same namespace as the base contract:
 * <code>
 * interface FactoryItemListProviderInterface extends ContractFactoryInterface
 * {
 *     public function getDefault(): ItemListProviderInterface;
 *     public function get(string $key): ItemListProviderInterface;
 *     public function keys(): array; // @return list<string>
 * }
 * </code>
 *
 * Keys are composite: "Module::ShortClassName" (e.g. "Website::WebsiteItemListProvider").
 * Lookup in get($key) is case-insensitive (e.g. "website::websiteitemlistprovider" is the same).
 * Use getDefault() for the active implementation (by module extends order), or get($key) for a specific one.
 */
interface ContractFactoryInterface
{
    /**
     * The default (active) implementation, chosen by module "extends" order.
     */
    public function getDefault(): object;

    /**
     * Get implementation by key (module name).
     *
     * @throws \InvalidArgumentException when key is unknown
     */
    public function get(string $key): object;

    /**
     * Available keys (module names that provide an implementation).
     *
     * @return list<string>
     */
    public function keys(): array;
}
