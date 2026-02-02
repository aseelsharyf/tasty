<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCms
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $path = $request->path();
        $cmsDomains = config('cms.domains', []);
        $disablePathAccess = config('cms.disable_path_access', false);

        // Check if this is a CMS domain
        $isCmsDomain = in_array($host, $cmsDomains, true);

        // Block /cms path access on non-CMS domains if configured
        if ($disablePathAccess && ! $isCmsDomain && str_starts_with($path, 'cms')) {
            abort(404);
        }

        // Store CMS context for use in routes and views
        app()->instance('cms.is_cms_domain', $isCmsDomain);
        app()->instance('cms.base_path', $isCmsDomain ? '' : '/cms');

        return $next($request);
    }

    /**
     * Check if the current request is on a CMS-only domain.
     */
    public static function isCmsDomain(): bool
    {
        return app()->bound('cms.is_cms_domain') ? app('cms.is_cms_domain') : false;
    }

    /**
     * Get the CMS base path for the current request.
     */
    public static function getCmsBasePath(): string
    {
        return app()->bound('cms.base_path') ? app('cms.base_path') : '/cms';
    }
}
