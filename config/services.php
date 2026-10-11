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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Google Vertex AI (Gemini + Imagen). Service account key file lives outside the repo.
    'vertex' => [
        'project' => env('GCP_PROJECT_ID'),
        'region' => env('GCP_REGION', 'asia-southeast1'),
        // Imagen may not be offered in every region; falls back to the text region.
        'image_region' => env('GCP_IMAGE_REGION'),
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        // Alternative to the file: the whole key JSON, base64-encoded (easy to paste in Forge).
        'credentials_base64' => env('GOOGLE_CREDENTIALS_BASE64'),
        // Optional static token for local testing (gcloud auth print-access-token).
        'access_token' => env('VERTEX_ACCESS_TOKEN'),
        'token_url' => 'https://oauth2.googleapis.com/token',
        'timeout' => 180,
    ],

    // Fine-grained token: Contents read & write on the customer website repos only.
    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'base_url' => 'https://api.github.com',
        'timeout' => 60,
    ],

    'indexnow' => [
        'endpoint' => 'https://api.indexnow.org/indexnow',
        'timeout' => 15,
    ],

];
