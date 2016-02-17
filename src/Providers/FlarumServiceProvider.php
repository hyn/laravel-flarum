<?php

namespace Hyn\LaravelFlarum\Providers;

use Flarum\Database\DatabaseMigrationRepository;
use Flarum\Install\Prerequisite\Composite;
use Flarum\Install\Prerequisite\PhpExtensions;
use Flarum\Install\Prerequisite\PhpVersion;
use Flarum\Install\Prerequisite\WritablePaths;
use Flarum\Settings\DatabaseSettingsRepository;
use Flarum\Settings\MemoryCacheSettingsRepository;
use Hyn\LaravelFlarum\Flarum;
use Hyn\LaravelFlarum\Flarum\Commands\InstallCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

class FlarumServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // merge defaults
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'hyn.laravel-flarum');

        // allow publishing the config for editing
        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('hyn.laravel-flarum.php')
        ], 'laravel-flarum-config');

//        $this->publishes([
//            base_path('vendor/flarum/core/migrations/') => database_path('migrations')
//        ], 'laravel-flarum-migrations');

        $this->flarumListeners();

//        $this->app->singleton('Illuminate\Database\ConnectionResolverInterface', function () {
//            return $this->app->make('db');
//        });
//        $this->app->singleton('Flarum\Database\MigrationRepositoryInterface', function ($app) {
//            return new DatabaseMigrationRepository($app['db'], 'migrations');
//        });
//
//        $this->app->singleton('Flarum\Settings\SettingsRepositoryInterface', function () {
//            return new MemoryCacheSettingsRepository(
//                new DatabaseSettingsRepository(
//                    $this->app->make('Illuminate\Database\ConnectionInterface')
//                )
//            );
//        });
//
//        $this->app->alias('Flarum\Settings\SettingsRepositoryInterface', 'flarum.settings');
//
//        $this->installCommand();

        if (!app()->runningInConsole()) {
            $this->setupServer();
        }
    }

    protected function installCommand()
    {
        $this->app->bind(
            'Flarum\Install\Prerequisite\PrerequisiteInterface',
            function () {
                return new Composite(
                    new PhpVersion('5.5.0'),
                    new PhpExtensions([
                        'dom',
                        'fileinfo',
                        'gd',
                        'json',
                        'mbstring',
                        'openssl',
                        'pdo_mysql',
                    ]),
                    new WritablePaths([
                        public_path(),
                        public_path('assets'),
                        public_path('extensions'),
                        storage_path(),
                    ])
                );
            }
        );

        $this->commands([
            InstallCommand::class
        ]);
    }

    /**
     * Add listeners to Flarums code.
     */
    protected function flarumListeners()
    {
        app(Dispatcher::class)->subscribe(Flarum\Listeners\PrefixesForumRoutes::class);
    }

    /**
     * Set up the Flarum server, either Api, Forum, Admin
     *
     */
    protected function setupServer()
    {
        $prefix = config('hyn.laravel-flarum.prefix');
        $path   = request()->getPathInfo();
        if (empty($prefix) || Str::startsWith($prefix, $path)) {
            foreach (config('hyn.laravel-flarum.paths') as $type => $typePath) {
                if (Str::startsWith($prefix . '/' . $typePath, $path)) {
                    $this->setupFlarumServer($type);
                    return;
                }
            }

            $this->setupFlarumServer();
        }
    }

    /**
     * @return array
     */
    protected function getConfiguration()
    {
        return [
            'debug'    => config('app.debug'),
            'url'      => config('app.url'),
            'paths'    => config('hyn.laravel-flarum.paths', []),
            'database' => config('database.connections.' . config('hyn.laravel-flarum.database',
                    config('database.default')), [])
        ];
    }

    /**
     * @param string $name
     */
    protected function setupFlarumServer($name = 'forum')
    {
        $serverName = "\\Hyn\\LaravelFlarum\\Flarum\\" . studly_case($name) . "\\Server";
        $server     = new $serverName(base_path());
        $server->setConfig($this->getConfiguration());

        $server->listen();
    }
}