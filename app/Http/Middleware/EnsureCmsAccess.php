<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmsAccess
{
    /**
     * Allowed hosts for CMS access.
     *
     * @var array<string>
     */
    protected array $allowedHosts = [
        'localhost',
        '127.0.0.1',
        'live.tastymaldives.com',
        'staging.tastymaldives.com',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the host is allowed for CMS access
        if (! $this->isAllowedHost($request)) {
            abort(404);
        }

        if (! $request->user() || ! $request->user()->canAccessCms()) {
            abort(403, 'You do not have permission to access the CMS.');
        }

        return $next($request);
    }

    /**
     * Check if the current host is allowed to access the CMS.
     */
    protected function isAllowedHost(Request $request): bool
    {
        $host = $request->getHost();

        // Allow if in local/development environment
        if (app()->environment('local', 'development', 'testing')) {
            return true;
        }

        return in_array($host, $this->allowedHosts, true);
    }
}
