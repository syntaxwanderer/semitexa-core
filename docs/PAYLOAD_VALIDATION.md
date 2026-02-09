# Payload validation

## Idea

Handlers work with **clean, validated data** only. If a Payload property is declared as `int`, the handler receives a real `int`; invalid input is **rejected** (422 Unprocessable Entity), not coerced. Security and type guarantees are enforced at the framework layer so handlers don’t need to re-check.

## Pipeline

1. **Hydrate** — `RequestDtoHydrator::hydrate($dto, $request)` fills public properties from request (JSON/POST/query) with **type casting** (string→int, etc.).
2. **Validate** — `PayloadValidator::validate($dto, $request)` runs **after** hydrate:
   - **Strict type check:** for each property, checks that the **raw** request value was valid for the declared type (e.g. `int` allows only integers or numeric strings that represent integers; `"abc"` → error, not 0).
   - **Constraint attributes:** optional attributes on properties (e.g. `#[Email]`, `#[Length(min, max)]`, `#[NotBlank]`) are evaluated; violations are collected.
3. If validation fails → return **422** with a body listing field errors; handler is **not** called.
4. If validation passes → handler runs with a DTO that is guaranteed to have correct types and to satisfy declared constraints.

## Strict type rules (examples)

| Type    | Valid raw input                    | Invalid (reported)   |
|---------|------------------------------------|----------------------|
| `int`   | `123`, `"123"`                     | `"abc"`, `""` → 0    |
| `float` | `1.5`, `"1.5"`                     | `"x"`                |
| `bool`  | `true`, `false`, `"1"`, `"true"`  | (coercion allowed)   |
| `string`| any                                | —                    |
| `?int`  | as above or `null`/missing         | `"abc"`              |

## Constraint attributes (optional)

- **`#[NotBlank]`** — string not empty (after trim).
- **`#[Email]`** — string matches a simple email format.
- **`#[Length(min?: int, max?: int)]`** — string/array length in range.
- **`#[OneOf(values: array)]`** — value in allowed list.

All live in `Semitexa\Core\Request\Attribute\` (or `Validation\`) and are read by `PayloadValidator`.

## Response on validation failure

- **Status:** 422 Unprocessable Entity.
- **Body (e.g. JSON):** `{ "errors": { "fieldName": ["message1", "message2"], ... } }` so the client can show field-level errors.

## Where it runs

- In **Application::handleRoute**, after `RequestDtoHydrator::hydrate`, call `PayloadValidator::validate($reqDto, $request)`.
- If `!$result->isValid()`, return a 422 response with `$result->getErrors()` and do **not** call the handler.

## Optional fields

If a property is **not present** in the raw request (key missing), the validator **skips** type check for it; the hydrated default (e.g. `public int $id = 0`) is left as-is. Only when the client sends a value do we strictly validate type and constraints.

## Config

- Optional: env or config to turn strict validation **off** (fallback to “coerce only”, current behaviour) for gradual rollout.
