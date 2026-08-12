<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Unit;

use Blalmal10a\FreeUpload\FreeUploadServiceProvider;
use Illuminate\Support\ServiceProvider;

it('exposes the expected configuration defaults', function (): void {
    expect(config('free-upload.upload_endpoint'))->toBeNull()
        ->and(config('free-upload.image_path'))->toBe('images')
        ->and(config('free-upload.files_path'))->toBe('files')
        ->and(config('free-upload.max_encoded_file_mb'))->toBe(30)
        ->and(config('free-upload.register_routes'))->toBeTrue()
        ->and(config('free-upload.route_prefix'))->toBe('freeupload');
});

it('registers the upload and serving routes', function (): void {
    expect(parse_url(route('freeupload.upload'), PHP_URL_PATH))->toBe('/freeupload/upload')
        ->and(parse_url(route('freeupload.image', ['id' => 'abc123.png', 'filename' => 'photo.png']), PHP_URL_PATH))
        ->toBe('/freeupload/images/abc123.png/photo.png')
        ->and(parse_url(route('freeupload.files', ['id' => 'abc123.png', 'filename' => 'report.pdf']), PHP_URL_PATH))
        ->toBe('/freeupload/files/abc123.png/report.pdf');
});

it('registers a publishable stub of the FreeUpload component', function (): void {
    $stubSource = realpath(__DIR__ . '/../../stubs/Forms/Components/FreeUpload.php.stub');

    expect($stubSource)->not->toBeFalse();

    $publishTarget = app_path('Forms/Components/FreeUpload.php');

    $publishSources = ServiceProvider::$publishes[FreeUploadServiceProvider::class] ?? [];

    expect(realpath(array_search($publishTarget, $publishSources, true)))->toBe($stubSource);
});
