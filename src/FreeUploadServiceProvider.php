<?php

namespace Blalmal10a\FreeUpload;

use Blalmal10a\FreeUpload\Http\Controllers\FreeUploadUploadController;
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FreeUploadServiceProvider extends PackageServiceProvider
{
    public static string $name = 'free-upload';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('blalmal10a/free-upload');
            });
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        $this->registerRoutes();
    }

    protected function getAssetPackageName(): ?string
    {
        return 'blalmal10a/free-upload';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    protected function registerRoutes(): void
    {
        if (! config('free-upload.register_routes', true)) {
            return;
        }

        Route::prefix((string) config('free-upload.route_prefix', 'freeupload'))
            ->middleware((array) config('free-upload.route_middleware', ['web', 'auth']))
            ->group(function (): void {
                Route::post('/upload', FreeUploadUploadController::class)->name('freeupload.upload');
            });
    }
}
