<?php

namespace Blalmal10a\FreeUpload\Commands;

use Illuminate\Console\Command;

class FreeUploadCommand extends Command
{
    public $signature = 'free-upload';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
