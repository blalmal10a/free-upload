<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Feature;

use Blalmal10a\FreeUpload\Forms\Components\FreeUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Livewire;

class FreeUploadTestForm extends Component implements HasSchemas
{
    use InteractsWithForms;

    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            FreeUpload::make('file')->multiple(),
        ])->statePath('data');
    }

    public function render(): View
    {
        return view('free-upload-test-form');
    }
}

it('keeps url strings in state through hydration', function (): void {
    $url = 'https://media-server.kawnek.workers.dev/dec/abc123/report.pdf';

    Livewire::test(FreeUploadTestForm::class)
        ->fillForm(['file' => [$url]])
        ->assertFormSet(['file' => [$url]]);
});

it('resolves uploaded files from url strings', function (): void {
    $urls = [
        'https://media-server.kawnek.workers.dev/dec/abc123/report.pdf',
        'https://media-server.kawnek.workers.dev/abc123.png',
    ];

    $testable = Livewire::test(FreeUploadTestForm::class)
        ->fillForm(['file' => $urls]);

    $testable->call('callSchemaComponentMethod', 'form.file', 'getUploadedFiles')
        ->assertReturned([
            [
                'name' => 'report.pdf',
                'size' => 0,
                'type' => null,
                'url' => 'https://media-server.kawnek.workers.dev/dec/abc123/report.pdf',
            ],
            [
                'name' => 'abc123.png',
                'size' => 0,
                'type' => null,
                'url' => 'https://media-server.kawnek.workers.dev/abc123.png',
            ],
        ]);
});

it('passes url strings through dehydration unchanged', function (): void {
    $urls = ['https://media-server.kawnek.workers.dev/dec/abc123/report.pdf'];

    $livewire = Livewire::test(FreeUploadTestForm::class)
        ->fillForm(['file' => $urls])
        ->instance();

    $state = $livewire->getSchema('form')->getState();

    expect($state['file'])->toBe($urls);
});

it('removes url strings from state on delete', function (): void {
    $url = 'https://media-server.kawnek.workers.dev/dec/abc123/report.pdf';

    Livewire::test(FreeUploadTestForm::class)
        ->fillForm(['file' => [$url]])
        ->call('callSchemaComponentMethod', 'form.file', 'removeUploadedFile', ['fileKey' => '0'])
        ->assertSet('data.file', []);
});

it('defaults the upload endpoint to the plugin route', function (): void {
    $component = FreeUpload::make('file');

    expect($component->getUploadEndpoint())->toBe(route('freeupload.upload'));
});

it('uses the configured upload endpoint over the plugin route', function (): void {
    config()->set('free-upload.upload_endpoint', 'https://config.example.com/upload');

    expect(FreeUpload::make('file')->getUploadEndpoint())->toBe('https://config.example.com/upload');

    config()->set('free-upload.upload_endpoint', null);

    expect(FreeUpload::make('file')->getUploadEndpoint())->toBe(route('freeupload.upload'));
});

it('defaults the encoded file size cap to the configured value', function (): void {
    config()->set('free-upload.max_encoded_file_mb', 12);

    expect(FreeUpload::make('file')->getMaxEncodedFileMb())->toBe(12)
        ->and(FreeUpload::make('file')->maxEncodedFileMb(5)->getMaxEncodedFileMb())->toBe(5);
});

it('inlines the upload endpoint instead of referencing an alpine data property', function (): void {
    config()->set('free-upload.upload_endpoint', 'https://example.com/upload');

    $testable = Livewire::test(FreeUploadTestForm::class);

    $testable->assertSee('xhr.open(\'POST\', \'https://example.com/upload\')', escape: false)
        ->assertDontSee('POST\', uploadEndpoint)', escape: false)
        ->assertDontSee('uploadEndpoint:', escape: false);
});

it('inlines the encoded file size cap instead of referencing an alpine data property', function (): void {
    config()->set('free-upload.max_encoded_file_mb', 7);

    Livewire::test(FreeUploadTestForm::class)
        ->assertSee('if (file.size > 7340032)', escape: false)
        ->assertDontSee('maxEncodedFileMb', escape: false);
});
