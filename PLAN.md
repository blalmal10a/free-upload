# FreeUpload — Plan

Implementation process for the package, tracked as checkbox tasks. Feature details live in `ARCHITECTURE.md`; agent essentials live in `AGENTS.md`.

## Current work — docs restructure (Aug 2026)

- [x] Create `ARCHITECTURE.md` with all feature/design details moved from `AGENTS.md`
- [x] Create `PLAN.md` (this file) with checkbox tasks
- [x] Slim `AGENTS.md` to agent essentials only (repo status, toolchain, test conventions, tooling constraints, verification checklist + doc pointers)
- [x] Remove stray `./--version/` husky-artifact dir (untracked, invisible to `git status` — leftover from a bad husky install)
- [x] Verify doc cross-references and run `composer verify` (green: 43 tests / 99 assertions)

## History — Phase 0–4 (done, Aug 2026)

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