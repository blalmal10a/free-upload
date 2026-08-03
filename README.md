# FreeUpload

[![Latest Version on Packagist](https://img.shields.io/packagist/v/blalmal10a/free-upload.svg?style=flat-square)](https://packagist.org/packages/blalmal10a/free-upload)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/blalmal10a/free-upload/tests.yml?branch=5.x&label=tests&style=flat-square)](https://github.com/blalmal10a/free-upload/actions?query=workflow%3Atests+branch%3A5.x)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/blalmal10a/free-upload/fix-code-style.yml?branch=5.x&label=code%20style&style=flat-square)](https://github.com/blalmal10a/free-upload/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3A5.x)
[![Total Downloads](https://img.shields.io/packagist/dt/blalmal10a/free-upload.svg?style=flat-square)](https://packagist.org/packages/blalmal10a/free-upload)

A [Filament](https://filamentphp.com) v4/v5 file upload form component that uploads files directly to an external image hosting API over `XMLHttpRequest` (with real upload progress), stores plain URL strings in your model, and ships a standalone framework-free PHP proxy server for serving and decoding those files.

- **Filament v4 and v5** — one package major, same component API on both.
- **Images** (`image/*`) are uploaded raw and served straight from the image host.
- **Non-image files** are PXVT-encoded client-side into a PNG, uploaded, then decoded server-side by the proxy back into the original bytes (MIME type + filename preserved).
- **No axios** — plain `XMLHttpRequest` with `xhr.upload.onprogress`.
- **URL state** — the stored value is a string URL; no state casts, no storage disk needed.

## Installation

```bash
composer require blalmal10a/free-upload
```

Publish the config file:

```bash
php artisan vendor:publish --tag="free-upload-config"
```

## Configuration

All options are environment-driven:

| Key | Env | Default | Purpose |
| --- | --- | --- | --- |
| `upload_endpoint` | `FREEUPLOAD_UPLOAD_ENDPOINT` | `null` → plugin route `freeupload.upload` | user-defined upload endpoint |
| `decode_base_url` | `FREEUPLOAD_DECODE_BASE_URL` | `https://media-server.kawnek.workers.dev` | base URL of your proxy server |
| `upload_host` | `FREEUPLOAD_UPLOAD_HOST` | `https://freeimage.host/api/1/upload` | upstream hosting API |
| `api_key` | `FREEUPLOAD_API_KEY` | `''` | upstream API key |
| `max_size_kb` | `FREEUPLOAD_MAX_SIZE_KB` | `32768` | upload validation cap |
| `max_encoded_file_mb` | `FREEUPLOAD_MAX_ENCODED_FILE_MB` | `30` | client-side cap for encoded non-image uploads |
| `image_host` | `FREEUPLOAD_IMAGE_HOST` | `https://iili.io` | image host used by the standalone proxy |
| `register_routes` | `FREEUPLOAD_REGISTER_ROUTES` | `true` | register the plugin's upload route |
| `route_prefix` | `FREEUPLOAD_ROUTE_PREFIX` | `freeupload` | route prefix (`POST {prefix}/upload`) |
| `route_middleware` | `FREEUPLOAD_ROUTE_MIDDLEWARE` | `['web', 'auth']` | route middleware |

Set `FREEUPLOAD_API_KEY` in your `.env` file:

```env
FREEUPLOAD_API_KEY=your-freeimage-host-key
```

## Usage

```php
use Blalmal10a\FreeUpload\Forms\Components\FreeUpload;

FreeUpload::make('file')
    ->multiple()
    ->acceptedFileTypes(['image/*', 'application/pdf'])
```

The component extends Filament's `FileUpload`, so all regular options work (`->image()`, `->maxSize()`, `->reorderable()`, ...). The uploaded value is a URL string (or an array of URL strings when `->multiple()`).

The upload endpoint is configured **globally** via env/config (`FREEUPLOAD_UPLOAD_ENDPOINT` / `upload_endpoint`) — it is deliberately not overridable per component, so every form field uploads to the same endpoint.

Additional per-component options:

```php
FreeUpload::make('file')
    ->maxEncodedFileMb(10)                  // override the encoded-upload size cap per component
```

### How uploads work

1. The user picks a file. If it is a non-image file and fits within `max_encoded_file_mb`, the browser PXVT-encodes it into a PNG on a canvas and uploads that; images are uploaded raw.
2. The upload is sent via `XMLHttpRequest` with progress reporting to the component's endpoint (by default the plugin's own `POST /freeupload/upload` route, validated and forwarded to your `upload_host`).
3. The endpoint responds with `{"url": "..."}` — a URL on your proxy server (`{decode_base_url}/{id}/{filename}` for images, `{decode_base_url}/dec/{id}/{filename}` for encoded files).
4. The URL is stored as the field state and rendered back into the component on re-visit; the proxy serves the file and decodes PXVT payloads on demand.

> [!NOTE]
> Stored URL strings are client-controllable values. Treat them as untrusted when the field is editable by non-admin users.

## Publishing & customizing the component

You can publish the `FreeUpload` component source into your application and take full ownership of it:

```bash
php artisan vendor:publish --tag="free-upload-component"
```

This publishes a copy of `FreeUpload.php` to `app/Forms/Components/FreeUpload.php`. To use your copy instead of the package's:

1. Change the namespace in the published file from `Blalmal10a\FreeUpload\Forms\Components` to your application's namespace (e.g. `App\Forms\Components`).
2. Reference the published class in your schemas:

```php
use App\Forms\Components\FreeUpload;

FreeUpload::make('file')
    ->multiple()
    ->acceptedFileTypes(['image/*', 'application/pdf']);
```

The published copy is a snapshot — upgrades to the package won't touch it. The component keeps using the package's config keys and `freeupload.upload` route unless you override them, so a published copy only changes how the component renders and uploads.

## The proxy server

The `server/` directory contains a framework-free PHP proxy (cURL + GD, PHP 8.2+):

- `GET /{id}/{filename?}` — proxies the image from `image_host` with `Cache-Control: public, max-age=86400`.
- `GET /dec/{id}/{filename}` — fetches the encoded PNG and returns the original file bytes, MIME type, and `Content-Disposition: inline`.
- CORS headers are set on every response, so the browser can render files cross-origin.
- Responses are buffered (no streaming).

Deploy it anywhere PHP 8.2+ runs (shared hosting with `.htaccess`, or `php -S 0.0.0.0:8080 -t server/public` for a quick local test). Configure it with:

```env
FREEUPLOAD_IMAGE_HOST=https://iili.io
FREEUPLOAD_PROXY_TIMEOUT=10
```

Point `FREEUPLOAD_DECODE_BASE_URL` in your app at the deployed proxy.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
