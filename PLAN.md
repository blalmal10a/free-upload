# FreeUpload — Plan

Implementation process for the package, tracked as checkbox tasks. Feature details live in `ARCHITECTURE.md`; agent essentials live in `AGENTS.md`.

## Current work — Filament 4 prefer-lowest test failures (Aug 2026)

- [x] Reproduce the failing CI row locally (php 8.4 × laravel 12 lowest × filament 4 lowest): temp env pinned Filament 4.11.5 / Laravel 12.61.1 / Livewire 3.6.4 — the 3 component tests (inlined upload endpoint, encoded size cap, `$wire.set` state) fail, 10 pass
- [x] Root cause: in Filament 4, `FileUpload` does **not** implement `HasEmbeddedView` and hardcodes `$view`, so `ViewComponent::toHtml()` renders the Blade file-upload view with the stock `$wire.upload(...)` JS — our `toEmbeddedHtml()` override is never called. Filament 5's `FileUpload implements HasEmbeddedView`, so the embedded path runs there.
- [x] Fix `src/Forms/Components/FreeUpload.php`: implement `HasEmbeddedView`, override `toHtml()` → `toEmbeddedHtml()`, and wrap the embedded HTML via `wrapEmbeddedHtml()` when the base `FileUpload` implements the interface (v5) or via the blade field-wrapper view when it does not (v4; `filament-forms::field-wrapper` falls back to `filament-forms::components.field-wrapper`, which is the actually registered view)
- [x] Verify: `composer verify` green locally (Filament 5.7.5 / Laravel 13.23); full suite green in the v4 repro — 43 tests / 99 assertions in both
- [ ] Re-run CI and confirm the previously failing matrix rows pass (P8.4 × L12.* × F4.* × prefer-lowest, ubuntu + windows)

## History — Phase 0–4 (done, Aug 2026)

> Earlier current-work block (docs restructure, done before the F4 fix): create `ARCHITECTURE.md`; create `PLAN.md` with checkbox tasks; slim `AGENTS.md` to agent essentials; remove stray `./--version/` husky-artifact dir; verify doc cross-references + `composer verify` (43 tests / 99 assertions).

- [x] Update `AGENTS.md`: v4+v5 support framing (replace v5-only roadmap)
- [x] Phase 0: run configure.php, composer.json (filament ^4||^5, ext-gd), CI matrix
- [x] Phase 1: `config/free-upload.php`
- [x] Phase 1: `FreeUploadUploadController` class (route registration rolled into provider wiring)
- [x] Phase 1: Skeleton cleanup (full): delete `database/` (migration stub + factory), `stubs/`, `src/Commands|Facades|Testing`, demo `src/FreeUpload.php`, `resources/`, `bin/`, `package.json`, `.npmrc`, `.prettierrc`, `tests/DebugTest.php`; drop `Database\Factories` autoload + `FreeUpload` alias from composer.json; trim `database` from `phpstan.neon.dist` paths; `composer dump-autoload`
- [x] Phase 1: Provider wiring: drop `hasCommands`/`hasMigrations`/stub-publishing/`Testable::mixin`; register config-gated route → `POST {prefix}/upload` named `freeupload.upload`
- [x] Phase 1: `src/Proxy/` — PxvtDecoder, PxvtDecodeException, ProxyResponse, Proxy (injectable fetcher)
- [x] Phase 1: In-app file serving: `FreeUploadFileController` wraps `Proxy`; GET `{prefix}/{image_path}/{id}/{filename}` (raw) + `{prefix}/{files_path}/{id}/{filename}` (decode); standalone `server/` deleted
- [x] Phase 2: `FreeUpload` component + XHR/PXVT JS (StateCast dropped for MVP; asset registration hooks stay empty — upload JS is inline in `toEmbeddedHtml`); `$wire.set(statePath, [...state, url])` after upload
- [x] Phase 3: Pest tests — Unit: PxvtDecoder, Proxy, Config · Feature: Controller (Http::fake), FileController, Component (Livewire::test); replace skeleton `ExampleTest`/`DebugTest`
- [x] Phase 4: README rewrite, update-changelog.yml branch fix, verification (lint/analyse/test, `route:list --name=freeupload`)