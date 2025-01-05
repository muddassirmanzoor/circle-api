<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [
    /*
      |--------------------------------------------------------------------------
      | Default Log Channel
      |--------------------------------------------------------------------------
      |
      | This option defines the default log channel that gets used when writing
      | messages to the logs. The name specified in this option should match
      | one of the channels defined in the "channels" configuration array.
      |
     */

    'default' => env('LOG_CHANNEL', 'stack'),
    /*
      |--------------------------------------------------------------------------
      | Log Channels
      |--------------------------------------------------------------------------
      |
      | Here you may configure the log channels for your application. Out of
      | the box, Laravel uses the Monolog PHP logging library. This gives
      | you a variety of powerful log handlers / formatters to utilize.
      |
      | Available Drivers: "single", "daily", "slack", "syslog",
      |                    "errorlog", "monolog",
      |                    "custom", "stack"
      |
     */
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],
        
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/' . php_sapi_name() . '-laravel.log'),
//            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'pushnotification' => [
            'driver' => 'daily',
            'path' => storage_path('logs/push-notification-' . php_sapi_name() . '-laravel.log'),
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'pushnotification_reminder' => [
            'driver' => 'daily',
            'path' => storage_path('logs/pushnotification_reminder-' . php_sapi_name() . '-laravel.log'),
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'cronjobs' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cron-job-' . php_sapi_name() . '-laravel.log'),
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'snsmessage' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sns-message-' . php_sapi_name() . '-laravel.log'),
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/' . php_sapi_name() . '-laravel.log'),
//            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => 14,
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'daily_change_status' => [
            'driver' => 'daily',
            'path' => storage_path('logs/' . 'daily_change_status.log'),
            'level' => 'debug',
            'days' => 14,
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'recurring_payments' => [
            'driver' => 'daily',
            'path' => storage_path('logs/'.'recurring_payments.log'),
            'level' => 'debug',
            'days' => 7,
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'premium_folder' => [
            'driver' => 'daily',
            'path' => storage_path('logs/'.'premium_folder.log'),
            'level' => 'debug',
            'days' => 7,
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'pusher' => [
            'driver' => 'daily',
            'path' => storage_path('logs/'.'pusher.log'),
            'level' => 'debug',
            'days' => 7,
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'cron_subscription_update' => [
            'driver' => 'single',
            'path' => storage_path('logs/cron/subscriptions/update/sub-' . date('Y-m-d') . '.log'),
            'permission' => 0777,
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'ses_bounces' => [
            'driver' => 'single',
            'path' => storage_path('logs/ses/bounces/bounces-' . date('Y-m-d') . '.log'),
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
        'ses_complaints' => [
            'driver' => 'single',
            'path' => storage_path('logs/ses/complaints/complaints-' . date('Y-m-d') . '.log'),
            'formatter' => Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "%datetime%: %message%\n",
            ],
        ],
    ],
];
