<?php

return [
    'company_id' => env('WORKLIVE_COMPANY_ID', 'worklivepro'),
    'company_name' => env('WORKLIVE_COMPANY_NAME', 'WorkLivePro'),
    'client_key_prefix' => env('WORKLIVE_CLIENT_KEY_PREFIX', 'SAFEB'),
    'agent_token_ttl_days' => (int) env('WORKLIVE_AGENT_TOKEN_TTL_DAYS', 365),
    'admin_api_key' => env('WORKLIVE_ADMIN_API_KEY', ''),
];
