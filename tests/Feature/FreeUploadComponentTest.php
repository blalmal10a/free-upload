<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Feature;

use Blalmal10a\FreeUpload\Forms\Components\FreeUpload;
use Livewire\Livewire;

it('keeps url strings in state through hydration', function (): void {
    $url = url('/freeupload/files/abc123.png/report.pdf');

    Livewire::test(FreeUploadTestForm::class)
        ->fillForm(['file' => [$url]])
        ->assertFormSet(['file' => [$url]]);
});

it('resolves uploaded files from url strings', function (): void {
    $url = url('/freeupload/files/abc123.png/report.pdf');

    Livewire::test(FreeUploadTestForm::class)
        ->fillForm(['file' => [$url]])
        ->call('callSchemaComponentMethod', 'form.file', 'getUploadedFiles')
        ->assertReturned([
            [
                'name' => 'report.pdf',
                'size' => 0,
                'type' => null,
                'url' => $url,
            ],
        ]);
});

it('defaults the upload endpoint to the plugin route', function (): void {
    expect(FreeUpload::make('file')->getUploadEndpoint())->toBe(route('freeupload.upload'));
});

it('inlines the upload endpoint instead of referencing an alpine data property', function (): void {
    config()->set('free-upload.upload_endpoint', 'https://example.com/upload');

    Livewire::test(FreeUploadTestForm::class)
        ->assertSee('xhr.open(\'POST\', \'https://example.com/upload\')', escape: false)
        ->assertDontSee('uploadEndpoint:', escape: false);
});

it('inlines the encoded file size cap instead of referencing an alpine data property', function (): void {
    config()->set('free-upload.max_encoded_file_mb', 7);

    Livewire::test(FreeUploadTestForm::class)
        ->assertSee('if (file.size > 7340032)', escape: false)
        ->assertDontSee('maxEncodedFileMb', escape: false);
});

it('sets the livewire state with the uploaded url after a successful upload', function (): void {
    Livewire::test(FreeUploadTestForm::class)
        ->assertSee('$wire.set(\'data.file\', next)', escape: false);
});
