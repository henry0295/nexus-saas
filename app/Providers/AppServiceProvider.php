<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Suppress the tempnam() warning from Laravel's view compiler
        // This is a known issue in Docker containers where tempnam() creates files
        // in the system temp directory, causing harmless warnings
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            if ($errno === E_WARNING && 
                strpos($errstr, 'tempnam()') !== false && 
                strpos($errfile, 'Filesystem.php') !== false) {
                return true; // Suppress the warning
            }
            return false; // Let other errors be handled normally
        }, E_WARNING);
    }
}
