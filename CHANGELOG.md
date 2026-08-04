# Changelog

All notable changes to `free-upload` will be documented in this file.

## v1.0.0 - 2026-08-04

### v1.0.0

First stable release of **blalmal10a/free-upload** — a Filament v4/v5 upload component backed by FreeImage.host with in-app file serving.

#### Highlights

- **In-app file proxy** — `FreeUploadFileController` + `Proxy` (iili.io fetch + PXVT decode) replaces the external `media-server.kawnek.workers.dev`; no third-party file server needed
- **Config-driven endpoints** — `upload_endpoint` (full URL, path, or route name), `proxy_base_url`, `image_path`, `files_path`, `proxy_timeout` via env or config; stored URLs point at your own app
- **XHR uploads with progress** — no axios; non-image files encoded client-side to PNG (PXVT), images uploaded raw
- **Native Filament state** — URL strings stored directly; `$wire.set(...)` keeps state in sync after upload
- **Component publishing** — `freeupload:publish-component` command + `vendor:publish --tag=free-upload-component` stub with namespace placeholder
- **Routes** — `POST /freeupload/upload`, `GET /freeupload/images/{id}/{filename}`, `GET /freeupload/files/{id}/{filename}` (configurable prefix/middleware)
- **Full Pest suite** — 43 tests / 99 assertions (Proxy, PxvtDecoder, controllers, component)

#### Requirements

- PHP ^8.2, ext-gd
- Laravel ^11|^12|^13, Filament ^4.0|^5.0
