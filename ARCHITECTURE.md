# FreeUpload — Architecture

This is the **feature/design reference** for the package. It is not an agent instruction file.

- Doc roles: `AGENTS.md` = agent root (operational essentials) · `ARCHITECTURE.md` = what/why/how (this file) · `PLAN.md` = implementation process as checkbox tasks.

## Roadmap — FreeUpload plugin

Standalone Filament plugin `blalmal10a/free-upload` (namespace `Blalmal10a\FreeUpload`) supporting **Filament v4 and v5 — one package major** (`filament/filament: ^4.0 || ^5.0`). Ports the host app's `KawnekFileUpload` component + state hook + upload controller into this package; serving/decoding happens **in-app** via the plugin's own PHP proxy (fetch from the image host + PXVT decode), full docs (README), and a Pest suite. **Nothing in the host app is deleted** — the old classes stay in place.

## Version support decisions (verified against Filament 4.x/5.x source, Aug 2026)

- **v4 and v5 share an identical component API** — v5 shipped solely to adopt Livewire 4 (PHP ^8.2, Laravel ^11.28|^12|^13 in both). One codebase / one package major covers both. There is **no v3 support** (v3 = PHP ^8.1, Livewire 3, no `filament/schemas`, Blade-view render).
- If v3 support is ever added later: split into separate majors (`1.x`→v3, `2.x`→v4|v5) — the v3 `FileUpload` renders a Blade view (`$view = 'filament-forms::components.file-upload'`, overridable via `->view()`) and has **no** `Filament\Schemas\Components\StateCasts`, so it needs a Blade re-render + a `dehydrateStateUsing` fallback instead of the cast.
- `FilamentAsset`/`FilamentIcon` registration and the `Plugin` interface (`getId`/`register`/`boot`) are identical in v3/v4/v5 — those parts port cleanly.
- **Known risk**: `FileUpload::toEmbeddedHtml()` and the JS `uploadUsing:` block are at the same source line in 4.x and 5.x today, but they are Filament internals; pin the source commit you copy and re-diff on upgrades.

## Locked decisions

