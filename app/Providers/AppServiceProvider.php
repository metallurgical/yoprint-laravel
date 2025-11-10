<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Increase Livewire file upload size to 100MB
        // TemporaryUploadedFile::macro('validateFile', function () {
        //     return validator(
        //         ['file' => $this],
        //         ['file' => 'file|max:102400'] // 100MB in kilobytes
        //     )->validate();
        // });
    }
}
