<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary credentials (development media storage)
    |--------------------------------------------------------------------------
    |
    | Prefer CLOUDINARY_URL (dashboard copy-paste). Discrete vars are a fallback
    | and should match the URL if both are set — mismatched secrets cause
    | "Invalid Signature" upload failures.
    |
    */

    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),

    'api_key' => env('CLOUDINARY_API_KEY'),

    'api_secret' => env('CLOUDINARY_API_SECRET'),

    'url' => env('CLOUDINARY_URL'),

    'secure' => true,

    /*
    |--------------------------------------------------------------------------
    | Folder layout (kept separate for clean Cloudinary media management)
    |--------------------------------------------------------------------------
    */

    'folders' => [
        'root' => env('CLOUDINARY_ROOT_FOLDER', 'isabi'),
        'profiles' => 'profiles',
        'work_logs' => 'work-logs',
    ],

];
