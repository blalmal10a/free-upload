<?php

namespace Blalmal10a\FreeUpload\Commands;

use Illuminate\Console\Command;

class PublishComponent extends Command
{
    protected $signature = 'freeupload:publish-component {--namespace= : Namespace to use for the published component}';

    protected $description = 'Publish the FreeUpload form component stub into your application';

    public function handle(): int
    {
        $target = app_path('Forms/Components/FreeUpload.php');

        if (file_exists($target)) {
            $this->warn(["Skipping publish: {$target} already exists."]);

            return self::SUCCESS;
        }

        $namespace = $this->option('namespace') ?: 'App\\Forms\\Components';

        $stub = __DIR__ . '/../../stubs/Forms/Components/FreeUpload.php.stub';

        if (! is_file($stub)) {
            $this->error('The FreeUpload component stub could not be found.');

            return self::FAILURE;
        }

        $directory = dirname($target);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(
            $target,
            str_replace('{{ namespace }}', $namespace, file_get_contents($stub))
        );

        $this->info("Published component: {$target}");

        return self::SUCCESS;
    }
}
