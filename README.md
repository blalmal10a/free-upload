# FreeUpload

[![Latest Version on Packagist](https://img.shields.io/packagist/v/blalmal10a/free-upload.svg?style=flat-square)](https://packagist.org/packages/blalmal10a/free-upload)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/blalmal10a/free-upload/tests.yml?branch=5.x&label=tests&style=flat-square)](https://github.com/blalmal10a/free-upload/actions?query=workflow%3Atests+branch%3A5.x)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/blalmal10a/free-upload/fix-code-style.yml?branch=5.x&label=code%20style&style=flat-square)](https://github.com/blalmal10a/free-upload/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3A5.x)
[![Total Downloads](https://img.shields.io/packagist/dt/blalmal10a/free-upload.svg?style=flat-square)](https://packagist.org/packages/blalmal10a/free-upload)

A [Filament](https://filamentphp.com) v4/v5 file upload form component that uploads files directly to an external image hosting API over `XMLHttpRequest` (with real upload progress), stores plain URL strings in your model, and serves those files back through the plugin's own PHP proxy (fetch from the image host, PXVT-decode on demand).

- **Filament v4 and v5** — one package major, same component API on both.
- **Images** (`image/*`) are uploaded raw and served straight from the image host.
- **Non-image files** are PXVT-encoded client-side into a PNG, uploaded, then decoded server-side back into the original bytes (MIME type + filename preserved).
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
| `upload_endpoint` | `FREEUPLOAD_UPLOAD_ENDPOINT` | `null` → plugin route `freeupload.upload` | user-defined upload endpoint (URL, path, or route name) |
| `proxy_base_url` | `FREEUPLOAD_PROXY_BASE_URL` | `null` → the app's own routes | base URL of the proxy that serves stored files |
| `image_path` | `FREEUPLOAD_IMAGE_PATH` | `images` | path segment for raw image streaming (no decode) |
| `files_path` | `FREEUPLOAD_FILES_PATH` | `files` | path segment for PXVT-decoded files |
| `upload_host` | `FREEUPLOAD_UPLOAD_HOST` | `https://freeimage.host/api/1/upload` | upstream hosting API |
| `api_key` | `FREEUPLOAD_API_KEY` | `''` | upstream API key |
| `max_size_kb` | `FREEUPLOAD_MAX_SIZE_KB` | `32768` | upload validation cap |
| `max_encoded_file_mb` | `FREEUPLOAD_MAX_ENCODED_FILE_MB` | `30` | client-side cap for encoded non-image uploads |
| `image_host` | `FREEUPLOAD_IMAGE_HOST` | `https://iili.io` | image host the proxy fetches files from |
| `proxy_timeout` | `FREEUPLOAD_PROXY_TIMEOUT` | `10` | upstream fetch timeout (seconds) |
| `register_routes` | `FREEUPLOAD_REGISTER_ROUTES` | `true` | register the plugin's upload and file routes |
| `route_prefix` | `FREEUPLOAD_ROUTE_PREFIX` | `freeupload` | route prefix |
| `route_middleware` | `FREEUPLOAD_ROUTE_MIDDLEWARE` | `['web', 'auth']` | route middleware |

Set `FREEUPLOAD_API_KEY` in your `.env` file:

```env
FREEUPLOAD_API_KEY=your-freeimage-host-key
```

### Upload endpoint

`upload_endpoint` accepts:

- a full URL (`https://example.com/upload`),
- a path (`some/path` or `/some/path`),
- a named route (`some.route.name` — resolved via Laravel's route helper, falling back to the plugin's `freeupload.upload` route when the name doesn't exist).

When unset, the plugin's own `POST /freeupload/upload` route (named `freeupload.upload`) is used.

### Serving files (the proxy)

The plugin registers two GET routes under the route prefix (when `register_routes` is enabled):

- `GET /{prefix}/{image_path}/{id}/{filename}` — fetches the image from `image_host` and streams it as-is (`Cache-Control: public, max-age=86400`).
- `GET /{prefix}/{files_path}/{id}/{filename}` — fetches the encoded PNG, PXVT-decodes it, and streams the original file (`Content-Disposition: inline`, `Cache-Control: no-store`).

Stored URLs point at these routes by default (`proxy_base_url` unset), e.g. `https://your-app.com/freeupload/images/abc123.png/photo.png`. Set `FREEUPLOAD_PROXY_BASE_URL` to serve them from an external proxy instead; the same `/images` and `/files` path structure is used on the external base.

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
3. The endpoint responds with `{"url": "..."}` — a URL served by the proxy (`{base}/{image_path}/{id}/{filename}` for images, `{base}/{files_path}/{id}/{filename}` for encoded files).
4. The URL is written back into the Livewire state and rendered into the component on re-visit; the proxy serves the file and decodes PXVT payloads on demand.

> [!NOTE]
> Stored URL strings are client-controllable values. Treat them as untrusted when the field is editable by non-admin users.

## Publishing & customizing the component

You can publish the `FreeUpload` component stub into your application and take full ownership of it:

```bash
php artisan freeupload:publish-component
```

This writes the component to `app/Forms/Components/FreeUpload.php` with the namespace `App\Forms\Components` (override with `--namespace=`):

```bash
php artisan freeupload:publish-component --namespace="MyApp\Forms\Components"
```

The raw stub can also be copied via `php artisan vendor:publish --tag="free-upload-component"` (the `{{ namespace }}` placeholder then needs to be replaced manually).

To use your copy instead of the package's:

```php
use App\Forms\Components\FreeUpload;

FreeUpload::make('file')
    ->multiple()
    ->acceptedFileTypes(['image/*', 'application/pdf']);
```

The published copy is a snapshot — upgrades to the package won't touch it. The component keeps using the package's config keys and routes unless you override them, so a published copy only changes how the component renders and uploads.

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
