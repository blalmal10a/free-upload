# FreeUpload — Filament Plugin Package

## Repo status (read this first)

- **Configured**: skeleton fully renamed to vendor `blalmal10a`, package `free-upload`, namespace `Blalmal10a\FreeUpload` (`configure.php` was run and self-deleted — no placeholders remain).
- `composer.json`: `php: ^8.2`, `ext-gd`, `filament/filament: ^4.0 || ^5.0`. CI matrix (`tests.yml` + `phpstan.yml`) runs a `filament: [4.*, 5.*]` dimension; the phpstan job loads `gd`.
- **Implemented so far**: Phase 0–4 done (config, controller, skeleton cleanup, provider wiring, `src/Proxy/`, `server/`, `FreeUpload` component + inline XHR/PXVT JS, full Pest suite, README). Verification green locally: `pint`, `phpstan`, `rector --dry-run`, `pest` (35 tests), `route:list --name=freeupload` shows `freeupload.upload`, server smoke (OPTIONS 204+CORS / 400 / 404 via real network).
- **Imported into host template** (`blalmal10a/kawnek-template`, Aug 2026): path repo + `@dev` require + test autoload wired, `UserForm.php` uses `FreeUpload`, `FREEUPLOAD_API_KEY` in `.env(.example)`, route registered — see "Wiring into the host template".
- `vendor/` is installed. `composer install` runs `testbench package:discover` via `post-autoload-dump`, so testbench is required even for `composer lint`.
- Branch is `5.x`. `update-changelog.yml` fixed to `5.x` (badges in README also use `5.x`).

## Current plan (todo)

- [x] Update AGENTS.md: v4+v5 support framing (replace v5-only roadmap)
- [x] Phase 0: run configure.php, composer.json (filament ^4||^5, ext-gd), CI matrix
- [x] Phase 1: `config/free-upload.php` (done)
- [x] Phase 1: `FreeUploadUploadController` class (done — route registration still pending in provider wiring)
- [x] Phase 1: Skeleton cleanup (full): delete `database/` (migration stub + factory), `stubs/`, `src/Commands|Facades|Testing`, demo `src/FreeUpload.php`, `resources/`, `bin/`, `package.json`, `.npmrc`, `.prettierrc`, `tests/DebugTest.php`; drop `Database\Factories` autoload + `FreeUpload` alias from composer.json; trim `database` from `phpstan.neon.dist` paths; `composer dump-autoload`
- [x] Phase 1: Provider wiring: drop `hasCommands`/`hasMigrations`/stub-publishing/`Testable::mixin`; register config-gated route → `POST {prefix}/upload` named `freeupload.upload`
- [x] Phase 1: `src/Proxy/` — PxvtDecoder, PxvtDecodeException, ProxyResponse, Proxy (injectable fetcher)
- [x] Phase 1: `server/` standalone deployable proxy (composer.json, public/index.php, .htaccess)
- [x] Phase 2: `FreeUpload` component + XHR/PXVT JS (StateCast dropped for MVP; asset registration hooks stay empty — upload JS is inline in `toEmbeddedHtml`)
- [x] Phase 3: Pest tests — Unit: PxvtDecoder, Proxy, Config · Feature: Controller (Http::fake), Component (Livewire::test); replace skeleton `ExampleTest`/`DebugTest`
- [x] Phase 4: README rewrite, update-changelog.yml branch fix, verification (lint/analyse/test, `route:list --name=freeupload`, server smoke)

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

