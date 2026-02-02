# Adding new pages and routes

**New routes (pages, endpoints) in Syntexa are added only via modules.**  
Request/Handler classes in the project folder `src/` (namespace `App\`) are **not discovered** by the framework — do not add them there for routes. Put all new pages and endpoints in **modules** (`src/modules/`, `packages/`, or installed packages in `vendor/`).

---

## Step-by-step: create a new module and add a route

1. **Create the module directory**  
   Example: `src/modules/Website/` (or `Api`, `Blog`, etc.).

2. **Add `composer.json` inside the module**  
   So the framework recognises it as a Syntexa module and registers its autoload:

   ```json
   {
     "name": "syntexa/module-website",
     "type": "syntexa-module",
     "autoload": {
       "psr-4": {
         "Syntexa\\Modules\\Website\\": "src/"
       }
     }
   }
   ```

   Run `composer dump-autoload` in the **project root** after adding or changing module `composer.json`.

3. **Create Request and Handler in the module namespace**  
   Use namespace `Syntexa\Modules\{ModuleName}\...`, e.g. `Syntexa\Modules\Website\Application\Request\HomeRequest` and `Syntexa\Modules\Website\Application\Handler\HomeHandler`.

   **Example Request** — e.g. `src/modules/Website/src/Application/Request/HomeRequest.php`:

   ```php
   <?php

   declare(strict_types=1);

   namespace Syntexa\Modules\Website\Application\Request;

   use Syntexa\Core\Attributes\AsRequest;
   use Syntexa\Core\Contract\RequestInterface;

   #[AsRequest(
       doc: 'docs/attributes/AsRequest.md',
       path: '/',
       methods: ['GET']
   )]
   class HomeRequest implements RequestInterface
   {
   }
   ```

   **Example Handler** — e.g. `src/modules/Website/src/Application/Handler/HomeHandler.php`:

   ```php
   <?php

   declare(strict_types=1);

   namespace Syntexa\Modules\Website\Application\Handler;

   use Syntexa\Core\Attributes\AsRequestHandler;
   use Syntexa\Core\Contract\RequestInterface;
   use Syntexa\Core\Contract\ResponseInterface;
   use Syntexa\Core\Response;

   #[AsRequestHandler(
       doc: 'docs/attributes/AsRequestHandler.md',
       for: HomeRequest::class
   )]
   class HomeHandler
   {
       public function handle(RequestInterface $request, ResponseInterface $response): ResponseInterface
       {
           return Response::json(['message' => 'Hello from Website module']);
       }
   }
   ```

   Paths inside the module can follow your convention (e.g. `Application/Request/`, `Application/Handler/` or `Handler/`, `Request/`); the important part is that the class lives under the **module namespace** (`Syntexa\Modules\Website\...`) and the module has a valid `composer.json` with `"type": "syntexa-module"` and PSR-4 autoload.

4. **Reload**  
   Restart the app (e.g. `bin/syntexa server:stop` then `bin/syntexa server:start`) or ensure your runtime picks up the new classes; the framework will discover the new Request/Handler from the module.

---

## Where to put Request/Handler

| Location | Discovered for routes? |
|----------|-------------------------|
| **Modules:** `src/modules/{ModuleName}/` (with `composer.json` `type: syntexa-module`) | Yes |
| **Packages:** project `packages/` (Syntexa packages with `composer.json`) | Yes |
| **Vendor:** installed packages (e.g. `vendor/syntexa/...`) | Yes |
| **Project `src/Request/`, `src/Handler/` (namespace `App\`) | **No** — not scanned for routes |

Place **all new routes** in a module (existing or new) under `src/modules/`, in `packages/`, or in an installed package. Do **not** add Request/Handler in `src/Request/` or `src/Handler/` in the project root for new pages or endpoints.

---

## How discovery works (architecture)

- **ModuleRegistry** finds modules in: `src/modules/`, project `packages/`, and `vendor/` (packages with `type: syntexa-module` or under `vendor/syntexa/`).
- **IntelligentAutoloader** and **AttributeDiscovery** load and scan only classes from those module namespaces (e.g. `Syntexa\Modules\*`, package namespaces). They do **not** scan the project `App\` namespace under `src/` for routes.
- So to add new routes you must have a **module** with a proper `composer.json` and PSR-4 mapping (e.g. `Syntexa\Modules\Website\` → `src/`). Adding `App\Request\*` / `App\Handler\*` in project `src/` is not a supported way to register routes.

---

## Common mistakes / FAQ

**Why don’t my Request/Handler in `src/Request/` or `src/Handler/` work (404)?**  
Because route discovery only uses **modules**. Classes in the project `src/` with namespace `App\` are not scanned for `#[AsRequest]` / `#[AsRequestHandler]`. Create a module in `src/modules/` with a `composer.json` (`"type": "syntexa-module"` and PSR-4 autoload) and put your Request/Handler there. See the step-by-step above.

**Can I patch `IntelligentAutoloader` or `AttributeDiscovery` to scan `App\`?**  
Do not patch vendor. The supported way to add routes is via modules; changing framework discovery to scan `App\` would break the intended architecture (everything route-related lives in modules).

**A future project check** could warn if classes with `#[AsRequest]` or `#[AsRequestHandler]` are found in project `src/Request/` or `src/Handler/` (namespace `App\`), and suggest moving them into a module (`src/modules/`).

---

## Summary

- **New pages/routes = only via modules** (`src/modules/`, `packages/`, or `vendor/`).
- **Never** add new routes as `App\Request\*` / `App\Handler\*` in project `src/Request/` or `src/Handler/`.
- Each module: directory, `composer.json` with `"type": "syntexa-module"` and PSR-4 (e.g. `Syntexa\Modules\Website\` → `src/`), then Request/Handler classes with `#[AsRequest]` and `#[AsRequestHandler]` in that namespace.
