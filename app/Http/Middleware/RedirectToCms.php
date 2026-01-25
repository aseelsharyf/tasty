<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCms
{
    /**
     * Hosts that should redirect to CMS by default.
     *
     * @var array<string>
     */
    protected array $cmsOnlyHosts = [
        'live.tastymaldives.com',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // If this is a CMS-only host and not already on a /cms route, redirect to CMS
        if (in_array($host, $this->cmsOnlyHosts, true) && ! $request->is('cms*')) {
            return redirect('/cms');
        }

        return $next($request);
    }
}