- **Tests**: Pest on Orchestra Testbench (`tests/TestCase.php` boots the full Filament provider stack + Livewire + `WithWorkbench`, so Livewire component tests work out of the box). `tests/Pest.php` binds `Blalmal10a\FreeUpload\Tests\TestCase`.
- **Component test gotchas** (learned writing the suite): `getEnvironmentSetUp` must set an `app.key` (exactly 32 chars, else Encrypter throws) and register `tests/views`; Livewire form components need `implements HasSchemas` + explicit `render()`; state is a **list** of URL strings keyed numerically, so `callSchemaComponentMethod('form.file', 'removeUploadedFile', ['fileKey' => '0'])` uses the index; controller tests need `actingAs` (route has `auth`) and `withoutMiddleware(PreventRequestForgery::class)`; multipart upstream assertions must read `$request->toPsrRequest()->getBody()` (no `$request['key']` access for multipart).
- **`phpunit.xml.dist` is strict**: `failOnWarning`, `failOnRisky`, `failOnEmptyTestSuite` — an empty/misnamed test file fails the suite. Junit log goes to `build/` (gitignored). The skeleton's `<coverage>` block was removed: with PHPUnit 12, coverage config + no xdebug/pcov driver + `failOnWarning` aborts the run before tests start; generate coverage on demand with `--coverage-*` flags instead.
- **Pint** (`pint.json`): laravel preset + `blank_line_before_statement`, `concat_space: one`, `single_trait_insert_per_statement`, `types_spaces: single`. The `fix-code-style` CI workflow auto-commits Pint fixes on every PHP push — keep changes small to avoid churn.
- **PHPStan** (`phpstan.neon.dist`): level 4 only, paths `src`, `config`; includes `phpstan-baseline.neon` (currently empty). CI runs it across PHP 8.2–8.4 × Laravel 11–13 × Filament 4/5 with pinned testbench (9/10/11). Skeleton's `checkOctaneCompatibility`/`checkModelProperties` params were dropped — invalid in larastan 3.10.
- **Rector** (`rector.php`): `src/` only, prepared sets (deadCode, codeQuality, typeDeclarations, privatization, earlyReturn). `strictBooleans` was removed from rector 2.6's `withPreparedSets` — don't re-add it.
- **CI matrix** (`tests.yml`): ubuntu + windows × PHP 8.2/8.3/8.4 × Laravel 11/12/13 × filament 4.*/5.* × `prefer-lowest`/`prefer-stable`; Laravel 13 is excluded on PHP 8.2. `zizmor.yml` lints workflow files. Local testing only exercises your current PHP/Laravel — always pass CI variants when changing constraints.
- `testbench.yaml` is gitignored (generated); `.gitattributes` export-ignores tests/config/tooling from Packagist dists.

## Roadmap — FreeUpload plugin

Standalone Filament plugin `blalmal10a/free-upload` (namespace `Blalmal10a\FreeUpload`) supporting **Filament v4 and v5 — one package major** (`filament/filament: ^4.0 || ^5.0`). Ports the host app's `KawnekFileUpload` component + state hook + upload controller into this package, ships a **framework-free PHP proxy server** (port of the `media-server.kawnek.workers.dev` worker: image proxying + PXVT decode), full docs (README), and a Pest suite. **Nothing in the host app is deleted** — the old classes stay in place.

### Version support decisions (verified against Filament 4.x/5.x source, Aug 2026)

- **v4 and v5 share an identical component API** — v5 shipped solely to adopt Livewire 4 (PHP ^8.2, Laravel ^11.28|^12|^13 in both). One codebase / one package major covers both. There is **no v3 support** (v3 = PHP ^8.1, Livewire 3, no `filament/schemas`, Blade-view render).
- If v3 support is ever added later: split into separate majors (`1.x`→v3, `2.x`→v4|v5) — the v3 `FileUpload` renders a Blade view (`$view = 'filament-forms::components.file-upload'`, overridable via `->view()`) and has **no** `Filament\Schemas\Components\StateCasts`, so it needs a Blade re-render + a `dehydrateStateUsing` fallback instead of the cast.
- `FilamentAsset`/`FilamentIcon` registration and the `Plugin` interface (`getId`/`register`/`boot`) are identical in v3/v4/v5 — those parts port cleanly.
- **Known risk**: `FileUpload::toEmbeddedHtml()` and the JS `uploadUsing:` block are at the same source line in 4.x and 5.x today, but they are Filament internals; pin the source commit you copy and re-diff on upgrades.

### Locked decisions

