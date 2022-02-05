<?php

namespace Loxi5\Framework;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Http\Middleware\ShareInertiaData;
use Loxi5\Framework\Bindings\Actions;
use Loxi5\Framework\Bindings\Alerts;
use Loxi5\Framework\Console\NewLanguage;
use Loxi5\Framework\Console\NewModule;
use Loxi5\Framework\Console\NewTheme;
use Inertia\Inertia;
use Loxi5\Framework\Console\Publish;
use Loxi5\Framework\Http\Middleware\AuthenticateAdmin;
use Loxi5\Framework\Http\Middleware\Backend;
use Loxi5\Framework\Http\Middleware\Frontend;
use Loxi5\Framework\Http\Middleware\HasAdminAccess;
use Loxi5\Framework\Http\Middleware\Widget;
use Modules\Core\Models\Country;
use Modules\Core\Models\Currency;
use Modules\Language\Models\Language;
use Modules\Language\Models\LanguageSection;
use Modules\User\Models\UserGroup;
use Loxi5\Framework\Http\Middleware\Language as LanguageMiddleware;
use Modules\Core\Models\Module;
use Modules\Core\Models\Theme;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('loxi5.module', function () {
            return new NewModule;
        });
        $this->app->singleton('loxi5.theme', function () {
            return new NewTheme;
        });
        $this->app->singleton('loxi5.publish', function () {
            return new Publish;
        });

        $this->commands(['loxi5.module', 'loxi5.theme', 'loxi5.publish']);

        $this->app->singleton('actions', function () {
            return (new Actions());
        });
        $this->app->singleton('alerts', function () {
            return (new Alerts());
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            $modules = Module::where('is_active', true)->orderBy('is_core')->orderBy('folder')->get();

            $default_theme = Theme::where('is_default', true)->first();

            $languages = Language::where('is_active', true)->orderBy('order')->get();

            $user_groups = UserGroup::get();

            $currencies = Currency::where('is_active', true)->orderBy('order')->get();

            $countries = Country::where('is_active', true)->orderBy('order')->orderBy('title')->get();
        } catch (\Exception $e) {
            $modules = collect([]);

            $default_theme = collect([
                'id' => 1,
                'folder' => 'Default'
            ]);

            $languages = collect([]);

            $user_groups = collect([]);

            $currencies = collect([]);

            $countries = collect([]);
        }

        $this->app->bind('modules', function () use ($modules) {
            return $modules;
        });
        $this->app->bind('default_theme', function () use ($default_theme) {
            return $default_theme;
        });
        $this->app->bind('languages', function () use ($languages) {
            return $languages;
        });
        $this->app->bind('user_groups', function () use ($user_groups) {
            return $user_groups;
        });
        $this->app->bind('currencies', function () use ($currencies) {
            return $currencies;
        });
        $this->app->bind('countries', function () use ($countries) {
            return $countries;
        });

        foreach ($modules as $module) {
            $this->loadViewsFrom(base_path('modules/' . $module->folder . '/Views/' . $default_theme->folder), $module->folder);
            $this->mergeConfigFrom(
                base_path('modules/' . $module->folder . '/Config/config.php'), $module->folder
            );
            $this->mergeConfigFrom(
                base_path('modules/' . $module->folder . '/Config/group.php'), 'UserConfig.' . $module->folder
            );
            $this->loadTranslationsFrom(
                base_path('modules/' . $module->folder . '/Lang'), $module->folder
            );
            $this->loadMigrationsFrom(base_path('modules/' . $module->folder . '/Migrations'));
            if ($languages->contains('code_with_dash', request()->segment(1))) {
                Route::prefix(request()->segment(1))->group(function () use ($module) {
                    Route::middleware(['web', Frontend::class, LanguageMiddleware::class])->namespace('Modules\\' . $module->folder . '\\Controllers')->group(base_path('modules/' . $module->folder . '/Routes/frontend.php'));
                    Route::middleware(['web', 'auth:sanctum', Backend::class, LanguageMiddleware::class, HasAdminAccess::class])->namespace('Modules\\' . $module->folder . '\\Controllers')->prefix(config('app.admin_url', 'admin'))->name('admin.')->group(base_path('modules/' . $module->folder . '/Routes/admin.php'));
                    Route::middleware(['api', LanguageMiddleware::class])->namespace('Modules\\' . $module->folder . '\\Controllers')->prefix('api/v2')->name('api.v2.')->group(base_path('modules/' . $module->folder . '/Routes/api.php'));
                });
            } else {
                Route::middleware(['web', Frontend::class, LanguageMiddleware::class])->namespace('Modules\\' . $module->folder . '\\Controllers')->group(base_path('modules/' . $module->folder . '/Routes/frontend.php'));
                Route::middleware(['web', 'auth:sanctum', Backend::class, LanguageMiddleware::class, HasAdminAccess::class])->namespace('Modules\\' . $module->folder . '\\Controllers')->prefix(config('app.admin_url', 'admin'))->name('admin.')->group(base_path('modules/' . $module->folder . '/Routes/admin.php'));
                Route::middleware(['api', LanguageMiddleware::class])->namespace('Modules\\' . $module->folder . '\\Controllers')->prefix('api/v2')->name('api.v2.')->group(base_path('modules/' . $module->folder . '/Routes/api.php'));
            }
        }
        $this->loadViewsFrom(base_path('themes/' . $default_theme->folder . '/Views'), 'Theme');

        $kernel = $this->app->make(Kernel::class);

        $kernel->appendMiddlewareToGroup('web', ShareInertiaData::class);
        $kernel->appendToMiddlewarePriority(ShareInertiaData::class);

        if (class_exists(HandleInertiaRequests::class)) {
            $kernel->appendToMiddlewarePriority(HandleInertiaRequests::class);
        }

        Fortify::loginView(function () {
            return Inertia::render(_component('User::Frontend.Login'), [
                'canResetPassword' => Route::has('password.request'),
                'status' => session('status'),
            ]);
        });

        Fortify::requestPasswordResetLinkView(function () {
            return Inertia::render(_component('User::Frontend.ForgotPassword'), [
                'status' => session('status'),
                'translations' => __('User::login')
            ]);
        });

        Fortify::resetPasswordView(function (Request $request) {
            return Inertia::render(_component('User::Frontend.ResetPassword'), [
                'email' => $request->input('email'),
                'token' => $request->route('token'),
                'translations' => __('User::login')
            ]);
        });

        Fortify::registerView(function () {
            return Inertia::render(_component('User::Frontend.SignUp'));
        });

        Fortify::verifyEmailView(function () {
            return Inertia::render(_component('User::Frontend.VerifyEmail'), [
                'status' => session('status'),
            ]);
        });

        Fortify::twoFactorChallengeView(function () {
            return Inertia::render(_component('User::Frontend.TwoFactorChallenge'), [
                'translations' => __('User::login')
            ]);
        });

        Fortify::confirmPasswordView(function () {
            return Inertia::render(_component('User::Frontend.ConfirmPassword'));
        });

        RateLimiter::for('user-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->email.$request->ip());
        });

        RateLimiter::for('user-two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
