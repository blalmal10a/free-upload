<?php

declare(strict_types=1);

namespace Blalmal10a\FreeUpload\Tests\Feature;

use Blalmal10a\FreeUpload\Forms\Components\FreeUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

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
