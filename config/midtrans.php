<?php

return [
    'client_key' => trim((string) env('MIDTRANS_CLIENT_KEY', '')),
    'server_key' => trim((string) env('MIDTRANS_SERVER_KEY', '')),
    'is_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
    'is_sanitized' => filter_var(env('MIDTRANS_IS_SANITIZED', true), FILTER_VALIDATE_BOOLEAN),
    'is_3ds' => filter_var(env('MIDTRANS_IS_3DS', true), FILTER_VALIDATE_BOOLEAN),
];