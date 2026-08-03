# FreeUpload — Filament Plugin Package

## Repo status (read this first)

- **Configured**: skeleton fully renamed to vendor `blalmal10a`, package `free-upload`, namespace `Blalmal10a\FreeUpload` (`configure.php` was run and self-deleted — no placeholders remain).
- `composer.json`: `php: ^8.2`, `ext-gd`, `filament/filament: ^4.0 || ^5.0`. CI matrix (`tests.yml` + `phpstan.yml`) runs a `filament: [4.*, 5.*]` dimension; the phpstan job loads `gd`.
- **Implemented so far**: `config/free-upload.php` (all keys), `src/Http/Controllers/FreeUploadUploadController.php`. Everything else in the plan below is pending.
- `vendor/` is installed. `composer install` runs `testbench package:discover` via `post-autoload-dump`, so testbench is required even for `composer lint`.
- Branch is `5.x`. `update-changelog.yml` still references `main` (fix at release time).
- Skeleton cruft still on disk until the cleanup task below runs: `database/` (migration stub — **not needed, delete**), `stubs/`, `src/Commands/`, `src/Facades/`, `src/Testing/`, demo `src/FreeUpload.php`, `resources/` (lang/views/css/js/dist), `bin/`, `package.json`, `tests/DebugTest.php`.

## Current plan (todo)

- [x] Update AGENTS.md: v4+v5 support framing (replace v5-only roadmap)
- [x] Phase 0: run configure.php, composer.json (filament ^4||^5, ext-gd), CI matrix
- [x] Phase 1: `config/free-upload.php` (done)
- [x] Phase 1: `FreeUploadUploadController` class (done — route registration still pending in provider wiring)
- [ ] Phase 1: Skeleton cleanup (full): delete `database/` (migration stub + factory), `stubs/`, `src/Commands|Facades|Testing`, demo `src/FreeUpload.php`, `resources/`, `bin/`, `package.json`, `.npmrc`, `.prettierrc`, `tests/DebugTest.php`; drop `Database\Factories` autoload + `FreeUpload` alias from composer.json; trim `database` from `phpstan.neon.dist` paths; `composer dump-autoload`
- [ ] Phase 1: Provider wiring: drop `hasCommands`/`hasMigrations`/stub-publishing/`Testable::mixin`; register config-gated route → `POST {prefix}/upload` named `freeupload.upload`
- [ ] Phase 1: `src/Proxy/` — PxvtDecoder, PxvtDecodeException, ProxyResponse, Proxy (injectable fetcher)
- [ ] Phase 1: `server/` standalone deployable proxy (composer.json, public/index.php, .htaccess)
- [ ] Phase 2: `FreeUpload` component + XHR/PXVT JS (StateCast dropped for MVP; asset registration hooks stay empty — upload JS is inline in `toEmbeddedHtml`)
- [ ] Phase 3: Pest tests — Unit: PxvtDecoder, Proxy, Config · Feature: Controller (Http::fake), Component (Livewire::test); replace skeleton `ExampleTest`/`DebugTest`
- [ ] Phase 4: README rewrite, update-changelog.yml branch fix, verification (lint/analyse/test, `route:list --name=freeupload`, server smoke)

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
- **`phpunit.xml.dist` is strict**: `failOnWarning`, `failOnRisky`, `failOnEmptyTestSuite` — an empty/misnamed test file fails the suite. Coverage/report artifacts go to `build/` (gitignored).
- **Pint** (`pint.json`): laravel preset + `blank_line_before_statement`, `concat_space: one`, `single_trait_insert_per_statement`, `types_spaces: single`. The `fix-code-style` CI workflow auto-commits Pint fixes on every PHP push — keep changes small to avoid churn.
- **PHPStan** (`phpstan.neon.dist`): level 4 only, paths `src`, `config` (drop `database` after cleanup); includes `phpstan-baseline.neon` (currently empty). CI runs it across PHP 8.2–8.4 × Laravel 11–13 × Filament 4/5 with pinned testbench (9/10/11).
- **Rector** (`rector.php`): `src/` only, prepared sets (deadCode, codeQuality, typeDeclarations, privatization, earlyReturn, strictBooleans).
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

- Upload endpoint: plugin's own Laravel controller + route by default; overridable via config or per-component `->uploadEndpoint()`.
- Decode endpoint: configurable base URL (`decode_base_url`, default `https://media-server.kawnek.workers.dev`); stored values are URL strings built as `{base}{/dec if encoded}/{id}/{filename}`.
- Client HTTP: `XMLHttpRequest` with `xhr.upload.onprogress` — **no axios**.
- Non-image files: encoded client-side PXVT → PNG via canvas `toBlob` (30 MB cap); images (`image/*`) upload raw, never encoded.
- Decoding is server-side only, via GD (`imagecreatefromstring`/`imagecolorat`). Proxy buffers responses (no streaming).
- Proxy server is framework-free (cURL + GD), deployable on PHP 8.2+; tests run from the host template via `php artisan test packages/freeupload/tests`.
- Component stores URL state natively: `fetchFileInformation(false)` + a `getUploadedFileUsing` override so URL strings hydrate and render as remote files. **No StateCast in the MVP** (the roadmap's `FreeUploadStateCast` was dropped; re-add only if v3 support is ever ported).

### Package structure (target)

```
config/free-upload.php                 # all endpoints + knobs, env-driven
src/Forms/Components/FreeUpload.php    # extends FileUpload; XHR uploadUsing; toEmbeddedHtml
src/Http/Controllers/FreeUploadUploadController.php
src/Proxy/{PxvtDecoder,Proxy,ProxyResponse,PxvtDecodeException}.php
server/                                # standalone deployable proxy (composer.json, public/index.php, .htaccess)
tests/                                 # Unit: PxvtDecoder, Proxy, Config · Feature: Controller, Component
```

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

### Wiring into the host template (when porting)

1. Template `composer.json`: path repository `"../../packages/freeupload"` + `require: "blalmal10a/free-upload": "dev-main"` + `autoload-dev: "Blalmal10a\\FreeUpload\\Tests\\": "../../packages/freeupload/tests"`; then `composer update blalmal10a/free-upload`.
2. Swap the 2 `KawnekFileUpload` usages in `app/Filament/Resources/Users/Schemas/UserForm.php` to `FreeUpload` (old class untouched).
3. `.env(.example)`: add `FREEUPLOAD_API_KEY` (keep existing key), optional decode/upload/image host overrides.

### Verification checklist

- `php artisan route:list --name=freeupload` → `freeupload.upload` registered
- `php artisan test packages/freeupload/tests --compact` → green
- `vendor/bin/pint` clean (repo + `server/`)
- Server smoke: `composer install` in `server/`, `php -S 0.0.0.0:8080 -t public`, curl `/{id}` and `/dec/{id}/{name}`
- Browser smoke: one upload through `FreeUpload` in the admin panel
