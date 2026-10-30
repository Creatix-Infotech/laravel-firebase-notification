<?php

namespace Creatix\AppNotificationManager;

use Creatix\AppNotificationManager\Commands\SendTestNotificationCommand;
use Creatix\AppNotificationManager\Events\InvalidTokenDetected;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppNotificationManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/notification-manager.php',
            'notification-manager'
        );

        $this->app->singleton(NotificationManager::class, function ($app) {
            return new NotificationManager($app);
        });

        $this->app->alias(NotificationManager::class, 'app-notification');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $configTarget = function_exists('config_path')
                ? config_path('notification-manager.php')
                : $this->app->basePath('config/notification-manager.php');

            $this->publishes([
                __DIR__ . '/../config/notification-manager.php' => $configTarget,
            ], 'notification-manager-config');

            if (! class_exists('CreateDeviceTokensTable')) {
                $migrationTarget = function_exists('database_path')
                    ? database_path('migrations/' . date('Y_m_d_His', time()) . '_create_device_tokens_table.php')
                    : $this->app->basePath('database/migrations/' . date('Y_m_d_His', time()) . '_create_device_tokens_table.php');

                $this->publishes([
                    __DIR__ . '/../database/migrations/create_device_tokens_table.php.stub' => $migrationTarget,
                ], 'notification-manager-migrations');
            }

            $this->commands([
                SendTestNotificationCommand::class,
            ]);
        }

        $this->registerTokenPruningListener();
    }

    /**
     * Auto prune or deactivate invalid tokens if configured and table exists.
     */
    protected function registerTokenPruningListener(): void
    {
        Event::listen(InvalidTokenDetected::class, function (InvalidTokenDetected $event) {
            $config = (array) Config::get('notification-manager.device_tokens', []);

            if (empty($config['auto_prune_invalid_tokens'])) {
                return;
            }

            $table = $config['table'] ?? 'device_tokens';

            try {
                if (Schema::hasTable($table)) {
                    DB::table($table)
                        ->where('token', $event->deviceToken)
                        ->update(['is_active' => false]);
                }
            } catch (\Throwable) {
                // Ignore DB lookup failures during pruning
            }
        });
    }
}
