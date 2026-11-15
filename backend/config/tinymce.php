<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TinyMCE Cloud API key
    |--------------------------------------------------------------------------
    |
    | Get your key: https://www.tiny.cloud/my-account/dashboard/
    | Set TINYMCE_API_KEY in .env OR paste the key directly below.
    |
    */

    // Option A: paste key here (overrides .env when non-empty)
    // 'api_key' => 'your-tinymce-cloud-api-key',

    // Option B: use .env
    'api_key' => env('TINYMCE_API_KEY', 'cdo3zd0et7da3bv6mtki8iojktqnnqnmf004yhnx9ucxry98'),

];