- Upload endpoint: plugin's own Laravel controller + route by default; configurable **only** via env/config (`upload_endpoint` / `FREEUPLOAD_UPLOAD_ENDPOINT`) — deliberately **not** per-component (removed `->uploadEndpoint()` in Aug 2026; the endpoint must be a single env-driven value across all form fields).
- Decode endpoint: configurable base URL (`decode_base_url`, default `https://media-server.kawnek.workers.dev`); stored values are URL strings built as `{base}{/dec if encoded}/{id}/{filename}`.
- Client HTTP: `XMLHttpRequest` with `xhr.upload.onprogress` — **no axios**.
- Non-image files: encoded client-side PXVT → PNG via canvas `toBlob` (30 MB cap); images (`image/*`) upload raw, never encoded.
- Decoding is server-side only, via GD (`imagecreatefromstring`/`imagecolorat`). Proxy buffers responses (no streaming).
- Proxy server is framework-free (cURL + GD), deployable on PHP 8.2+; tests run from the package dir via `composer test` (Testbench), not from the host (see "Running the package tests").
- Component stores URL state natively: `fetchFileInformation(false)` + a `getUploadedFileUsing` override so URL strings hydrate and render as remote files. **No StateCast in the MVP** (the roadmap's `FreeUploadStateCast` was dropped; re-add only if v3 support is ever ported).

### Package structure (target)

```
config/free-upload.php                 # Public endpoints + config marks, env-driven
src/Forms/Components/FreeUpload.php    # extends FileUpload; XHR uploadUsing; toEmbeddedHtml
src/Http/Controllers/FreeUploadUploadController.php
src/Proxy/{PxvtDecoder,Proxy,ProxyResponse,PxvtDecodeException}.php
server/                                # standalone deployable proxy (composer.json, public/index.php, .htaccess)
tests/                                 # Unit: PxvtDecoder, Proxy, Config · Feature: Controller, Component
```

### Publishing the component (August 2026)

- `FreeUploadServiceProvider` registers a `publishes()` map for the component class: tag `free-upload-component` (also grouped under `free-upload`) copies `src/Forms/Components/FreeUpload.php` → `app/Forms/Components/FreeUpload.php`.
- The copy keeps the package namespace, so it is **not autoloaded** as-is; the host dev changes the namespace to their app namespace and references `App\Forms\Components\FreeUpload` in their forms (documented in README). The published copy is a one-time snapshot — package upgrades don't touch it.
- Publishing the raw source (not a namespaced stub) keeps a single source of truth — no duplicated template to keep in sync. The published file is dead code until the namespace is changed, so there is no class-conflict risk.
- Upload endpoint, size caps, etc. are still config/env driven, so a published component changes only how it renders/uploads.

### Config keys (`config/free-upload.php`)

| Key | Env | Default | Purpose |
| --- | --- | --- | --- |
| `upload_endpoint` | `FREEUPLOAD_UPLOAD_ENDPOINT` | `null` → plugin route `freeupload.upload` | user-defined upload endpoint |
| `decode_base_url` | `FREEUPLOAD_DECODE_BASE_URL` | `https://media-server.kawnek.workers.dev` | user-defined decode endpoint |
| `upload_host` | `FREEUPLOAD_UPLOAD_HOST` | `https://freeimage.host/api/1/upload` | upstream hosting API |
| `api_key` | `FREEUPLOAD_API_KEY` | `''` | FreeImage.host API key |
| `max_size_kb` | `FREEUPLOAD_MAX_SIZE_KB` | `32768` | upload validation cap |
| `max_encoded_file_mb` | `FREEUPLOAD_MAX_ENCODED_FILE_MB` | `30` | client-side non-image cap |
| `image_host` | `FREEUPLOAD_IMAGE_HOST` | `https://iili.io` | upstream host for the standalone proxy |
| `register_routes` / `route_prefix` / `route_middleware` | `FREEUPLOAD_REGISTER_ROUTES` / `FREEUPLOAD_ROUTE_PREFIX` | `true` / `freeupload` / `['web','auth']` | plugin route toggles |

### PXVT format & decode algorithm

- Byte layout: `0–3` magic `PXVT` · `4–5` filename length (BE u16) · `6–7` MIME length (BE u16) · `8..8+n` UTF-8 filename · `8+n..h` MIME · `h..` file bytes zero-padded to a multiple of 3.
- Client encode: `ArrayBuffer` → header + bytes → RGBA `ImageData` (3 bytes/pixel, alpha 255) → square canvas → `toBlob('image/png')`.
- Server decode (GD): `imagecreatefromstring` → append R/G/B of every pixel row-major → check `PXVT` magic (else 400 `Invalid vault signature format`) → read lengths → slice filename/MIME/content → `rtrim($bytes, "\0")` → return `{filename, mimeType, bytes}`.

### Proxy routing (`src/Proxy/Proxy.php`)

`Proxy(string $imageHost, int $timeoutSeconds, ?callable $fetcher)` — fetcher injectable for tests. `OPTIONS` → 204 + CORS; `/{id}/{filename?}` → fetch upstream `{imageHost}/{id}`, ≥400 → 404, else stream with `Cache-Control: public, max-age=86400`; `/dec/{id}/{filename}` → fetch PNG, upstream fail → 502, bad magic/truncated → 400, other decode errors → 500, success → 200 with original `Content-Type` + `Content-Disposition: inline`, `Cache-Control: no-store`; anything else → 400. CORS headers (`Access-Control-Allow-Origin: *`, etc.) merged on all responses.

### Wiring into the host template (done — `blalmal10a/kawnek-template`, Aug 2026)

The package was imported into the host template `blalmal10a/kawnek-template` (GitHub default branch `main`). Steps taken and corrections to earlier docs:

1. Template `composer.json`: added path repository `{ "type": "path", "url": "../../packages/free-upload", "options": { "symlink": false } }`, `require: "blalmal10a/free-upload": "@dev"`, and `autoload-dev: "Blalmal10a\\FreeUpload\\Tests\\": "../../packages/free-upload/tests"`, then `composer update blalmal10a/free-upload --with-all-dependencies` (locks `5.x-dev`).
   - **Version constraint must be `@dev`** — the package branch is `5.x`; both `dev-main` and `dev-5.x` (and `dev-5.x as x.y.z`) fail to match the path repo's `5.x-dev`.
   - **Directory is `free-upload` (hyphen)** — earlier docs in `FREE_UPLOAD.md`/this file said `freeupload`.
2. Swapped the 2 usages in the template's [UserForm.php](https://github.com/blalmal10a/kawnek-template/blob/main/app/Filament/Resources/Users/Schemas/UserForm.php) from `KawnekFileUpload` (source: [KawnekFileUpload.php](https://github.com/blalmal10a/kawnek-template/blob/main/app/Filament/Forms/Components/KawnekFileUpload.php)) to `FreeUpload` — the old template class stays in place, untouched.
3. `.env` / `.env.example`: added `FREEUPLOAD_API_KEY=6d207e02198a847aa98d0a2a901485a5` (the FreeImage.host key that was hardcoded in the template's old `FreeImageHostUploadController`). Optional overrides (`FREEUPLOAD_DECODE_BASE_URL`, `FREEUPLOAD_UPLOAD_HOST`, `FREEUPLOAD_UPLOAD_ENDPOINT`, `FREEUPLOAD_IMAGE_HOST`) were not added.
4. Route auto-registered: `POST freeupload/upload` named `freeupload.upload` (template's `extra.laravel.dont-discover` is empty, so the provider auto-discovers; route middleware `['web','auth']`).
5. Template verification after the swap: `php artisan test --compact` (52 passed), `vendor/bin/pint --dirty`, `vendor/bin/phpstan`, `vendor/bin/rector --dry-run` — all green.

### Known issue fixed: Alpine v3 arrow-function scoping (Aug 2026)

Browser smoke test after the import hit `ReferenceError: uploadEndpoint is not defined` inside the component's `uploadUsing` JS. Root cause and fix:

- `toEmbeddedHtml()` originally declared `uploadEndpoint` and `maxEncodedFileMb` as **Alpine data properties** on the `fileUploadFormComponent({...})` object, then referenced them as bare variables inside the nested `send` arrow function. Alpine v3's `with(this)` scope wrapping only applies to regular method bodies, not arrow functions (which capture `this` lexically) — so bare property names resolve to nothing at runtime.
- **Fix**: both values are now **inlined at point of use** via `Js::from($this->getUploadEndpoint(), JSON_UNESCAPED_SLASHES)` and `Js::from($this->getMaxEncodedFileMb() * 1024 * 1024, JSON_UNESCAPED_SLASHES)`; the data properties were removed from the Alpine object.
- `JSON_UNESCAPED_SLASHES` keeps URLs readable in the emitted HTML (Laravel's `Js::from` otherwise escapes `/` as `\/`; runtime is unaffected either way).
- **Constraint going forward**: never reference Alpine data properties as bare variables inside arrow functions in `toEmbeddedHtml()` JS — inline PHP values with `Js::from()` instead (matches how Filament's own component code works).
- Regression coverage: `tests/Feature/FreeUploadComponentTest.php` now asserts the endpoint (`xhr.open('POST', 'https://example.com/upload')`) and size cap (`if (file.size > 7340032)`) are inlined and that no `uploadEndpoint:`/`maxEncodedFileMb` data properties remain.

### Running the package tests

- **Run from the package directory**: `composer test` (Testbench boots the full Filament stack; 35 tests / 73 assertions green).
- `php artisan test ../../packages/free-upload/tests` from the host **does not work**: the package's `tests/Pest.php` + `TestCase` (Orchestra Testbench, `WithWorkbench`) conflicts with the host's `tests/Pest.php` bootstrap — tests fail with `BindingResolutionException: Target class [url]/[config] does not exist`. Earlier docs ("run from the host template") are wrong; always use the package's own `composer test`.

### Verification checklist

- `composer test` (from the package dir) → green (35 tests / 73 assertions)
- `php artisan vendor:publish --tag=free-upload-component` in the host → drops `app/Forms/Components/FreeUpload.php`
- `php artisan route:list --name=freeupload` in the host template → `freeupload.upload` registered
- `vendor/bin/pint` clean (repo + `server/`)
- Server smoke: `composer install` in `server/`, `php -S 0.0.0.0:8080 -t public`, curl `/{id}` and `/dec/{id}/{name}`
- Browser smoke: one upload through `FreeUpload` in the admin panel
