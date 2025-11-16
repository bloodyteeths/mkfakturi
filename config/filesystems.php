<?php

return [

    'default' => env('FILESYSTEM_DISK', 'public'),

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    'disks' => [
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID', env('AWS_KEY')),
            'secret' => env('AWS_SECRET_ACCESS_KEY', env('AWS_SECRET')),
            'region' => env('AWS_DEFAULT_REGION', env('AWS_REGION')),
            'bucket' => env('AWS_BACKUP_BUCKET', env('AWS_BUCKET')),
            'root' => env('AWS_ROOT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        's3compat' => [
            'driver' => 's3',
            'endpoint' => env('S3_COMPAT_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'key' => env('S3_COMPAT_KEY'),
            'secret' => env('S3_COMPAT_SECRET'),
            'region' => env('S3_COMPAT_REGION'),
            'bucket' => env('S3_COMPAT_BUCKET'),
            // FIXED: Add public URL for Cloudflare R2 public access
            // Set this in .env to your R2 public URL: AWS_URL=https://pub-xxxxx.r2.dev
            'url' => env('AWS_URL'),
        ],

        'media' => [
            'driver' => 'local',
            'root' => public_path('media'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'doSpaces' => [
            'type' => 'AwsS3',
            'driver' => 's3',
            'key' => env('DO_SPACES_KEY'),
            'secret' => env('DO_SPACES_SECRET'),
            'region' => env('DO_SPACES_REGION'),
            'bucket' => env('DO_SPACES_BUCKET'),
            'root' => env('DO_SPACES_ROOT'),
            'endpoint' => env('DO_SPACES_ENDPOINT'),
            'use_path_style_endpoint' => false,
        ],

        'dropbox' => [
            'driver' => 'dropbox',
            'type' => 'DropboxV2',
            'token' => env('DROPBOX_TOKEN'),
            'key' => env('DROPBOX_KEY'),
            'secret' => env('DROPBOX_SECRET'),
            'app' => env('DROPBOX_APP'),
            'root' => env('DROPBOX_ROOT'),
        ],

        'views' => [
            'driver' => 'local',
            'root' => resource_path('views'),
        ],

        'pdf_templates' => [
            'driver' => 'local',
            'root' => storage_path('app/templates/pdf'),
        ],

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
            'report' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
