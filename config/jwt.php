<?php

return [
    'secret' => env('JWT_SECRET', env('APP_KEY')),
    'ttl' => (int) env('JWT_TTL', 900),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 1209600),
    'issuer' => env('JWT_ISSUER', env('APP_NAME', 'task-manager-api')),
];
