<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // PayPal Invoicing (REST API v2) — used by the TCB manual Orders "Send PayPal Request" action.
    // mode: "sandbox" or "live". Credentials come from a REST app at developer.paypal.com.
    // Vault / NMI-compatible card gateway (Direct Post API) used by "Charge Card" on manual orders.
    'vault' => [
        'url' => env('VAULT_GATEWAY_URL', 'https://secure.merchantservicegateway.com/api/transact.php'),
        'username' => env('VAULT_USERNAME'),
        'password' => env('VAULT_PASSWORD'),
    ],

    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'business_name' => env('PAYPAL_BUSINESS_NAME', 'My Box Printing'),
        'business_email' => env('PAYPAL_BUSINESS_EMAIL'),
    ],

];
