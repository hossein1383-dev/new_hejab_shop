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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'zibal' => [
        'driver' => env('PAYMENT_GATEWAY_DRIVER', 'fake'),
        'merchant' => env('ZIBAL_MERCHANT'),
    ],

    'heropost' => [
        'driver' => env('SHIPPING_GATEWAY_DRIVER', 'flat'),
        'base_url' => env('HEROPOST_BASE_URL', 'https://heropost.ir/api'),
        'username' => env('HEROPOST_USERNAME'),
        'password' => env('HEROPOST_PASSWORD'),
        'security_code' => env('HEROPOST_SECURITY_CODE'),
    ],

    'smsir' => [
        'driver' => env('SMS_GATEWAY_DRIVER', 'fake'),
        'otp_template_id' => env('SMSIR_OTP_TEMPLATE_ID'),
        'new_order_template_id' => env('SMSIR_NEW_ORDER_TEMPLATE_ID'),
        'order_confirmation_template_id' => env('SMSIR_ORDER_CONFIRMATION_TEMPLATE_ID'),
        'parcel_shipped_template_id' => env('SMSIR_PARCEL_SHIPPED_TEMPLATE_ID'),
        'coupon_gift_template_id' => env('SMSIR_COUPON_GIFT_TEMPLATE_ID'),
    ],
];
