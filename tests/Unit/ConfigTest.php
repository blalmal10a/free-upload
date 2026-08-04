<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Unit;

use Blalmal10a\FreeUpload\FreeUploadServiceProvider;
use Illuminate\Support\ServiceProvider;

it('exposes the expected configuration defaults', function (): void {
    expect(config('free-upload.upload_endpoint'))->toBeNull()
        ->and(config('free-upload.proxy_base_url'))->toBeNull()
        ->and(config('free-upload.image_path'))->toBe('images')
        ->and(config('free-upload.files_path'))->toBe('files')
        ->and(config('free-upload.upload_host'))->toBe('https://freeimage.host/api/1/upload')
        ->and(config('free-upload.api_key'))->toBe('6d207e02198a847aa98d0a2a901485a5')
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

it('registers the image and files serving routes', function (): void {
    expect(parse_url(route('freeupload.image', ['id' => 'abc123.png', 'filename' => 'photo.png']), PHP_URL_PATH))
        ->toBe('/freeupload/images/abc123.png/photo.png')
        ->and(parse_url(route('freeupload.files', ['id' => 'abc123.png', 'filename' => 'report.pdf']), PHP_URL_PATH))
        ->toBe('/freeupload/files/abc123.png/report.pdf');
});

it('reads runtime configuration overrides', function (): void {
    config()->set('free-upload.max_encoded_file_mb', 5);

    expect(config('free-upload.max_encoded_file_mb'))->toBe(5);
});

it('registers a publishable stub of the FreeUpload component', function (): void {
    $stubSource = realpath(__DIR__ . '/../../stubs/Forms/Components/FreeUpload.php.stub');

    expect($stubSource)->not->toBeFalse();

    $publishTarget = app_path('Forms/Components/FreeUpload.php');

    $publishSources = ServiceProvider::$publishes[FreeUploadServiceProvider::class] ?? [];
    $publishSource = array_search($publishTarget, $publishSources, true);

    expect($publishSource)
        ->toBeString()
        ->and(realpath($publishSource))->toBe($stubSource)
        ->and($publishSources[$publishSource] ?? null)->toBe($publishTarget)
        ->and(ServiceProvider::$publishGroups['free-upload-component'] ?? [])
        ->toContain($publishTarget);
});
