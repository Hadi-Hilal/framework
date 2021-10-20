<?php

namespace Loxi5\Framework;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\Module;
use Modules\Theme\Models\Theme;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $modules = Module::get();
        $default_theme = Theme::where('is_default', 1)->first();
        foreach ($modules as $module) {
            $this->loadViewsFrom(base_path('modules/' . $module->folder . '/Views'), $module->folder);
            if (File::exists(base_path('modules/' . $module->folder . '/Config/app.php'))) {
                $this->mergeConfigFrom(
                    base_path('modules/' . $module->folder . '/Config/config.php'), $module->folder
                );
            }
            $this->loadTranslationsFrom(
                base_path('modules/' . $module->folder . '/Lang'), $module->folder
            );
            $this->loadMigrationsFrom(base_path('modules/' . $module->folder . '/Migrations'));
            Route::middleware(['web'])->namespace('Modules\\' . $module->folder . '\\Controllers')->group(base_path('modules/' . $module->folder . '/Routes/frontend.php'));
            Route::middleware(['web'])->namespace('Modules\\' . $module->folder . '\\Controllers')->prefix(config('Core.admin_url'))->name('admin.')->group(base_path('modules/' . $module->folder . '/Routes/backend.php'));
            Route::middleware(['web'])->namespace('Modules\\' . $module->folder . '\\Controllers')->prefix('widget')->name('widget.')->group(base_path('modules/' . $module->folder . '/Routes/widget.php'));
            Route::middleware(['api'])->namespace('Modules\\' . $module->folder . '\\Controllers')->prefix('api/v1')->name('api.v1.')->group(base_path('modules/' . $module->folder . '/Routes/api.php'));
        }
        $this->loadViewsFrom(base_path('themes/' . $default_theme->folder . '/Views'), 'Theme');
    }
}
