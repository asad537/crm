<?php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],
    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        // Legacy mobile API compatibility. The original app sends API tokens
        // through Laravel's token guard and uses the legacy App\User model.
        'api' => ['driver' => 'token', 'provider' => 'app_users', 'hash' => false],
        'crm' => ['driver' => 'session', 'provider' => 'crm_users'],  // ← CRM guard
    ],
    'providers' => [
        'users' => ['driver' => 'eloquent', 'model' => App\Models\User::class],
        'app_users' => ['driver' => 'eloquent', 'model' => App\User::class],
        'crm_users' => ['driver' => 'eloquent', 'model' => App\CrmUser::class],  // ← CRM users
    ],
    'passwords' => [
        'users' => [
            'provider' => 'users', 'table' => 'password_reset_tokens',
            'expire' => 60, 'throttle' => 60,
        ],
    ],
    'password_timeout' => 10800,
];
