<?php

namespace Creatix\AppNotificationManager\Tests;

use Creatix\AppNotificationManager\AppNotificationManagerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AppNotificationManagerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('notification-manager.fcm.project_id', 'test-fcm-project');
        $app['config']->set('notification-manager.apns.bundle_id', 'com.example.testapp');
        $app['config']->set('notification-manager.apns.key_id', 'ABC123DEFG');
        $app['config']->set('notification-manager.apns.team_id', 'TEAM123456');
    }
}

