# FreeUpload — Agent Instructions (root)

Docs: `ARCHITECTURE.md` = feature/design details · `PLAN.md` = implementation tasks (checkboxes). This file holds only what the agent needs to operate.

## Repo status (read this first)

- **Configured**: skeleton fully renamed to vendor `blalmal10a`, package `free-upload`, namespace `Blalmal10a\FreeUpload`. `composer.json`: `php: ^8.2`, `ext-gd`, `filament/filament: ^4.0 || ^5.0`. CI matrix (`tests.yml` + `phpstan.yml`) runs a `filament: [4.*, 5.*]` dimension; the phpstan job loads `gd`.
- **Implemented**: Phases 0–4 done (see `PLAN.md`) — config, upload/file controllers, provider wiring, `src/Proxy/`, in-app file-serving routes, `FreeUpload` component + inline XHR/PXVT JS, stub publish command, full Pest suite, README. Verification green locally: `composer verify`, `route:list --name=freeupload` shows `freeupload.upload` + `freeupload.image` + `freeupload.files`.
- **Imported into host template** (`blalmal10a/kawnek-template`, `main` branch): path repo + `@dev` require + test autoload, `UserForm.php` uses `FreeUpload`, `FREEUPLOAD_API_KEY` in `.env(.example)`. Details in `ARCHITECTURE.md`.
- `vendor/` is installed; `composer install` runs `testbench package:discover` via `post-autoload-dump`, so testbench is required even for `composer lint`.
- Branch is `5.x` (`update-changelog.yml` + README badges use `5.x`).

## Toolchain & commands

All via composer scripts (see `composer.json`):

| Command | Runs |
| --- | --- |
| `composer test` | `pest` (full suite) |
| `composer test:lint` | `pint --test` (no edits) |
| `composer test:refactor` | `rector --dry-run` |
| `composer analyse` | `phpstan analyse` |
| `composer lint` | `pint` (auto-fixes) |
| `composer refactor` | `rector` (applies) |
| `composer verify` | all CI checks: pint --test → phpstan → rector --dry-run → pest (aborts on first failure) |

- **Pre-push hook (Husky)**: `.husky/pre-push` runs `composer verify` before every push. Requires `npm install` once (husky wires `core.hooksPath`); `package.json`/`package-lock.json`/`.husky/` are export-ignored from Packagist dists. Never commit from a dirty tree that doesn't pass `composer verify`.

## Tests

- **Run from the package directory**: `composer test` (Testbench boots the full Filament provider stack + Livewire + `WithWorkbench`, so Livewire component tests work out of the box). `tests/Pest.php` binds `Blalmal10a\FreeUpload\Tests\TestCase`.
- `php artisan test ../../packages/free-upload/tests` from the host **does not work**: `tests/Pest.php` + `TestCase` (Orchestra Testbench, `WithWorkbench`) conflicts with the host's bootstrap — fails with `BindingResolutionException: Target class [url]/[config] does not exist`. Always use the package's own `composer test`.
- **Component test gotchas** (learned writing the suite): `getEnvironmentSetUp` must set an `app.key` (exactly 32 chars, else Encrypter throws) and register `tests/views`; Livewire form components need `implements HasSchemas` + explicit `render()`; state is a **list** of URL strings keyed numerically, so `callSchemaComponentMethod('form.file', 'removeUploadedFile', ['fileKey' => '0'])` uses the index; controller tests need `actingAs` (route has `auth`) and `withoutMiddleware(PreventRequestForgery::class)`; multipart upstream assertions must read `$request->toPsrRequest()->getBody()` (no `$request['key']` access for multipart).
- **`phpunit.xml.dist` is strict**: `failOnWarning`, `failOnRisky`, `failOnEmptyTestSuite` — an empty/misnamed test file fails the suite. Junit log goes to `build/` (gitignored). No `<coverage>` block (PHPUnit 12 + no driver + `failOnWarning` aborts); generate coverage on demand with `--coverage-*` flags.

## Tooling constraints

- **Pint** (`pint.json`): laravel preset + `blank_line_before_statement`, `concat_space: one`, `single_trait_insert_per_statement`, `types_spaces: single`. The `fix-code-style` CI workflow auto-commits Pint fixes on every PHP push — keep changes small to avoid churn.
- **PHPStan** (`phpstan.neon.dist`): level 4 only, paths `src`, `config`; includes `phpstan-baseline.neon` (currently empty). CI runs PHP 8.2–8.4 × Laravel 11–13 × Filament 4/5 with pinned testbench (9/10/11). Skeleton's `checkOctaneCompatibility`/`checkModelProperties` params were dropped — invalid in larastan 3.10.
- **Rector** (`rector.php`): `src/` only, prepared sets (deadCode, codeQuality, typeDeclarations, privatization, earlyReturn). `strictBooleans` was removed from rector 2.6's `withPreparedSets` — don't re-add it.
- **CI matrix** (`tests.yml`): ubuntu + windows × PHP 8.2/8.3/8.4 × Laravel 11/12/13 × filament 4.*/5.* × `prefer-lowest`/`prefer-stable`; Laravel 13 is excluded on PHP 8.2. Local testing only exercises your current PHP/Laravel — always pass CI variants when changing constraints. `zizmor.yml` lints workflow files.
- `testbench.yaml` is gitignored (generated); `.gitattributes` export-ignores tests/config/tooling from Packagist dists.

## Verification checklist

- `composer verify` (from the package dir) → all green
- `php artisan freeupload:publish-component` in the host → writes `app/Forms/Components/FreeUpload.php` with `App\Forms\Components` namespace
- `php artisan route:list --name=freeupload` in the host template → `freeupload.upload` + `freeupload.image` + `freeupload.files` registered
- Browser smoke: one upload through `FreeUpload` in the admin panel