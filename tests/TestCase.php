<?php

namespace Creatix\AppNotificationManager\Tests;

use Creatix\AppNotificationManager\AppNotificationManagerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            AppNotificationManagerServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $app->make('config');

        $config->set('notification-manager.fcm.project_id', 'test-fcm-project');
        $config->set('notification-manager.apns.bundle_id', 'com.example.testapp');
        $config->set('notification-manager.apns.key_id', 'ABC123DEFG');
        $config->set('notification-manager.apns.team_id', 'TEAM123456');
    }
}
