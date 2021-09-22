<?php

return [
    'debug' => isset($_ENV['DEBUG']) ? filter_var($_ENV['DEBUG'], FILTER_VALIDATE_BOOLEAN) : true,
    'tmdb' => [
        'apiKey' => $_ENV['TMDB_API_KEY']
    ],
    'cache' => [
        'dir' => CACHE_DIR,
        'ttlMinutes' => isset($_ENV['CACHE_TTL_MINUTES']) ? filter_var($_ENV['CACHE_TTL_MINUTES'], FILTER_VALIDATE_INT) : 1
    ]
];