- Upload endpoint: plugin's own Laravel controller + route by default; configurable **only** via env/config (`upload_endpoint` / `FREEUPLOAD_UPLOAD_ENDPOINT`) — deliberately **not** per-component (removed `->uploadEndpoint()` in Aug 2026; the endpoint must be a single env-driven value across all form fields). The configured value may be a full URL, a path (`some/path`), or a route name (`some.path` — guarded by `Route::has()`, falls back to `freeupload.upload`).
- Serving: in-app routes under `{prefix}` — `GET /{prefix}/{image_path}/{id}/{filename}` streams raw from `image_host` (iili.io); `GET /{prefix}/{files_path}/{id}/{filename}` fetches the encoded PNG, PXVT-decodes it, and streams the original file. Stored URLs are `{proxy_base_url or app}/{image_path|files_path}/{idWithExt}/{filename}`. `proxy_base_url` (env `FREEUPLOAD_PROXY_BASE_URL`) defaults to null → the app's own routes. Decode happens in-app via GD; `Proxy` buffers responses (no streaming).
- Client HTTP: `XMLHttpRequest` with `xhr.upload.onprogress` — **no axios**.
- Non-image files: encoded client-side PXVT → PNG via canvas `toBlob` (30 MB cap); images (`image/*`) upload raw, never encoded.
- Decoding is server-side only, via GD (`imagecreatefromstring`/`imagecolorat`). Proxy buffers responses (no streaming).
- Component stores URL state natively: `fetchFileInformation(false)` + a `getUploadedFileUsing` override so URL strings hydrate and render as remote files. **No StateCast in the MVP** (the roadmap's `FreeUploadStateCast` was dropped; re-add only if v3 support is ever ported).

## Package structure

```
config/free-upload.php                 # Public endpoints + config marks, env-driven
src/Forms/Components/FreeUpload.php    # extends FileUpload; XHR uploadUsing; toEmbeddedHtml
src/Http/Controllers/FreeUploadUploadController.php
src/Http/Controllers/FreeUploadFileController.php   # in-app proxy: /{image_path} raw, /{files_path} decode
src/Commands/PublishComponent.php      # freeupload:publish-component (stub → app namespace)
src/Proxy/{PxvtDecoder,Proxy,ProxyResponse,PxvtDecodeException}.php
stubs/Forms/Components/FreeUpload.php.stub           # {{ namespace }} placeholder
tests/                                 # Unit: PxvtDecoder, Proxy, Config · Feature: Controller, FileController, Component
```

## Publishing the component (August 2026)

- `FreeUploadServiceProvider` registers a `publishes()` map for the component stub: tag `free-upload-component` (also grouped under `free-upload`) copies `stubs/Forms/Components/FreeUpload.php.stub` → `app/Forms/Components/FreeUpload.php`.
- The stub carries a `{{ namespace }}` placeholder, replaced by `php artisan freeupload:publish-component` (`--namespace=` overrides the default `App\Forms\Components`). The command creates `app/Forms/Components/` if needed and skips when the target already exists.
- The published copy is a one-time snapshot — package upgrades don't touch it.
- Upload endpoint, size caps, etc. are still config/env driven, so a published component changes only how it renders/uploads.

## Config keys (`config/free-upload.php`)

| Key | Env | Default | Purpose |
| --- | --- | --- | --- |
| `upload_endpoint` | `FREEUPLOAD_UPLOAD_ENDPOINT` | `null` → plugin route `freeupload.upload` | user-defined upload endpoint (URL, path, or route name) |
| `proxy_base_url` | `FREEUPLOAD_PROXY_BASE_URL` | `null` → the app's own routes | base URL of the proxy that serves stored files |
| `image_path` | `FREEUPLOAD_IMAGE_PATH` | `images` | path segment for raw image streaming |
| `files_path` | `FREEUPLOAD_FILES_PATH` | `files` | path segment for PXVT-decoded files |
| `upload_host` | `FREEUPLOAD_UPLOAD_HOST` | `https://freeimage.host/api/1/upload` | upstream hosting API |
| `api_key` | `FREEUPLOAD_API_KEY` | `''` | FreeImage.host API key |
| `max_size_kb` | `FREEUPLOAD_MAX_SIZE_KB` | `32768` | upload validation cap |
| `max_encoded_file_mb` | `FREEUPLOAD_MAX_ENCODED_FILE_MB` | `30` | client-side non-image cap |
| `image_host` | `FREEUPLOAD_IMAGE_HOST` | `https://iili.io` | upstream host the proxy fetches from |
| `proxy_timeout` | `FREEUPLOAD_PROXY_TIMEOUT` | `10` | upstream fetch timeout (seconds) |
| `register_routes` / `route_prefix` / `route_middleware` | `FREEUPLOAD_REGISTER_ROUTES` / `FREEUPLOAD_ROUTE_PREFIX` / `FREEUPLOAD_ROUTE_MIDDLEWARE` | `true` / `freeupload` / `['web','auth']` | plugin route toggles |

## PXVT format & decode algorithm

- Byte layout: `0–3` magic `PXVT` · `4–5` filename length (BE u16) · `6–7` MIME length (BE u16) · `8..8+n` UTF-8 filename · `8+n..h` MIME · `h..` file bytes zero-padded to a multiple of 3.
- Client encode: `ArrayBuffer` → header + bytes → RGBA `ImageData` (3 bytes/pixel, alpha 255) → square canvas → `toBlob('image/png')`.
- Server decode (GD): `imagecreatefromstring` → append R/G/B of every pixel row-major → check `PXVT` magic (else 400 `Invalid vault signature format`) → read lengths → slice filename/MIME/content → `rtrim($bytes, "\0")` → return `{filename, mimeType, bytes}`.

## Proxy routing (`src/Proxy/Proxy.php`)

`Proxy(string $imageHost, int $timeoutSeconds, ?callable $fetcher)` — fetcher injectable for tests. `OPTIONS` → 204 + CORS; `/images/{id}/{filename?}` → fetch upstream `{imageHost}/{id}`, ≥400 → 404, else stream with `Cache-Control: public, max-age=86400`; `/files/{id}/{filename}` → fetch PNG, upstream fail → 502, bad magic/truncated → 400, other decode errors → 500, success → 200 with original `Content-Type` + `Content-Disposition: inline`, `Cache-Control: no-store`; anything else → 400. CORS headers (`Access-Control-Allow-Origin: *`, etc.) merged on all responses.

## Wiring into the host template (done — `blalmal10a/kawnek-template`, Aug 2026)

The package was imported into the host template `blalmal10a/kawnek-template` (GitHub default branch `main`). Steps taken and corrections to earlier docs:

1. Template `composer.json`: added path repository `{ "type": "path", "url": "../../packages/free-upload", "options": { "symlink": false } }`, `require: "blalmal10a/free-upload": "@dev"`, and `autoload-dev: "Blalmal10a\\FreeUpload\\Tests\\": "../../packages/free-upload/tests"`, then `composer update blalmal10a/free-upload --with-all-dependencies` (locks `5.x-dev`).
   - **Version constraint must be `@dev`** — the package branch is `5.x`; both `dev-main` and `dev-5.x` (and `dev-5.x as x.y.z`) fail to match the path repo's `5.x-dev`.
   - **Directory is `free-upload` (hyphen)** — earlier docs in `FREE_UPLOAD.md`/`AGENTS.md` said `freeupload`.
2. Swapped the 2 usages in the template's [UserForm.php](https://github.com/blalmal10a/kawnek-template/blob/main/app/Filament/Resources/Users/Schemas/UserForm.php) from `KawnekFileUpload` (source: [KawnekFileUpload.php](https://github.com/blalmal10a/kawnek-template/blob/main/app/Filament/Forms/Components/KawnekFileUpload.php)) to `FreeUpload` — the old template class stays in place, untouched.
3. `.env` / `.env.example`: added `FREEUPLOAD_API_KEY=6d207e02198a847aa98d0a2a901485a5` (the FreeImage.host key that was hardcoded in the template's old `FreeImageHostUploadController`). Optional overrides (`FREEUPLOAD_PROXY_BASE_URL`, `FREEUPLOAD_UPLOAD_HOST`, `FREEUPLOAD_UPLOAD_ENDPOINT`, `FREEUPLOAD_IMAGE_HOST`) were not added.
4. Routes auto-registered: `POST freeupload/upload` named `freeupload.upload`, `GET freeupload/images/{id}/{filename}` named `freeupload.image`, `GET freeupload/files/{id}/{filename}` named `freeupload.files` (template's `extra.laravel.dont-discover` is empty, so the provider auto-discovers; route middleware `['web','auth']`).
5. Template verification after the swap: `php artisan test --compact` (52 passed), `vendor/bin/pint --dirty`, `vendor/bin/phpstan`, `vendor/bin/rector --dry-run` — all green.

## Known issue fixed: Alpine v3 arrow-function scoping (Aug 2026)

Browser smoke test after the import hit `ReferenceError: uploadEndpoint is not defined` inside the component's `uploadUsing` JS. Root cause and fix:

- `toEmbeddedHtml()` originally declared `uploadEndpoint` and `maxEncodedFileMb` as **Alpine data properties** on the `fileUploadFormComponent({...})` object, then referenced them as bare variables inside the nested `send` arrow function. Alpine v3's `with(this)` scope wrapping only applies to regular method bodies, not arrow functions (which capture `this` lexically) — so bare property names resolve to nothing at runtime.
- **Fix**: both values are now **inlined at point of use** via `Js::from($this->getUploadEndpoint(), JSON_UNESCAPED_SLASHES)` and `Js::from($this->getMaxEncodedFileMb() * 1024 * 1024, JSON_UNESCAPED_SLASHES)`; the data properties were removed from the Alpine object.
- `JSON_UNESCAPED_SLASHES` keeps URLs readable in the emitted HTML (Laravel's `Js::from` otherwise escapes `/` as `\/`; runtime is unaffected either way).
- **Constraint going forward**: never reference Alpine data properties as bare variables inside arrow functions in `toEmbeddedHtml()` JS — inline PHP values with `Js::from()` instead (matches how Filament's own component code works).
- Regression coverage: `tests/Feature/FreeUploadComponentTest.php` now asserts the endpoint (`xhr.open('POST', 'https://example.com/upload')`) and size cap (`if (file.size > 7340032)`) are inlined and that no `uploadEndpoint:`/`maxEncodedFileMb` data properties remain.