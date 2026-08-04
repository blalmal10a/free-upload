<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->actingAs(new class extends User
    {
        public $timestamps = false;
    });
});

it('uploads raw images and returns the image url', function (): void {
    Http::fake([
        'https://freeimage.host/*' => Http::response([
            'image' => ['url' => 'https://freeimage.host/img/abc123'],
        ]),
    ]);

    $response = $this->postJson(route('freeupload.upload'), [
        'file' => UploadedFile::fake()->image('photo.png'),
        'filename' => 'photo.png',
        'mime_type' => 'image/png',
    ]);

    $response->assertOk()
        ->assertJson([
            'url' => url('/freeupload/images/abc123.png/photo.png'),
        ]);
});

it('returns a files url for encoded non-image uploads', function (): void {
    Http::fake([
        'https://freeimage.host/*' => Http::response([
            'image' => ['url' => 'https://freeimage.host/img/abc123'],
        ]),
    ]);

    $response = $this->postJson(route('freeupload.upload'), [
        'file' => UploadedFile::fake()->create('encoded.png', 10),
        'filename' => 'report.pdf',
        'mime_type' => 'application/pdf',
        'encoded' => '1',
    ]);

    $response->assertOk()
        ->assertJson([
            'url' => url('/freeupload/files/abc123.png/report.pdf'),
        ]);
});

it('forwards the api key and mime type to the upstream host', function (): void {
    config()->set('free-upload.api_key', 'secret-key');

    Http::fake([
        'https://freeimage.host/*' => Http::response([
            'image' => ['url' => 'https://freeimage.host/img/abc123'],
        ]),
    ]);

    $this->postJson(route('freeupload.upload'), [
        'file' => UploadedFile::fake()->image('photo.png'),
        'filename' => 'photo.png',
        'mime_type' => 'image/png',
    ])->assertOk();

    Http::assertSent(function ($request) {
        $body = (string) $request->toPsrRequest()->getBody();

        return $request->url() === 'https://freeimage.host/api/1/upload'
            && $request->isMultipart()
            && $request->hasFile('source')
            && str_contains($body, 'secret-key')
            && str_contains($body, 'image/png');
    });
});

it('returns 502 when the upstream host fails', function (): void {
    Http::fake([
        'https://freeimage.host/*' => Http::response(status: 500),
    ]);

    $this->postJson(route('freeupload.upload'), [
        'file' => UploadedFile::fake()->image('photo.png'),
        'filename' => 'photo.png',
        'mime_type' => 'image/png',
    ])->assertStatus(502);
});

it('returns 422 for invalid uploads', function (): void {
    $this->postJson(route('freeupload.upload'), [
        'filename' => 'photo.png',
        'mime_type' => 'image/png',
    ])->assertStatus(422);
});

it('uses the configured proxy base url when building urls', function (): void {
    config()->set('free-upload.proxy_base_url', 'https://proxy.example.com');

    Http::fake([
        'https://freeimage.host/*' => Http::response([
            'image' => ['url' => 'https://freeimage.host/img/abc123'],
        ]),
    ]);

    $this->postJson(route('freeupload.upload'), [
        'file' => UploadedFile::fake()->image('photo.png'),
        'filename' => 'photo.png',
        'mime_type' => 'image/png',
    ])->assertJson([
        'url' => 'https://proxy.example.com/images/abc123.png/photo.png',
    ]);
});

it('uses the configured file paths when building urls', function (): void {
    config()->set('free-upload.image_path', 'media');
    config()->set('free-upload.files_path', 'vault');

    Http::fake([
        'https://freeimage.host/*' => Http::response([
            'image' => ['url' => 'https://freeimage.host/img/abc123'],
        ]),
    ]);

    $this->postJson(route('freeupload.upload'), [
        'file' => UploadedFile::fake()->image('photo.png'),
        'filename' => 'photo.png',
        'mime_type' => 'image/png',
    ])->assertJson([
        'url' => url('/freeupload/media/abc123.png/photo.png'),
    ]);

    $response = $this->postJson(route('freeupload.upload'), [
        'file' => UploadedFile::fake()->create('encoded.png', 10),
        'filename' => 'report.pdf',
        'mime_type' => 'application/pdf',
        'encoded' => '1',
    ]);

    $response->assertJson([
        'url' => url('/freeupload/vault/abc123.png/report.pdf'),
    ]);
});
