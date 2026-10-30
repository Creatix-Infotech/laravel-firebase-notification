<?php

namespace Creatix\AppNotificationManager;

use Creatix\AppNotificationManager\Commands\SendTestNotificationCommand;
use Creatix\AppNotificationManager\Events\InvalidTokenDetected;
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
            $this->publishes([
                __DIR__ . '/../config/notification-manager.php' => config_path('notification-manager.php'),
            ], 'notification-manager-config');

            if (! class_exists('CreateDeviceTokensTable')) {
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_device_tokens_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_device_tokens_table.php'),
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
            $config = config('notification-manager.device_tokens', []);

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
            } catch (\Throwable $e) {
                // Ignore DB lookup failures during pruning
            }
        });
    }
}

