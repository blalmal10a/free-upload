<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Unit;

use Blalmal10a\FreeUpload\FreeUploadServiceProvider;
use Illuminate\Support\ServiceProvider;

it('exposes the expected configuration defaults', function (): void {
    expect(config('free-upload.upload_endpoint'))->toBeNull()
        ->and(config('free-upload.decode_base_url'))->toBe('https://media-server.kawnek.workers.dev')
        ->and(config('free-upload.upload_host'))->toBe('https://freeimage.host/api/1/upload')
        ->and(config('free-upload.api_key'))->toBe('')
        ->and(config('free-upload.max_size_kb'))->toBe(32768)
        ->and(config('free-upload.max_encoded_file_mb'))->toBe(30)
        ->and(config('free-upload.image_host'))->toBe('https://iili.io')
        ->and(config('free-upload.register_routes'))->toBeTrue()
        ->and(config('free-upload.route_prefix'))->toBe('freeupload')
        ->and(config('free-upload.route_middleware'))->toBe(['web', 'auth']);
});

it('registers the upload route when route registration is enabled', function (): void {
    $route = route('freeupload.upload');

    expect($route)->toBeString()
        ->and(parse_url($route, PHP_URL_PATH))->toBe('/freeupload/upload');
});

it('reads runtime configuration overrides', function (): void {
    config()->set('free-upload.max_encoded_file_mb', 5);

    expect(config('free-upload.max_encoded_file_mb'))->toBe(5);
});

it('registers a publishable copy of the FreeUpload component', function (): void {
    $componentSource = realpath(__DIR__ . '/../../src/Forms/Components/FreeUpload.php');

    expect($componentSource)->not->toBeFalse();

    $publishTarget = app_path('Forms/Components/FreeUpload.php');

    expect(ServiceProvider::$publishes[FreeUploadServiceProvider::class] ?? [])
        ->toContain($publishTarget)
        ->and(ServiceProvider::$publishGroups['free-upload-component'] ?? [])
        ->toContain($publishTarget);
});
