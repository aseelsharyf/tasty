<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CMS-Only Domains
    |--------------------------------------------------------------------------
    |
    | Domains listed here will serve the CMS at the root path (/) instead of /cms.
    | By default, both CMS and website are accessible on these domains.
    | Set CMS_ONLY=true to disable the public website on these domains.
    |
    | Example: live.tastymaldives.com
    |
    */

    'domains' => array_filter(explode(',', env('CMS_DOMAINS', ''))),

    /*
    |--------------------------------------------------------------------------
    | CMS Only Mode
    |--------------------------------------------------------------------------
    |
    | When true, CMS domains will ONLY serve the CMS (no public website).
    | When false (default), both CMS and website are accessible on CMS domains.
    |
    | This allows you to have:
    | - live.tastymaldives.com with CMS at root AND website accessible
    | - Or live.tastymaldives.com with ONLY CMS (no website)
    |
    */

    'cms_only' => env('CMS_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Disable CMS Path Access
    |--------------------------------------------------------------------------
    |
    | When true, the /cms path will not be accessible on non-CMS domains.
    | This is useful for production sites where CMS should only be accessed
    | via a dedicated CMS domain (e.g., live.tastymaldives.com).
    |
    | Example: tasty.mv/cms would return 404 if this is true
    |
    */

    'disable_path_access' => env('CMS_DISABLE_PATH_ACCESS', false),

];
