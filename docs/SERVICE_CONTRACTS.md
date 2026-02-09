# Service contracts and active implementation

Service contracts are interfaces bound to a single implementation in the DI container. When several modules provide an implementation for the same interface, the **active** one is chosen by module "extends" order (child module wins).

## Seeing which contract is bound to which class

**Command (for developers and AI agents):**

```bash
bin/semitexa contracts:list
```

This prints a table: for each **contract (interface)** you see all **implementations** (module → class) and which one is **active** (marked).

**Machine-readable output (for AI agents and scripts):**

```bash
bin/semitexa contracts:list --json
```

Output is JSON: `contracts[]` with `contract`, `active`, and `implementations` (each with `module` and `class`). Use this when debugging "which class is injected for interface X" or when generating/checking bindings.

## When to use

- Debugging: "Which implementation of ItemListProviderInterface is actually used?"
- After adding or removing a module: confirm the active implementation is the one you expect.
- AI agents: before changing a contract or adding an override, run `contracts:list` or `contracts:list --json` to see current bindings.

## How it works

- Implementations are discovered via **#[AsServiceContract(of: SomeInterface::class)]** on classes (in modules).
- **Single implementation:** the container binds the interface directly to that class.
- **Multiple implementations:** a **registry resolver** is generated in `src/registry/Contracts/` (e.g. `ItemListProviderResolver`). The resolver receives all implementations via constructor (DI) and exposes `getContract()`; the container calls it to obtain the chosen implementation. By default the resolver returns the implementation chosen by module "extends" order; you can edit `getContract()` to pick another implementation, merge results, or add custom logic.
- **Generate resolvers:** run **`bin/semitexa registry:sync:contracts`** (or **`bin/semitexa registry:sync`** to sync payloads and contracts together). Only interfaces with **2+ implementations** get a resolver; single-implementation contracts are not generated. The container discovers resolvers by convention (`App\Registry\Contracts\{InterfaceShortName}Resolver`), no manifest needed.
- See `ServiceContractRegistry`, `RegistryContractResolverGenerator`, and `ModuleRegistry::getModuleOrderByExtends()` in the core package.

## Resolver as factory

The registry resolver is a **factory** in the usual sense: it receives all implementations via DI and exposes one method (`getContract()`) that returns the chosen instance. By default the generated code returns the implementation selected by module order, but you own the class and can change the logic:

- **Config-driven:** read a config key (e.g. `app.send_email.driver`) and return the implementation that matches.
- **Context-driven:** use request, tenant, or feature flags to pick an implementation.
- **Custom strategy:** merge, delegate, or switch between implementations inside `getContract()`.

There is no separate “Factory” pattern in Semitexa: service contracts with multiple implementations use this single mechanism. Document or name the resolver as a factory in your codebase if that helps (e.g. `SendEmailFactory` / `ItemListProviderResolver`).
