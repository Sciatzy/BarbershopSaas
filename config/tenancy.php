<?php

return [
    'switch_database' => env('TENANCY_SWITCH_DATABASE', false),

    'abort_if_unknown_domain' => env('TENANCY_ABORT_IF_UNKNOWN_DOMAIN', false),

    'central_domains' => array_values(array_filter(array_map(
        static fn (string $value): string => strtolower(trim($value)),
        explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', '127.0.0.1,localhost'))
    ))),
];
