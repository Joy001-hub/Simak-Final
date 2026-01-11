<?php

return [
    'grace_days' => env('LICENSE_GRACE_DAYS', 7),
    'device_limit' => env('LICENSE_DEVICE_LIMIT', 2),
    'shared_identifier_salt' => env('LICENSE_SHARED_SALT', 'simak'),
    'sejoli_identifier' => env('LICENSE_SEJOLI_IDENTIFIER', 'shared'),
    'basic_device_limit' => env('LICENSE_BASIC_DEVICE_LIMIT', 1),
    'remember_days' => env('LICENSE_REMEMBER_DAYS', 30),
    'upgrade_price' => env('LICENSE_UPGRADE_PRICE'),
    'force_mode' => env('LICENSE_FORCE_MODE'),
    'force_tenant_key' => env('LICENSE_FORCE_TENANT_KEY'),
];
