<?php

return [
    'driver' => env('SCOUT_DRIVER', 'elasticsearch'),
    'elasticsearch' => [
        'index' => env('SCOUT_ELASTICSEARCH_INDEX', 'laravel'),
        'hosts' => [
            env('ELASTICSEARCH_HOST', 'localhost:9200'), // Make sure this matches your host
        ],
    ],

    'chunk' => 1000,
];
