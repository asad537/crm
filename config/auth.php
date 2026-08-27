<?php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],
    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        'crm' => ['driver' => 'session', 'provider' => 'crm_users'],  // ← CRM guard
    ],
    'providers' => [
        'users' => ['driver' => 'eloquent', 'model' => App\Models\User::class],
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
