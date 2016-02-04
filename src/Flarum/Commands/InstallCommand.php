<?php

namespace Hyn\LaravelFlarum\Flarum\Commands;

use Exception;
use Flarum\Database\AbstractModel;
use Flarum\Install\Console\InstallCommand as FlarumInstallCommand;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends FlarumInstallCommand
{

    protected function configure()
    {
        $this
            ->setName('flarum:install')
            ->setDescription("Run Flarum's installation migration and seeds.")
            ->addOption(
                'defaults',
                'd',
                InputOption::VALUE_NONE,
                'Create default settings and user'
            )
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Use external configuration file in YAML format'
            );;
    }

    protected function install()
    {
        try {

            $this->settings = $this->dataSource->getSettings();
            $this->adminUser = $admin = $this->dataSource->getAdminUser();

            if (strlen($admin['password']) < 8) {
                throw new Exception('Password must be at least 8 characters.');
            }

            if ($admin['password'] !== $admin['password_confirmation']) {
                throw new Exception('The password did not match its confirmation.');
            }

            if (!filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('You must enter a valid email.');
            }

            if (!$admin['username'] || preg_match('/[^a-z0-9_-]/i', $admin['username'])) {
                throw new Exception('Username can only contain letters, numbers, underscores, and dashes.');
            }

            $this->runMigrations();

            $this->writeSettings();

            $this->application->register('Flarum\Core\CoreServiceProvider');

            $resolver = $this->application->make('Illuminate\Database\ConnectionResolverInterface');
            AbstractModel::setConnectionResolver($resolver);
            AbstractModel::setEventDispatcher($this->application->make('events'));

            $this->seedGroups();
            $this->seedPermissions();

            $this->createAdminUser();

            $this->enableBundledExtensions();

            $this->publishAssets();
        } catch (Exception $e) {
            @unlink($this->getConfigFile());

            throw $e;
        }
    }

    protected function storeConfiguration()
    {
        // .. unused
    }

    protected function runMigrations()
    {
        $this->application->bind('Illuminate\Database\Schema\Builder', function ($container) {
            return $container->make('Illuminate\Database\ConnectionInterface')->getSchemaBuilder();
        });

        $migrator = $this->application->make('Flarum\Database\Migrator');
        $migrator->getRepository()->createRepository();

        $migrator->run(base_path('vendor/flarum/core/migrations'));

        foreach ($migrator->getNotes() as $note) {
            $this->info($note);
        }
    }

    protected function publishAssets()
    {
        $this->filesystem->copyDirectory(
            base_path('vendor/flarum/core/assets'),
            public_path('assets')
        );
    }
}
