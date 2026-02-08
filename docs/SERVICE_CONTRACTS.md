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
- Resolution uses **module "extends"** from each module’s `composer.json` (`extra.semitexa-module.extends`). The module that extends another has higher priority; its implementation becomes active for that interface.
- See `ServiceContractRegistry` and `ModuleRegistry::getModuleOrderByExtends()` in the core package.
