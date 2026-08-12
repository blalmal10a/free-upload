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

    $this->postJson(route('freeupload.upload'), [
        'file' => UploadedFile::fake()->image('photo.png'),
        'filename' => 'photo.png',
        'mime_type' => 'image/png',
    ])->assertOk()
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

    $this->postJson(route('freeupload.upload'), [
        'file' => UploadedFile::fake()->createWithContent('encoded.png', 'fake-file-content-here'),
        'filename' => 'report.pdf',
        'mime_type' => 'application/pdf',
        'encoded' => '1',
    ])->assertOk()
        ->assertJson([
            'url' => url('/freeupload/files/abc123.png/report.pdf'),
        ]);
});

it('returns 422 for invalid uploads', function (): void {
    $this->postJson(route('freeupload.upload'), [
        'filename' => 'photo.png',
        'mime_type' => 'image/png',
    ])->assertStatus(422);
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
